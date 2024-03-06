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

namespace Flake\Core;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function array_key_exists;

abstract class View
{
    protected array $aData;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->aData = [];
    }

    /**
     * @param string $sName
     * @param mixed  $mData
     *
     * @return void
     */
    public function setData(string $sName, mixed $mData): void
    {
        $this->aData[$sName] = $mData;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->aData;
    }

    /**
     * @param string $sWhat
     *
     * @return false|mixed
     */
    public function get(string $sWhat): mixed
    {
        if (array_key_exists($sWhat, $this->aData)) {
            return $this->aData[$sWhat];
        }

        return false;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function render(): string
    {
        return (new Template($this->templatesPath()))->parse($this->getData());
    }

    /**
     * @return string
     */
    abstract public function templatesPath(): string;
}
