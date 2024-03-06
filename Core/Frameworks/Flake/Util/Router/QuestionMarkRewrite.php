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

namespace Flake\Util\Router;

use Flake\Util\Router;
use Flake\Util\Tools;
use RuntimeException;

use function array_key_exists;
use function array_slice;
use function strlen;

class QuestionMarkRewrite extends Router
{
    /**
     * @return string
     */
    public static function getCurrentRoute(): string
    {
        $aMatches     = [];
        $sRouteTokens = implode('/', self::getRouteTokens());

        $aRoutes = self::getRoutes();
        foreach (array_keys($aRoutes) as $sDefinedRoute) {
            if (str_starts_with($sRouteTokens, $sDefinedRoute)) {
                // found a match
                $iSlashCount = substr_count($sDefinedRoute, '/');
                if (!array_key_exists($iSlashCount, $aMatches)) {
                    $aMatches[$iSlashCount] = [];
                }

                $aMatches[$iSlashCount][] = $sDefinedRoute;
            }
        }

        if ($aMatches === []) {
            return 'default';
        }

        $aBestMatches = array_pop($aMatches);    // obtains the deepest matching route (higher number of slashes)

        return array_shift($aBestMatches);        // first route amongst best matches
    }

    /**
     * @param string $sRoute
     * @param array  $aParams
     *
     * @return string
     */
    public static function buildRoute(string $sRoute, array $aParams = []/* [, $sParam, $sParam2, ...] */): string
    {
        //		$aParams = func_get_args();
        //		array_shift($aParams);	# Stripping $sRoute

        //		$sParams = implode("/", $aParams);

        $aParamsSegments = [];
        reset($aParams);
        foreach ($aParams as $sParamName => $sParamValue) {
            $aParamsSegments[] = rawurlencode($sParamName) . '/' . rawurlencode((string) $sParamValue);
        }

        $sParams = implode('/', $aParamsSegments);

        if (trim($sParams) !== '') {
            $sParams .= '/';
        }

        $sUrl = $sRoute === 'default' && $aParams === [] ? '/' : '/' . $sRoute . '/' . $sParams;

        $sUriPath = self::getUriPath();
        if ($sUriPath === '' || $sUriPath === '/') {
            if ($sUrl !== '/') {
                $sUrl = '?' . $sUrl;
            }
        } elseif ($sUrl !== '/') {
            $sUrl = '/' . self::getUriPath() . '?' . $sUrl;
        } else {
            $sUrl = '/' . self::getUriPath();
        }

        return $sUrl;
    }

    /**
     * @return array
     */
    protected static function getUrlTokens(): array
    {
        $sUrl      = Tools::stripBeginSlash(Tools::getCurrentUrl());
        $aUrlParts = parse_url($sUrl);
        if (array_key_exists('query', $aUrlParts)) {
            return explode('/', '?' . $aUrlParts['query']);
        }

        return [];
    }

    /**
     * @return array
     */
    protected static function getRouteTokens(): array
    {
        $aUrlTokens = self::getUrlTokens();

        if ($aUrlTokens !== []) {
            return array_slice($aUrlTokens, 1);
        }

        return [];
    }

    /**
     * @return array
     */
    public static function getURLParams(): array
    {
        $aTokens = self::getRouteTokens();

        // stripping route
        if ($aTokens !== []) {
            $sRouteUrl     = implode('/', $aTokens);
            $sCurrentRoute = $GLOBALS['ROUTER']::getCurrentRoute();

            if (!str_contains($sRouteUrl, (string) $sCurrentRoute)) {
                throw new RuntimeException(
                    QuestionMarkRewrite::class . '::getURLParams(): unrecognized route.'
                );
            }

            $sParams = Tools::trimSlashes(substr($sRouteUrl, strlen((string) $sCurrentRoute)));

            $aParams = [];
            if ($sParams !== '') {
                $aParams = explode('/', $sParams);
            }

            foreach ($aParams as $sParam => $sValue) {
                $aParams[$sParam] = rawurldecode($sValue);
            }

            return $aParams;
        }

        return [];
    }
}
