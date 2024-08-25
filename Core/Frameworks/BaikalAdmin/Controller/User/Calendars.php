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

namespace BaikalAdmin\Controller\User;

use Baikal\Model\Calendar;
use Baikal\Model\User;
use BaikalAdmin\Controller\Users;
use Exception;
use Flake\Core\Controller;
use Flake\Core\Model;
use Flake\Util\Tools;
use Formal\Core\Message;
use Formal\Form;
use ReflectionException;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function array_key_exists;

class Calendars extends Controller
{
    protected array $aMessages = [];

    protected Calendar $oModel;
    // \Baikal\Model\Calendar
    protected User $oUser;
    // \Baikal\Model\User
    protected Form $oForm;    // \Formal\Form

    /**
     * @return void
     *
     * @throws Exception
     */
    public function execute(): void
    {
        if (($iUser = $this->currentUserId()) === false) {
            throw new RuntimeException(
                Calendars::class . '::render(): User get-parameter not found.'
            );
        }

        $this->oUser = new User($iUser);

        if ($this->actionNewRequested()) {
            $this->actionNew();
        } elseif ($this->actionEditRequested()) {
            $this->actionEdit();
        } elseif ($this->actionDeleteRequested()) {
            $this->actionDelete();
        }
    }

    /**
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ReflectionException
     * @throws Exception
     */
    public function render(): string
    {
        $oView = new \BaikalAdmin\View\User\Calendars();

        // User
        $oView->setData('user', $this->oUser);

        // List of calendars
        $oCalendars = $this->oUser->getCalendarsBaseRequester()->execute();
        $aCalendars = [];

        foreach ($oCalendars as $calendar) {
            $aCalendars[] = [
                'linkedit'    => $this->linkEdit($calendar),
                'linkdelete'  => $this->linkDelete($calendar),
                'davuri'      => $this->getDavUri($calendar),
                'icon'        => $calendar->icon(),
                'label'       => $calendar->label(),
                'instanced'   => $calendar->hasInstances(),
                'events'      => $calendar->getEventsBaseRequester()->count(),
                'description' => $calendar->get('description'),
            ];
        }

        $oView->setData('calendars', $aCalendars);

        // Messages
        $sMessages = implode("\n", $this->aMessages);
        $oView->setData('messages', $sMessages);

        $sForm = $this->actionNewRequested() || $this->actionEditRequested() ? $this->oForm->render() : '';

        $oView->setData('form', $sForm);
        $oView->setData('titleicon', (new Calendar())->bigicon());
        $oView->setData('modelicon', $this->oUser->mediumicon());
        $oView->setData('modellabel', $this->oUser->label());
        $oView->setData('linkback', Users::link());
        $oView->setData('linknew', $this->linkNew());
        $oView->setData('calendaricon', (new Calendar())->icon());

        return $oView->render();
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function initForm(): void
    {
        if ($this->actionEditRequested() || $this->actionNewRequested()) {
            $aOptions = [
                'closeurl' => $this->linkHome(),
            ];

            $this->oForm = $this->oModel->formForThisModelInstance($aOptions);
        }
    }

    /**
     * @return false|int
     */
    protected function currentUserId(): false|int
    {
        $aParams = $this->getParams();

        if (($iUser = ((int) $aParams['user'])) === 0) {
            return false;
        }

        return $iUser;
    }

    /**
     * @return string
     */
    public function linkNew(): string
    {
        return self::buildRoute([
            'user' => $this->currentUserId(),
            'new'  => 1,
        ]) . '#form';
    }

    /**
     * @return bool
     */
    protected function actionNewRequested(): bool
    {
        $aParams = $this->getParams();

        return array_key_exists('new', $aParams) && (int) $aParams['new'] === 1;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function actionNew(): void
    {
        // Building floating model object
        $this->oModel = new Calendar();
        $this->oModel->set(
            'principaluri',
            $this->oUser->get('uri')
        );

        $this->oModel->set(
            'components',
            'VEVENT'
        );

        // Initialize corresponding form
        $this->initForm();

        // Process form
        if ($this->oForm->submitted()) {
            $this->oForm->execute();

            if ($this->oForm->persisted()) {
                $this->oForm->setOption(
                    'action',
                    $this->linkEdit(
                        $this->oForm->modelInstance()
                    )
                );
            }
        }
    }

    // Action edit

    /**
     * @param Model $oModel
     *
     * @return string
     *
     * @throws Exception
     */
    public function linkEdit(Model $oModel): string
    {
        return self::buildRoute([
            'user' => $this->currentUserId(),
            'edit' => $oModel->get('id'),
        ]) . '#form';
    }

    /**
     * @return bool
     */
    protected function actionEditRequested(): bool
    {
        $aParams = $this->getParams();

        return array_key_exists('edit', $aParams) && (((int) $aParams['edit']) > 0);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function actionEdit(): void
    {
        // Building anchored model object
        $aParams      = $this->getParams();
        $this->oModel = new Calendar($aParams['edit']);

        // Initialize corresponding form
        $this->initForm();

        // Process form
        if ($this->oForm->submitted()) {
            $this->oForm->execute();
        }
    }

    // Action delete + confirm

    /**
     * @param Calendar $oModel
     *
     * @return string
     *
     * @throws Exception
     */
    public function linkDelete(Calendar $oModel): string
    {
        return self::buildRoute([
            'user'   => $this->currentUserId(),
            'delete' => $oModel->get('id'),
        ]) . '#message';
    }

    /**
     * @param Calendar $oModel
     *
     * @return string
     *
     * @throws Exception
     */
    public function linkDeleteConfirm(Calendar $oModel): string
    {
        return self::buildRoute([
            'user'    => $this->currentUserId(),
            'delete'  => $oModel->get('id'),
            'confirm' => 1,
        ]) . '#message';
    }

    /**
     * @return bool
     */
    protected function actionDeleteRequested(): bool
    {
        $aParams = $this->getParams();

        return array_key_exists('delete', $aParams) && (((int) $aParams['delete']) > 0);
    }

    /**
     * @return bool
     */
    protected function actionDeleteConfirmed(): bool
    {
        if ($this->actionDeleteRequested() === false) {
            return false;
        }

        $aParams = $this->getParams();

        return array_key_exists('confirm', $aParams) && (((int) $aParams['confirm']) === 1);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function actionDelete(): void
    {
        $aParams   = $this->getParams();
        $iCalendar = $aParams['delete'];

        if ($this->actionDeleteConfirmed()) {
            // catching Exception thrown when model already destroyed
            // happens when user refreshes page on delete-URL, for instance

            try {
                $oModel = new Calendar($iCalendar);
                $oModel->destroy();
            } catch (Exception) {
                // already deleted; silently discarding
            }

            // Redirecting to admin home
            Tools::redirectUsingMeta($this->linkHome());
        } else {
            $oModel            = new Calendar($iCalendar);
            $this->aMessages[] = Message::warningConfirmMessage(
                "Check twice, you're about to delete " . $oModel->label() . '</strong> from the database !',
                "<p>You are about to delete a calendar and all it's scheduled events. This operation cannot be undone.</p><p>So, now that you know all that, what shall we do ?</p>",
                $this->linkDeleteConfirm($oModel),
                "Delete <strong><i class='" . $oModel->icon() . " icon-white'></i> " . $oModel->label() . '</strong>',
                $this->linkHome()
            );
        }
    }

    /**
     * @return string
     */
    public function linkHome(): string
    {
        return self::buildRoute([
            'user' => $this->currentUserId(),
        ]);
    }

    /**
     * Generate a link to the CalDAV/CardDAV URI of the calendar.
     *
     * @param Calendar $calendar
     *
     * @return string Calender DAV URI
     *
     * @throws Exception
     */
    protected function getDavUri(Calendar $calendar): string
    {
        return PROJECT_URI . 'dav.php/calendars/' . $this->oUser->get('username') . '/' . $calendar->get('uri') . '/';
    }
}
