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

use PDO;
use PDOStatement;

class Statement
{
    protected bool|PDOStatement $stmt = false;

    /**
     * @param bool|PDOStatement $stmt
     */
    public function __construct(bool|PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * @return false|mixed The return value of this function on success depends on the fetch type.
     *                     In all cases, FALSE is returned on failure.
     */
    public function fetch(): mixed
    {
        if ($this->stmt !== false) {
            return $this->stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST);
        }

        return false;
    }
}
