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

/**
 *
 */
class Profiler
{
    protected static float $TUSAGE;
    protected static float $RUSAGE;

    protected function __construct()
    {
        # Static class
    }

    /**
     * @return void
     */
    public static function start(): void
    {
        $dat = getrusage();
        self::$TUSAGE = microtime(true);
        self::$RUSAGE = $dat['ru_utime.tv_sec'] * 1e6 + $dat['ru_utime.tv_usec'];
    }

    /**
     * @return string
     */
    public static function cpuUsage(): string
    {
        $dat = getrusage();
        $tv_usec = (($dat['ru_utime.tv_sec'] * 1e6) + $dat['ru_utime.tv_usec']) - self::$RUSAGE;
        $time = (microtime(true) - self::$TUSAGE) * 1e6;

        // cpu per request
        if ($time > 0) {
            $cpu = number_format(($tv_usec / $time) * 100, 2);
        } else {
            $cpu = '0.00';
        }

        return $cpu;
    }

    /**
     * @return float
     */
    public static function cpuTime(): float
    {
        $dat = getrusage();
        $tv_usec = (($dat['ru_utime.tv_sec'] * 1e6) + $dat['ru_utime.tv_usec']) - self::$RUSAGE;
        $time = (microtime(true) - self::$TUSAGE) * 1e6;
        $cpuusage = ($tv_usec / $time);

        return round(($time / 1000) * $cpuusage);
    }
}
