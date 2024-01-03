<?php

declare(strict_types=1);

#################################################################
#  Copyright notice
#
#  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://formal.codr.fr
#
#  This script is part of the Formal project. The Formal
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

namespace Formal;

use Exception;
use Flake\Util\Tools;
use RuntimeException;

use function array_key_exists;
use function get_class;
use function is_array;

/**
 *
 */
abstract class Element
{
    /**
     * @var array<string, string|bool>
     */
    protected array $aOptions = [
        'class'           => '',
        'inputclass'      => 'input-xlarge',
        'readonly'        => false,
        'validation'      => '',
        'error'           => false,
        'placeholder'     => '',
        'help'            => '',
        'popover'         => '',
        'refreshonchange' => false,
    ];

    protected string|bool $sValue = '';

    /**
     * @param $aOptions
     */
    public function __construct(array $aOptions)
    {
        $this->aOptions = array_merge($this->aOptions, $aOptions);
    }

    /**
     * @throws Exception
     */
    public function option(string $sName)
    {
        if (array_key_exists($sName, $this->aOptions)) {
            return $this->aOptions[$sName];
        }

        throw new RuntimeException("\Formal\Element->option(): Option '" . htmlspecialchars($sName) . "' not found.");
    }

    /**
     * @throws Exception
     */
    public function optionArray(string $sOptionName): array
    {
        $sOption = trim($this->option($sOptionName));
        if ($sOption !== '') {
            $aOptions = explode(',', $sOption);
        } else {
            $aOptions = [];
        }

        return $aOptions;
    }

    /**
     * @param string      $sOptionName
     * @param string|bool $sOptionValue
     *
     * @return void
     */
    public function setOption(string $sOptionName, string|bool $sOptionValue): void
    {
        $this->aOptions[$sOptionName] = $sOptionValue;
    }

    /**
     * @return string|bool
     */
    public function value(): string|bool
    {
        return $this->sValue;
    }

    /**
     * @param string|bool $sValue
     *
     * @return void
     */
    public function setValue(string|bool $sValue): void
    {
        $this->sValue = $sValue;
    }

    /**
     * @throws Exception
     */
    public function __toString()
    {
        return get_class($this) . '<' . $this->option('label') . '>';
    }

    /**
     * @throws Exception
     */
    public function renderWitness(): string
    {
        return '<input type="hidden" name="witness[' . $this->option('prop') . ']" value="1" />';
    }

    /**
     * @throws Exception
     */
    public function posted(): bool
    {
        $aPost = Tools::POST('witness');
        if (is_array($aPost)) {
            $sProp = $this->option('prop');

            return (array_key_exists($sProp, $aPost)) && ((int)$aPost[$sProp] === 1);
        }

        return false;
    }

    /**
     * @return string
     */
    abstract public function render(): string;
}
