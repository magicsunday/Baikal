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

namespace BaikalAdmin\Controller;

use Baikal\Model\User;
use BaikalAdmin\Controller\User\AddressBooks;
use BaikalAdmin\Controller\User\Calendars;
use Exception;
use Flake\Core\Controller;
use Flake\Core\Model;
use Flake\Util\Tools;
use Formal\Core\Message;
use Formal\Form;
use ReflectionException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function array_key_exists;

/**
 *
 */
class Users extends Controller
{
    protected array $aMessages = [];

    /**
     * @var User
     */
    private User $oModel;

    /**
     * @var Form
     */
    private Form $oForm;

    /**
     * @return void
     * @throws Exception
     */
    public function execute(): void
    {
        if ($this->actionEditRequested()) {
            $this->actionEdit();
        }

        if ($this->actionNewRequested()) {
            $this->actionNew();
        }

        if ($this->actionDeleteRequested()) {
            $this->actionDelete();
        }
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws ReflectionException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function render(): string
    {
        $oView = new \BaikalAdmin\View\Users();

        # List of users
        $aUsers = [];
        $oUsers = User::getBaseRequester()->execute();

        foreach ($oUsers as $user) {
            $aUsers[] = [
                'linkcalendars'    => self::linkCalendars($user),
                'linkaddressbooks' => self::linkAddressBooks($user),
                'linkedit'         => self::linkEdit($user),
                'linkdelete'       => self::linkDelete($user),
                'mailtouri'        => $user->getMailtoURI(),
                'username'         => $user->get('username'),
                'displayname'      => $user->get('displayname'),
                'email'            => $user->get('email'),
            ];
        }

        $oView->setData('users', $aUsers);
        $oView->setData('calendaricon', (new \Baikal\Model\Calendar)->icon());
        $oView->setData('usericon', (new \Baikal\Model\User)->icon());
        $oView->setData('davUri', PROJECT_URI . 'dav.php');

        # Messages
        $sMessages = implode("\n", $this->aMessages);
        $oView->setData('messages', $sMessages);

        # Form
        if ($this->actionNewRequested() || $this->actionEditRequested()) {
            $sForm = $this->oForm->render();
        } else {
            $sForm = '';
        }

        $oView->setData('form', $sForm);
        $oView->setData('usericon', (new \Baikal\Model\User)->icon());
        $oView->setData('controller', $this);

        return $oView->render();
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    protected function initForm(): void
    {
        if ($this->actionEditRequested() || $this->actionNewRequested()) {
            $aOptions = [
                'closeurl' => self::link(),
            ];

            $this->oForm = $this->oModel->formForThisModelInstance($aOptions);
        }
    }

    # Action edit

    /**
     * @return bool
     */
    protected function actionEditRequested(): bool
    {
        $aParams = $this->getParams();
        return array_key_exists('edit', $aParams) && (int)$aParams['edit'] > 0;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function actionEdit(): void
    {
        $aParams = $this->getParams();
        $this->oModel = new User((int)$aParams['edit']);
        $this->initForm();

        if ($this->oForm->submitted()) {
            $this->oForm->execute();
        }
    }

    # Action delete

    /**
     * @return bool
     */
    protected function actionDeleteRequested(): bool
    {
        $aParams = $this->getParams();
        return array_key_exists('delete', $aParams) && (int)$aParams['delete'] > 0;
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

        return array_key_exists('confirm', $aParams) && (int)$aParams['confirm'] === 1;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function actionDelete(): void
    {
        $aParams = $this->getParams();
        $iUser = (int)$aParams['delete'];

        if ($this->actionDeleteConfirmed() !== false) {
            # catching Exception thrown when model already destroyed
            # happens when user refreshes delete-page, for instance

            try {
                $oUser = new User($iUser);
                $oUser->destroy();
            } catch (Exception $e) {
                # user is already deleted; silently discarding
                error_log((string) $e);
            }

            # Redirecting to admin home
            Tools::redirectUsingMeta(self::link());
        } else {
            $oUser = new User($iUser);
            $this->aMessages[] = Message::warningConfirmMessage(
                "Check twice, you're about to delete " . $oUser->label() . '</strong> from the database !',
                "<p>You are about to delete a user and all it's calendars / contacts. This operation cannot be undone.</p><p>So, now that you know all that, what shall we do ?</p>",
                $this->linkDeleteConfirm($oUser),
                "Delete <strong><i class='" . $oUser->icon() . " icon-white'></i> " . $oUser->label() . '</strong>',
                $this->link()
            );
        }
    }

    # Action new

    /**
     * @return bool
     */
    protected function actionNewRequested(): bool
    {
        $aParams = $this->getParams();
        return array_key_exists('new', $aParams) && (int)$aParams['new'] === 1;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function actionNew(): void
    {
        $this->oModel = new User();
        $this->initForm();

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

    /**
     * @return string
     */
    public function linkNew(): string
    {
        return self::buildRoute([
                'new' => 1,
            ]) . '#form';
    }

    /**
     * @param Model $user
     *
     * @return string
     * @throws Exception
     */
    public static function linkEdit(Model $user): string
    {
        return self::buildRoute([
            'edit' => $user->get('id'),
        ]) . '#form';
    }

    /**
     * @param User $user
     *
     * @return string
     * @throws Exception
     */
    public static function linkDelete(User $user): string
    {
        return self::buildRoute([
                'delete' => $user->get('id'),
            ]) . '#message';
    }

    /**
     * @param User $user
     *
     * @return string
     * @throws Exception
     */
    public static function linkDeleteConfirm(User $user): string
    {
        return self::buildRoute([
                'delete'  => $user->get('id'),
                'confirm' => 1,
            ]) . '#message';
    }

    /**
     * @param User $user
     *
     * @return string
     * @throws Exception
     */
    public static function linkCalendars(User $user): string
    {
        return Calendars::buildRoute([
            'user' => $user->get('id'),
        ]);
    }

    /**
     * @param User $user
     *
     * @return string
     * @throws Exception
     */
    public static function linkAddressBooks(User $user): string
    {
        return AddressBooks::buildRoute([
            'user' => $user->get('id'),
        ]);
    }
}
