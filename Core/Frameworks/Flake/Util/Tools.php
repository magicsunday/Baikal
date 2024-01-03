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

namespace Flake\Util;

use Exception;
use Flake\Util\Router\QuestionMarkRewrite;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

use function array_key_exists;
use function array_slice;
use function chr;
use function count;
use function get_class;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;
use function ord;
use function strlen;

/**
 *
 */
class Tools
{
    private function __construct()
    {    # private constructor to force static class
    }

    /**
     * @return mixed|string
     */
    public static function getCurrentUrl(): mixed
    {
        $sUrl = $GLOBALS['_SERVER']['REQUEST_URI'];
        if (MONGOOSE_SERVER) {
            if (array_key_exists('QUERY_STRING', $GLOBALS['_SERVER']) && trim(
                    $GLOBALS['_SERVER']['QUERY_STRING']
                ) !== '') {
                $sUrl .= '?' . $GLOBALS['_SERVER']['QUERY_STRING'];
            }
        }

        return $sUrl;
    }

    /**
     * @return mixed|string
     */
    public static function getCurrentProtocol(): mixed
    {
        if (!empty($GLOBALS['_SERVER']['HTTP_X_FORWARDED_PROTO'])) {
            return $GLOBALS['_SERVER']['HTTP_X_FORWARDED_PROTO'];
        }

        if ((!empty($GLOBALS['_SERVER']['HTTPS']) && $GLOBALS['_SERVER']['HTTPS'] !== 'off') || (int)$_SERVER['SERVER_PORT'] === 443) {
            return 'https';
        }

        return 'http';
    }

    /**
     * @param        $sString
     * @param string $sGlue
     *
     * @return array|string|null
     */
    public static function deCamelCase($sString, string $sGlue = ' '): array|string|null
    {
        $sSep = md5((string)mt_rand());
        $sRes = preg_replace(
            '/(?!^)[[:upper:]][[:lower:]]/',
            '$0',
            preg_replace('/(?!^)[[:upper:]]+/', $sSep . '$0', $sString)
        );
        if ($sGlue !== '' && preg_match('/^[[:upper:]].*/', $sRes)) {
            $sRes = $sSep . $sRes;
        }

        return str_replace($sSep, $sGlue, $sRes);
    }

    /**
     * @param $sAbsPath
     *
     * @return string
     */
    public static function serverToRelativeWebPath($sAbsPath): string
    {
        return '/' . str_replace(PROJECT_PATH_WWWROOT, '', $sAbsPath);
    }

    /**
     * @param $array_in
     *
     * @return string
     */
    public static function view_array($array_in): string
    {
        if (is_array($array_in)) {
            $result = '<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">';
            if (!count($array_in)) {
                $result .= '<tr><td><font face="Verdana,Arial" size="1"><b>' . htmlspecialchars(
                        'EMPTY!'
                    ) . '</b></font></td></tr>';
            }
            foreach ($array_in as $key => $val) {
                $result .= '<tr><td valign="top"><font face="Verdana,Arial" size="1">' . htmlspecialchars(
                        (string)$key
                    ) . '</font></td><td>';
                if (is_array($val)) {
                    $result .= self::view_array($val);
                } else {
                    if (is_object($val)) {
                        if (method_exists($val, '__toString')) {
                            $sWhat = nl2br(htmlspecialchars((string)$val));
                        } else {
                            $sWhat = nl2br(htmlspecialchars(get_class($val)));
                        }
                    } elseif (is_bool($val)) {
                        $sWhat = ($val === true ? 'boolean:TRUE' : 'boolean:FALSE');
                    } else {
                        $sWhat = nl2br(htmlspecialchars((string)$val));
                    }

                    $result .= '<font face="Verdana,Arial" size="1" color="red">' . $sWhat . '<br /></font>';
                }

                $result .= '</td></tr>';
            }
            $result .= '</table>';
        } else {
            $result = '<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">
				<tr>
					<td><font face="Verdana,Arial" size="1" color="red">' . nl2br(htmlspecialchars((string)$array_in)) . '<br /></font></td>
				</tr>
			</table>';    // Output it as a string.
        }

        return $result;
    }

