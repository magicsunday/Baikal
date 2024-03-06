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
//  http://flake.codr.fr
//
//  This script is part of the Flake project. The Flake
//  project is free software; you can redistribute it
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

namespace Flake;

use Exception;
use Flake\Core\Database;
use Flake\Core\Database\Mysql;
use Flake\Core\Database\Sqlite;
use Flake\Util\Tools;
use ReflectionException;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;
use function chr;
use function define;
use function defined;
use function dirname;
use function in_array;
use function is_array;
use function strlen;

class Framework extends Core\Framework
{
    /**
     * @param $sString
     *
     * @return mixed|string
     */
    public static function rmBeginSlash($sString): mixed
    {
        if (str_starts_with((string) $sString, '/')) {
            return substr((string) $sString, 1);
        }

        return $sString;
    }

    /**
     * @param $sString
     *
     * @return mixed|string
     */
    public static function appendSlash(string $sString): mixed
    {
        if (!str_ends_with($sString, '/')) {
            $sString .= '/';
        }

        return $sString;
    }

    /**
     * @param $sString
     *
     * @return mixed|string
     */
    public static function prependSlash(string $sString): mixed
    {
        if (!str_starts_with($sString, '/')) {
            return '/' . $sString;
        }

        return $sString;
    }

    /**
     * @param $sString
     *
     * @return string
     */
    public static function rmQuery($sString): string
    {
        $iStart = strpos((string) $sString, '?');

        return ($iStart === false) ? $sString : substr((string) $sString, 0, $iStart);
    }

    /**
     * @param $sString
     * @param $sScriptName
     *
     * @return string
     */
    public static function rmScriptName($sString, $sScriptName): string
    {
        $sScriptBaseName = basename((string) $sScriptName);
        if (self::endswith($sString, $sScriptBaseName)) {
            return substr((string) $sString, 0, -strlen($sScriptBaseName));
        }

        return $sString;
    }

    /**
     * @param $sString
     *
     * @return mixed|string
     */
    public static function rmProjectContext($sString): mixed
    {
        return self::appendSlash(
            substr((string) $sString, 0, -1 * strlen(PROJECT_CONTEXT_BASEURI))
        );
    }

    /**
     * @param $sString
     * @param $sTest
     *
     * @return bool
     */
    public static function endsWith($sString, $sTest): bool
    {
        $iTestLen = strlen((string) $sTest);
        if ($iTestLen > strlen((string) $sString)) {
            return false;
        }

        return substr_compare((string) $sString, (string) $sTest, -$iTestLen) === 0;
    }

