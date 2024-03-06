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
//  http://sabre.io/baikal
//
//  This script is part of the Baïkal Server project. The Baïkal
//  Server project is free software; you can redistribute it
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

namespace Baikal\Model;

use Exception;
use Flake\Core\Model\NoDb;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;

abstract class Config extends NoDb
{
    protected string $sConfigFileSection = '';
    protected array $aData               = [];

    /**
     * @param $sConfigFileSection
     */
    public function __construct($sConfigFileSection)
    {
        // Note: no call to parent::__construct() to avoid erasing $this->aData
        $this->sConfigFileSection = $sConfigFileSection;

        try {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
            if (isset($config[$sConfigFileSection])) {
                $aConfig = $config[$sConfigFileSection];
            } else {
                error_log(
                    'Section ' . $sConfigFileSection
                    . ' not found in config file. Using default values.'
                );
                $aConfig = [];
            }

            foreach (array_keys($this->aData) as $sProp) {
                if (array_key_exists($sProp, $aConfig)) {
                    $this->aData[$sProp] = $aConfig[$sProp];
                }
            }
        } catch (Exception $e) {
            error_log('Error reading baikal.yaml file : ' . $e->getMessage());
            // Keep default values in $aData
        }
    }

    /**
     * @return array|mixed
     */
    protected function getConfigAsString(): mixed
    {
        if (file_exists(PROJECT_PATH_CONFIG . 'baikal.yaml')) {
            return Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml')[$this->sConfigFileSection];
        }

        return $this->aData;
    }

    /**
     * @return bool
     */
    public function writable(): bool
    {
        return
            @file_exists(PROJECT_PATH_CONFIG . 'baikal.yaml')
            && @is_file(PROJECT_PATH_CONFIG . 'baikal.yaml')
            && @is_writable(PROJECT_PATH_CONFIG . 'baikal.yaml')
        ;
    }

    /**
     * @return string
     */
    public static function icon(): string
    {
        return 'icon-cog';
    }

    /**
     * @return string
     */
    public static function mediumicon(): string
    {
        return 'glyph-cogwheel';
    }

    /**
     * @return string
     */
    public static function bigicon(): string
    {
        return 'glyph2x-cogwheel';
    }

    /**
     * @return bool
     */
    public function floating(): bool
    {
        return false;
    }

    /**
     * @return void
     */
    public function persist(): void
    {
        if (file_exists(PROJECT_PATH_CONFIG . 'baikal.yaml')) {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
        } else {
            $config = [];
        }
        $config[$this->sConfigFileSection] = $this->aData;
        $yaml                              = Yaml::dump($config);
        file_put_contents(PROJECT_PATH_CONFIG . 'baikal.yaml', $yaml);
    }

    /**
     * @return void
     */
    public function destroy(): void
    {
    }
}
