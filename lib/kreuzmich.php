<?php
/**
 * @author Raphael Menke <ramen100@hhu.de>
 * @author Jonas Sulzer <jonas@violoncello.ch>
 * @author Christian Weiske <cweiske@cweiske.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCP\IUser;
use OCP\IConfig;
use OCP\UserInterface;
use \OCP\IUserBackend;
use OCA\kreuzmichlogin\AppConfig;

/**
 * External auth class fort authentification against Kreuzmich Server.
 * Stores users on their first login in a local table.
 * This is required for making many of the user-related ownCloud functions
 * work, including sharing files with them.
 * Modified version of User_External, original work by Christian Weiske
 *
 * @category Apps
 * @package  KreuzmichLogin
 * @author   Raphael Menke <ramen100@hhu.de>
 * @license  http://www.gnu.org/licenses/agpl AGPL
 * @link     http://github.com/owncloud/apps
 */
class OC_User_Kreuzmich extends \OC\User\Backend {
	protected $backend = '';
	private $config;
	private $http_user;
	private $http_pwd;
	private $allow_expired;
	private $allow_new;
	private $groups_all;

	/**
	 * Create new Kreuzmich authentication provider
	 *
	 * @param string	$km_server		Hostname of Kreuzmich Server, Identifier of the backend
	 * @param string	$http_user		HTTP username for additional HTTP Auth required for cURL (debug only)
	 * @param string	$http_pwd		HTTP password for additional HTTP Auth required for cURL (debug only)
	 * @param boolean	$allow_expired		determines if expired Kreuzmich users can log in
	 * @param boolean	$allow_new		determines if Kreuzmich users who never logged in into Nextcloud before can log in
	 * @param string	$groups_all		Name of existing default group for all external users, group should already exist
	*/
	public function __construct($km_server, $http_user = '', $http_pwd = '', $allow_expired = false, $allow_new = true, $groups_all = 'Studierende') {
		$this->config = \OC::$server->query(OCA\kreuzmichlogin\AppConfig::class)->LoadFromDatabase();
		if (empty($this->config)) {
			$this->backend = $km_server;
			$this->http_user = $http_user;
			$this->http_pwd =  $http_pwd;
			$this->allow_expired = $allow_expired;
			$this->allow_new = $allow_new;
			$this->groups_all = $groups_all;
		} else {
			// take database entries, fallback to config
			$this->backend = (!empty($this->config['city'])) ? $this->config['city'] : $km_server; 
			$this->http_user = (!empty($this->config['httpuser'])) ? $this->config['httpuser'] : $http_user;
			$this->http_pwd = (!empty($this->config['httppass'])) ? $this->config['httppass'] : $http_pwd;
			$this->allow_expired = ($this->config['expired'] === "false") ? false : boolval($this->config['expired']);
			$this->allow_new = ($this->config['new'] === "false") ? false : boolval($this->config['new']);
			$this->groups_all = (!empty($this->config['groups'])) ? $this->config['groups'] : $groups_all;
		}
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
				'Kreuzmich Server nicht erreichbar! Kreuzmich Subdomain aus Einstellungen: ' . $this->backend,
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
			
			if (!empty($this->groups_all))
			{
				$groups[] = $this->groups_all;
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

	/**
	  * Delete User Email if existing
	  *
	  * @return true
	  */
	public function deleteUserEmail($uid)
	{
		if ($this->userEmailExists($uid)) {
			$connection = \OC::$server->getDatabaseConnection();
			$query = $connection->getQueryBuilder();
			$query->delete('preferences')
				->where($query->expr()->eq('userid', $query->createNamedParameter($uid)))
				->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('email')));
			$query->execute();
			return true;
		}
	}
	
	/**
	* Diese Funktion kann verwendet werden, um zusätzlich zu den Kreuzmich-Daten Gruppen von anderen Quellen zu erhalten
	*
	* Es wird ein eindimensionales Array mit Gruppennamen als Strings zurückgegeben
	*
	* @return Array 
	*/
	private function getUserGroups( $username ) 
	{
		
	}
	
