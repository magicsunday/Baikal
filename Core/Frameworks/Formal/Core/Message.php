<?php

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

namespace Formal\Core;

/**
 *
 */
class Message
{
    private function __construct()
    {
    }

    /**
     * @param        $sMessage
     * @param string $sTitle
     *
     * @return string
     */
    public static function error($sMessage, string $sTitle = ''): string
    {
        if ($sTitle !== '') {
            $sTitle = '<h3 class="alert-heading">' . $sTitle . '</h3>';
        }

        return <<<HTML
<div id="message" class="alert alert-block alert-error">
	{$sTitle}
	{$sMessage}
</div>
HTML;
    }

    /**
     * @param string $sMessage
     * @param string $sTitle
     * @param bool   $bClose
     *
     * @return string
     */
    public static function notice(string $sMessage, string $sTitle = '', bool $bClose = true): string
    {
        $sClose = '';

        if ($sTitle !== '') {
            $sTitle = '<h3 class="alert-heading">' . $sTitle . '</h3>';
        }

        if ($bClose === true) {
            $sClose = '<a class="close" data-dismiss="alert" href="#">&times;</a>';
        }

        return <<<HTML
<div id="message" class="alert alert-info">
	{$sClose}
	{$sTitle}
	{$sMessage}
</div>
HTML;
    }

    /**
     * @param        $sHeader
     * @param        $sDescription
     * @param        $sActionUrl
     * @param        $sActionLabel
     * @param        $sCancelUrl
     * @param string $sCancelLabel
     *
     * @return string
     */
    public static function warningConfirmMessage(
        $sHeader,
        $sDescription,
        $sActionUrl,
        $sActionLabel,
        $sCancelUrl,
        string $sCancelLabel = 'Cancel'
    ): string {
        return <<<HTML
<div id="message" class="alert alert-block alert-error">
	<!--a class="close" data-dismiss="alert" href="#">&times;</a-->
	<h3 class="alert-heading">{$sHeader}</h3>
	{$sDescription}
	<p>
		<a class="btn btn-danger" href="{$sActionUrl}">{$sActionLabel}</a> <a class="btn" href="{$sCancelUrl}">Cancel</a>
	</p>
</div>
HTML;
    }
}
