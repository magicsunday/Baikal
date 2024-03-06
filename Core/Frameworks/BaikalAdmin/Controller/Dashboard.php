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

use Baikal\Model\AddressBook;
use Baikal\Model\AddressBook\Contact;
use Baikal\Model\Calendar;
use Baikal\Model\Calendar\Event;
use Baikal\Model\User;
use Flake\Core\Controller;
use Symfony\Component\Yaml\Yaml;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Dashboard extends Controller
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
        $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');

        $oView = new \BaikalAdmin\View\Dashboard();
        $oView->setData('BAIKAL_VERSION', BAIKAL_VERSION);

        // Services status
        $oView->setData('cal_enabled', $config['system']['cal_enabled']);
        $oView->setData('card_enabled', $config['system']['card_enabled']);

        // Statistics: Users
        $iNbUsers = User::getBaseRequester()->count();
        $oView->setData('nbusers', $iNbUsers);

        // Statistics: CalDAV
        $iNbCalendars = Calendar::getBaseRequester()->count();
        $oView->setData('nbcalendars', $iNbCalendars);

        $iNbEvents = Event::getBaseRequester()->count();
        $oView->setData('nbevents', $iNbEvents);

        // Statistics: CardDAV
        $iNbBooks = AddressBook::getBaseRequester()->count();
        $oView->setData('nbbooks', $iNbBooks);

        $iNbContacts = Contact::getBaseRequester()->count();
        $oView->setData('nbcontacts', $iNbContacts);

        return $oView->render();
    }
}
