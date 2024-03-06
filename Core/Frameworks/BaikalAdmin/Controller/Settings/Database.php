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
//  http://sabre.io/baikal
//
//  This script is part of the Baïkal Server project. The Baïkal
//  Server project is free software; you can redistribute it
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

namespace BaikalAdmin\Controller\Settings;

use Baikal\Core\Tools;
use Exception;
use Flake\Core\Controller;
use Flake\Core\Database\Mysql;
use Flake\Core\Database\Sqlite;
use Formal\Core\Message;
use Formal\Form;
use Formal\Form\Morphology;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function dirname;

class Database extends Controller
{
    /**
     * @var Form
     */
    private Form $oForm;

    /**
     * @return void
     *
     * @throws ReflectionException
     */
    public function execute(): void
    {
        $oModel = new \Baikal\Model\Config\Database();

        // Assert that config file is writable
        if (!$oModel->writable()) {
            throw new RuntimeException('Config file is not writable;' . __FILE__ . ' > ' . __LINE__);
        }

        $this->oForm = $oModel->formForThisModelInstance([
            'close'           => false,
            'hook.morphology' => [
                $this,
                'morphologyHook',
            ],
            'hook.validation' => [
                $this,
                'validationHook',
            ],
        ]);

        if ($this->oForm->submitted()) {
            $this->oForm->execute();
        }
    }

    /**
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(): string
    {
        $oView = new \BaikalAdmin\View\Settings\Database();
        $oView->setData(
            'message',
            Message::notice(
                'Do not change anything on this page unless you really know what you are doing.<br />You might break Baïkal if you misconfigure something here.',
                'Warning !',
                false
            )
        );

        $oView->setData('form', $this->oForm->render());

        return $oView->render();
    }

    /**
     * @param Form       $oForm
     * @param Morphology $oMorpho
     *
     * @return void
     */
    public function morphologyHook(Form $oForm, Morphology $oMorpho): void
    {
        if ($oForm->submitted()) {
            $bMySQL = ((int) $oForm->postValue('mysql') === 1);
        } else {
            try {
                $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
            } catch (Exception $e) {
                error_log('Error reading baikal.yaml file : ' . $e->getMessage());
            }
            $bMySQL = $config['database']['mysql'] ?? true;
        }

        if ($bMySQL === true) {
            $oMorpho->remove('sqlite_file');
        } else {
            $oMorpho->remove('mysql_host');
            $oMorpho->remove('mysql_dbname');
            $oMorpho->remove('mysql_username');
            $oMorpho->remove('mysql_password');
        }
    }

    /**
     * @param Form       $oForm
     * @param Morphology $oMorpho
     *
     * @return true|void
     */
    public function validationHook(Form $oForm, Morphology $oMorpho)
    {
        if ($oForm->refreshed()) {
            return true;
        }
        if ((int) $oForm->modelInstance()->get('mysql') === 1) {
            // We have to check the MySQL connection
            $sHost     = $oForm->modelInstance()->get('mysql_host');
            $sDbName   = $oForm->modelInstance()->get('mysql_dbname');
            $sUsername = $oForm->modelInstance()->get('mysql_username');
            $sPassword = $oForm->modelInstance()->get('mysql_password');

            try {
                $oDB = new Mysql(
                    $sHost,
                    $sDbName,
                    $sUsername,
                    $sPassword
                );
            } catch (Exception $e) {
                $sMessage = '<strong>MySQL error:</strong> ' . $e->getMessage();
                $sMessage .= '<br /><strong>Nothing has been saved</strong>';
                $oForm->declareError($oMorpho->element('mysql_host'), $sMessage);
                $oForm->declareError($oMorpho->element('mysql_dbname'));
                $oForm->declareError($oMorpho->element('mysql_username'));
                $oForm->declareError($oMorpho->element('mysql_password'));

                return;
            }

            if (($aMissingTables = Tools::isDBStructurallyComplete($oDB)) !== true) {
                $sMessage = '<strong>MySQL error:</strong> These tables, required by Baïkal, are missing: <strong>' . implode(
                    ', ',
                    $aMissingTables
                ) . '</strong><br />';
                $sMessage .= 'You may want create these tables using the file <strong>Core/Resources/Db/MySQL/db.sql</strong>';
                $sMessage .= '<br /><br /><strong>Nothing has been saved</strong>';

                $oForm->declareError($oMorpho->element('mysql'), $sMessage);
            }
        } else {
            $sFile = $oMorpho->element('sqlite_file')->value();

            try {
                // Asserting DB file is writable
                if (file_exists($sFile) && !is_writable($sFile)) {
                    $sMessage = "DB file is not writable. Please give write permissions on file <span style='font-family: monospace'>" . $sFile . '</span>';
                    $oForm->declareError($oMorpho->element('sqlite_file'), $sMessage);

                    return;
                }
                // Asserting DB directory is writable
                if (!is_writable(dirname($sFile))) {
                    $sMessage = "The <em>FOLDER</em> containing the DB file is not writable, and it has to.<br />Please give write permissions on folder <span style='font-family: monospace'>" . dirname(
                        $sFile
                    ) . '</span>';
                    $oForm->declareError($oMorpho->element('sqlite_file'), $sMessage);

                    return;
                }

                $oDb = new Sqlite(
                    $sFile
                );

                if (($aMissingTables = Tools::isDBStructurallyComplete($oDb)) !== true) {
                    $sMessage = '<br /><p><strong>Database is not structurally complete.</strong></p>';
                    $sMessage .= '<p>Missing tables are: <strong>' . implode(
                        '</strong>, <strong>',
                        $aMissingTables
                    ) . '</strong></p>';
                    $sMessage .= '<p>You will find the SQL definition of Baïkal tables in this file: <strong>Core/Resources/Db/SQLite/db.sql</strong></p>';
                    $sMessage .= '<br /><p>Nothing has been saved. <strong>Please, add these tables to the database before pursuing Baïkal initialization.</strong></p>';

                    $oForm->declareError(
                        $oMorpho->element('sqlite_file'),
                        $sMessage
                    );
                }

                return;
            } catch (Exception $e) {
                $oForm->declareError(
                    $oMorpho->element('sqlite_file'),
                    'Baïkal was not able to establish a connexion to the SQLite database as configured.<br />SQLite says: ' . $e->getMessage(
                    ) . $e
                );
            }
        }
    }
}
