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
use Flake\Util\Tools;
use ReflectionException;
use RuntimeException;

/**
 *
 */
class CollectionTyped extends Collection
{
    protected string $sTypeClassOrProtocol;

    /**
     * @param $sTypeClassOrProtocol
     */
    public function __construct($sTypeClassOrProtocol)
    {
        $this->sTypeClassOrProtocol = $sTypeClassOrProtocol;
        $this->setMetaType($this->sTypeClassOrProtocol);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function push($mMixed): void
    {
        if (!Tools::is_a($mMixed, $this->sTypeClassOrProtocol)) {
            throw new RuntimeException(
                "\Flake\Core\CollectionTyped<" . $this->sTypeClassOrProtocol . '>: Given object is not correctly typed.'
            );
        }

        parent::push($mMixed);
    }

    /**
     * Create a new collection like this one
     *
     * @return CollectionTyped
     */
    public function newCollectionLikeThisOne(): CollectionTyped
    {
        return new CollectionTyped($this->sTypeClassOrProtocol);
    }
}
