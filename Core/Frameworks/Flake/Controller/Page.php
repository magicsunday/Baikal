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

namespace Flake\Controller;

use Flake\Core\Render\Container;
use Flake\Core\Template;
use Flake\Util\Tools;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Page extends Container
{
    protected string $sTitle = '';

    protected string $sMetaKeywords = '';

    protected string $sMetaDescription = '';

    protected string $sTemplatePath = '';

    /**
     * @var string
     */
    private string $sBaseUrl;

    /**
     * @param string $sTemplatePath
     */
    public function __construct(string $sTemplatePath)
    {
        $this->sTemplatePath = $sTemplatePath;
    }

    /**
     * @param string $sTitle
     *
     * @return void
     */
    public function setTitle(string $sTitle): void
    {
        $this->sTitle = $sTitle;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->sTitle;
    }

    /**
     * @return string
     */
    public function getMetaKeywords(): string
    {
        $sString = str_replace([
            'le',
            'la',
            'les',
            'de',
            'des',
            'un',
            'une',
        ], ' ', $this->sMetaKeywords);
        $sString = Tools::stringToUrlToken($sString);

        return implode(', ', explode('-', $sString));
    }

    /**
     * @return string
     */
    public function getMetaDescription(): string
    {
        return $this->sMetaDescription;
    }

    /**
     * @param $sBaseUrl
     *
     * @return void
     */
    public function setBaseUrl(string $sBaseUrl): void
    {
        $this->sBaseUrl = $sBaseUrl;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->sBaseUrl;
    }

    /**
     * @return void
     */
    public function injectHTTPHeaders(): void
    {
        header('Content-Type: text/html; charset=UTF-8');

        header('X-Frame-Options: DENY');    // Prevent Clickjacking attacks
        header('X-Content-Type-Options: nosniff');    // Prevent code injection via mime type sniffing
    }

    /**
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(): string
    {
        $this->execute();

        $aRenderedBlocks                        = $this->renderBlocks();
        $aRenderedBlocks['pagetitle']           = $this->getTitle();
        $aRenderedBlocks['pagemetakeywords']    = $this->getMetaKeywords();
        $aRenderedBlocks['pagemetadescription'] = $this->getMetaDescription();
        $aRenderedBlocks['baseurl']             = $this->getBaseUrl();

        return (new Template($this->sTemplatePath))->parse(
            $aRenderedBlocks
        );
    }
}
