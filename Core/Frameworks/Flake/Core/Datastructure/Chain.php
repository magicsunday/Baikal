<?php

declare(strict_types=1);

#################################################################
#  Copyright notice
#
#  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://flake.codr.fr
#
#  This script is part of the Flake project. The Flake
#  project is free software; you can redistribute it
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

namespace Flake\Core\Datastructure;

use Exception;
use RuntimeException;
use SplDoublyLinkedList;

/**
 * @extends SplDoublyLinkedList<Chainable>
 */
class Chain extends SplDoublyLinkedList
{
    /**
     * @param $value
     *
     * @return void
     */
    public function push($value): void
    {
        $value->chain($this, $this->count());
        parent::push($value);
    }

    /**
     * @throws Exception
     */
    public function offsetUnset($offset): void
    {
        throw new RuntimeException('Cannot delete Chainable in Chain');
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return $this->bottom();
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return $this->top();
    }

    /**
     * @return void
     */
    public function reset(): void
    {
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();
        var_dump($this);
        $sDump = ob_get_clean();

        return '<pre>' . htmlspecialchars($sDump) . '</pre>';
    }
}
