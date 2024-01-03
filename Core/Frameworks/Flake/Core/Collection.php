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

namespace Flake\Core;

use Exception;
use Iterator;
use ReturnTypeWillChange;
use RuntimeException;

use function array_key_exists;
use function count;
use function get_class;
use function in_array;
use function strlen;

/**
 *
 */
class Collection extends FLObject implements Iterator
{
    protected array $aCollection = [];
    protected array $aMeta = [];

    /**
     * @return false|mixed
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return current($this->aCollection);
    }

    /**
     * @return int|string|null
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return key($this->aCollection);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        next($this->aCollection);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->reset();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        $key = key($this->aCollection);

        return ($key !== null && $key !== false);
    }

    /**
     * @throws Exception
     */
    public function getForKey($sKey)
    {
        $aKeys = $this->keys();
        if (!in_array($sKey, $aKeys, true)) {
            throw new RuntimeException(
                "\Flake\Core\Collection->getForKey(): key '" . $sKey . "' not found in Collection"
            );
        }

        return $this->aCollection[$sKey];
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        reset($this->aCollection);
    }

    /**
     * @return false|mixed
     */
    public function prev()
    {
        return prev($this->aCollection);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->aCollection);
    }

    /**
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->aCollection);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return bool
     */
    public function isAtFirst(): bool
    {
        $keys = $this->keys();
        return $this->key() === array_shift($keys);
    }

    /**
     * @return bool
     */
    public function isAtLast(): bool
    {
        $keys = $this->keys();
        return $this->key() === array_pop($keys);
    }

    /**
     * @param $mMixed
     *
     * @return void
     */
    public function push($mMixed): void
    {
        $this->aCollection[] = $mMixed;
    }

    /**
     * @return void
     */
    public function flush(): void
    {
        unset($this->aCollection);
        $this->aCollection = [];
    }

    /**
     * @return mixed|null
     */
    public function first()
    {
        if (!$this->isEmpty()) {
            $aKeys = $this->keys();

            return $this->aCollection[array_shift($aKeys)];
        }

        # two lines instead of one

        return null;    # as PHP needs a variable to return by ref
    }

    /**
     * @return mixed|null
     */
    public function last()
    {
        if (!$this->isEmpty()) {
            $aKeys = $this->keys();

            return $this->aCollection[array_pop($aKeys)];
        }

        return null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->aCollection;
    }

    /**
     * @param $aData
     *
     * @return Collection
     */
    public static function fromArray($aData): Collection
    {
        $oColl = new self();
        reset($aData);
        foreach ($aData as $mData) {
            $oColl->push($mData);
        }

        return $oColl;
    }

    # Create a new collection like this one
    # This abstraction is useful because of CollectionTyped
    /**
     * @return Collection
     */
    protected function newCollectionLikeThisOne(): Collection
    {
        # two lines instead of one

        return new self();                            # as PHP needs a variable to return by ref
    }

    /**
     * @param $sFunc
     *
     * @return Collection
     */
    public function map($sFunc): Collection
    {
        $aData = $this->toArray();
        return self::fromArray(array_map($sFunc, $aData));
    }

    /**
     * @param       $sFunc
     * @param array $aParams
     *
     * @return Collection
     */
    public function walk($sFunc, array $aParams = []): Collection
    {
        $aData = $this->toArray();
        return self::fromArray(array_walk($aData, $sFunc, $aParams));
    }

    /**
     * @throws Exception
     */
    public function remove($sKey): void
    {
        $aKeys = $this->keys();
        if (!in_array($sKey, $aKeys, true)) {
            throw new RuntimeException("\Flake\Core\Collection->remove(): key '" . $sKey . "' not found in Collection");
        }

        unset($this->aCollection[$sKey]);
        $this->aCollection = array_values($this->aCollection);
    }

    /**
     * @throws Exception
     */
    public function __call($sName, $aArguments)
    {
        if (strlen($sName) > 7 &&
            $sName[0] === 's' &&
            $sName[1] === 'e' &&
            $sName[2] === 't' &&
            $sName[3] === 'M' &&
            $sName[4] === 'e' &&
            $sName[5] === 't' &&
            $sName[6] === 'a') {
            $sKey = strtolower(substr($sName, 7, 1)) . substr($sName, 8);
            $mValue = &$aArguments[0];

            if ($mValue === null) {
                if (array_key_exists($sKey, $this->aMeta)) {
                    unset($this->aMeta[$sKey]);
                }
            } else {
                $this->aMeta[$sKey] = &$mValue;
            }

            return null;    # To avoid 'Notice: Only variable references should be returned by reference'
        }

        if (
            strlen($sName) > 7 &&
            $sName[0] === 'g' &&
            $sName[1] === 'e' &&
            $sName[2] === 't' &&
            $sName[3] === 'M' &&
            $sName[4] === 'e' &&
            $sName[5] === 't' &&
            $sName[6] === 'a'
        ) {
            $sKey = strtolower(substr($sName, 7, 1)) . substr($sName, 8);
            return $this->aMeta[$sKey] ?? null;
        } else {
            throw new RuntimeException('Method ' . $sName . '() not found on ' . get_class($this));
        }
    }
}