    /**
     * @param string $var
     * @param int    $brOrHeader
     *
     * @return void
     */
    public static function debug(string $var = '', int $brOrHeader = 0): void
    {
        if ($brOrHeader === 0) {
            try {
                $trail = debug_backtrace();
                $trail = array_reverse($trail);
                array_pop($trail);    // la ligne d'appel à debug
                array_pop($trail);    // la ligne d'appel à debug
                $aLastNode = array_pop($trail);    // l'appel qui nous intéresse

                if (array_key_exists('class', $aLastNode)) {
                    $sClass = @(string)$aLastNode['class'];
                } else {
                    $sClass = '';
                }

                if (array_key_exists('type', $aLastNode)) {
                    $sType = @(string)$aLastNode['type'];
                } else {
                    $sType = '';
                }

                $brOrHeader = $sClass . $sType . @(string)$aLastNode['function'];
            } catch (Exception $e) {
                $brOrHeader = 'Undetermined context';
            }
        }

        if ($brOrHeader) {
            echo '<table border="0" cellpadding="0" cellspacing="0" bgcolor="white" style="border:0px; margin-top:3px; margin-bottom:3px;"><tr><td style="background-color:#bbbbbb; font-family: verdana,arial; font-weight: bold; font-size: 10px;">' . htmlspecialchars(
                    (string)$brOrHeader
                ) . '</td></tr><tr><td>';
        }

        if (is_array($var)) {
            echo self::view_array($var);
        } elseif (is_object($var)) {
            echo '<b>|Object:<pre>';
            print_r($var);
            echo '</pre>|</b>';
        } elseif ($var != '') {
            echo '<b>|' . htmlspecialchars($var) . '|</b>';
        } else {
            echo '<b>| debug |</b>';
        }

        if ($brOrHeader) {
            echo '</td></tr></table>';
        }
    }

    /**
     * @return string
     */
    public static function debug_trail(): string
    {
        $trail = debug_backtrace();
        $trail = array_reverse($trail);
        array_pop($trail);

        $path = [];
        foreach ($trail as $dat) {
            $path[] = $dat['class'] . $dat['type'] . $dat['function'];
        }

        return implode(' // ', $path);
    }

    /**
     * @param string|bool $sVar
     *
     * @return array|mixed|string
     */
    public static function POST(string|bool $sVar = false): mixed
    {
        if ($sVar !== false) {
            $aData = self::POST();
            if (array_key_exists($sVar, $aData)) {
                return $aData[$sVar];
            }

            return '';
        }

        return is_array($GLOBALS['_POST']) ? $GLOBALS['_POST'] : [];
    }

    /**
     * @param string|bool $sVar
     *
     * @return array|string
     */
    public static function GET(string|bool $sVar = false): array|string
    {
        if ($sVar !== false) {
            $aData = self::GET();
            if (array_key_exists($sVar, $aData)) {
                return $aData[$sVar];
            }

            return '';
        }

        return is_array($GLOBALS['_GET']) ? $GLOBALS['_GET'] : [];
    }

    /**
     * @param string|bool $sVar
     *
     * @return array|string
     */
    public static function GP(string|bool $sVar = false): array|string
    {
        if ($sVar !== false) {
            $aData = self::GP();
            if (array_key_exists($sVar, $aData)) {
                return $aData[$sVar];
            }

            return '';
        }

        return array_merge(
            self::GET(),
            self::POST()
        );
    }

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function safelock(string $sString): string
    {
        return substr(md5(PROJECT_SAFEHASH_SALT . ':' . $sString), 0, 5);
    }

    /**
     * @param string $sUrl
     *
     * @return void
     */
    public static function redirect(string $sUrl): void
    {
        header('Location: ' . $sUrl);
        exit(0);
    }

