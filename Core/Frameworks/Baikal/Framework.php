<?php

declare(strict_types=1);

#################################################################
#  Copyright notice
#
#  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://sabre.io/baikal
#
#  This script is part of the Baïkal Server project. The Baïkal
#  Server project is free software; you can redistribute it
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

namespace Baikal;

use Baikal\Core\Tools;
use ErrorException;
use ReflectionException;
use Symfony\Component\Yaml\Yaml;

use function define;
use function defined;

/**
 *
 */
class Framework extends \Flake\Core\Framework
{
    /**
     * @return void
     */
    public static function installTool(): void
    {
        if (defined('BAIKAL_CONTEXT_INSTALL') && BAIKAL_CONTEXT_INSTALL === true) {
            # Install tool has been launched and we're already on the install page
            return;
        }

# Install tool has been launched; redirecting user
        $sInstallToolUrl = PROJECT_URI . 'admin/install/';
        header('Location: ' . $sInstallToolUrl);
        exit(0);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public static function bootstrap(): void
    {
        # Registering Baikal classloader
        define('BAIKAL_PATH_FRAMEWORKROOT', __DIR__ . '/');

        Tools::assertEnvironmentIsOk();
        Tools::configureEnvironment();

        # Check that a config file exists
        if (file_exists(PROJECT_PATH_CONFIG . 'baikal.yaml')) {
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
            date_default_timezone_set($config['system']['timezone']);

            # Check that Baïkal is already configured
            if (isset($config['system']['configured_version'])) {
                # Check that running version matches configured version
                if (version_compare(BAIKAL_VERSION, $config['system']['configured_version']) > 0) {
                    self::installTool();
                } else {
                    # Check that admin password is set
                    if (!$config['system']['admin_passwordhash']) {
                        self::installTool();
                    }

                    Tools::assertBaikalIsOk();

                    set_error_handler("\Baikal\Framework::exception_error_handler");
                }
            } else {
                self::installTool();
            }
        } else {
            self::installTool();
        }
    }

    # Mapping PHP errors to exceptions; needed by SabreDAV

    /**
     * @throws ErrorException
     */
    public static function exception_error_handler($errno, $errstr, $errfile, $errline): void
    {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
