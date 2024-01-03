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

use ArrayAccess;
use Countable;
use Iterator;

/**
 *
 */
interface Chainable extends ArrayAccess, Iterator, Countable
{
    #	public function next();	# This is already specified by interface Iterator
    /**
     * @return mixed
     */
    public function prev(): mixed;

    /**
     * @return mixed
     */
    public function first(): mixed;

    /**
     * @return mixed
     */
    public function last(): mixed;

    /**
     * @param Chain $chain
     * @param       $key
     *
     * @return void
     */
    public function chain(Chain $chain, $key): void;
}
