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

use Flake\Core\Render\Container;
use Flake\Core\Template;
use Flake\Util\Frameworks;
use Flake\Util\Tools;
use Frameworks\LessPHP\Delegate;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 *
 */
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
     * @param $sTitle
     *
     * @return void
     */
    public function setTitle($sTitle): void
    {
        $this->sTitle = $sTitle;
    }

    /**
     * @param $sKeywords
     *
     * @return void
     */
    public function setMetaKeywords($sKeywords): void
    {
        $this->sMetaKeywords = $sKeywords;
    }

    /**
     * @param $sDescription
     *
     * @return void
     */
    public function setMetaDescription($sDescription): void
    {
        $this->sMetaDescription = $sDescription;
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
    public function setBaseUrl($sBaseUrl): void
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

        header('X-Frame-Options: DENY');    # Prevent Clickjacking attacks
        header('X-Content-Type-Options: nosniff');    # Prevent code injection via mime type sniffing
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(): string
    {
        $this->execute();

        $aRenderedBlocks = $this->renderBlocks();
        $aRenderedBlocks['pagetitle'] = $this->getTitle();
        $aRenderedBlocks['pagemetakeywords'] = $this->getMetaKeywords();
        $aRenderedBlocks['pagemetadescription'] = $this->getMetaDescription();
        $aRenderedBlocks['baseurl'] = $this->getBaseUrl();

        return (new Template($this->sTemplatePath))->parse(
            $aRenderedBlocks
        );
    }

    /**
     * @param $sCssAbsPath
     *
     * @return void
     */
    public function addCss($sCssAbsPath): void
    {
        if (Frameworks::enabled('LessPHP')) {
            $sCompiledPath = PATH_buildcss;
            $sFileName = basename($sCssAbsPath);

            $sCompiledFilePath = $sCompiledPath . Tools::shortMD5($sFileName) . '_' . $sFileName;

            if (!str_ends_with(strtolower($sCompiledFilePath), '.css')) {
                $sCompiledFilePath .= '.css';
            }

            if (!file_exists($sCompiledPath)) {
                if (!mkdir($sCompiledPath) && !is_dir($sCompiledPath)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $sCompiledPath));
                }
                if (!file_exists($sCompiledPath)) {
                    exit('Page: Cannot create ' . $sCompiledPath);
                }
            }

            Delegate::compileCss($sCssAbsPath, $sCompiledFilePath);
            $sCssUrl = Tools::serverToRelativeWebPath($sCompiledFilePath);
        } else {
            $sCssUrl = Tools::serverToRelativeWebPath($sCssAbsPath);
        }

        $sHtml = '<link rel="stylesheet" type="text/css" href="' . $sCssUrl . '" media="all"/>';
        $this->zone('head')->addBlock(new HtmlBlock($sHtml));
    }
}
