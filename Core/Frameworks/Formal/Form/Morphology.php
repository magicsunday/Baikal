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

namespace Formal\Form;

use Exception;
use Formal\Element;
use Formal\ElementCollection;
use ReflectionException;
use RuntimeException;

/**
 *
 */
class Morphology
{
    /**
     * @var ElementCollection
     */
    protected ElementCollection $oElements;

    public function __construct()
    {
        $this->oElements = new ElementCollection();
    }

    /**
     * @param Element $oElement
     */
    public function add(Element $oElement): void
    {
        $this->oElements->push($oElement);
    }

    /**
     * @param string $sPropName
     *
     * @return int|string|bool
     */
    protected function keyForPropName(string $sPropName): int|string|bool
    {
        $aKeys = $this->oElements->keys();

        foreach ($aKeys as $sKey) {
            /** @var Element $oElement */
            $oElement = $this->oElements->getForKey($sKey);

            if ($oElement->option('prop') === $sPropName) {
                return $sKey;
            }
        }

        return false;
    }

    /**
     * @param string $sPropName
     *
     * @return Element
     */
    public function element(string $sPropName): Element
    {
        if (($sKey = $this->keyForPropName($sPropName)) === false) {
            throw new RuntimeException(
                "\Formal\Form\Morphology->element(): Element prop='" . $sPropName . "' not found"
            );
        }

        return $this->oElements->getForKey($sKey);
    }

    /**
     * @param string $sPropName
     *
     * @return void
     */
    public function remove(string $sPropName): void
    {
        if (($sKey = $this->keyForPropName($sPropName)) === false) {
            throw new RuntimeException(
                "\Formal\Form\Morphology->element(): Element prop='" . $sPropName . "' not found"
            );
        }

        $this->oElements->remove($sKey);
    }

    /**
     * @return ElementCollection
     */
    public function elements(): ElementCollection
    {
        return $this->oElements;
    }
}
