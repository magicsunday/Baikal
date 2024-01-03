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

namespace Baikal\Model\Config;

use Baikal\Model\Config;
use Formal\Element\Checkbox;
use Formal\Element\Password;
use Formal\Element\Text;
use Formal\Form\Morphology;

/**
 *
 */
class Database extends Config
{
    # Default values
    protected array $aData = [
        'sqlite_file'    => PROJECT_PATH_SPECIFIC . 'db/db.sqlite',
        'mysql'          => false,
        'mysql_host'     => '',
        'mysql_dbname'   => '',
        'mysql_username' => '',
        'mysql_password' => '',
        'encryption_key' => '',
    ];

    public function __construct()
    {
        parent::__construct('database');
    }

    /**
     * @return Morphology
     */
    public function formMorphologyForThisModelInstance(): Morphology
    {
        $oMorpho = new Morphology();

        $oMorpho->add(new Text([
            'prop'       => 'sqlite_file',
            'label'      => 'SQLite file path',
            'validation' => 'required',
            'inputclass' => 'input-xxlarge',
            'help'       => 'The absolute server path to the SQLite file',
        ]));

        $oMorpho->add(new Checkbox([
            'prop'            => 'mysql',
            'label'           => 'Use MySQL',
            'help'            => 'If checked, Baïkal will use MySQL instead of SQLite.',
            'refreshonchange' => true,
        ]));

        $oMorpho->add(new Text([
            'prop'  => 'mysql_host',
            'label' => 'MySQL host',
            'help'  => "Host ip or name, including ':portnumber' if port is not the default one (3306)",
        ]));

        $oMorpho->add(new Text([
            'prop'  => 'mysql_dbname',
            'label' => 'MySQL database name',
        ]));

        $oMorpho->add(new Text([
            'prop'  => 'mysql_username',
            'label' => 'MySQL username',
        ]));

        $oMorpho->add(new Password([
            'prop'  => 'mysql_password',
            'label' => 'MySQL password',
        ]));

        return $oMorpho;
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return 'Baïkal Database Settings';
    }
}
