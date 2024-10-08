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

namespace Baikal\Core;

use DateTimeZone;
use Flake\Core\Database;
use Flake\Framework;
use PDO;
use ReflectionException;
use RuntimeException;

use function count;
use function defined;
use function in_array;
use function is_writable;
use function sys_get_temp_dir;

class Tools
{
    /**
     * @return mixed
     */
    public static function db(): mixed
    {
        return $GLOBALS['pdo'];
    }

    /**
     * @return void
     */
    public static function assertEnvironmentIsOk(): void
    {
        // Asserting Baikal Context
        if (!defined('BAIKAL_CONTEXT') || !BAIKAL_CONTEXT) {
            exit('Bootstrap.php may not be included outside the Baikal context');
        }

        // Asserting PDO
        if (!defined('PDO::ATTR_DRIVER_NAME')) {
            exit("Baikal Fatal Error: PDO is unavailable. It's required by Baikal.");
        }

        // Asserting PDO::SQLite or PDO::MySQL
        $aPDODrivers = PDO::getAvailableDrivers();
        if (!in_array('sqlite', $aPDODrivers, true) && !in_array('mysql', $aPDODrivers, true)) {
            exit('<strong>Baikal Fatal Error</strong>: Both <strong>PDO::sqlite</strong> and <strong>PDO::mysql</strong> are unavailable. One of them at least is required by Baikal.');
        }

        // Assert that the temp folder is writable
        if (!is_writable(sys_get_temp_dir())) {
            exit('<strong>Baikal Fatal Error</strong>: The system temp directory is not writable.');
        }
    }

    /**
     * @return void
     */
    public static function configureEnvironment(): void
    {
        set_exception_handler(Tools::class . '::handleException');
        ini_set('error_reporting', E_ALL);
    }

    /**
     * @param $exception
     *
     * @return void
     */
    public static function handleException(string $exception): void
    {
        echo '<pre>' . $exception . '<pre>';
    }

    /**
     * @return void
     *
     * @throws ReflectionException
     */
    public static function assertBaikalIsOk(): void
    {
        // DB connexion has not been asserted earlier by Flake, to give us a chance to trigger the install tool
        // We assert it right now
        if ((!defined('BAIKAL_CONTEXT_INSTALL') || BAIKAL_CONTEXT_INSTALL === false)
            && !Framework::isDBInitialized()
        ) {
            throw new RuntimeException('<strong>Fatal error</strong>: no connection to a database is available.');
        }

        // Asserting that the database is structurally complete
        // if(($aMissingTables = self::isDBStructurallyComplete($GLOBALS["DB"])) !== TRUE) {
        //	throw new \Exception("<strong>Fatal error</strong>: Database is not structurally complete; missing tables are: <strong>" . implode("</strong>, <strong>", $aMissingTables) . "</strong>");
        // }

        // Asserting config file exists
        if (!file_exists(PROJECT_PATH_CONFIG . 'baikal.yaml')) {
            throw new RuntimeException(
                'config/baikal.yaml does not exist. Please use the Install tool to create it or duplicate baikal.yaml.dist.'
            );
        }

        // Asserting config file is readable
        if (!is_readable(PROJECT_PATH_CONFIG . 'baikal.yaml')) {
            throw new RuntimeException(
                "config/baikal.yaml is not readable. Please give read permissions to httpd user on file 'config/baikal.yaml'."
            );
        }

        // Asserting config file is writable
        if (!is_writable(PROJECT_PATH_CONFIG . 'baikal.yaml')) {
            throw new RuntimeException(
                "config/baikal.yaml is not writable. Please give write permissions to httpd user on file 'config/baikal.yaml'."
            );
        }
    }

    /**
     * @return string[]
     */
    public static function getRequiredTablesList(): array
    {
        return [
            'addressbooks',
            'calendarobjects',
            'calendars',
            'cards',
            'groupmembers',
            'locks',
            'principals',
            'users',
        ];
    }

    /**
     * @param Database $oDB
     *
     * @return array|true
     */
    public static function isDBStructurallyComplete(Database $oDB): array|true
    {
        $aRequiredTables = self::getRequiredTablesList();
        $aPresentTables  = $oDB->tables();

        $aIntersect = array_intersect($aRequiredTables, $aPresentTables);
        if (count($aIntersect) !== count($aRequiredTables)) {
            return array_diff($aRequiredTables, $aIntersect);
        }

        return true;
    }

    /**
     * @return array
     */
    public static function timezones(): array
    {
        $aZones = DateTimeZone::listIdentifiers();

        reset($aZones);

        return $aZones;
    }
}
