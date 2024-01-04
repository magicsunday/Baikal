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

use Flake\Util\Router\QuestionMarkRewrite;
use ReflectionClass;
use ReflectionException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

use function array_key_exists;
use function count;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function strlen;

/**
 *
 */
class Tools
{
    private function __construct()
    {
        // private constructor to force static class
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
     * @param string $sEmail
     *
     * @return bool
     */
    public static function validEmail(string $sEmail): bool
    {
        return (filter_var($sEmail, FILTER_VALIDATE_EMAIL) !== false);
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
        # Taken from http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential#answer-4254008
        # count() will return 0 if numeric, and > 0 if assoc, even partially
        return (bool) count(array_filter(array_keys($aArray), '\is_string'));
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
}
