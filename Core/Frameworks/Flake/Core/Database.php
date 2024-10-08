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

namespace Flake\Core;

use Flake\Core\Database\Statement;
use PDO;
use RuntimeException;

use function in_array;
use function is_array;
use function is_string;

abstract class Database
{
    /**
     * @var bool
     */
    protected bool $debugOutput = false;

    /**
     * @var string
     */
    protected string $debug_lastBuiltQuery = '';

    /**
     * @var bool
     */
    protected bool $store_lastBuiltQuery = false;

    /**
     * @var PDO|null
     */
    protected ?PDO $oDb = null;

    /**
     * @param string            $table
     * @param array             $fields_values
     * @param array|bool|string $no_quote_fields
     *
     * @return Statement
     */
    public function exec_INSERTquery(
        string $table,
        array $fields_values,
        array|bool|string $no_quote_fields = false
    ): Statement {
        return $this->query($this->INSERTquery($table, $fields_values, $no_quote_fields));
    }

    /**
     * @param string            $table
     * @param array             $fields_values
     * @param array|bool|string $no_quote_fields
     *
     * @return string|void
     */
    public function INSERTquery(string $table, array $fields_values, array|bool|string $no_quote_fields = false)
    {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function (contrary to values in the arrays which may be insecure).
        if ($fields_values !== []) {
            // quote and escape values
            $fields_values = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);

            // Build query:
            $query = 'INSERT INTO ' . $table . '
				(
					' . implode(
                ',
					',
                array_keys($fields_values)
            ) . '
				) VALUES (
					' . implode(
                ',
					',
                $fields_values
            ) . '
				)';

            // Return query:
            if ($this->debugOutput || $this->store_lastBuiltQuery) {
                $this->debug_lastBuiltQuery = $query;
            }

            return $query;
        }
    }

    /**
     * @param string            $table
     * @param string            $where
     * @param array             $fields_values
     * @param array|bool|string $no_quote_fields
     *
     * @return Statement
     */
    public function exec_UPDATEquery(
        string $table,
        string $where,
        array $fields_values,
        array|bool|string $no_quote_fields = false
    ): Statement {
        return $this->query($this->UPDATEquery($table, $where, $fields_values, $no_quote_fields));
    }

    /**
     * @param string            $table
     * @param string            $where
     * @param array             $fields_values
     * @param array|bool|string $no_quote_fields
     *
     * @return string|void
     */
    public function UPDATEquery(
        string $table,
        string $where,
        array $fields_values,
        array|bool|string $no_quote_fields = false
    ) {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function (contrary to values in the arrays which may be insecure).
        if ($fields_values !== []) {
            // quote and escape values
            $nArr = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);

            $fields = [];
            foreach ($nArr as $k => $v) {
                $fields[] = $k . '=' . $v;
            }

            // Build query:
            $query = 'UPDATE ' . $table . '
                SET
                    ' . implode(
                ',
                    ',
                $fields
            ) .
                ($where !== '' ? '
                WHERE
                    ' . $where : '');

            // Return query:
            if ($this->debugOutput || $this->store_lastBuiltQuery) {
                $this->debug_lastBuiltQuery = $query;
            }

            return $query;
        }
    }

    /**
     * @param string $table
     * @param string $where
     *
     * @return Statement
     */
    public function exec_DELETEquery(string $table, string $where): Statement
    {
        return $this->query($this->DELETEquery($table, $where));
    }

    /**
     * @param string $table
     * @param string $where
     *
     * @return string
     */
    public function DELETEquery(string $table, string $where): string
    {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function
        $query = 'DELETE FROM ' . $table .
            ($where !== '' ? '
            WHERE
                ' . $where : '');

        if ($this->debugOutput || $this->store_lastBuiltQuery) {
            $this->debug_lastBuiltQuery = $query;
        }

        return $query;
    }

    /**
     * @param string $select_fields
     * @param string $from_table
     * @param string $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return Statement
     */
    public function exec_SELECTquery(
        string $select_fields,
        string $from_table,
        string $where_clause,
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): Statement {
        return $this->query($this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit));
    }

    /**
     * @param string $select_fields
     * @param string $from_table
     * @param string $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return string
     */
    public function SELECTquery(
        string $select_fields,
        string $from_table,
        string $where_clause,
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): string {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function
        // Build basic query:
        $query = 'SELECT ' . $select_fields . '
			FROM ' . $from_table .
            ($where_clause !== '' ? '
			WHERE
				' . $where_clause : '');

        // Group by:
        if ($groupBy !== '') {
            $query .= '
			GROUP BY ' . $groupBy;
        }

        // Order by:
        if ($orderBy !== '') {
            $query .= '
			ORDER BY ' . $orderBy;
        }

        // Group by:
        if ($limit !== '') {
            $query .= '
			LIMIT ' . $limit;
        }

        // Return query:
        if ($this->debugOutput || $this->store_lastBuiltQuery) {
            $this->debug_lastBuiltQuery = $query;
        }

        return $query;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function fullQuote(string $str): string
    {
        return "'" . $this->quote($str) . "'";
    }

    /**
     * @param array             $arr
     * @param string            $table
     * @param bool|string|array $noQuote
     *
     * @return array
     */
    public function fullQuoteArray(array $arr, string $table, bool|string|array $noQuote = false): array
    {
        if (is_string($noQuote)) {
            $noQuote = explode(',', $noQuote);
        } elseif (!is_array($noQuote)) {    // sanity check
            $noQuote = false;
        }

        foreach ($arr as $k => $v) {
            if ($noQuote === false || !in_array($k, $noQuote, true)) {
                $arr[$k] = $v === null ? 'NULL' : $this->fullQuote((string) $v);
            }
        }

        return $arr;
    }

    /* Should be abstract, but we provide a body anyway as PDO abstracts these methods for us */

    /**
     * @param string $sSql
     *
     * @return Statement
     */
    public function query(string $sSql): Statement
    {
        if (($stmt = $this->oDb->query($sSql)) === false) {
            $sMessage = print_r($this->oDb->errorInfo(), true);
            throw new RuntimeException("SQL ERROR in: '" . $sSql . "'; Message: " . $sMessage);
        }

        return new Statement($stmt);
    }

    /**
     * @return false|string
     */
    public function lastInsertId(): false|string
    {
        return $this->oDb->lastInsertId();
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function quote(string $str): string
    {
        return substr($this->oDb->quote($str), 1, -1);    // stripping first and last quote
    }

    /**
     * @return PDO|null
     */
    public function getPDO(): ?PDO
    {
        return $this->oDb;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->oDb = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return array
     */
    abstract public function tables(): array;
}
