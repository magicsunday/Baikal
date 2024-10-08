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
//  http://formal.codr.fr
//
//  This script is part of the Formal project. The Formal
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

namespace Formal\Element;

use Flake\Util\Tools;
use Formal\Element;
use RuntimeException;

use function is_array;

class Listbox extends Element
{
    /**
     * @return string
     */
    public function render(): string
    {
        $disabled   = '';
        $inputclass = '';
        $groupclass = '';
        $onchange   = '';

        $value     = $this->value();
        $label     = $this->option('label');
        $prop      = $this->option('prop');
        $helpblock = '';
        $popover   = '';

        if ($this->option('readonly') === true) {
            $inputclass .= ' disabled';
            $disabled = ' disabled';
        }

        if ($this->option('error') === true) {
            $groupclass .= ' error';
        }

        $aOptions = $this->option('options');
        if (!is_array($aOptions)) {
            throw new RuntimeException("\Formal\Element\Listbox->render(): 'options' has to be an array.");
        }

        if (($sHelp = trim($this->option('help'))) !== '') {
            $helpblock = '<p class="help-block">' . $sHelp . '</p>';
        }

        if (($aPopover = $this->option('popover')) !== '') {
            $inputclass .= ' popover-focus ';
            $popover = ' title="' . htmlspecialchars((string) $aPopover['title']) . '" ';
            $popover .= ' data-content="' . htmlspecialchars((string) $aPopover['content']) . '" ';
        }

        if ($this->option('refreshonchange') === true) {
            $onchange = " onchange=\"document.getElementsByTagName('form')[0].elements['refreshed'].value=1;document.getElementsByTagName('form')[0].submit();\" ";
        }

        $aRenderedOptions = [];

        if (Tools::arrayIsSeq($aOptions)) {
            // Array is sequential
            reset($aOptions);
            foreach ($aOptions as $sOptionValue) {
                $selected           = ($sOptionValue === $value) ? ' selected="selected"' : '';
                $aRenderedOptions[] = '<option' . $selected . '>' . htmlspecialchars((string) $sOptionValue) . '</option>';
            }
        } else {
            // Array is associative
            reset($aOptions);
            foreach ($aOptions as $sOptionValue => $sOptionCaption) {
                $selected           = ($sOptionValue === $value) ? ' selected="selected"' : '';
                $aRenderedOptions[] = '<option value="' . htmlspecialchars(
                    $sOptionValue
                ) . '"' . $selected . '>' . htmlspecialchars((string) $sOptionCaption) . '</option>';
            }
        }

        $sRenderedOptions = implode("\n", $aRenderedOptions);
        unset($aRenderedOptions);

        $sHtml = <<<HTML
	<div class="control-group{$groupclass}">
		<label class="control-label" for="{$prop}">{$label}</label>
		<div class="controls">
			<select class="{$inputclass}" id="{$prop}" name="data[{$prop}]"{$disabled}{$popover}{$onchange}>
				{$sRenderedOptions}
			</select>
			{$helpblock}
		</div>
	</div>
HTML;

        return $sHtml . $this->renderWitness();
    }
}
