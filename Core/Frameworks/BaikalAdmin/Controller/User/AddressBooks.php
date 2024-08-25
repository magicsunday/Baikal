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

use Baikal\Model\AddressBook;
use Baikal\Model\User;
use BaikalAdmin\Controller\Users;
use Exception;
use Flake\Core\Collection;
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

class AddressBooks extends Controller
{
    protected array $aMessages = [];

    protected AddressBook $oModel;
    // \Baikal\Model\Contact
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
            throw new RuntimeException("BaikalAdmin\Controller\User\Contacts::render(): User get-parameter not found.");
        }

        $this->oUser = new User($iUser);

        if ($this->actionNewRequested()) {
            $this->actionNew();
        }

        if ($this->actionEditRequested()) {
            $this->actionEdit();
        }

        if ($this->actionDeleteRequested()) {
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
        $oView = new \BaikalAdmin\View\User\AddressBooks();

        // User
        $oView->setData('user', $this->oUser);

        // Render list of address books
        $aAddressBooks = [];

        /** @var Collection<AddressBook> $oAddressBooks */
        $oAddressBooks = $this->oUser->getAddressBooksBaseRequester()->execute();

        foreach ($oAddressBooks as $addressbook) {
            $aAddressBooks[] = [
                'linkedit'    => $this->linkEdit($addressbook),
                'linkdelete'  => $this->linkDelete($addressbook),
                'davuri'      => $this->getDavUri($addressbook),
                'icon'        => $addressbook->icon(),
                'label'       => $addressbook->label(),
                'contacts'    => $addressbook->getContactsBaseRequester()->count(),
                'description' => $addressbook->get('description'),
            ];
        }

        $oView->setData('addressbooks', $aAddressBooks);

        // Messages
        $sMessages = implode("\n", $this->aMessages);
        $oView->setData('messages', $sMessages);

        $sForm = $this->actionNewRequested() || $this->actionEditRequested() ? $this->oForm->render() : '';

        $oView->setData('form', $sForm);
        $oView->setData('titleicon', AddressBook::bigicon());
        $oView->setData('modelicon', $this->oUser->mediumIcon());
        $oView->setData('modellabel', $this->oUser->label());
        $oView->setData('linkback', Users::link());
        $oView->setData('linknew', $this->linkNew());
        $oView->setData('addressbookicon', AddressBook::icon());

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
        if (($iUser = (int) $aParams['user']) === 0) {
            return false;
        }

        return $iUser;
    }

    // Action new

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
        $this->oModel = new AddressBook();
        $this->oModel->set(
            'principaluri',
            $this->oUser->get('uri')
        );

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

    // Action edit

    /**
     * @param Model $oModel
     *
     * @return string
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

        return array_key_exists('edit', $aParams) && (int) $aParams['edit'] > 0;
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
        $this->oModel = new AddressBook((int) $aParams['edit']);

        // Initialize corresponding form
        $this->initForm();

        // Process form
        if ($this->oForm->submitted()) {
            $this->oForm->execute();
        }
    }

    // Action delete + confirm

    /**
     * @param AddressBook $oModel
     *
     * @return string
     */
    public function linkDelete(AddressBook $oModel): string
    {
        return self::buildRoute([
            'user'   => $this->currentUserId(),
            'delete' => $oModel->get('id'),
        ]) . '#message';
    }

    /**
     * @param AddressBook $oModel
     *
     * @return string
     */
    public function linkDeleteConfirm(AddressBook $oModel): string
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

        return array_key_exists('delete', $aParams) && (int) $aParams['delete'] > 0;
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

        return array_key_exists('confirm', $aParams) && (((int) $aParams['confirm']) > 0);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function actionDelete(): void
    {
        $aParams = $this->getParams();
        $iModel  = (int) $aParams['delete'];

        if ($this->actionDeleteConfirmed()) {
            // catching Exception thrown when model already destroyed
            // happens when user refreshes page on delete-URL, for instance

            try {
                $oModel = new AddressBook($iModel);
                $oModel->destroy();
            } catch (Exception) {
                // already deleted; silently discarding
            }

            // Redirecting to admin home
            Tools::redirectUsingMeta($this->linkHome());
        } else {
            $oModel            = new AddressBook($iModel);
            $this->aMessages[] = Message::warningConfirmMessage(
                "Check twice, you're about to delete " . $oModel->label() . '</strong> from the database !',
                "<p>You are about to delete a contact book and all it's visiting cards. This operation cannot be undone.</p><p>So, now that you know all that, what shall we do ?</p>",
                $this->linkDeleteConfirm($oModel),
                "Delete <strong><i class='" . $oModel->icon() . " icon-white'></i> " . $oModel->label() . '</strong>',
                $this->linkHome()
            );
        }
    }

    // Link to home

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
     * Generate a link to the CalDAV/CardDAV URI of the addressbook.
     *
     * @param AddressBook $addressbook
     *
     * @return string AddressBook DAV URI
     *
     * @throws Exception
     */
    protected function getDavUri(AddressBook $addressbook): string
    {
        return PROJECT_URI . 'dav.php/addressbooks/' . $this->oUser->get('username') . '/' . $addressbook->get(
            'uri'
        ) . '/';
    }
}
