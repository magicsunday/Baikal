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
use ReturnTypeWillChange;

/**
 *
 */
abstract class ChainLink implements Chainable
{
    protected ?Chain $__container = null;
    protected $__key = null;

    /**
     * @param Chain $container
     * @param       $key
     *
     * @return void
     */
    public function chain(Chain $container, $key): void
    {
        $this->__container = $container;
        $this->__key = $key;
    }

    /**
     * @param $offset
     * @param $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if ($this->__container === null) {
            return;
        }

        $this->__container->offsetSet($offset, $value);
    }

    /**
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        if ($this->__container === null) {
            return false;
        }

        return $this->__container->offsetExists($offset);
    }

    /**
     * @throws Exception
     */
    public function offsetUnset($offset): void
    {
        if ($this->__container === null) {
            return;
        }

        $this->__container->offsetUnset($offset);
    }

    /**
     * @param $offset
     *
     * @return mixed|null
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($this->__container === null) {
            return null;
        }

        return $this->__container->offsetGet($offset);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->__container->rewind();
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->__container->current();
    }

    /**
     * @return bool|float|int|string|null
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->__container->key();
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->__container->next();
    }

    /**
     * @return void
     */
    public function prev(): void
    {
        $this->__container->prev();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->__container->valid();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->__container->count();
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return $this->__container->first();
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return $this->__container->last();
    }
}
