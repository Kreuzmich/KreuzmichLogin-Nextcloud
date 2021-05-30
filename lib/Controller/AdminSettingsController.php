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

namespace OCA\kreuzmichlogin\Controller;

use OCP\App;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;

use OCA\kreuzmichlogin\AppConfig;

/**
 * Settings controller for the administration page
 */
class AdminSettingsController extends Controller {

    /**
     * l10n service
     *
     * @var IL10N
     */
    private $trans;

    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

    /**
     * Application configuration
     *
     * @var OCA\kreuzmichlogin\AppConfig
     */
    private $config;

    /**
     * Url generator service
     *
     * @var IURLGenerator
     */
    private $urlGenerator;


    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IURLGenerator $urlGenerator - url generator service
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param OCA\Onlyoffice\AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                IRequest $request,
                                IURLGenerator $urlGenerator,
                                IL10N $trans,
                                ILogger $logger,
                                AppConfig $config
                                ) {
        parent::__construct($AppName, $request);

        $this->urlGenerator = $urlGenerator;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Print config section
     *
     * @return TemplateResponse
     */
    public function index() {
		$response = $this->config->LoadFromDatabase();
        $data = [
			"city" => $response["city"],
			"httpuser" => $response["httpuser"],
			"httppass" => $response["httppass"],
			"expired" => ($response["expired"] === "false") ? false : true,
			"new" => ($response["new"] === "false") ? false : true,
			"groups" => $response["groups"]
            ];
        return new TemplateResponse($this->appName, "settings", $data, "user");
    }

   
    /**
     * Save settings
     *
     *
     * @return array
     */
    public function save($httpUser,
						$httpPass,
						$city,
						$expired,
						$new,
						$groups
						) {
			$array = [
						"httpuser" => $httpUser ?? "",
						"httppass" => $httpPass ?? "",
						"city" => $city,
						"expired" => $expired,
						"new" => $new,
						"groups" => $groups
					];
			$this->config->SaveToDatabase ($array);
			$response = $this->config->LoadFromDatabase();
        return [
			"city" => $response["city"],
			"httpuser" => $response["httpuser"],
			"httppass" => $response["httppass"],
			"expired" => ($response["expired"] === "false") ? false : true,
			"new" => ($response["new"] === "false") ? false : true,
			"groups" => $groups
            ];
    }

    /**
     * Get app settings
     *
     * @return array
     *
     * @NoAdminRequired
     * @PublicPage
     */
    public function GetSettings() {
        $result = [
            "formats" => $this->config->FormatsSetting(),
            "sameTab" => $this->config->GetSameTab()
        ];
        return $result;
    }


}
