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

namespace Flake\Core;

/**
 *
 */
abstract class Requester extends FLObject
{
    protected string $sModelClass = '';
    protected string $sOrderField = '';
    protected string $sOrderDirection = 'ASC';
    protected bool|int $iLimitStart = false;
    protected bool|int $iLimitNumber = false;

    /**
     * @param string $sModelClass
     */
    public function __construct(string $sModelClass)
    {
        $this->sModelClass = $sModelClass;
    }

    /**
     * @param string $sField
     * @param string $sValue
     *
     * @return $this
     */
    protected function addClause(string $sField, string $sValue): Requester
    {
        $this->addClauseEquals($sField, $sValue);

        return $this;
    }

    /**
     * @param int      $iStart
     * @param bool|int $iNumber
     *
     * @return $this
     */
    public function limit(int $iStart, bool|int $iNumber = false): Requester
    {
        if ($iNumber !== false) {
            return $this->setLimitStart($iStart)->setLimitNumber($iNumber);
        }

        return $this->setLimitStart($iStart);
    }

    /**
     * @param string $sOrderField
     * @param string $sOrderDirection
     *
     * @return $this
     */
    public function orderBy(string $sOrderField, string $sOrderDirection = 'ASC'): Requester
    {
        $this->sOrderField = $sOrderField;
        $this->sOrderDirection = $sOrderDirection;

        return $this;
    }

    /**
     * @param int $iLimitStart
     *
     * @return $this
     */
    public function setLimitStart(int $iLimitStart): Requester
    {
        $this->iLimitStart = $iLimitStart;

        return $this;
    }

    /**
     * @param int $iLimitNumber
     *
     * @return $this
     */
    public function setLimitNumber(int $iLimitNumber): Requester
    {
        $this->iLimitNumber = $iLimitNumber;

        return $this;
    }

    /**
     * @param string $sField
     * @param string $sValue
     *
     * @return mixed
     */
    abstract public function addClauseEquals(string $sField, string $sValue);

    /**
     * @return mixed
     */
    abstract public function execute(): mixed;

    /**
     * @return int
     */
    abstract public function count(): int;
}
