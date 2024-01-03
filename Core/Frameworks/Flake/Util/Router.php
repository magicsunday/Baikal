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

namespace Flake\Util;

use Flake\Core\Render\Container;
use Flake\Core\Route;
use RuntimeException;

use function array_key_exists;
use function call_user_func_array;
use function func_get_args;

/**
 *
 */
abstract class Router
{
    /**
     * @var string
     */
    public static string $sURIPath = '';

    /**
     * Private constructor for static class.
     */
    private function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getRoutes(): array
    {
        reset($GLOBALS['ROUTES']);

        return $GLOBALS['ROUTES'];
    }

    /**
     * @param string $sRoute
     *
     * @return string
     */
    public static function getControllerForRoute(string $sRoute): string
    {
        return str_replace("\\Route", "\\Controller", self::getRouteClassForRoute($sRoute));
    }

    /**
     * @param string $sRoute
     *
     * @return string
     */
    public static function getRouteClassForRoute(string $sRoute): string
    {
        $aRoutes = $GLOBALS['ROUTER']::getRoutes();

        return $aRoutes[$sRoute];
    }

    /**
     * @param string $sController
     *
     * @return false|int|string
     */
    public static function getRouteForController(string $sController): false|int|string
    {
        if ($sController[0] !== "\\") {
            $sController = "\\" . $sController;
        }

        $aRoutes = $GLOBALS['ROUTER']::getRoutes();

        foreach ($aRoutes as $sKey => $sRoute) {
            if (str_replace("\\Route", "\\Controller", $sRoute) === $sController) {
                return $sKey;
            }
        }

        return false;
    }

    /**
     * @param Container $oRenderContainer
     *
     * @return void
     */
    public static function route(Container $oRenderContainer): void
    {
        $sRouteClass = $GLOBALS['ROUTER']::getRouteClassForRoute(
            $GLOBALS['ROUTER']::getCurrentRoute()
        );

        $sRouteClass::layout($oRenderContainer);
    }

    /**
     * @param string $sController
     * @param array  $aParams
     *
     * @return string
     */
    public static function buildRouteForController(string $sController, array $aParams = []): string
    {
        #$aParams = func_get_args();
        #array_shift($aParams);	# stripping $sController
        if (($sRouteForController = $GLOBALS['ROUTER']::getRouteForController($sController)) === false) {
            throw new RuntimeException(
                "buildRouteForController '" . htmlspecialchars($sController) . "': no route available."
            );
        }

        $aRewrittenParams = [];

        /** @var Route $sRouteClass */
        $sRouteClass = self::getRouteClassForRoute($sRouteForController);
        $aParametersMap = $sRouteClass::parametersMap();

        foreach ($aParametersMap as $sParam => $aMap) {
            if (!array_key_exists($sParam, $aParams)) {
                # if parameter not in parameters map, skip !
                continue;
            }

            $sUrlToken = $sParam;
            if (array_key_exists('urltoken', $aMap)) {
                $sUrlToken = $aMap['urltoken'];
            }

            $aRewrittenParams[$sUrlToken] = $aParams[$sParam];
        }

        #array_unshift($aParams, $sRouteForController);	# Injecting route as first param
        #return call_user_func_array($GLOBALS["ROUTER"] . "::buildRoute", $aParams);
        return $GLOBALS['ROUTER']::buildRoute($sRouteForController, $aRewrittenParams);
    }

    /**
     * @return string
     */
    public static function buildCurrentRoute(/*[$sParam, $sParam2, ...]*/): string
    {
        $aParams = func_get_args();
        $sCurrentRoute = $GLOBALS['ROUTER']::getCurrentRoute();

        array_unshift($aParams, $sCurrentRoute);    # Injecting route as first param

        return call_user_func_array($GLOBALS['ROUTER'] . '::buildRoute', $aParams);
    }

    /**
     * @param string $sURIPath
     *
     * @return void
     */
    public static function setURIPath(string $sURIPath): void
    {
        static::$sURIPath = $sURIPath;
    }

    /**
     * @return string
     */
    public static function getUriPath(): string
    {
        return FLAKE_URIPATH . static::$sURIPath;
    }

    /* ----------------------- CHANGING METHODS ----------------------------*/

    # this method is likely to change with every Router implementation
    # should be abstract, but is not, because of PHP's strict standards
    /**
     * @param string $sRoute
     * @param array  $aParams
     *
     * @return string
     */
    public static function buildRoute(string $sRoute, array $aParams/* [, $sParam, $sParam2, ...] */): string
    {
        return '';
    }

    # should be abstract, but is not, because of PHP's strict standards

    /**
     * @return string
     */
    public static function getCurrentRoute(): string
    {
        return '';
    }

    # should be abstract, but is not, because of PHP's strict standards

    /**
     * @return array
     */
    public static function getURLParams(): array
    {
        return [];
    }
}
