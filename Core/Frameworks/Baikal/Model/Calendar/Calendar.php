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

namespace Baikal\Model\Calendar;

use Flake\Core\Model\Db;
use RuntimeException;

class Calendar extends Db
{
    public const DATATABLE = 'calendars';

    public const PRIMARYKEY = 'id';

    public const LABELFIELD = 'components';

    protected array $aData = [
        'components' => '',
    ];

    /**
     * @return bool|null
     */
    public function hasInstances(): ?bool
    {
        $rSql = $GLOBALS['DB']->exec_SELECTquery(
            'count(*)',
            'calendarinstances',
            'calendarid=\'' . $this->aData['id'] . "'"
        );

        if (($aRs = $rSql->fetch()) === false) {
            return false;
        }

        reset($aRs);

        return $aRs['count(*)'] > 1;
    }

    /**
     * @return void
     *
     * @throws RuntimeException
     */
    public function destroy(): void
    {
        if ($this->hasInstances()) {
            throw new RuntimeException('Trying to destroy a calendar with instances');
        }

        parent::destroy();
    }
}
