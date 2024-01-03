<?php

declare(strict_types=1);

#################################################################
#  Copyright notice
#
#  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://sabre.io/baikal
#
#  This script is part of the Baïkal Server project. The Baïkal
#  Server project is free software; you can redistribute it
#  and/or modify it under the terms of the GNU General Public
#  License as published by the Free Software Foundation; either
#  version 2 of the License, or (at your option) any later version.
#
#  The GNU General Public License can be found at
#  http://www.gnu.org/copyleft/gpl.html.
#
#  This script is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  This copyright notice MUST APPEAR in all copies of the script!
#################################################################

namespace BaikalAdmin\Core;

use Exception;
use Flake\Util\Tools;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class Auth
{
    /**
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');

        return isset($_SESSION['baikaladminauth']) && $_SESSION['baikaladminauth'] === md5(
                $config['system']['admin_passwordhash']
            );
    }

    /**
     * @return bool
     */
    public static function authenticate(): bool
    {
        if ((int)Tools::POST('auth') !== 1) {
            return false;
        }

        $sUser = Tools::POST('login');
        $sPass = Tools::POST('password');

        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
        } catch (Exception $e) {
            error_log('Error reading baikal.yaml file : ' . $e->getMessage());

            return false;
        }
        $sPassHash = self::hashAdminPassword($sPass, $config['system']['auth_realm']);
        if ($sUser === 'admin' && $sPassHash === $config['system']['admin_passwordhash']) {
            $_SESSION['baikaladminauth'] = md5($config['system']['admin_passwordhash']);

            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public static function unAuthenticate(): void
    {
        unset($_SESSION['baikaladminauth']);
    }

    /**
     * @param $sPassword
     * @param $sAuthRealm
     *
     * @return string
     */
    public static function hashAdminPassword($sPassword, $sAuthRealm): string
    {
        return hash('sha256', 'admin:' . $sAuthRealm . ':' . $sPassword);
    }
}
