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

/**
 *
 */
class Cli extends Container
{
    /**
     * @var array
     */
    private array $aArgs = [];

    public string $sLog = '';

    /**
     * @return string
     */
    public function render(): string
    {
        $this->sys_init();
        $this->init();

        $this->echoFlush($this->notice('process started @' . strftime('%d/%m/%Y %H:%M:%S')));
        $this->execute();
        $this->echoFlush($this->notice('process ended @' . strftime('%d/%m/%Y %H:%M:%S')) . "\n\n");

        return '';
    }

    /**
     * @return void
     */
    public function sys_init(): void
    {
        $this->rawLine('Command line: ' . (implode(' ', $_SERVER['argv'])));
        $this->initArgs();
    }

    /**
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * @return void
     */
    public function initArgs(): void
    {
        $sShortOpts = '';
        $sShortOpts .= 'h';        // help; pas de valeur
        $sShortOpts .= 'w:';    // author; valeur obligatoire

        $aLongOpts = [
            'help',
            // help; pas de valeur
            'helloworld',
            // author; pas de valeur
        ];

        $this->aArgs = getopt($sShortOpts, $aLongOpts);
    }

    /**
     * @return false|string
     */
    public function getScriptPath(): false|string
    {
        return realpath($_SERVER['argv'][0]);
    }

    /**
     * @return false|string
     */
    public function getSyntax(): false|string
    {
        return $this->getScriptPath();
    }

    /**
     * @return void
     */
    public function syntaxError(): void
    {
        $sStr = $this->rawLine("Syntax error.\nUsage: " . $this->getSyntax());
        exit("\n\n" . $sStr . "\n\n");
    }

    /**
     * @param $sStr
     *
     * @return void
     */
    public function log($sStr): void
    {
        $this->sLog .= $sStr;
    }

    /**
     * @param $sMsg
     *
     * @return string
     */
    public function header($sMsg): string
    {
        $sStr = "\n" . str_repeat('#', 80);
        $sStr .= "\n" . '#' . str_repeat(' ', 78) . '#';
        $sStr .= "\n" . '#' . str_pad(strtoupper($sMsg), 78, ' ', STR_PAD_BOTH) . '#';
        $sStr .= "\n" . '#' . str_repeat(' ', 78) . '#';
        $sStr .= "\n" . str_repeat('#', 80);
        $sStr .= "\n";

        $this->log($sStr);

        return $sStr;
    }

    /**
     * @param $sMsg
     *
     * @return string
     */
    public function subHeader($sMsg): string
    {
        $sStr = "\n\n# " . str_pad(strtoupper($sMsg) . ' ', 78, '-') . "\n";
        $this->log($sStr);

        return $sStr;
    }

    /**
     * @param $sMsg
     *
     * @return string
     */
    public function subHeader2($sMsg): string
    {
        $sStr = "\n# # " . str_pad($sMsg . ' ', 76, '-') . "\n";
        $this->log($sStr);

        return $sStr;
    }

    /**
     * @param $sMsg
     *
     * @return string
     */
    public function textLine($sMsg): string
    {
        $sStr = '. ' . $sMsg . "\n";
        $this->log($sStr);

        return $sStr;
    }

    /**
     * @param $sMsg
     *
     * @return string
     */
    public function rawLine($sMsg): string
    {
        $sStr = $sMsg . "\n";
        $this->log($sStr);

        return $sStr;
    }

    /**
     * @param $sMsg
     *
     * @return string
     */
    public function notice($sMsg): string
    {
        $sStr = "\n" . str_pad($sMsg, 80, '.', STR_PAD_BOTH) . "\n";
        $this->log($sStr);

        return $sStr;
    }

    /**
     * @return string
     */
    public function getLog(): string
    {
        return $this->sLog;
    }

    /**
     * @param string $sPath
     * @param string $sData
     * @param bool   $bUTF8
     *
     * @return void
     */
    public function file_writeBin(string $sPath, string $sData, bool $bUTF8 = true): void
    {
        $rFile = fopen($sPath, 'wb');

        if ($bUTF8 === true) {
            fwrite($rFile, "\xEF\xBB\xBF" . $sData);
        } else {
            fwrite($rFile, $sData);
        }

        fclose($rFile);
    }

    /**
     * @param string $sString
     *
     * @return void
     */
    public function echoFlush(string $sString = ''): void
    {
        echo $sString;
        ob_flush();
        flush();
    }
}
