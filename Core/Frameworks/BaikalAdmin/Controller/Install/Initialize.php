<?php

/**
 * This file is part of the package sabre/baikal.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

// ################################################################
//  Copyright notice
//
//  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
//  All rights reserved
//
//  http://sabre.io/baikal
//
//  This script is part of the Baïkal Server project. The Baïkal
//  Server project is free software; you can redistribute it
//  and/or modify it under the terms of the GNU General Public
//  License as published by the Free Software Foundation; either
//  version 2 of the License, or (at your option) any later version.
//
//  The GNU General Public License can be found at
//  http://www.gnu.org/copyleft/gpl.html.
//
//  This script is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  This copyright notice MUST APPEAR in all copies of the script!
// ################################################################

namespace BaikalAdmin\Controller\Install;

use Baikal\Model\Config\Database;
use Baikal\Model\Config\Standard;
use Flake\Core\Controller;
use Flake\Util\Tools;
use Formal\Form;
use PDO;
use ReflectionException;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function defined;
use function in_array;

class Initialize extends Controller
{
    protected array $aMessages = [];

    protected Standard $oModel;

    protected Form $oForm;    // \Formal\Form

    /**
     * @return void
     *
     * @throws ReflectionException
     * @throws Exception
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     */
    public function execute(): void
    {
        // Assert that /Specific is writable

        if (!file_exists(PROJECT_PATH_SPECIFIC) || !is_dir(PROJECT_PATH_SPECIFIC) || !is_writable(
            PROJECT_PATH_SPECIFIC
        ) || !file_exists(PROJECT_PATH_CONFIG) || !is_dir(PROJECT_PATH_CONFIG) || !is_writable(
            PROJECT_PATH_CONFIG
        )) {
            $message = '<h1>Error - Insufficient  permissions on the configuration folders</h1><p>';
            $message .= '<p>In order to work properly, Baïkal needs to have write permissions in the <strong>Specific/</strong> and <strong>config/</strong> folder.</p>';

            exit($message);
        }

        $this->createHtaccessFilesIfNeeded();

        $this->oModel = new Standard();

        // If we come from pre-0.7.0, we need to get the values from the config.php and config.system.php files
        if (file_exists(PROJECT_PATH_SPECIFIC . 'config.php')) {
            require_once PROJECT_PATH_SPECIFIC . 'config.php';
            $this->oModel->set('timezone', PROJECT_TIMEZONE);
            $this->oModel->set('card_enabled', BAIKAL_CARD_ENABLED);
            $this->oModel->set('cal_enabled', BAIKAL_CAL_ENABLED);
            $this->oModel->set('invite_from', defined('BAIKAL_INVITE_FROM') ? BAIKAL_INVITE_FROM : '');
            $this->oModel->set('dav_auth_type', BAIKAL_DAV_AUTH_TYPE);
        }

        if (file_exists(PROJECT_PATH_SPECIFIC . 'config.system.php')) {
            require_once PROJECT_PATH_SPECIFIC . 'config.system.php';
            $this->oModel->set('auth_realm', BAIKAL_AUTH_REALM);
        }

        $this->oForm = $this->oModel->formForThisModelInstance([
            'close' => false,
        ]);

        if ($this->oForm->submitted()) {
            $this->oForm->execute();

            if ($this->oForm->persisted()) {
                // If we come from pre-0.7.0, we need to remove the INSTALL_DISABLED file so we go to the next step
                if (file_exists(PROJECT_PATH_SPECIFIC . '/INSTALL_DISABLED')) {
                    @unlink(PROJECT_PATH_SPECIFIC . '/INSTALL_DISABLED');
                }

                if (file_exists(PROJECT_PATH_SPECIFIC . 'config.php')) {
                    @unlink(PROJECT_PATH_SPECIFIC . 'config.php');
                }

                // Creating system config, and initializing BAIKAL_ENCRYPTION_KEY
                $oDatabaseConfig = new Database();
                $oDatabaseConfig->set('encryption_key', md5(microtime() . mt_rand()));

                // Default: PDO::SQLite or PDO::MySQL ?
                $aPDODrivers = PDO::getAvailableDrivers();
                if (!in_array(
                    'sqlite',
                    $aPDODrivers,
                    true
                )) {    // PDO::MySQL is already asserted in \Baikal\Core\Tools::assertEnvironmentIsOk()
                    $oDatabaseConfig->set('mysql', true);
                }

                $oDatabaseConfig->persist();
            }
        }
    }

    /**
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(): string
    {
        $oView = new \BaikalAdmin\View\Install\Initialize();
        $oView->setData('baikalversion', BAIKAL_VERSION);

        // If we come from pre-0.7.0 (old config files are still present),
        // we need to tell the installer page to show a warning message.
        $oView->setData('oldConfigSystem', file_exists(PROJECT_PATH_SPECIFIC . 'config.system.php'));

        if ($this->oForm->persisted()) {
            $sLink = PROJECT_URI . 'admin/install/?/database';
            Tools::redirect($sLink);
            exit(0);

            // $sMessage = "<p>Baïkal is now configured. You may <a class='btn btn-success' href='" . PROJECT_URI . "admin/'>Access the Baïkal admin</a></p>";
            // $sForm = "";
        }

        $sMessage = '';
        $sForm    = $this->oForm->render();

        $oView->setData('message', $sMessage);
        $oView->setData('form', $sForm);

        return $oView->render();
    }

    /**
     * @return void
     */
    protected function createHtaccessFilesIfNeeded(): void
    {
        $this->copyResourceFile('System/htaccess-documentroot', PROJECT_PATH_DOCUMENTROOT . '.htaccess');
        $this->copyResourceFile('System/htaccess-deny-all', PROJECT_PATH_SPECIFIC . '.htaccess');
        $this->copyResourceFile('System/htaccess-deny-all', PROJECT_PATH_CONFIG . '.htaccess');
    }

    /**
     * @param $template
     * @param $destination
     *
     * @return void
     */
    private function copyResourceFile(string $template, string $destination): void
    {
        if (!file_exists($destination)) {
            @copy(PROJECT_PATH_CORERESOURCES . $template, $destination);
        }

        if (!file_exists($destination)) {
            throw new RuntimeException(
                'Unable to create ' . $destination . '; you may try to create it manually by copying ' . PROJECT_PATH_CORERESOURCES . $template
            );
        }
    }
}
