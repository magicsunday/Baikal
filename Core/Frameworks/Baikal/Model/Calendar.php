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

namespace Baikal\Model;

use Baikal\Model\Calendar\Event;
use Exception;
use Flake\Core\Model;
use Flake\Core\Model\Db;
use Flake\Core\Requester\Sql;
use Formal\Element\Checkbox;
use Formal\Element\Text;
use Formal\Form\Morphology;
use ReflectionException;
use Symfony\Component\Yaml\Yaml;

use function in_array;

class Calendar extends Db
{
    public const DATATABLE = 'calendarinstances';

    public const PRIMARYKEY = 'id';

    public const LABELFIELD = 'displayname';

    protected array $aData = [
        'principaluri'       => '',
        'displayname'        => '',
        'uri'                => '',
        'description'        => '',
        'calendarorder'      => 0,
        'calendarcolor'      => '',
        'timezone'           => null,
        'calendarid'         => 0,
        'access'             => 1,
        'share_invitestatus' => 2,
    ];

    protected Calendar\Calendar $oCalendar; // Baikal\Model\Calendar\Calendar

    /**
     * @param false|int|string $sPrimary
     *
     * @throws Exception
     */
    public function __construct(false|int|string $sPrimary = false)
    {
        parent::__construct($sPrimary);

        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
            $this->set('timezone', $config['system']['timezone']);
        } catch (Exception $exception) {
            error_log('Error reading baikal.yaml file : ' . $exception->getMessage());
        }
    }

    /**
     * @return void
     */
    protected function initFloating(): void
    {
        parent::initFloating();
        $this->oCalendar = new Calendar\Calendar();
    }

    /**
     * @param int|string $sPrimary
     *
     * @return void
     *
     * @throws Exception
     */
    protected function initByPrimary(int|string $sPrimary): void
    {
        parent::initByPrimary($sPrimary);
        $this->oCalendar = new Calendar\Calendar($this->get('calendarid'));
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function persist(): void
    {
        $this->oCalendar->persist();
        $this->aData['calendarid'] = $this->oCalendar->get('id');
        parent::persist();
    }

    /**
     * @return string
     */
    public static function icon(): string
    {
        return 'icon-calendar';
    }

    /**
     * @return string
     */
    public static function mediumicon(): string
    {
        return 'glyph-calendar';
    }

    /**
     * @return string
     */
    public static function bigicon(): string
    {
        return 'glyph2x-calendar';
    }

    /**
     * @return Sql
     *
     * @throws Exception
     */
    public function getEventsBaseRequester(): Sql
    {
        $oBaseRequester = Event::getBaseRequester();
        $oBaseRequester->addClauseEquals(
            'calendarid',
            (string) $this->get('calendarid')
        );

        return $oBaseRequester;
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
        if ($sPropName === 'components') {
            return $this->oCalendar->get($sPropName);
        }

        if ($sPropName === 'todos') {
            // TRUE if components contains VTODO, FALSE otherwise
            $aComponents = ($sComponents = $this->get('components')) !== '' ? explode(',', $sComponents) : [];

            return in_array('VTODO', $aComponents, true);
        }

        if ($sPropName === 'notes') {
            // TRUE if components contains VJOURNAL, FALSE otherwise
            $aComponents = ($sComponents = $this->get('components')) !== '' ? explode(',', $sComponents) : [];

            return in_array('VJOURNAL', $aComponents, true);
        }

        return parent::get($sPropName);
    }

    /**
     * @param string               $sPropName
     * @param bool|int|string|null $sPropValue
     *
     * @return Model
     *
     * @throws Exception
     */
    public function set(string $sPropName, bool|int|string|null $sPropValue): Model
    {
        if ($sPropName === 'components') {
            return $this->oCalendar->set($sPropName, $sPropValue);
        }

        if ($sPropName === 'todos') {
            $aComponents = ($sComponents = $this->get('components')) !== '' ? explode(',', $sComponents) : [];

            if ($sPropValue === true) {
                if (!in_array('VTODO', $aComponents, true)) {
                    $aComponents[] = 'VTODO';
                }
            } elseif (in_array('VTODO', $aComponents, true)) {
                unset($aComponents[array_search('VTODO', $aComponents, true)]);
            }

            return $this->set('components', implode(',', $aComponents));
        }

        if ($sPropName === 'notes') {
            $aComponents = ($sComponents = $this->get('components')) !== '' ? explode(',', $sComponents) : [];

            if ($sPropValue === true) {
                if (!in_array('VJOURNAL', $aComponents, true)) {
                    $aComponents[] = 'VJOURNAL';
                }
            } elseif (in_array('VJOURNAL', $aComponents, true)) {
                unset($aComponents[array_search('VJOURNAL', $aComponents, true)]);
            }

            return $this->set('components', implode(',', $aComponents));
        }

        return parent::set($sPropName, $sPropValue);
    }

    /**
     * @return Morphology
     */
    public function formMorphologyForThisModelInstance(): Morphology
    {
        $oMorpho = new Morphology();

        $oMorpho->add(new Text([
            'prop'       => 'uri',
            'label'      => 'Calendar token ID',
            'validation' => 'required,tokenid',
            'popover'    => [
                'title'   => 'Calendar token ID',
                'content' => 'The unique identifier for this calendar.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'       => 'displayname',
            'label'      => 'Display name',
            'validation' => 'required',
            'popover'    => [
                'title'   => 'Display name',
                'content' => 'This is the name that will be displayed in your CalDAV client.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'       => 'calendarcolor',
            'label'      => 'Calendar color',
            'validation' => 'color',
            'popover'    => [
                'title'   => 'Calendar color',
                'content' => 'This is the color that will be displayed in your CalDAV client.<br/>' .
                    "Must be supplied in format '#RRGGBBAA' (alpha channel optional) with hexadecimal values.<br/>" .
                    'This value is optional.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'  => 'description',
            'label' => 'Description',
        ]));

        $oMorpho->add(new Checkbox([
            'prop'  => 'todos',
            'label' => 'Todos',
            'help'  => 'If checked, todos will be enabled on this calendar.',
        ]));

        $oMorpho->add(new Checkbox([
            'prop'  => 'notes',
            'label' => 'Notes',
            'help'  => 'If checked, notes will be enabled on this calendar.',
        ]));

        if ($this->floating()) {
            $oMorpho->element('uri')->setOption(
                'help',
                "Allowed characters are digits, lowercase letters and the dash symbol '-'."
            );
        } else {
            $oMorpho->element('uri')->setOption('readonly', true);
        }

        return $oMorpho;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function isDefault(): bool
    {
        return $this->get('uri') === 'default';
    }

    /**
     * @return bool|null
     */
    public function hasInstances(): ?bool
    {
        $rSql = $GLOBALS['DB']->exec_SELECTquery(
            'count(*)',
            'calendarinstances',
            'calendarid=\'' . $this->aData['calendarid'] . "'"
        );

        if (($aRs = $rSql->fetch()) === false) {
            return false;
        }

        reset($aRs);

        return $aRs['count(*)'] > 1;
    }

    /**
     * @return void
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function destroy(): void
    {
        $hasInstances = $this->hasInstances();

        if (!$hasInstances) {
            /** @var Event[] $oEvents */
            $oEvents = $this->getEventsBaseRequester()->execute();

            foreach ($oEvents as $event) {
                $event->destroy();
            }
        }

        parent::destroy();

        if (!$hasInstances) {
            $this->oCalendar->destroy();
        }
    }
}
