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

namespace Flake\Core\Requester;

use Flake\Core\Collection;
use Flake\Core\Requester;
use ReflectionException;

/**
 *
 */
class Sql extends Requester
{
    protected string $sDataTable = '';
    protected array $aClauses = [];
    protected string $sModelClass = '';
    protected string $sOrderField = '';
    protected string $sOrderDirection = 'ASC';
    protected bool|int $iLimitStart = false;
    protected bool|int $iLimitNumber = false;
    protected bool $bHasBeenExecuted = false;

    /**
     * @param $sDataTable
     *
     * @return Sql
     */
    public function setDataTable($sDataTable): Sql
    {
        $this->sDataTable = $sDataTable;

        return $this;
    }

    /**
     * @param string $sField
     * @param string $sValue
     *
     * @return Sql
     */
    public function addClauseEquals(string $sField, string $sValue): Sql
    {
        $sWrap = "{field}='{value}'";
        $this->addClauseWrapped($sField, $sValue, $sWrap);

        return $this;
    }

    /**
     * @param string $sField
     * @param string $sValue
     * @param string $sWrap
     *
     * @return Sql
     */
    protected function addClauseWrapped(string $sField, string $sValue, string $sWrap): Sql
    {
        $sValue = $this->escapeSqlValue($sValue);
        $sClause = str_replace(
            [
                '{field}',
                '{value}',
            ],
            [
                $sField,
                $sValue,
            ],
            $sWrap
        );

        $this->addClauseLiteral($sClause);

        return $this;
    }

    /**
     * @param string $sClause
     *
     * @return Sql
     */
    public function addClauseLiteral(string $sClause): Sql
    {
        $this->aClauses[] = $sClause;

        return $this;
    }

    /**
     * @param string $sValue
     *
     * @return string
     */
    protected function escapeSqlValue(string $sValue): string
    {
        return $GLOBALS['DB']->quote(
            $sValue,
            $this->sDataTable
        );
    }

    /**
     * @param array $aData
     *
     * @return mixed
     */
    protected function reify(array $aData): mixed
    {
        $sTemp = $this->sModelClass;

        return new $sTemp(
            $aData[$sTemp::getPrimaryKey()]
        );    # To address 'Notice: Only variable references should be returned by reference'
    }

    /**
     * @param string $sFields
     *
     * @return string
     */
    public function getQuery(string $sFields = '*'): string
    {
        $sWhere = '1=1';
        $sOrderBy = '';
        $sLimit = '';

        if (!empty($this->aClauses)) {
            $sWhere = implode(' AND ', $this->aClauses);
        }

        if (trim($this->sOrderField) !== '') {
            $sOrderBy = $this->sOrderField . ' ' . $this->sOrderDirection;
        }

        if ($this->iLimitStart !== false) {
            if ($this->iLimitNumber !== false) {
                $sLimit = $this->iLimitStart . ', ' . $this->iLimitNumber;
            } else {
                $sLimit = $this->iLimitStart;
            }
        } elseif ($this->iLimitNumber !== false) {
            $sLimit = '0, ' . $this->iLimitNumber;
        }

        return $GLOBALS['DB']->SELECTquery(
            $sFields,
            $this->sDataTable,
            $sWhere,
            '',
            $sOrderBy,
            $sLimit
        );
    }

    /**
     * @return string
     */
    public function getCountQuery(): string
    {
        return $this->getQuery('count(*) as nbitems');
    }

    /**
     * @throws ReflectionException
     */
    public function execute(): Collection
    {
        $oCollection = new Collection();
        $sSql = $this->getQuery();

        $rSql = $GLOBALS['DB']->query($sSql);
        while (($aRs = $rSql->fetch()) !== false) {
            $oCollection->push(
                $this->reify($aRs)
            );
        }

        $this->bHasBeenExecuted = true;

        return $oCollection;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $sSql = $this->getCountQuery();

        $rSql = $GLOBALS['DB']->query($sSql);
        if (($aRs = $rSql->fetch()) !== false) {
            return (int)$aRs['nbitems'];
        }

        return 0;
    }
}
