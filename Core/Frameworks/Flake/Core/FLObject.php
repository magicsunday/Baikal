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

use Flake\Util\Tools;
use ReflectionException;

/**
 *
 */
class FLObject
{
    /**
     * @return string
     */
    public function __toString(): string
    {
        ob_start();
        var_dump($this);
        $sDump = ob_get_clean();

        return '<pre>' . htmlspecialchars($sDump) . '</pre>';
    }

    /**
     * @throws ReflectionException
     */
    public function isA($sClassOrProtocolName): bool
    {
        return Tools::is_a($this, $sClassOrProtocolName);
    }
}
