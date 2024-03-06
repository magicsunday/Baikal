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

namespace Flake\Core\Render;

use Flake\Core\Controller;

use function array_key_exists;

abstract class Container extends Controller
{
    public array $aSequence = [];

    public array $aBlocks = [];

    /**
     * @var Zone[]
     */
    public array $aZones = [];

    /**
     * @param Controller $oBlock
     * @param string     $sZone
     *
     * @return void
     */
    public function addBlock(Controller $oBlock, string $sZone = '_DEFAULT_'): void
    {
        $aTemp = [
            'block' => &$oBlock,
            'rendu' => '',
        ];
        $this->aSequence[]       = &$aTemp;
        $this->aBlocks[$sZone][] = &$aTemp['rendu'];
    }

    /**
     * @param string $sZone
     *
     * @return Zone
     */
    public function zone(string $sZone): Zone
    {
        if (!array_key_exists($sZone, $this->aZones)) {
            $this->aZones[$sZone] = new Zone($this, $sZone);
        }

        return $this->aZones[$sZone];
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $this->execute();
        $aRenderedBlocks = $this->renderBlocks();

        return implode('', $aRenderedBlocks);
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->aSequence as $aStep) {
            $aStep['block']->execute();
        }
    }

    /**
     * @return array
     */
    protected function renderBlocks(): array
    {
        foreach (array_keys($this->aSequence) as $sKey) {
            $this->aSequence[$sKey]['rendu'] = $this->aSequence[$sKey]['block']->render();
        }

        $aHtml = [];
        foreach ($this->aBlocks as $sZone => $aBlock) {
            $aHtml[$sZone] = implode('', $aBlock);
        }

        return $aHtml;
    }
}
