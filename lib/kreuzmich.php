<?php
/**
 * Copyright (c) 2019 Raphael Menke <ramen100@hhu.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * User authentication against Kreuzmich server
 *
 * @category Apps
 * @package  UserExternal
 * @author   Raphael Menke <ramen100@hhu.de>
 * @license  http://www.gnu.org/licenses/agpl AGPL
 * @link     http://github.com/owncloud/apps
 */
 
 

use OCP\IUser;
use OCP\IConfig;
use OCP\UserInterface;
use \OCP\IUserBackend;
use OCA\kreuzmichlogin\AppConfig;

 
class OC_User_Kreuzmich extends \OCA\kreuzmichlogin\Base{
	private $config;
	private $km_server;
	private $http_user;
	private $http_pwd;
	private $allow_expired;
	private $allow_new;
	private $groups_all;


	/**
	 * Create new FTP authentication provider
	 *
	 * @param string  $host   Hostname or IP of FTP server
	 * @param boolean $secure TRUE to enable SSL
	 */
	public function __construct($km_server, $http_user = '', $http_pwd = '', $allow_expired = false, $allow_new = true, $groups_all = 'Studierende') {
		$this->config = \OC::$server->query(OCA\kreuzmichlogin\AppConfig::class)->LoadFromDatabase();
		$this->km_server = empty($this->config['city']) ? $km_server : $this->config['city'];
		$this->http_user =  empty($this->config['httpuser']) ? $http_user : $this->config['httpuser'];
		$this->http_pwd =  empty($this->config['httppass']) ? $http_pwd : $this->config['httppass'];
		$this->allow_expired = empty($this->config['expired']) ? $allow_expired : $this->config['expired'];
		$this->allow_new =  empty($this->config['new']) ? $allow_new : $this->config['new'];
		$this->groups_all =  empty($this->config['groups']) ? $groups_all : $this->config['groups'];
		parent::__construct($km_server);

	}

	/**
	 * Check if the password is correct without logging in the user
	 *
	 * @param string $uid      The username
	 * @param string $password The password
	 *
	 * @return true/false
	 */
	public function checkPassword($uid, $password) {
		
		// Prüfe auf Umlaute
		if  (preg_match('/[äÄöÖüÜß]/', $uid)) 
		{
			OC::$server->getLogger()->error(
				'Keine Umlaute im Benutzernamen_' . $uid,
				['app' => 'kreuzmichLogin']
			);
			return false;
		}	
		
		// Prüfe Login und PW gegen Kreuzmich ExtAuth API
		$ext_auth = $this->getKreuzmichInfo($uid, $password);
		$user_data = $ext_auth['user'];
		
		
		// Antwort entspricht nicht der Norm, Server nicht erreichbar (technische Probleme oder keine Stadt in Config definiert)
		if (!isset($ext_auth['success'])) 
		{
			OC::$server->getLogger()->error(
				'Kreuzmich Server nicht erreichbar! Kreuzmich Subdomain aus Einstellungen: ' . $this->km_server,
				['app' => 'kreuzmichLogin']
			);
			return false;
		}	
			
		if ( (isset($ext_auth['success'])) && ( $ext_auth['success'] == true ))
		{
			// gültiger Benutzer von Kreuzmich erkannt
			
			//abgelaufen?
			if ( ($this->allow_expired == false) && ($ext_auth['user']['expired'] == true) ) 
			{
				OC::$server->getLogger()->error(
				'Kreuzmich Benutzer abgelaufen: ' . $uid,
				['app' => 'kreuzmichLogin']
				);
				return false;
			}
			
			
			
			// Wenn du es bis hierhin schaffst, darf der Benutzer sich einloggen
			$groups = [];
			// NEU: $uid wird von case-insensitivem Benutzernamen aus Kreuzmich überschrieben
			$uid = $ext_auth['user']['username'];
			
			// Hole Gruppen aus der zweiten Funktion und füge den Benutzer hinzu
			// Auch wenn der Benutzer schon in der Cloud existiert
			// Gruppe wird erstellt, falls es sie noch nicht gibt
			$data_groups = $this->getUserGroups ($uid);
			if (is_array($data_groups)) 
			{
				foreach ($data_groups as $key => $value) 
				{
					$groups[] = $value;
				}
			}
			
			// Verarbeitung Userdaten
			if (!$this->userExists($uid)) 
			{
				if ($this->allow_new == true)
				{
					$this->storeUser($uid, $groups);
				} else {
					OC::$server->getLogger()->error(
					'Kein Login (' . $uid . '), da keine neuen Konten erlaubt!',
					['app' => 'kreuzmichLogin']
				);
				return false;
				}
			} else {
				// falls User bekannt, setze Gruppen, ist bei neuem User in storeUser schon enthalten
				$this->UserSetGroups ($uid, $groups);
			}
			
			// Falls Email vorhanden, speichere/update sie
			if ($user_data['email']) {
				$this->insertUserEmail($uid, $user_data['email']);
			} else {
				// falls keine Email vorhanden, lösche die vorhandene in Nextcloud, falls existent
				$this->deleteUserEmail($uid);
			}
		
			// Setze den Anzeige Namen auf den vollen Namen des Benutzers
			$this->setDisplayName($uid, $user_data['firstname'] . " " . $user_data['lastname']);

			return $uid;
		} else {
			if ($ext_auth['code'] == '403') {
				OC::$server->getLogger()->error(
					"Fehler in Kreuzmich-Login: ".$ext_auth['reason'].' '. $ext_auth['detail'],
					['app' => 'kreuzmichLogin']
				);
			} else {
				OC::$server->getLogger()->error(
					'Falscher Benutzername/Falsches Passwort: ' . $uid,
					['app' => 'kreuzmichLogin']
				);
			}
			return false;
		}		
	}
	
	/*
	* Diese Funktion kann verwendet werden, um zusätzlich zu den Kreuzmich-Daten Gruppen von anderen Quellen zu erhalten
	*
	* Es wird ein eindimensionales Array mit Gruppennamen als Strings zurückgegeben
	*
	* @return Array 
	*/
	private function getUserGroups( $username ) 
	{
		
	}
	
	/*
	* cURL Abgleich mit Kreuzmich
	*/
	private function getKreuzmichInfo ($uid, $password) 
	{
		$city = $this->km_server;
		
		$ch = curl_init("https://". $city . ".kreuzmich.de/extAuth/json");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $this->http_user . ':' . $this->http_pwd); // HTTP Benutzer und Passwort aus den Einstellungen
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('username' => $uid, 'password' => $password ))); //Benutzername und Passwort aus Loginfeld
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$ext_auth = @json_decode($result, true);
		if (is_null($ext_auth)) {
			$ext_auth = ["success" => 0, 'code' => 403, 'reason' => 'Unknown Error.', 'detail' => $result, 'user' => [] ];
		}
		curl_close($ch);
		
		return $ext_auth; 
	}
}
