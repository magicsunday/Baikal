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

/**
 *
 */
abstract class Controller extends FLObject
{
    protected mixed $aParams = [];

    /**
     * @param array $aParams
     */
    public function __construct(array $aParams = [])
    {
        $this->aParams = $aParams;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->aParams;
    }

    /**
     * @return string
     */
    public static function link(/*[$sParam, $sParam2, ...]*/): string
    {
        return static::buildRoute();
    }

    /**
     * @param array $aParams
     *
     * @return string
     */
    public static function buildRoute(array $aParams = []): string
    {
        # TODO: il faut remplacer le mécanisme basé sur un nombre variable de paramètres en un mécanisme basé sur un seul paramètre "tableau"
        #$aParams = func_get_args();
        $sController = "\\" . static::class;
        #array_unshift($aParams, $sController);		# Injecting current controller as first param
        #return call_user_func_array($GLOBALS["ROUTER"] . "::buildRouteForController", $aParams);
        return $GLOBALS['ROUTER']::buildRouteForController($sController, $aParams);
    }

    /**
     * @return void
     */
    abstract public function execute(): void;

    /**
     * @return string
     */
    abstract public function render(): string;
}