    /**
     * @return void
     */
    public static function bootstrap(): void
    {
        // Asserting PHP 5.5.0+
        if (PHP_VERSION_ID < 50500) {
            exit('Flake Fatal Error: Flake requires PHP 5.5.0+ to run properly. Your version is: ' . PHP_VERSION . '.');
        }

        // Define safehash salt
        define('PROJECT_SAFEHASH_SALT', 'strong-secret-salt');

        // Define absolute server path to Flake Framework
        define('FLAKE_PATH_ROOT', PROJECT_PATH_ROOT . 'Core/Frameworks/Flake/');    // ./

        if (!defined('LF')) {
            define('LF', chr(10));
        }

        if (!defined('CR')) {
            define('CR', chr(13));
        }

        if (array_key_exists('SERVER_NAME', $_SERVER) && $_SERVER['SERVER_NAME'] === 'mongoose') {
            define('MONGOOSE_SERVER', true);
        } else {
            define('MONGOOSE_SERVER', false);
        }

        $magicQuotes = ini_get('magic_quotes_gpc');

        // Undo magic_quotes as this cannot be disabled by .htaccess on PHP ran as CGI
        // Source: http://stackoverflow.com/questions/517008/how-to-turn-off-magic-quotes-on-shared-hosting
        // Also: https://github.com/netgusto/Baikal/issues/155
        if ($magicQuotes !== false && in_array(
            strtolower($magicQuotes),
            [
                '1',
                'on',
            ]
        )) {
            $process = [];
            if (isset($_GET) && is_array($_GET)) {
                $process[] = &$_GET;
            }

            if (isset($_POST) && is_array($_POST)) {
                $process[] = &$_POST;
            }

            if (isset($_COOKIE) && is_array($_COOKIE)) {
                $process[] = &$_COOKIE;
            }

            if (isset($_REQUEST) && is_array($_REQUEST)) {
                $process[] = &$_REQUEST;
            }

            foreach ($process as $key => $val) {
                foreach ($val as $k => $v) {
                    unset($process[$key][$k]);
                    if (is_array($v)) {
                        $process[$key][stripslashes($k)] = $v;
                        $process[]                       = &$process[$key][stripslashes($k)];
                    } else {
                        $process[$key][stripslashes($k)] = stripslashes((string) $v);
                    }
                }
            }

            unset($process);
        }

        // Fixing some CGI environments, that prefix HTTP_AUTHORIZATION (forwarded in .htaccess) with "REDIRECT_"
        if (array_key_exists('REDIRECT_HTTP_AUTHORIZATION', $_SERVER)) {
            $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // ################################################################################################

        // determine Flake install root path
        // not using realpath here to avoid symlinks resolution

        define('PROJECT_PATH_CORE', PROJECT_PATH_ROOT . 'Core/');
        define('PROJECT_PATH_CORERESOURCES', PROJECT_PATH_CORE . 'Resources/');
        define('PROJECT_PATH_FRAMEWORKS', PROJECT_PATH_CORE . 'Frameworks/');
        define('PROJECT_PATH_WWWROOT', PROJECT_PATH_CORE . 'WWWRoot/');

        // set PROJECT_PATH_CONFIG from BAIKAL_PATH_CONFIG
        $baikalPathConfig = getenv('BAIKAL_PATH_CONFIG');
        if ($baikalPathConfig !== false) {
            define('PROJECT_PATH_CONFIG', $baikalPathConfig);
        } else {
            define('PROJECT_PATH_CONFIG', PROJECT_PATH_ROOT . 'config/');
        }

        // set PROJECT_PATH_SPECIFIC from BAIKAL_PATH_CONFIG
        $baikalPathConfig = getenv('BAIKAL_PATH_SPECIFIC');
        if ($baikalPathConfig !== false) {
            define('PROJECT_PATH_SPECIFIC', $baikalPathConfig);
        } else {
            define('PROJECT_PATH_SPECIFIC', PROJECT_PATH_ROOT . 'Specific/');
        }

        require_once PROJECT_PATH_CORE . 'Distrib.php';

        define('PROJECT_PATH_DOCUMENTROOT', PROJECT_PATH_ROOT . 'html/');

        self::defineBaseUri();

        // ################################################################################################

        // Include Flake Framework config
        require_once FLAKE_PATH_ROOT . 'config.php';

        // Determine Router class
        $GLOBALS['ROUTER'] = Tools::router();

        if (!Tools::isCliPhp()) {
            ini_set('html_errors', true);
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['CSRF_TOKEN'])) {
                $_SESSION['CSRF_TOKEN'] = bin2hex(openssl_random_pseudo_bytes(20));
            }
        }

        setlocale(LC_ALL, FLAKE_LOCALE);
        date_default_timezone_set(FLAKE_TIMEZONE);

        $GLOBALS['TEMPLATESTACK'] = [];

        $aUrlInfo = parse_url((string) PROJECT_URI);
        define('FLAKE_DOMAIN', $_SERVER['HTTP_HOST']);
        define('FLAKE_URIPATH', Tools::stripBeginSlash($aUrlInfo['path']));
        unset($aUrlInfo);

