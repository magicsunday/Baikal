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

namespace BaikalAdmin\Controller\Navigation;

use BaikalAdmin\Controller\Logout;
use BaikalAdmin\Controller\Settings\Database;
use BaikalAdmin\Controller\Settings\Standard;
use BaikalAdmin\Controller\User\AddressBooks;
use BaikalAdmin\Controller\User\Calendars;
use BaikalAdmin\Controller\Users;
use Flake\Core\Controller;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Topbar extends Controller
{
    /**
     * @return void
     */
    public function execute(): void
    {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(): string
    {
        $oView = new \BaikalAdmin\View\Navigation\Topbar();

        $sCurrentRoute = $GLOBALS['ROUTER']::getCurrentRoute();
        $sActiveHome   = $sActiveUsers = $sActiveSettingsStandard = $sActiveSettingsDatabase = '';

        $sControllerForDefaultRoute = $GLOBALS['ROUTER']::getControllerForRoute('default');
        $sHomeLink                  = $sControllerForDefaultRoute::link();
        $sUsersLink                 = Users::link();
        $sSettingsStandardLink      = Standard::link();
        $sSettingsDatabaseLink      = Database::link();
        $sLogoutLink                = Logout::link();

        if ($sCurrentRoute === 'default') {
            $sActiveHome = 'active';
        }
        if (
            $sCurrentRoute === $GLOBALS['ROUTER']::getRouteForController(Users::class)
            || $sCurrentRoute === $GLOBALS['ROUTER']::getRouteForController(Calendars::class)
            || $sCurrentRoute === $GLOBALS['ROUTER']::getRouteForController(AddressBooks::class)
        ) {
            $sActiveUsers = 'active';
        }

        if ($sCurrentRoute === $GLOBALS['ROUTER']::getRouteForController(Standard::class)) {
            $sActiveSettingsStandard = 'active';
        }

        if ($sCurrentRoute === $GLOBALS['ROUTER']::getRouteForController(Database::class)) {
            $sActiveSettingsDatabase = 'active';
        }

        $oView->setData('activehome', $sActiveHome);
        $oView->setData('activeusers', $sActiveUsers);
        $oView->setData('activesettingsstandard', $sActiveSettingsStandard);
        $oView->setData('activesettingsdatabase', $sActiveSettingsDatabase);
        $oView->setData('homelink', $sHomeLink);
        $oView->setData('userslink', $sUsersLink);
        $oView->setData('settingsstandardlink', $sSettingsStandardLink);
        $oView->setData('settingsdatabaselink', $sSettingsDatabaseLink);
        $oView->setData('logoutlink', $sLogoutLink);

        return $oView->render();
    }
}
