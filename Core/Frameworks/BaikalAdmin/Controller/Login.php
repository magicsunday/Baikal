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

namespace BaikalAdmin\Controller;

use BaikalAdmin\Core\Auth;
use Flake\Core\Controller;
use Flake\Util\Tools;
use Formal\Core\Message;
use Symfony\Component\Yaml\Yaml;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Login extends Controller
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
        $sActionUrl         = $GLOBALS['ROUTER']::buildRoute('default', []);
        $sSubmittedFlagName = 'auth';
        $sMessage           = '';

        $sLogin = htmlspecialchars((string) Tools::POST('login'));

        if (self::isSubmitted() && !Auth::isAuthenticated()) {
            // Log failed accesses, for further processing by tools like Fail2Ban
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
            if (isset($config['system']['failed_access_message']) && $config['system']['failed_access_message'] !== '') {
                $log_msg = str_replace('%u', $sLogin, (string) $config['system']['failed_access_message']);
                error_log($log_msg, 4);
            }

            $sMessage = Message::error(
                'The login/password you provided is invalid. Please retry.',
                'Authentication error'
            );
        } elseif (self::justLoggedOut()) {
            $sMessage = Message::notice(
                'You have been disconnected from your session.',
                'Session ended',
                false
            );
        }

        $sPassword = htmlspecialchars((string) Tools::POST('password'));

        if (trim($sLogin) === '') {
            $sLogin = 'admin';
        }

        $oView = new \BaikalAdmin\View\Login();
        $oView->setData('message', $sMessage);
        $oView->setData('actionurl', $sActionUrl);
        $oView->setData('submittedflagname', $sSubmittedFlagName);
        $oView->setData('login', $sLogin);
        $oView->setData('password', $sPassword);

        return $oView->render();
    }

    /**
     * @return bool
     */
    protected static function isSubmitted(): bool
    {
        return (int) Tools::POST('auth') === 1;
    }

    /**
     * @return bool
     */
    protected static function justLoggedOut(): bool
    {
        $aParams = $GLOBALS['ROUTER']::getURLParams();

        return !empty($aParams) && $aParams[0] === 'loggedout';
    }
}
