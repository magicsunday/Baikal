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
//  http://flake.codr.fr
//
//  This script is part of the Flake project. The Flake
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

namespace Flake\Core\Model;

use Exception;
use Flake\Core\Database;
use Flake\Core\Model;
use Flake\Core\Requester\Sql;
use RuntimeException;

abstract class Db extends Model
{
    /**
     * @var bool
     */
    protected bool $bFloating = true;

    /**
     * @param false|string|int $sPrimary
     *
     * @throws Exception
     */
    public function __construct(false|string|int $sPrimary = false)
    {
        if ($sPrimary !== false) {
            $this->initByPrimary($sPrimary);
            $this->bFloating = false;
        } else {
            // Object will be floating
            $this->initFloating();
            $this->bFloating = true;
        }
    }

    /**
     * @return Sql
     */
    public static function getBaseRequester(): Sql
    {
        $oRequester = new Sql(static::class);
        $oRequester->setDataTable(self::getDataTable());

        return $oRequester;
    }

    /**
     * @return string
     */
    public static function getDataTable(): string
    {
        $sClass = static::class;

        return $sClass::DATATABLE;
    }

    /**
     * @return string
     */
    public static function getPrimaryKey(): string
    {
        $sClass = static::class;

        return $sClass::PRIMARYKEY;
    }

    /**
     * @return string|int
     */
    public function getPrimary(): string|int
    {
        return $this->get(self::getPrimaryKey());
    }

    /**
     * @param string|int $sPrimary
     *
     * @return void
     *
     * @throws Exception
     */
    protected function initByPrimary(string|int $sPrimary): void
    {
        /** @var Database $db */
        $db = $GLOBALS['DB'];

        $rSql = $db->exec_SELECTquery(
            '*',
            self::getDataTable(),
            self::getPrimaryKey() . "='" . $db->quote((string) $sPrimary) . "'"
        );

        if (($aRs = $rSql->fetch()) === false) {
            throw new RuntimeException(
                "\Flake\Core\Model '" . htmlspecialchars($sPrimary) . "' not found for model " . static::class
            );
        }

        reset($aRs);
        $this->aData = $aRs;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function persist(): void
    {
        /** @var Database $db */
        $db = $GLOBALS['DB'];

        if ($this->floating()) {
            $db->exec_INSERTquery(
                self::getDataTable(),
                $this->getData()
            );

            $sPrimary = $db->lastInsertId();
            $this->initByPrimary($sPrimary);
            $this->bFloating = false;
        } else {
            $db->exec_UPDATEquery(
                self::getDataTable(),
                self::getPrimaryKey() . "='" . $db->quote((string) $this->getPrimary()) . "'",
                $this->getData()
            );
        }
    }

    /**
     * @return void
     */
    public function destroy(): void
    {
        /** @var Database $db */
        $db = $GLOBALS['DB'];

        $db->exec_DELETEquery(
            self::getDataTable(),
            self::getPrimaryKey() . "='" . $db->quote((string) $this->getPrimary()) . "'"
        );
    }

    /**
     * @return void
     */
    protected function initFloating(): void
    {
        // nothing; object will be blank
    }

    /**
     * @return bool
     */
    public function floating(): bool
    {
        return $this->bFloating;
    }
}
