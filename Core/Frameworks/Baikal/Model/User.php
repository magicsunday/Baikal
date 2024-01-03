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

namespace Baikal\Model;

use Exception;
use Flake\Core\Model\Db;
use Flake\Core\Requester\Sql;
use Formal\Element\Password;
use Formal\Element\Text;
use Formal\Form\Morphology;
use ReflectionException;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class User extends Db
{
    public const DATATABLE = 'users';
    public const PRIMARYKEY = 'id';
    public const LABELFIELD = 'username';

    protected array $aData = [
        'username' => '',
        'digesta1' => '',
    ];

    protected ?Principal $oIdentityPrincipal = null;

    /**
     * @param string|int $sPrimary
     *
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    protected function initByPrimary(string|int $sPrimary): void
    {
        parent::initByPrimary($sPrimary);

        # Initializing principals
        $this->oIdentityPrincipal = Principal::getBaseRequester()
            ->addClauseEquals('uri', 'principals/' . $this->get('username'))
            ->execute()
            ->first();
    }

    /**
     * @throws Exception
     */
    public function getAddressBooksBaseRequester(): Sql
    {
        $oBaseRequester = AddressBook::getBaseRequester();
        $oBaseRequester->addClauseEquals(
            'principaluri',
            'principals/' . $this->get('username')
        );

        return $oBaseRequester;
    }

    /**
     * @throws Exception
     */
    public function getCalendarsBaseRequester(): Sql
    {
        $oBaseRequester = Calendar::getBaseRequester();
        $oBaseRequester->addClauseEquals(
            'principaluri',
            'principals/' . $this->get('username')
        );

        return $oBaseRequester;
    }

    /**
     * @return void
     */
    public function initFloating(): void
    {
        parent::initFloating();

        # Initializing principals
        $this->oIdentityPrincipal = new Principal();
    }

    /**
     * @param string $sPropName
     *
     * @return bool|int|string|null
     *
     * @throws Exception
     */
    public function get(string $sPropName): bool|int|string|null
    {
        if ($sPropName === 'password' || $sPropName === 'passwordconfirm') {
            # Special handling for password and passwordconfirm
            return '';
        }

        try {
            # does the property exist on the model object ?
            $sRes = parent::get($sPropName);
        } catch (Exception $e) {
            # no, it may belong to the oIdentityPrincipal model object
            if ($this->oIdentityPrincipal) {
                $sRes = $this->oIdentityPrincipal->get($sPropName);
            } else {
                $sRes = '';
            }
        }

        return $sRes;
    }

    /**
     * @param string               $sPropName
     * @param bool|int|string|null $sPropValue
     *
     * @return $this
     * @throws Exception
     */
    public function set(string $sPropName, bool|int|string|null $sPropValue): static
    {
        if ($sPropName === 'password' || $sPropName === 'passwordconfirm') {
            # Special handling for password and passwordconfirm

            if ($sPropName === 'password' && $sPropValue !== '') {
                parent::set(
                    'digesta1',
                    $this->getPasswordHashForPassword($sPropValue)
                );
            }

            return $this;
        }

        try {
            # does the property exist on the model object ?
            parent::set($sPropName, $sPropValue);
        } catch (Exception $e) {
            # no, it may belong to the oIdentityPrincipal model object
            if ($this->oIdentityPrincipal) {
                $this->oIdentityPrincipal->set($sPropName, $sPropValue);
            }
        }

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function persist(): void
    {
        $bFloating = $this->floating();

        # Persisted first, as Model users loads this data
        $this->oIdentityPrincipal->set('uri', 'principals/' . $this->get('username'));
        $this->oIdentityPrincipal->persist();

        parent::persist();

        if ($bFloating) {
            # Creating default calendar for user
            $oDefaultCalendar = new Calendar();
            $oDefaultCalendar->set(
                'principaluri',
                'principals/' . $this->get('username')
            )->set(
                'displayname',
                'Default calendar'
            )->set(
                'uri',
                'default'
            )->set(
                'description',
                'Default calendar'
            )->set(
                'components',
                'VEVENT,VTODO'
            );

            $oDefaultCalendar->persist();

            # Creating default address book for user
            $oDefaultAddressBook = new AddressBook();
            $oDefaultAddressBook->set(
                'principaluri',
                'principals/' . $this->get('username')
            )->set(
                'displayname',
                'Default Address Book'
            )->set(
                'uri',
                'default'
            )->set(
                'description',
                'Default Address Book for ' . $this->get('displayname')
            );

            $oDefaultAddressBook->persist();
        }
    }

    /**
     * @return void
     * @throws ReflectionException
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function destroy(): void
    {
        # TODO: delete all related resources (principals, calendars, calendar events, contact books and contacts)

        # Destroying identity principal
        if ($this->oIdentityPrincipal != null) {
            $this->oIdentityPrincipal->destroy();
        }

        $oCalendars = $this->getCalendarsBaseRequester()->execute();
        foreach ($oCalendars as $calendar) {
            $calendar->destroy();
        }

        $oAddressBooks = $this->getAddressBooksBaseRequester()->execute();
        foreach ($oAddressBooks as $addressbook) {
            $addressbook->destroy();
        }

        parent::destroy();
    }

    /**
     * @throws Exception
     */
    public function getMailtoURI(): string
    {
        return 'mailto:' . rawurlencode($this->get('displayname') . ' <' . $this->get('email') . '>');
    }

    /**
     * @return Morphology
     * @throws ReflectionException
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function formMorphologyForThisModelInstance(): Morphology
    {
        $oMorpho = new Morphology();

        $oMorpho->add(new Text([
            'prop'       => 'username',
            'label'      => 'Username',
            'validation' => 'required,unique',
            'popover'    => [
                'title'   => 'Username',
                'content' => 'The login for this user account. It has to be unique.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'       => 'displayname',
            'label'      => 'Display name',
            'validation' => 'required',
            'popover'    => [
                'title'   => 'Display name',
                'content' => 'This is the name that will be displayed in your CalDAV/CardDAV clients.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'       => 'email',
            'label'      => 'Email',
            'validation' => 'required,email',
        ]));

        $oMorpho->add(new Password([
            'prop'  => 'password',
            'label' => 'Password',
        ]));

        $oMorpho->add(new Password([
            'prop'       => 'passwordconfirm',
            'label'      => 'Confirm password',
            'validation' => 'sameas:password',
        ]));

        if ($this->floating()) {
            $oMorpho->element('username')->setOption('help', 'May be an email, but not forcibly.');
            $oMorpho->element('password')->setOption('validation', 'required');
        } else {
            $sNotice = '-- Leave empty to keep current password --';
            $oMorpho->element('username')->setOption('readonly', true);

            $oMorpho->element('password')->setOption('popover', [
                'title'   => 'Password',
                'content' => 'Write something here only if you want to change the user password.',
            ]);

            $oMorpho->element('passwordconfirm')->setOption('popover', [
                'title'   => 'Confirm password',
                'content' => 'Write something here only if you want to change the user password.',
            ]);

            $oMorpho->element('password')->setOption('placeholder', $sNotice);
            $oMorpho->element('passwordconfirm')->setOption('placeholder', $sNotice);
        }

        return $oMorpho;
    }

    /**
     * @return string
     */
    public static function icon(): string
    {
        return 'icon-user';
    }

    /**
     * @return string
     */
    public static function mediumicon(): string
    {
        return 'glyph-user';
    }

    /**
     * @return string
     */
    public static function bigicon(): string
    {
        return 'glyph2x-user';
    }

    /**
     * @throws Exception
     */
    public function getPasswordHashForPassword($sPassword): string
    {
        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
        } catch (Exception $e) {
            error_log('Error reading baikal.yaml file : ' . $e->getMessage());
        }

        return md5($this->get('username') . ':' . $config['system']['auth_realm'] . ':' . $sPassword);
    }
}
