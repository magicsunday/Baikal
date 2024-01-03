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

use Baikal\Model\AddressBook\Contact;
use Flake\Core\Model\Db;
use Flake\Core\Requester\Sql;
use Formal\Element\Text;
use Formal\Form\Morphology;
use ReflectionException;

/**
 *
 */
class AddressBook extends Db
{
    public const DATATABLE = 'addressbooks';
    public const PRIMARYKEY = 'id';
    public const LABELFIELD = 'displayname';

    protected array $aData = [
        'principaluri' => '',
        'displayname'  => '',
        'uri'          => '',
        'description'  => '',
    ];

    /**
     * @return string|null
     */
    public function humanName(): ?string
    {
        return 'Address Book';
    }

    /**
     * @return string
     */
    public static function mediumicon(): string
    {
        return 'glyph-adress-book';
    }

    /**
     * @return string
     */
    public static function bigicon(): string
    {
        return 'glyph2x-adress-book';
    }

    /**
     * @return Sql<AddressBook>
     */
    public function getContactsBaseRequester(): Sql
    {
        $oBaseRequester = Contact::getBaseRequester();
        $oBaseRequester->addClauseEquals(
            'addressbookid',
            (string)$this->get('id')
        );

        return $oBaseRequester;
    }

    /**
     * @return Morphology
     */
    public function formMorphologyForThisModelInstance(): Morphology
    {
        $oMorpho = new Morphology();

        $oMorpho->add(new Text([
            'prop'       => 'uri',
            'label'      => 'Address Book token ID',
            'validation' => 'required,tokenid',
            'popover'    => [
                'title'   => 'Address Book token ID',
                'content' => 'The unique identifier for this address book.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'       => 'displayname',
            'label'      => 'Display name',
            'validation' => 'required',
            'popover'    => [
                'title'   => 'Display name',
                'content' => 'This is the name that will be displayed in your CardDAV client.',
            ],
        ]));

        $oMorpho->add(new Text([
            'prop'  => 'description',
            'label' => 'Description',
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
     * @return void
     * @throws ReflectionException
     */
    public function destroy(): void
    {
        /** @var Contact[] $oContacts */
        $oContacts = $this->getContactsBaseRequester()->execute();

        foreach ($oContacts as $contact) {
            $contact->destroy();
        }

        parent::destroy();
    }
}
