<?php
/**
 *
 * (c) Copyright Raphael Menke 2020
 *
 * This program is a free software product.
 * You can redistribute it and/or modify it under the terms of the GNU Affero General Public License
 * (AGPL) version 3 as published by the Free Software Foundation.
 * In accordance with Section 7(a) of the GNU AGPL its Section 15 shall be amended to the effect
 * that Ascensio System SIA expressly excludes the warranty of non-infringement of any third-party rights.
 *
 * This program is distributed WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * For details, see the GNU AGPL at: http://www.gnu.org/licenses/agpl-3.0.html
 *
 *
 * The interactive user interfaces in modified source and object code versions of the Program
 * must display Appropriate Legal Notices, as required under Section 5 of the GNU AGPL version 3.
 *
 * Pursuant to Section 7(b) of the License you must retain the original Product logo when distributing the program.
 * Pursuant to Section 7(e) we decline to grant you any rights under trademark law for use of our trademarks.
 *
 * All the Product's GUI elements, including illustrations and icon sets, as well as technical
 * writing content are licensed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International.
 * See the License terms at http://creativecommons.org/licenses/by-sa/4.0/legalcode
 *
 */

namespace OCA\kreuzmichlogin;

use \DateInterval;
use \DateTime;

use OCP\IConfig;
use OCP\ILogger;

/**
 * Application configutarion
 *
 * @package OCA\Onlyoffice
 */
class AppConfig {

    /**
     * Application name
     *
     * @var string
     */
    private $appName;

    /**
     * Config service
     *
     * @var OCP\IConfig
     */
    private $config;

    /**
     * Logger
     *
     * @var OCP\ILogger
     */
    private $logger;

    /**
     * @param string $AppName - application name
     */
    public function __construct($AppName) {

        $this->appName = $AppName;

        $this->config = \OC::$server->getConfig();
        $this->logger = \OC::$server->getLogger();
    }

    /**
     * Get value from the system configuration
     *
     * @param string $key - key configuration
     * @param bool $system - get from root or from app section
     *
     * @return string
     */
    public function GetSystemValue($key, $system = false) {
        if ($system) {
            return $this->config->getSystemValue($key);
        }
        if (!empty($this->config->getSystemValue($this->appName))
            && array_key_exists($key, $this->config->getSystemValue($this->appName))) {
            return $this->config->getSystemValue($this->appName)[$key];
        }
        return NULL;
    }

	public function SaveToDatabase($value) {
		$this->config->setAppValue($this->appName, 'settings', json_encode($value));
	}

	public function LoadFromDatabase() {
		return json_decode($this->config->getAppValue($this->appName, 'settings', ""), true);
	}
}
