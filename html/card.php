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

use Baikal\Core\Server;
use Baikal\Framework;
use Symfony\Component\Yaml\Yaml;

ini_set('session.cookie_httponly', 1);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('BAIKAL_CONTEXT', true);
define('PROJECT_CONTEXT_BASEURI', '/');

$currentWorkingDirectory = getcwd();

if ($currentWorkingDirectory === false) {
    exit('<h1>Incomplete installation</h1><p>The current working directory is not accessible. Check if any one of the parent directories does have the readable or search mode set. See chmod for more information on modes and permissions.</p>');
}

if (file_exists($currentWorkingDirectory . '/Core')) {
    // Flat FTP mode
    define('PROJECT_PATH_ROOT', $currentWorkingDirectory . '/');    // ./
} else {
    // Dedicated server mode
    define('PROJECT_PATH_ROOT', dirname($currentWorkingDirectory) . '/');    // ../
}

if (!file_exists(PROJECT_PATH_ROOT . 'vendor/')) {
    exit('<h1>Incomplete installation</h1><p>Ba&iuml;kal dependencies have not been installed. If you are a regular user, this means that you probably downloaded the wrong zip file.</p><p>To install the dependencies manually, execute "<strong>composer install</strong>" in the Ba&iuml;kal root folder.</p>');
}

require PROJECT_PATH_ROOT . 'vendor/autoload.php';

// Bootstrapping Flake
Flake\Framework::bootstrap();

// Bootstrapping Baïkal
Framework::bootstrap();

try {
    /** @var array<string, array<string, mixed>> $config */
    $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
} catch (Exception) {
    exit('<h1>Incomplete installation</h1><p>Ba&iuml;kal is missing its configuration file, or its configuration file is unreadable.');
}

if (!isset($config['system']['card_enabled']) || $config['system']['card_enabled'] !== true) {
    throw new ErrorException('Baikal CardDAV is disabled.', 0, 255, __FILE__, __LINE__);
}

/** @var array{system: array{cal_enabled: bool, card_enabled: bool, dav_auth_type: string, auth_realm: string}} $config */
$server = new Server(
    $config['system']['cal_enabled'],
    $config['system']['card_enabled'],
    $config['system']['dav_auth_type'],
    $config['system']['auth_realm'],
    $GLOBALS['DB']->getPDO(),
    PROJECT_BASEURI . 'card.php/'
);
$server->start();
