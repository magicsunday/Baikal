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

use Flake\Core\Render\Container;

use function array_key_exists;

abstract class Route
{
    // should be abstract, but is not, due to PHP strict standard
    /**
     * @param Container $oRenderContainer
     *
     * @return void
     */
    public static function layout(Container $oRenderContainer): void
    {
    }

    /**
     * @return array
     */
    public static function parametersMap(): array
    {
        return [];
    }

    // converts raw url params "a/b/c/d"=[a, b, c, d] in route params [a=>b, c=>d]

    /**
     * @return array
     */
    public static function getParams(): array
    {
        $aRouteParams = [];

        $aParametersMap = static::parametersMap();    // static to use method as defined in derived class
        $aURLParams     = $GLOBALS['ROUTER']::getURLParams();

        foreach ($aParametersMap as $sParam => $aMap) {
            $sURLToken = $sParam;

            if (array_key_exists('urltoken', $aMap)) {
                $sURLToken = $aMap['urltoken'];
            }

            if (($iPos = array_search($sURLToken, $aURLParams, true)) !== false) {
                $aRouteParams[$sParam] = $aURLParams[$iPos + 1];    // the value corresponding to this param is the next one in the URL
            }
        }

        return $aRouteParams;
    }
}
