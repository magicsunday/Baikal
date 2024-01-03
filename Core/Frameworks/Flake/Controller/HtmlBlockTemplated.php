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

namespace Flake\Controller;

use Flake\Core\Controller;
use Flake\Core\Template;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 *
 */
class HtmlBlockTemplated extends Controller
{
    /**
     * @var string
     */
    private string $sTemplatePath;

    /**
     * @var array
     */
    private mixed $aMarkers;

    /**
     * @param       $sTemplatePath
     * @param array $aMarkers
     */
    public function __construct($sTemplatePath, array $aMarkers = [])
    {
        $this->sTemplatePath = $sTemplatePath;
        $this->aMarkers = $aMarkers;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(): string
    {
        return (new Template($this->sTemplatePath))->parse(
            $this->aMarkers
        );
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        // TODO: Implement execute() method.
    }
}