        self::initDb();
    }

    /**
     * @return void
     */
    protected static function defineBaseUri(): void
    {
        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
            if (isset($config['system']['base_uri']) && $config['system']['base_uri'] !== '') {
                // SabreDAV needs a "/" at the beginning of BASEURL
                define(
                    'PROJECT_BASEURI',
                    self::prependSlash(self::appendSlash($config['system']['base_uri']))
                );
                define(
                    'PROJECT_URI',
                    Tools::getCurrentProtocol() . '://'
                    . $_SERVER['HTTP_HOST'] . PROJECT_BASEURI
                );

                return;
            }
        } catch (Exception $exception) {
            error_log((string) $exception);
        }

        $sScript  = substr((string) $_SERVER['SCRIPT_FILENAME'], strlen((string) $_SERVER['DOCUMENT_ROOT']));
        $sDirName = str_replace('\\', '/', dirname($sScript));    // fix windows backslashes

        $sDirName = $sDirName !== '.' ? self::appendSlash($sDirName) : '/';

        $sBaseUrl = self::rmBeginSlash(self::rmProjectContext($sDirName));
        define('PROJECT_BASEURI', self::prependSlash($sBaseUrl));    // SabreDAV needs a "/" at the beginning of BASEURL

        // Determine PROJECT_URI
        $sProtocol    = Tools::getCurrentProtocol();
        $sHttpBaseUrl = $_SERVER['REQUEST_URI'];
        $sHttpBaseUrl = self::rmQuery($sHttpBaseUrl);
        $sHttpBaseUrl = self::rmScriptName($sHttpBaseUrl, $sScript);
        $sHttpBaseUrl = self::rmProjectContext($sHttpBaseUrl);
        define('PROJECT_URI', $sProtocol . '://' . $_SERVER['HTTP_HOST'] . $sHttpBaseUrl);
        unset($sScript, $sDirName, $sBaseUrl, $sProtocol, $sHttpBaseUrl);
    }

    /**
     * @return true|void
     */
    protected static function initDb()
    {
        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
        } catch (Exception $exception) {
            error_log('Error reading baikal.yaml file : ' . $exception->getMessage());

            return true;
        }

        // Dont init db on install, but in normal mode and when upgrading
        if (defined(
            'BAIKAL_CONTEXT_INSTALL'
        ) && (!isset($config['system']['configured_version']) || $config['system']['configured_version'] === BAIKAL_VERSION)) {
            return true;
        }

        if ($config['database']['mysql'] === true) {
            self::initDbMysql($config);
        } else {
            self::initDbSqlite($config);
        }
    }

    /**
     * @param array $config
     *
     * @return bool|void
     */
    protected static function initDbSqlite(array $config): bool
    {
        // Asserting DB filepath is set
        if (!$config['database']['sqlite_file']) {
            return false;
        }

        // Asserting DB file is writable
        if (file_exists($config['database']['sqlite_file']) && !is_writable($config['database']['sqlite_file'])) {
            exit("<h3>DB file is not writable. Please give write permissions on file '<span style='font-family: monospace; background: yellow;'>" . $config['database']['sqlite_file'] . "</span>'</h3>");
        }

        // Asserting DB directory is writable
        if (!is_writable(dirname((string) $config['database']['sqlite_file']))) {
            exit(
                "<h3>The <em>FOLDER</em> containing the DB file is not writable, and it has to.<br />Please give write permissions on folder '<span style='font-family: monospace; background: yellow;'>" . dirname(
                    (string) $config['database']['sqlite_file']
                ) . "</span>'</h3>"
            );
        }

        if (!isset($GLOBALS['DB'])
            && file_exists($config['database']['sqlite_file'])
            && is_readable($config['database']['sqlite_file'])
        ) {
            $GLOBALS['DB'] = new Sqlite($config['database']['sqlite_file']);

            return true;
        }

        return false;
    }

    /**
     * @param array $config
     *
     * @return true|void
     */
    protected static function initDbMysql(array $config): bool
    {
        if (!$config['database']['mysql_host']) {
            exit('<h3>The constant PROJECT_DB_MYSQL_HOST, containing the MySQL host name, is not set.<br />You should set it in config/baikal.yaml</h3>');
        }

        if (!$config['database']['mysql_dbname']) {
            exit('<h3>The constant PROJECT_DB_MYSQL_DBNAME, containing the MySQL database name, is not set.<br />You should set it in config/baikal.yaml</h3>');
        }

        if (!$config['database']['mysql_username']) {
            exit('<h3>The constant PROJECT_DB_MYSQL_USERNAME, containing the MySQL database username, is not set.<br />You should set it in config/baikal.yaml</h3>');
        }

        if (!$config['database']['mysql_password']) {
            exit('<h3>The constant PROJECT_DB_MYSQL_PASSWORD, containing the MySQL database password, is not set.<br />You should set it in config/baikal.yaml</h3>');
        }

        try {
            $GLOBALS['DB'] = new Mysql(
                $config['database']['mysql_host'],
                $config['database']['mysql_dbname'],
                $config['database']['mysql_username'],
                $config['database']['mysql_password']
            );

            // We now setup t6he connexion to use UTF8
            $GLOBALS['DB']->query('SET NAMES UTF8');
        } catch (Exception) {
            exit('<h3>Baïkal was not able to establish a connexion to the configured MySQL database (as configured in config/baikal.yaml).</h3>');
        }

        return true;
    }

    /**
     * @throws ReflectionException
     */
    public static function isDBInitialized(): bool
    {
        return isset($GLOBALS['DB']) && Tools::is_a($GLOBALS['DB'], Database::class);
    }
}
