<?php

/**
 * This file is part of the package sabre/baikal.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
 *  All rights reserved
 *
 *  http://sabre.io/baikal
 *
 *  This script is part of the Baïkal Server project. The Baïkal
 *  Server project is free software; you can redistribute it
 *  and/or modify it under the terms of the GNU General Public
 *  License as published by the Free Software Foundation; either
 *  version 2 of the License, or (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use BaikalAdmin\Controller\Install\Database;
use BaikalAdmin\Controller\Install\Initialize;
use BaikalAdmin\Controller\Install\UpgradeConfirmation;
use BaikalAdmin\Controller\Install\VersionUpgrade;
use BaikalAdmin\Controller\Navigation\Topbar\Install;
use BaikalAdmin\Framework;
use Flake\Controller\Page;
use Flake\Util\Tools;
use Symfony\Component\Yaml\Yaml;

ini_set('session.cookie_httponly', 1);
ini_set('log_errors', 1);
$maxtime = (int) ini_get('max_execution_time');
if ($maxtime !== 0 && ($maxtime < 3600)) {
    ini_set('max_execution_time', 3600); // 1 hour
}
ini_set('ignore_user_abort', true);
error_reporting(E_ALL);

define('BAIKAL_CONTEXT', true);
define('BAIKAL_CONTEXT_INSTALL', true);
define('PROJECT_CONTEXT_BASEURI', '/admin/install/');

$currentWorkingDirectory = getcwd();

if ($currentWorkingDirectory === false) {
    exit('<h1>Incomplete installation</h1><p>The current working directory is not accessible. Check if any one of the parent directories does have the readable or search mode set. See chmod for more information on modes and permissions.</p>');
}

if (file_exists(dirname($currentWorkingDirectory, 2) . '/Core')) {
    // Flat FTP mode
    define('PROJECT_PATH_ROOT', dirname($currentWorkingDirectory, 2) . '/');
} else {
    // Dedicated server mode
    define('PROJECT_PATH_ROOT', dirname($currentWorkingDirectory, 3) . '/');
}

if (!file_exists(PROJECT_PATH_ROOT . 'vendor/')) {
    exit('<h1>Incomplete installation</h1><p>Ba&iuml;kal dependencies have not been installed. If you are a regular user, this means that you probably downloaded the wrong zip file.</p><p>To install the dependencies manually, execute "<strong>composer install</strong>" in the Ba&iuml;kal root folder.</p>');
}

require PROJECT_PATH_ROOT . 'vendor/autoload.php';

// Bootstrapping Flake
Flake\Framework::bootstrap();

// Bootstrap BaikalAdmin
Framework::bootstrap();

// Create and set up a page object
$oPage = new Page(BAIKALADMIN_PATH_TEMPLATES . 'Page/index.html');
$oPage->injectHTTPHeaders();
$oPage->setTitle('Baïkal Maintainance');
$oPage->setBaseUrl(PROJECT_URI);

$oPage->zone('navbar')->addBlock(new Install());

try {
    /** @var array<string, array<string, mixed>> $config */
    $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
} catch (Exception $e) {
    $config = null;
    error_log('Error reading baikal.yaml file : ' . $e->getMessage());
}

if (($config === null) || !isset($config['system']['configured_version'])) {
    // we have to upgrade Baïkal (existing installation)
    $oPage->zone('Payload')->addBlock(new Initialize());
} elseif (isset($config['system']['admin_passwordhash'])) {
    if ($config['system']['configured_version'] !== BAIKAL_VERSION) {
        // we have to upgrade Baïkal
        if (Tools::GET('upgradeConfirmed')) {
            $oPage->zone('Payload')->addBlock(new VersionUpgrade());
        } else {
            $oPage->zone('Payload')->addBlock(new UpgradeConfirmation());
        }
    } elseif (file_exists(PROJECT_PATH_SPECIFIC . '/INSTALL_DISABLED')) {
        echo "Installation was already completed. Please head to the admin interface to modify any settings.\n";
        exit;
    } else {
        $oPage->zone('Payload')->addBlock(new Database());
    }
} else {
    // we have to set an admin password
    $oPage->zone('Payload')->addBlock(new Initialize());
}

// Render the page
echo $oPage->render();
