<?php /** @noinspection ALL */

declare(strict_types=1);

#################################################################
#  Copyright notice
#
#  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://formal.codr.fr
#
#  This script is part of the Formal project. The Formal
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

namespace Formal\Element;

use Formal\Element;

/**
 *
 */
class Checkbox extends Element
{
    /**
     * @param $sValue
     *
     * @return void
     */
    public function setValue($sValue): void
    {
        # Boolean
        $this->sValue = (((int)$sValue === 1));
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $disabled = '';
        $inputclass = '';
        $groupclass = '';
        $onchange = '';
        $helpblock = '';
        $popover = '';

        $value = $this->value();

        $checked = ($value === true ? ' checked="checked" ' : '');
        $label = $this->option('label');
        $prop = $this->option('prop');

        if ($this->option('readonly') === true) {
            $inputclass .= ' disabled';
            $disabled = ' disabled';
        }

        if ($this->option('error') === true) {
            $groupclass .= ' error';
        }

        if (($sHelp = trim($this->option('help'))) !== '') {
            $helpblock = '<p class="help-block">' . $sHelp . '</p>';
        }

        if (($aPopover = $this->option('popover')) !== '') {
            $inputclass .= ' popover-hover ';
            $popover = ' title="' . htmlspecialchars($aPopover['title']) . '" ';
            $popover .= ' data-content="' . htmlspecialchars($aPopover['content']) . '" ';
        }

        if ($this->option('refreshonchange') === true) {
            $onchange = " onchange=\"document.getElementsByTagName('form')[0].elements['refreshed'].value=1;document.getElementsByTagName('form')[0].submit();\" ";
        }

        $sHtml = <<<HTML
<div class="control-group{$groupclass}">
	<label class="control-label" for="{$prop}">{$label}</label>
	<div class="controls">
		<input type="checkbox" class="input-xlarge{$inputclass}" id="{$prop}" name="data[{$prop}]" value="1"{$checked}{$disabled}{$popover}{$onchange}/>
		{$helpblock}
	</div>
</div>
HTML;

        return $sHtml . $this->renderWitness();
    }
}
