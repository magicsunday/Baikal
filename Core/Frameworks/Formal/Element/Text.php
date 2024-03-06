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

use Formal\Element;

use function array_key_exists;

class Text extends Element
{
    /**
     * @return string
     */
    protected function inputtype(): string
    {
        return 'text';
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $disabled   = '';
        $inputclass = '';
        $groupclass = '';

        $value       = $this->value();
        $label       = $this->option('label');
        $prop        = $this->option('prop');
        $placeholder = '';
        $helpblock   = '';
        $popover     = '';

        if ($this->option('readonly') === true) {
            $inputclass .= ' disabled';
            $disabled = ' disabled';
        }

        if ($this->option('error') === true) {
            $groupclass .= ' error';
        }

        if (trim($this->option('class')) !== '') {
            $groupclass .= ' ' . $this->option('class');
        }

        if (trim($this->option('inputclass')) !== '') {
            $inputclass = $this->option('inputclass');
        }

        if (($sPlaceHolder = trim($this->option('placeholder'))) !== '') {
            $placeholder = ' placeholder="' . htmlspecialchars($sPlaceHolder) . '" ';
        }

        $clientvalue = htmlspecialchars($value ?? '');

        $sInputType = $this->inputtype();

        if (($sHelp = trim($this->option('help'))) !== '') {
            $helpblock = '<p class="help-block">' . $sHelp . '</p>';
        }

        if (($aPopover = $this->option('popover')) !== '') {
            if (array_key_exists('position', $aPopover)) {
                $sPosition = $aPopover['position'];
                $inputclass .= ' popover-focus-' . $sPosition;
            } else {
                $inputclass .= ' popover-focus ';
            }

            $popover = ' title="' . htmlspecialchars((string) $aPopover['title']) . '" ';
            $popover .= ' data-content="' . htmlspecialchars((string) $aPopover['content']) . '" ';
            $popover .= ' data-html="true"';
        }

        $sHtml = <<<HTML
<div class="control-group{$groupclass}">
	<label class="control-label" for="{$prop}">{$label}</label>
	<div class="controls">
		<input type="{$sInputType}" class="{$inputclass}" id="{$prop}" name="data[{$prop}]" value="{$clientvalue}"{$disabled}{$placeholder}{$popover}/>
		{$helpblock}
	</div>
</div>
HTML;

        return $sHtml . $this->renderWitness();
    }
}
