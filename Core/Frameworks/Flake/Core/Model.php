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
use Formal\Form;
use Formal\Form\Morphology;
use RuntimeException;

use function array_key_exists;
use function get_class;

/**
 *
 */
abstract class Model extends FLObject
{
    /**
     * @var array<bool|int|string|null>
     */
    protected array $aData = [];

    /**
     * @return array<bool|int|string|null>
     */
    protected function getData(): array
    {
        reset($this->aData);

        return $this->aData;
    }

    /**
     * @param string $sPropName
     *
     * @return bool|int|string|null
     *
     * @throws Exception
     */
    public function __get(string $sPropName): bool|int|string|null
    {
        return $this->get($sPropName);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        if (array_key_exists($name, $this->aData)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $sPropName
     *
     * @return bool|int|string|null
     */
    public function get(string $sPropName): bool|int|string|null
    {
        if (array_key_exists($sPropName, $this->aData)) {
            return $this->aData[$sPropName];
        }

        throw new RuntimeException(
            "\Flake\Core\Model->get(): property " . htmlspecialchars($sPropName) . ' does not exist on ' . get_class(
                $this
            )
        );
    }

    /**
     * @param string               $sPropName
     * @param bool|int|string|null $sPropValue
     *
     * @return Model
     */
    public function set(string $sPropName, bool|int|string|null $sPropValue): Model
    {
        if (array_key_exists($sPropName, $this->aData)) {
            $this->aData[$sPropName] = $sPropValue;

            return $this;
        }

        throw new RuntimeException(
            "\Flake\Core\Model->set(): property " . htmlspecialchars($sPropName) . ' does not exist on ' . get_class(
                $this
            )
        );
    }

    /**
     * @throws Exception
     */
    public function label(): string
    {
        return (string)$this->get($this::LABELFIELD);
    }

    /**
     * @return string
     */
    public static function icon(): string
    {
        return 'icon-book';
    }

    /**
     * @return string
     */
    public static function mediumicon(): string
    {
        return 'glyph-book';
    }

    /**
     * @return string
     */
    public static function bigicon(): string
    {
        return 'glyph2x-book';
    }

    /**
     * @return string|null
     */
    public static function humanName(): ?string
    {
        $aRes = explode("\\", static::class);

        return array_pop($aRes);
    }

    /**
     * @return bool
     */
    public function floating(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function formForThisModelInstance($options = []): Form
    {
        $sClass = get_class($this);
        $oForm = new Form($sClass, $options);
        $oForm->setModelInstance($this);

        return $oForm;
    }

    /**
     * @throws Exception
     */
    public function formMorphologyForThisModelInstance(): Morphology
    {
        throw new RuntimeException(get_class($this) . ': No form morphology provided for Model.');
    }

    /**
     * @return void
     */
    abstract public function persist(): void;

    /**
     * @return void
     */
    abstract public function destroy(): void;
}
