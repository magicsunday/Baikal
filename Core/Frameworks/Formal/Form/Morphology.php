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
use Flake\Core\CollectionTyped;
use Formal\Element;
use ReflectionException;
use RuntimeException;

/**
 *
 */
class Morphology
{
    protected ?CollectionTyped $oElements = null;

    public function __construct()
    {
        $this->oElements = new CollectionTyped(Element::class);
    }

    /**
     * @throws ReflectionException
     */
    public function add(Element $oElement): void
    {
        $this->oElements->push($oElement);
    }

    /**
     * @throws Exception
     */
    protected function keyForPropName($sPropName)
    {
        $aKeys = $this->oElements->keys();
        foreach ($aKeys as $sKey) {
            $oElement = $this->oElements->getForKey($sKey);

            if ($oElement->option('prop') === $sPropName) {
                return $sKey;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function element($sPropName)
    {
        if (($sKey = $this->keyForPropName($sPropName)) === false) {
            throw new RuntimeException(
                "\Formal\Form\Morphology->element(): Element prop='" . $sPropName . "' not found"
            );
        }

        return $this->oElements->getForKey($sKey);
    }

    /**
     * @throws Exception
     */
    public function remove($sPropName): void
    {
        if (($sKey = $this->keyForPropName($sPropName)) === false) {
            throw new RuntimeException(
                "\Formal\Form\Morphology->element(): Element prop='" . $sPropName . "' not found"
            );
        }

        $this->oElements->remove($sKey);
    }

    /**
     * @return CollectionTyped|null
     */
    public function elements(): ?CollectionTyped
    {
        return $this->oElements;
    }
}
