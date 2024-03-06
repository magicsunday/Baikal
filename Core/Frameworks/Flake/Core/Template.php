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

use Flake\Util\Tools;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Template
{
    private readonly string|false $sHtml;

    /**
     * @param string $sAbsPath
     */
    public function __construct(string $sAbsPath)
    {
        $this->sHtml = $this->getTemplateFile($sAbsPath);
    }

    /**
     * @param $sAbsPath
     *
     * @return false|string
     */
    private function getTemplateFile(string $sAbsPath): false|string
    {
        return file_get_contents($sAbsPath);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function parse(array $aMarkers = []): string
    {
        return Tools::parseTemplateCode(
            $this->sHtml,
            $aMarkers
        );
    }
}
