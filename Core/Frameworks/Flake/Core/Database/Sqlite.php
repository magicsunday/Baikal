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
//  http://flake.codr.fr
//
//  This script is part of the Flake project. The Flake
//  project is free software; you can redistribute it
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

namespace Flake\Core\Database;

use Flake\Core\Database;
use PDO;

class Sqlite extends Database
{
    protected string $sDbPath = '';

    /**
     * @param string $sDbPath
     */
    public function __construct(string $sDbPath)
    {
        $this->sDbPath = $sDbPath;
        $this->oDb     = new PDO('sqlite:' . $this->sDbPath);
        $this->oDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Taken from http://dev.kohanaframework.org/issues/2985

    /**
     * @return array
     */
    public function tables(): array
    {
        $aTables = [];

        // Find all user level table names
        $oStmt = $this->query(
            'SELECT name FROM sqlite_master WHERE type=\'table\' AND name NOT LIKE \'sqlite_%\' ORDER BY name'
        );

        while (($aRs = $oStmt->fetch()) !== false) {
            // Get the table name from the results
            $aTables[] = array_shift($aRs);
        }

        return $aTables;
    }
}
