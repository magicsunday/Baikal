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

namespace Baikal\Model\Config;

use Baikal\Core\Tools;
use Baikal\Model\Config;
use BaikalAdmin\Core\Auth;
use Exception;
use Formal\Element\Checkbox;
use Formal\Element\Listbox;
use Formal\Element\Password;
use Formal\Element\Text;
use Formal\Form\Morphology;
use Symfony\Component\Yaml\Yaml;

class Standard extends Config
{
    // Default values
    protected array $aData = [
        'configured_version'    => BAIKAL_VERSION,
        'timezone'              => 'Europe/Paris',
        'card_enabled'          => true,
        'cal_enabled'           => true,
        'dav_auth_type'         => 'Digest',
        'admin_passwordhash'    => '',
        'failed_access_message' => 'user %u authentication failure for Baikal',
        // While not editable as will change admin & any existing user passwords,
        // could be set to different value when migrating from legacy config
        'auth_realm' => 'BaikalDAV',
        'base_uri'   => '',
    ];

    public function __construct()
    {
        $this->aData['invite_from'] = 'noreply@' . $_SERVER['SERVER_NAME']; // Default value
        parent::__construct('system');
    }

    /**
     * @return Morphology
     */
    public function formMorphologyForThisModelInstance(): Morphology
    {
        $oMorpho = new Morphology();

        $oMorpho->add(new Listbox([
            'prop'       => 'timezone',
            'label'      => 'Server Time zone',
            'validation' => 'required',
            'options'    => Tools::timezones(),
        ]));

        $oMorpho->add(new Checkbox([
            'prop'  => 'card_enabled',
            'label' => 'Enable CardDAV',
        ]));

        $oMorpho->add(new Checkbox([
            'prop'  => 'cal_enabled',
            'label' => 'Enable CalDAV',
        ]));

        $oMorpho->add(new Text([
            'prop'  => 'invite_from',
            'label' => 'Email invite sender address',
            'help'  => 'Leave empty to disable sending invite emails',
        ]));

        $oMorpho->add(new Listbox([
            'prop'    => 'dav_auth_type',
            'label'   => 'WebDAV authentication type',
            'options' => [
                'Digest',
                'Basic',
                'Apache',
            ],
        ]));

        $oMorpho->add(new Password([
            'prop'  => 'admin_passwordhash',
            'label' => 'Admin password',
        ]));

        $oMorpho->add(new Password([
            'prop'       => 'admin_passwordhash_confirm',
            'label'      => 'Admin password, confirmation',
            'validation' => 'sameas:admin_passwordhash',
        ]));

        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
        } catch (Exception $e) {
            error_log('Error reading baikal.yaml file : ' . $e->getMessage());
        }

        if (!isset($config['system']['admin_passwordhash']) || trim(
            (string) $config['system']['admin_passwordhash']
        ) === '') {
            // No password set (Form is used in install tool), so password is required as it has to be defined
            $oMorpho->element('admin_passwordhash')->setOption('validation', 'required');
        } else {
            $sNotice = '-- Leave empty to keep current password --';
            $oMorpho->element('admin_passwordhash')->setOption('placeholder', $sNotice);
            $oMorpho->element('admin_passwordhash_confirm')->setOption('placeholder', $sNotice);
        }

        return $oMorpho;
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return 'Baïkal Settings';
    }

    /**
     * @param $sPropName
     * @param $sPropValue
     *
     * @return $this
     *
     * @throws Exception
     */
    public function set($sPropName, $sPropValue): static
    {
        if ($sPropName === 'admin_passwordhash' || $sPropName === 'admin_passwordhash_confirm') {
            // Special handling for password and passwordconfirm

            if ($sPropName === 'admin_passwordhash' && $sPropValue !== '') {
                parent::set(
                    'admin_passwordhash',
                    Auth::hashAdminPassword($sPropValue, $this->aData['auth_realm'])
                );
            }

            return $this;
        }

        return parent::set($sPropName, $sPropValue);
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
        if ($sPropName === 'admin_passwordhash' || $sPropName === 'admin_passwordhash_confirm') {
            return '';
        }

        return parent::get($sPropName);
    }
}