    /**
     * @param string $sUrl
     *
     * @return void
     */
    public static function redirectUsingMeta(string $sUrl): void
    {
        $sDoc = "<html><head><meta http-equiv='refresh' content='0; url=" . $sUrl . "'></meta></head><body></body></html>";
        echo $sDoc;
        exit(0);
    }

    /**
     * @return void
     */
    public static function refreshPage(): void
    {
        header('Location: ' . self::getCurrentUrl());
        exit(0);
    }

    /**
     * @param string $sEmail
     *
     * @return bool
     */
    public static function validEmail(string $sEmail): bool
    {
        return (filter_var($sEmail, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * @param string $sInput
     *
     * @return string
     */
    public static function filterFormInput(string $sInput): string
    {
        return strip_tags($sInput);
    }

    /**
     * @param int $iStamp
     *
     * @return string
     */
    public static function getHumanDate(int $iStamp): string
    {
        return ucwords(strftime('%A, %d %B %Y', $iStamp));
    }

    /**
     * @param int $iStamp
     *
     * @return false|string
     */
    public static function getHumanTime(int $iStamp): false|string
    {
        return strftime('%Hh%M', $iStamp);
    }

    /**
     * @param string $string
     * @param string $delim
     * @param bool   $removeEmptyValues
     * @param int    $limit
     *
     * @return array
     */
    public static function trimExplode(
        string $string,
        string $delim = ',',
        bool $removeEmptyValues = false,
        int $limit = 0
    ): array {
        $explodedValues = explode($delim, $string);

        $result = array_map('trim', $explodedValues);

        if ($removeEmptyValues) {
            $temp = [];
            foreach ($result as $value) {
                if ($value !== '') {
                    $temp[] = $value;
                }
            }
            $result = $temp;
        }

        if ($limit != 0) {
            if ($limit < 0) {
                $result = array_slice($result, 0, $limit);
            } elseif (count($result) > $limit) {
                $lastElements = array_slice($result, $limit - 1);
                $result = array_slice($result, 0, $limit - 1);
                $result[] = implode($delim, $lastElements);
            }
        }

        return $result;
    }

    /**
     * Taken from TYPO3
     * Returns true if the first part of $str matches the string $partStr.
     *
     * @param string $str
     * @param string $partStr
     *
     * @return    bool        True if $partStr was found to be equal to the first part of $str
     */
    public static function isFirstPartOfStr(string $str, string $partStr): bool
    {
        // Returns true, if the first part of a $str equals $partStr and $partStr is not ''
        $psLen = strlen($partStr);
        if ($psLen) {
            return str_starts_with($str, $partStr);
        }

        return false;
    }

    /**
     * Binary-reads a file.
     *
     * @param string $sPath : absolute server path to file
     *
     * @return    string        file contents
     */
    public static function file_readBin(string $sPath): string
    {
        $sData = '';
        $rFile = fopen($sPath, 'rb');
        while (!feof($rFile)) {
            $sData .= fread($rFile, 1024);
        }
        fclose($rFile);

        return $sData;
    }

    /**
     * Binary-writes a file.
     *
     * @param string $sPath : absolute server path to file
     * @param string $sData : file contents
     *
     * @return    void
     */
    public static function file_writeBin(string $sPath, string $sData): void
    {
        $rFile = fopen($sPath, 'wb');
        fwrite($rFile, $sData);
        fclose($rFile);
    }

    /**
     * @param string $sToAddress
     * @param string $sSubject
     * @param string $sBody
     * @param string $sFromName
     * @param string $sFromAddress
     * @param string $sReplyToName
     * @param string $sReplyToAddress
     *
     * @return void
     */
    public static function sendHtmlMail(
        string $sToAddress,
        string $sSubject,
        string $sBody,
        string $sFromName,
        string $sFromAddress,
        string $sReplyToName,
        string $sReplyToAddress
    ): void {
        $sMessage = <<<TEST
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>Email</title>
	</head>
	<body>
	{$sBody}
	</body>
</html>
TEST;

        $sHeaders = 'From: ' . $sFromName . '<' . $sFromAddress . '>' . "\r\n";
        $sHeaders .= 'Reply-To: ' . $sReplyToName . '<' . $sReplyToAddress . '>' . "\r\n";
        $sHeaders .= 'Bcc: ' . $sReplyToName . '<' . $sReplyToAddress . '>' . "\r\n";
        $sHeaders .= 'Content-Type: text/html' . "\r\n";

        mail($sToAddress, $sSubject, $sMessage, $sHeaders);
    }

    /**
     * @param string $sValue
     *
     * @return string
     */
    public static function shortMD5(string $sValue): string
    {
        return strtolower(substr(md5($sValue), 0, 5));
    }

    /**
     * @param string $sFirst
     * @param string $sSecond
     *
     * @return string
     */
    public static function overrideFirstWithSecond(string $sFirst, string $sSecond): string
    {
        if (trim($sSecond) !== '') {
            return $sSecond;
        }

        return '' . $sFirst;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function parseTemplateCode(string $sCode, array $aMarkers): string
    {
        $tplName = md5($sCode);
        $loader = new ArrayLoader([$tplName => $sCode]);
        $env = new Environment($loader);
        $env->setCache(false);

        return $env->render($tplName, $aMarkers);
    }

    /**
     * @throws ReflectionException
     */
    public static function is_a(object|string $object, string $class): bool
    {
        if (is_object($object)) {
            return $object instanceof $class;
        }
        if (is_string($object)) {
            if (is_object($class)) {
                $class = get_class($class);
            }

            if (class_exists($class)) {    # TRUE to autoload class
                return @is_subclass_of($object, $class) || $object == $class;
            }

            if (interface_exists($class)) {
                return (new ReflectionClass($object))->implementsInterface($class);
            }
        }

        return false;
    }

    /**
     * @param int $iCode
     * @param string $sMessage
     *
     * @return void
     */
    public static function HTTPStatus(int $iCode, string $sMessage): void
    {
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        exit('<h1>HTTP Status ' . $iCode . ' : ' . $sMessage . '</h1>');
    }

    /**
     * @param int $a
     *
     * @return string
     */
    public static function number2Rank(int $a): string
    {
        $a = $a;

        if ($a === 1) {
            return 'premier';
        }

        if ($a === 2) {
            return 'second';
        }

        $sNumber = self::number2Human($a);

        $sLastLetter = $sNumber[strlen($sNumber) - 1];
        if ($sLastLetter === 'e') {
            $sNumber = substr($sNumber, 0, -1);
        } elseif ($sLastLetter === 'q') {
            $sNumber .= 'u';
        } elseif ($sLastLetter === 'f') {
            $sNumber = substr($sNumber, 0, -1) . 'v';
        }

        return $sNumber . 'ième';
    }

    /**
     * @param int $a
     *
     * @return string|void
     */
    public static function number2Human(int $a)
    {
        $temp = explode('.', (string)$a);
        if (isset($temp[1]) && $temp[1] != '') {
            return self::number2Human((int)$temp[0]) . ' virgule ' . self::number2Human((int)$temp[1]);
        }

        if ($a < 0) {
            return 'moins ' . self::number2Human(-$a);
        }

        if ($a < 17) {
            switch ($a) {
                case 0:
                    return 'zero';
                case 1:
                    return 'un';
                case 2:
                    return 'deux';
                case 3:
                    return 'trois';
                case 4:
                    return 'quatre';
                case 5:
                    return 'cinq';
                case 6:
                    return 'six';
                case 7:
                    return 'sept';
                case 8:
                    return 'huit';
                case 9:
                    return 'neuf';
                case 10:
                    return 'dix';
                case 11:
                    return 'onze';
                case 12:
                    return 'douze';
                case 13:
                    return 'treize';
                case 14:
                    return 'quatorze';
                case 15:
                    return 'quinze';
                case 16:
                    return 'seize';
            }
        } elseif ($a < 20) {
            return 'dix-' . self::number2Human($a - 10);
        } elseif ($a < 100) {
            if ($a % 10 == 0) {
                switch ($a) {
                    case 20:
                        return 'vingt';
                    case 30:
                        return 'trente';
                    case 40:
                        return 'quarante';
                    case 50:
                        return 'cinquante';
                    case 60:
                        return 'soixante';
                    case 70:
                        return 'soixante-dix';
                    case 80:
                        return 'quatre-vingt';
                    case 90:
                        return 'quatre-vingt-dix';
                }
            } elseif (substr((string)$a, -1) == 1) {
                if (((int)($a / 10) * 10) < 70) {
                    return self::number2Human((int)($a / 10) * 10) . '-et-un';
                }

                if ($a == 71) {
                    return 'soixante-et-onze';
                }

                if ($a == 81) {
                    return 'quatre-vingt-un';
                }

                if ($a == 91) {
                    return 'quatre-vingt-onze';
                }
            } elseif ($a < 70) {
                return self::number2Human($a - $a % 10) . '-' . self::number2Human($a % 10);
            } elseif ($a < 80) {
                return self::number2Human(60) . '-' . self::number2Human($a % 20);
            } else {
                return self::number2Human(80) . '-' . self::number2Human($a % 20);
            }
        } elseif ($a == 100) {
            return 'cent';
        } elseif ($a < 200) {
            return self::number2Human(100) . ' ' . self::number2Human($a % 100);
        } elseif ($a < 1000) {
            return self::number2Human((int)($a / 100)) . ' ' . self::number2Human(100) . ' ' . self::number2Human(
                    $a % 100
                );
        } elseif ($a == 1000) {
            return 'mille';
        } elseif ($a < 2000) {
            return self::number2Human(1000) . ' ' . self::number2Human($a % 1000) . ' ';
        } elseif ($a < 1000000) {
            return self::number2Human((int)($a / 1000)) . ' ' . self::number2Human(1000) . ' ' . self::number2Human(
                    $a % 1000
                );
        }
    }

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function stringToUrlToken(string $sString): string
    {
        # Taken from TYPO3 extension realurl

        $space = '-';
        $sString = strtr($sString, ' -+_\'', $space . $space . $space . $space . $space); // convert spaces

        # De-activated; @see https://github.com/netgusto/Baikal/issues/244
        #if(function_exists("iconv")) {
        #	$sString = iconv('UTF-8', 'ASCII//TRANSLIT', $sString);
        #}

        $sString = strtolower($sString);

        $sString = preg_replace('/[^a-zA-Z0-9\\' . $space . ']/', '', $sString);
        $sString = preg_replace(
            '/\\' . $space . '{2,}/',
            $space,
            $sString
        ); // Convert multiple 'spaces' to a single one
        return trim($sString, $space);
    }

    /**
     * @return bool
     */
    public static function isCliPhp(): bool
    {
        return strtolower(PHP_SAPI) === 'cli';
    }

    /**
     * @return string
     */
    public static function getIP(): string
    {
        $alt_ip = $_SERVER['REMOTE_ADDR'];

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $alt_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all(
                '#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s',
                $_SERVER['HTTP_X_FORWARDED_FOR'],
                $matches
            )) {
            // make sure we dont pick up an internal IP defined by RFC1918
            foreach ($matches[0] as $ip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $ip)) {
                    $alt_ip = $ip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_FROM'])) {
            $alt_ip = $_SERVER['HTTP_FROM'];
        }

        return $alt_ip;
    }

    /**
     * @return string
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    ###########

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function appendSlash(string $sString): string
    {
        return self::appendString($sString, '/');
    }

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function prependSlash(string $sString): string
    {
        return self::prependString($sString, '/');
    }

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function stripBeginSlash(string $sString): string
    {
        return self::stripBeginString($sString, '/');
    }

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function stripEndSlash(string $sString): string
    {
        return self::stripEndString($sString, '/');
    }

    /**
     * @param string $sString
     *
     * @return string
     */
    public static function trimSlashes(string $sString): string
    {
        return self::stripBeginSlash(self::stripEndSlash($sString));
    }

    ###########

    /**
     * @param string $sString
     * @param string $sAppend
     *
     * @return string
     */
    public static function appendString(string $sString, string $sAppend): string
    {
        if (!str_ends_with($sString, $sAppend)) {
            $sString .= $sAppend;
        }

        return $sString;
    }

    /**
     * @param string $sString
     * @param string $sAppend
     *
     * @return string
     */
    public static function prependString(string $sString, string $sAppend): string
    {
        if (!str_starts_with($sString, $sAppend)) {
            $sString = $sAppend . $sString;
        }

        return $sString;
    }

    /**
     * @param $sString
     * @param $sAppend
     *
     * @return mixed|string
     */
    public static function stripBeginString($sString, $sAppend): mixed
    {
        if (str_starts_with($sString, $sAppend)) {
            $sString = substr($sString, strlen($sAppend));
        }

        return $sString;
    }

    /**
     * @param string $sString
     * @param string $sAppend
     *
     * @return string
     */
    public static function stripEndString(string $sString, string $sAppend): string
    {
        if (str_ends_with($sString, $sAppend)) {
            $sString = substr($sString, 0, -1 * strlen($sAppend));
        }

        return $sString;
    }

    /**
     * @param string $sString
     * @param string $sAppend
     *
     * @return string
     */
    public static function trimStrings(string $sString, string $sAppend): string
    {
        return self::stripBeginString(self::stripEndString($sString, $sAppend), $sAppend);
    }

    /**
     * @param string $sHaystack
     * @param string $sNeedle
     *
     * @return bool
     */
    public static function stringEndsWith(string $sHaystack, string $sNeedle): bool
    {
        return str_ends_with($sHaystack, $sNeedle);
    }

    /**
     * @return string
     */
    public static function router(): string
    {
        return QuestionMarkRewrite::class;
    }

    /**
     * @param array $aArray
     *
     * @return bool
     */
    public static function arrayIsAssoc(array $aArray): bool
    {
        if (!is_array($aArray)) {
            throw new RuntimeException("\Flake\Util\Tools::arrayIsAssoc(): parameter has to be an array.");
        }

        # Taken from http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential#answer-4254008
        # count() will return 0 if numeric, and > 0 if assoc, even partially
        return (bool)count(array_filter(array_keys($aArray), '\is_string'));
    }

    /**
     * @param array $aArray
     *
     * @return bool
     */
    public static function arrayIsSeq(array $aArray): bool
    {
        return !self::arrayIsAssoc($aArray);
    }

    /**
     * @param string $sMessage
     *
     * @return void
     */
    public static function echoAndCutClient(string $sMessage = ''): void
    {
        ignore_user_abort(true);

        header('Connection: close');
        header('Content-Length: ' . strlen($sMessage));

        echo $sMessage;
        echo str_repeat("\r\n", 10); // just to be sure

        flush();
    }

    /**
     * @return int
     */
    public static function milliseconds(): int
    {
        return (int)(microtime(true) * 1000);
    }

    /**
     * @param string $sWhat
     *
     * @return void
     */
    public static function stopWatch(string $sWhat): void
    {
        $iStop = self::milliseconds();

        $trail = debug_backtrace();
        $aLastNode = $trail[0];    // l'appel qui nous intéresse
        $sFile = basename($aLastNode['file']);
        $iLine = (int)$aLastNode['line'];

        if (!array_key_exists('FLAKE_STOPWATCHES', $GLOBALS)) {
            $GLOBALS['FLAKE_STOPWATCHES'] = [];
        }

        if (array_key_exists($sWhat, $GLOBALS['FLAKE_STOPWATCHES'])) {
            $iTime = $iStop - $GLOBALS['FLAKE_STOPWATCHES'][$sWhat];
            echo "<h3 style='color: silver'><span style='display: inline-block; width: 400px;'>@" . $sFile . '+' . $iLine . ':</span>' . $sWhat . ':' . $iTime . ' ms</h1>';
            flush();
        } else {
            $GLOBALS['FLAKE_STOPWATCHES'][$sWhat] = $iStop;
        }
    }

    # Taken from http://www.php.net/manual/en/function.gzdecode.php#82930

    /**
     * @param string $data
     * @param string $filename
     * @param string $error
     * @param int    $maxlength
     *
     * @return false|string|null
     */
    public static function gzdecode(
        string $data,
        string &$filename = '',
        string &$error = '',
        int $maxlength = 0
    ): false|string|null {
        $len = strlen($data);
        if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            $error = 'Not in GZIP format.';

            return null;  // Not GZIP format (See RFC 1952)
        }
        $method = ord($data[2]);  // Compression method
        $flags = ord($data[3]);  // Flags
        if ($flags & $flags != 31) {
            $error = 'Reserved bits not allowed.';

            return null;
        }
        // NOTE: $mtime may be negative (PHP integer limitations)
        $mtime = unpack('V', substr($data, 4, 4));
        $mtime = $mtime[1];
        $xfl = $data[8];
        $os = $data[8];
        $headerlen = 10;
        $extralen = 0;
        $extra = '';
        if ($flags & 4) {
            // 2-byte length prefixed EXTRA data in header
            if ($len - $headerlen - 2 < 8) {
                return false;  // invalid
            }
            $extralen = unpack('v', substr($data, 8, 2));
            $extralen = $extralen[1];
            if ($len - $headerlen - 2 - $extralen < 8) {
                return false;  // invalid
            }
            $extra = substr($data, 10, $extralen);
            $headerlen += 2 + $extralen;
        }
        $filenamelen = 0;
        $filename = '';
        if ($flags & 8) {
            // C-style string
            if ($len - $headerlen - 1 < 8) {
                return false; // invalid
            }
            $filenamelen = strpos(substr($data, $headerlen), chr(0));
            if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                return false; // invalid
            }
            $filename = substr($data, $headerlen, $filenamelen);
            $headerlen += $filenamelen + 1;
        }
        $commentlen = 0;
        $comment = '';
        if ($flags & 16) {
            // C-style string COMMENT data in header
            if ($len - $headerlen - 1 < 8) {
                return false;    // invalid
            }
            $commentlen = strpos(substr($data, $headerlen), chr(0));
            if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                return false;    // Invalid header format
            }
            $comment = substr($data, $headerlen, $commentlen);
            $headerlen += $commentlen + 1;
        }
        $headercrc = '';
        if ($flags & 2) {
            // 2-bytes (lowest order) of CRC32 on header present
            if ($len - $headerlen - 2 < 8) {
                return false;    // invalid
            }
            $calccrc = crc32(substr($data, 0, $headerlen)) & 0xFFFF;
            $headercrc = unpack('v', substr($data, $headerlen, 2));
            $headercrc = $headercrc[1];
            if ($headercrc != $calccrc) {
                $error = 'Header checksum failed.';

                return false;    // Bad header CRC
            }
            $headerlen += 2;
        }
        // GZIP FOOTER
        $datacrc = unpack('V', substr($data, -8, 4));
        $datacrc = sprintf('%u', $datacrc[1] & 0xFFFFFFFF);
        $isize = unpack('V', substr($data, -4));
        $isize = $isize[1];
        // decompression:
        $bodylen = $len - $headerlen - 8;
        if ($bodylen < 1) {
            // IMPLEMENTATION BUG!
            return null;
        }
        $body = substr($data, $headerlen, $bodylen);
        $data = '';
        if ($bodylen > 0) {
            switch ($method) {
                case 8:
                    // Currently the only supported compression method:
                    $data = gzinflate($body, $maxlength);
                    break;
                default:
                    $error = 'Unknown compression method.';

                    return false;
            }
        }  // zero-byte body content is allowed
        // Verifiy CRC32
        $crc = sprintf('%u', crc32($data));
        $crcOK = $crc == $datacrc;
        $lenOK = $isize == strlen($data);
        if (!$lenOK || !$crcOK) {
            $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');

            return false;
        }

        return $data;
    }
}