	/**
	*
	* cURL Abgleich mit Kreuzmich
	*/
	private function getKreuzmichInfo ($uid, $password) 
	{
		$city = $this->backend;
		
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
	
	/**
	 * Check if Email for User exists
	 *
	 * @return int|bool The number of users on success false on failure
	*/
	public function GetUserEmail($uid)
	{
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->select("*")
			->from("preferences")
			->where($query->expr()->eq("userid", $query->createNamedParameter($uid)))
			->andWhere($query->expr()->eq("configkey", $query->createNamedParameter("email")));
		
		$result = $query->execute();

		$email = [];
		
		while ($row = $result->fetch()) {
			$email[] = $row["configvalue"];
		}
		$result->closeCursor();

		return $email;
	}

	/**
	 * Insert or Update Email
	  *
	  * @return true
	  */
	public function insertUserEmail($uid, $email)
	{
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		if (!$this->GetUserEmail($uid)) {
			$query->insert('preferences')
				->values([
					'userid' => $query->createNamedParameter($uid),
					'appid' => $query->createNamedParameter('settings'),
					'configkey' =>$query->createNamedParameter('email'),
					'configvalue' => $query->createNamedParameter($email)
				]);			
		} else if (in_array( $email , $this->GetUserEmail($uid) ) ) {
			$query->update('preferences')
				->set('configvalue', $query->createNamedParameter($email))
				->where($query->expr()->eq('userid', $query->createNamedParameter($uid)))
				->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('email')));		
		}
		$query->execute();
		return true; 
	}
 
	/**
	 * Change the display name of a user
	 *
	 * @param string $uid         The username
	 * @param string $displayName The new display name
	 *
	 * @return true/false
	 */
	public function setDisplayName($uid, $displayName)
	{
		if (!$this->userExists($uid)) {
			return false;
		}

		$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$query->update('users_external')
			->set('displayname', $query->createNamedParameter($displayName))
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->andWhere($query->expr()->eq('backend', $query->createNamedParameter($this->backend)));
		$query->execute();

		return true;
	}

	/**
	 * Create user record in database
	 *
	 * @param string $uid The username
	 * @param array $groups Groups to add the user to on creation
	 *
	 * @return void
	 */
	protected function storeUser($uid, $groups = [])
	{
		if (!$this->userExists($uid)) {
			$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
			$query->insert('users_external')
				->values([
					'uid' => $query->createNamedParameter($uid),
					'backend' => $query->createNamedParameter($this->backend),
				]);
			$query->execute();

			if ($groups) {
				$this-> UserSetGroups ($uid, $groups);
			}
		}
	}

	/**
	 * Check if a user exists
	 *
	 * @param string $uid the username
	 *
	 * @return boolean
	 */
	public function userExists($uid)
	{
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->getQueryBuilder();
		$query->select($query->func()->count('*', 'num_users'))
			->from('users_external')
			->where($query->expr()->iLike('uid', $query->createNamedParameter($connection->escapeLikeParameter($uid))))
			->andWhere($query->expr()->eq('backend', $query->createNamedParameter($this->backend)));
		$result = $query->execute();
		$users = $result->fetchColumn();
		$result->closeCursor();

		return $users > 0;
	}
		
	public function UserSetGroups ($uid, $groups)
	{
				$createduser = \OC::$server->getUserManager()->get($uid); 
				$old_groups = \OC::$server->getGroupManager()-> getUserGroupIds($createduser);
	
				$groupsToAdd = array_diff($groups, $old_groups);
				$groupsToRemove = array_diff($old_groups, $groups);	

				foreach ($groupsToRemove as $group) {
					if ($group !== 'admin') {
					\OC::$server->getGroupManager()->createGroup($group)->removeUser($createduser);
				}
		}
				foreach ($groupsToAdd as $group) {
					\OC::$server->getGroupManager()->createGroup($group)->addUser($createduser);
				}
	}

}
