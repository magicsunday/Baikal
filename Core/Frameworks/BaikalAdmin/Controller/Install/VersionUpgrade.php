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

namespace BaikalAdmin\Controller\Install;

use Baikal\Model\Config\Standard;
use Exception;
use Flake\Core\Controller;
use PDO;
use RuntimeException;
use Sabre\DAV\Xml\Property\Href;
use Sabre\VObject\Reader;
use Symfony\Component\Yaml\Yaml;

use function strlen;

class VersionUpgrade extends Controller
{
    protected array $aErrors = [];

    protected array $aSuccess = [];

    /**
     * @return void
     */
    public function execute(): void
    {
    }

    /**
     * @return string
     */
    public function render(): string
    {
        try {
            /** @var array $config */
            $config = Yaml::parseFile(PROJECT_PATH_CONFIG . 'baikal.yaml');
        } catch (Exception $exception) {
            error_log('Error reading baikal.yaml file : ' . $exception->getMessage());
        }

        $sBigIcon                 = 'glyph2x-magic';
        $sBaikalVersion           = BAIKAL_VERSION;
        $sBaikalConfiguredVersion = $config['system']['configured_version'];

        if ($config['system']['configured_version'] === BAIKAL_VERSION) {
            $sMessage = 'Your system is configured to use version <strong>' . $sBaikalConfiguredVersion . "</strong>.<br />There's no upgrade to be done.";
        } else {
            $sMessage = 'Upgrading Baïkal from version <strong>' . $sBaikalConfiguredVersion . '</strong> to version <strong>' . $sBaikalVersion . '</strong>';
        }

        $sHtml = <<<HTML
<header class="jumbotron subhead" id="overview">
	<h1><i class="{$sBigIcon}"></i>Baïkal upgrade wizard</h1>
	<p class="lead">{$sMessage}</p>
</header>
HTML;

        try {
            $bSuccess = $this->upgrade($config['database'], $config['system']['configured_version'], BAIKAL_VERSION);
        } catch (Exception $exception) {
            $bSuccess        = false;
            $this->aErrors[] = 'Uncaught exception during upgrade: ' . $exception;
        }

        if ($this->aErrors !== []) {
            $sHtml .= '<h3>Errors</h3>' . implode("<br />\n", $this->aErrors);
        }

        if ($this->aSuccess !== []) {
            $sHtml .= '<h3>Successful operations</h3>' . implode("<br />\n", $this->aSuccess);
        }

        if ($bSuccess) {
            $sHtml .= "<p>&nbsp;</p><p>Baïkal has been successfully upgraded. You may now <a class='btn btn-success' href='" . PROJECT_URI . "admin/'>Access the Baïkal admin</a></p>";
        } else {
            $sHtml .= "<p>&nbsp;</p><p><span class='label label-important'>Error</span> Baïkal has not been upgraded. See the section 'Errors' for details.</p>";
        }

        return $sHtml;
    }

    /**
     * @param string $databaseConfig
     * @param string $sVersionFrom
     * @param string $sVersionTo
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function upgrade(string $databaseConfig, string $sVersionFrom, string $sVersionTo): bool
    {
        if (version_compare($sVersionFrom, '0.2.3', '<=')) {
            throw new RuntimeException(
                'This version of Baikal does not support upgrading from version 0.2.3 and older. Please request help on Github if this is a problem.'
            );
        }

        $this->assertConfigWritable();

        $pdo = $GLOBALS['DB']->getPDO();
        if (version_compare($sVersionFrom, '0.3.0', '<')) {
            // Upgrading from sabre/dav 1.8 schema to 3.1 schema.

            if ($databaseConfig['mysql'] === true) {
                // MySQL upgrade

                // sabre/dav 2.0 changes
                foreach (
                    [
                        'calendar',
                        'addressbook',
                    ] as $dataType
                ) {
                    $tableName = $dataType . 's';
                    $pdo->exec(sprintf('ALTER TABLE %s ADD synctoken INT(11) UNSIGNED NOT NULL DEFAULT \'1\'', $tableName));
                    $this->aSuccess[] = 'synctoken was added to ' . $tableName;

                    $pdo->exec(sprintf('ALTER TABLE %s DROP ctag', $tableName));
                    $this->aSuccess[] = 'ctag was removed from ' . $tableName;

                    $changesTable = $dataType . 'changes';
                    $pdo->exec(
                        "
                        CREATE TABLE {$changesTable} (
                            id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                            uri VARCHAR(200) NOT NULL,
                            synctoken INT(11) UNSIGNED NOT NULL,
                            {$dataType}id INT(11) UNSIGNED NOT NULL,
                            operation TINYINT(1) NOT NULL,
                            INDEX {$dataType}id_synctoken ({$dataType}id, synctoken)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                    "
                    );
                    $this->aSuccess[] = $changesTable . ' was created';
                }

                $pdo->exec(
                    "
                    CREATE TABLE calendarsubscriptions (
                        id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                        uri VARCHAR(200) NOT NULL,
                        principaluri VARCHAR(100) NOT NULL,
                        source TEXT,
                        displayname VARCHAR(100),
                        refreshrate VARCHAR(10),
                        calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
                        calendarcolor VARCHAR(10),
                        striptodos TINYINT(1) NULL,
                        stripalarms TINYINT(1) NULL,
                        stripattachments TINYINT(1) NULL,
                        lastmodified INT(11) UNSIGNED,
                        UNIQUE(principaluri, uri)
                    );
                "
                );
                $this->aSuccess[] = 'calendarsubscriptions was created';

                $pdo->exec(
                    '
                    ALTER TABLE cards
                    ADD etag VARBINARY(32),
                    ADD size INT(11) UNSIGNED NOT NULL;
                '
                );
                $this->aSuccess[] = 'etag and size were added to cards';

                // sabre/dav 2.1 changes;
                $pdo->exec('ALTER TABLE calendarobjects ADD uid VARCHAR(200)');

                $this->aSuccess[] = 'uid was added to calendarobjects';

                $pdo->exec(
                    '
                    CREATE TABLE IF NOT EXISTS schedulingobjects
                    (
                        id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                        principaluri VARCHAR(255),
                        calendardata MEDIUMBLOB,
                        uri VARCHAR(200),
                        lastmodified INT(11) UNSIGNED,
                        etag VARCHAR(32),
                        size INT(11) UNSIGNED NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                '
                );

                $this->aSuccess[] = 'schedulingobjects was created';

                // sabre/dav 3.0 changes
                $pdo->exec(
                    '
                    CREATE TABLE propertystorage (
                        id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                        path VARBINARY(1024) NOT NULL,
                        name VARBINARY(100) NOT NULL,
                        valuetype INT UNSIGNED,
                        value MEDIUMBLOB
                    );
                '
                );
                $pdo->exec('CREATE UNIQUE INDEX path_property ON propertystorage (path(600), name(100));');
                $this->aSuccess[] = 'propertystorage was created';
            } else {
                // SQLite upgrade

                // sabre/dav 2.0 changes
                foreach (
                    [
                        'calendar',
                        'addressbook',
                    ] as $dataType
                ) {
                    $tableName = $dataType . 's';
                    // Note: we can't remove the ctag field in sqlite :(;
                    $pdo->exec(sprintf('ALTER TABLE %s ADD synctoken integer', $tableName));
                    $this->aSuccess[] = 'synctoken was added to ' . $tableName;

                    $changesTable = $dataType . 'changes';
                    $pdo->exec(
                        "
                        CREATE TABLE {$changesTable} (
                            id integer primary key asc,
                            uri text,
                            synctoken integer,
                            {$dataType}id integer,
                            operation bool
                        );
                    "
                    );
                    $this->aSuccess[] = $changesTable . ' was created';
                }

                $pdo->exec(
                    '
                    CREATE TABLE calendarsubscriptions (
                        id integer primary key asc,
                        uri text,
                        principaluri text,
                        source text,
                        displayname text,
                        refreshrate text,
                        calendarorder integer,
                        calendarcolor text,
                        striptodos bool,
                        stripalarms bool,
                        stripattachments bool,
                        lastmodified int
                    );
                '
                );
                $this->aSuccess[] = 'calendarsubscriptions was created';
                $pdo->exec('CREATE INDEX principaluri_uri ON calendarsubscriptions (principaluri, uri);');

                $pdo->exec(
                    '
                    ALTER TABLE cards ADD etag text;
                    ALTER TABLE cards ADD size integer;
                '
                );
                $this->aSuccess[] = 'etag and size were added to cards';

                // sabre/dav 2.1 changes;
                $pdo->exec('ALTER TABLE calendarobjects ADD uid TEXT');
                $this->aSuccess[] = 'uid was added to calendarobjects';

                $pdo->exec(
                    '
                    CREATE TABLE IF NOT EXISTS schedulingobjects (
                        id integer primary key asc,
                        principaluri text,
                        calendardata blob,
                        uri text,
                        lastmodified integer,
                        etag text,
                        size integer
                    )
                '
                );
                $this->aSuccess[] = 'schedulingobjects was created';

                // sabre/dav 3.0 changes
                $pdo->exec(
                    '
                    CREATE TABLE propertystorage (
                        id integer primary key asc,
                        path text,
                        name text,
                        valuetype integer,
                        value blob
                    );
                '
                );
                $pdo->exec('CREATE UNIQUE INDEX path_property ON propertystorage (path, name);');
                $this->aSuccess[] = 'propertystorage was created';
            }

            // Statements for both SQLite and MySQL
            $result = $pdo->query('SELECT id, carddata FROM cards');
            $stmt   = $pdo->prepare('UPDATE cards SET etag = ?, size = ? WHERE id = ?');
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stmt->execute([
                    md5((string) $row['carddata']),
                    strlen((string) $row['carddata']),
                    $row['id'],
                ]);
            }

            $this->aSuccess[] = 'etag and size was recalculated for cards';
            $result           = $pdo->query('SELECT id, calendardata FROM calendarobjects');
            $stmt             = $pdo->prepare('UPDATE calendarobjects SET uid = ? WHERE id = ?');

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                try {
                    $vobj = Reader::read($row['calendardata']);
                } catch (Exception $e) {
                    $this->aSuccess[] = 'warning: skipped record ' . $row['id'] . '. Error: ' . $e->getMessage();
                    continue;
                }

                $item = $vobj->getBaseComponent();
                if (!isset($item->UID)) {
                    $vobj->destroy();
                    continue;
                }

                $uid = (string) $item->UID;
                $stmt->execute(
                    [
                        $uid,
                        $row['id'],
                    ]
                );

                $vobj->destroy();
            }

            $this->aSuccess[] = 'uid was recalculated for calendarobjects';

            $result = $pdo->query('SELECT id, uri, vcardurl FROM principals WHERE vcardurl IS NOT NULL');
            $stmt1  = $pdo->prepare('INSERT INTO propertystorage (path, name, valuetype, value) VALUES (?, ?, 3, ?)');

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                // Inserting the new record
                $stmt1->execute([
                    'addressbooks/' . basename((string) $row['uri']),
                    '{http://calendarserver.org/ns/}me-card',
                    serialize(new Href($row['vcardurl'])),
                ]);
            }

            $this->aSuccess[] = 'vcardurl was migrated to the propertystorage system';
        }

        // The sqlite schema had issues with both the calendar and
        // addressbooks tables. The tables didn't have a DEFAULT '1' for
        // the synctoken column. So we're adding it now.
        if (version_compare($sVersionFrom, '0.4.0', '<') && $databaseConfig['mysql'] === false) {
            $pdo->exec('UPDATE calendars SET synctoken = 1 WHERE synctoken IS NULL');
            $tmpTable = '_' . time();
            $pdo->exec('ALTER TABLE calendars RENAME TO calendars' . $tmpTable);
            $pdo->exec(
                '
CREATE TABLE calendars (
    id integer primary key asc NOT NULL,
    principaluri text NOT NULL,
    displayname text,
    uri text NOT NULL,
    synctoken integer DEFAULT 1 NOT NULL,
    description text,
    calendarorder integer,
    calendarcolor text,
    timezone text,
    components text NOT NULL,
    transparent bool
);'
            );
            $pdo->exec(
                'INSERT INTO calendars SELECT id, principaluri, displayname, uri, synctoken, description, calendarorder, calendarcolor, timezone, components, transparent FROM calendars' . $tmpTable
            );
            $this->aSuccess[] = 'Updated calendars table';
        }

        // Similar to upgrading from older than 0.4.5, there were still
        // issues with a missing DEFAULT 1 for sthe synctoken field in the
        // addressbook.
        if (version_compare($sVersionFrom, '0.4.5', '<=') && $databaseConfig['mysql'] === false) {
            $pdo->exec('UPDATE addressbooks SET synctoken = 1 WHERE synctoken IS NULL');
            $tmpTable = '_' . time();
            $pdo->exec('ALTER TABLE addressbooks RENAME TO addressbooks' . $tmpTable);
            $pdo->exec(
                '
CREATE TABLE addressbooks (
    id integer primary key asc NOT NULL,
    principaluri text NOT NULL,
    displayname text,
    uri text NOT NULL,
    description text,
    synctoken integer DEFAULT 1 NOT NULL
);
                '
            );
            $pdo->exec(
                'INSERT INTO addressbooks SELECT id, principaluri, displayname, uri, description, synctoken FROM addressbooks' . $tmpTable
            );
            $this->aSuccess[] = 'Updated addressbooks table';
        }

        if (version_compare($sVersionFrom, '0.5.1', '<')) {
            if ($databaseConfig['mysql'] !== false) { // mysql
                $pdo->exec(
                    <<<SQL
CREATE TABLE calendarinstances (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendarid INTEGER UNSIGNED NOT NULL,
    principaluri VARBINARY(100),
    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
    displayname VARCHAR(100),
    uri VARBINARY(200),
    description TEXT,
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    timezone TEXT,
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    share_href VARBINARY(100),
    share_displayname VARCHAR(100),
    share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
    UNIQUE(principaluri, uri),
    UNIQUE(calendarid, principaluri),
    UNIQUE(calendarid, share_href)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
                );
                $this->aSuccess[] = 'Created calendarinstances table';
                $pdo->exec(
                    '
INSERT INTO calendarinstances
(
calendarid,
principaluri,
access,
displayname,
uri,
description,
calendarorder,
calendarcolor,
transparent
)
SELECT
id,
principaluri,
1,
displayname,
uri,
description,
calendarorder,
calendarcolor,
transparent
FROM calendars
'
                );
                $this->aSuccess[] = 'Migrated calendarinstances table';
                $calendarBackup   = 'calendars_3_1';
                $pdo->exec('RENAME TABLE calendars TO ' . $calendarBackup);
                $this->aSuccess[] = 'Did calendars backup';

                $pdo->exec(
                    <<<SQL
CREATE TABLE calendars (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
    components VARBINARY(21)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL
                );
                $this->aSuccess[] = 'Created new calendars table';
            } else {
                $pdo->exec(
                    <<<SQL
CREATE TABLE calendarinstances (
    id integer primary key asc NOT NULL,
    calendarid integer,
    principaluri text,
    access integer COMMENT '1 = owner, 2 = read, 3 = readwrite' NOT NULL DEFAULT '1',
    displayname text,
    uri text NOT NULL,
    description text,
    calendarorder integer,
    calendarcolor text,
    timezone text,
    transparent bool,
    share_href text,
    share_displayname text,
    share_invitestatus integer DEFAULT '2',
    UNIQUE (principaluri, uri),
    UNIQUE (calendarid, principaluri),
    UNIQUE (calendarid, share_href)
);
SQL
                );
                $this->aSuccess[] = 'Created calendarinstances table';
                $pdo->exec(
                    '
INSERT INTO calendarinstances
(
calendarid,
principaluri,
access,
displayname,
uri,
description,
calendarorder,
calendarcolor,
transparent
)
SELECT
id,
principaluri,
1,
displayname,
uri,
description,
calendarorder,
calendarcolor,
transparent
FROM calendars
'
                );
                $this->aSuccess[] = 'Migrated calendarinstances table';
                $calendarBackup   = 'calendars_3_1';
                $pdo->exec('ALTER TABLE calendars RENAME TO ' . $calendarBackup);
                $this->aSuccess[] = 'Did calendars backup';

                $pdo->exec(
                    <<<SQL
CREATE TABLE calendars (
    id integer primary key asc NOT NULL,
    synctoken integer DEFAULT 1 NOT NULL,
    components text NOT NULL
);
SQL
                );
                $this->aSuccess[] = 'Created new calendars table';
            }

            $pdo->exec(
                <<<SQL
INSERT INTO calendars (id, synctoken, components) SELECT id, COALESCE(synctoken,1) as synctoken, COALESCE(components,"VEVENT,VTODO,VJOURNAL") as components FROM {$calendarBackup}
SQL
            );
            $this->aSuccess[] = 'Migrated calendars table';
        }

        if (version_compare($sVersionFrom, '0.9.4', '<')) {
            $pdo->exec('UPDATE calendarinstances SET access = 1 WHERE access IS NULL');
            $pdo->exec('UPDATE calendarinstances SET share_invitestatus = 2 WHERE share_invitestatus IS NULL');
            $this->aSuccess[] = 'Updated default values in calendarinstances table';
        }

        $this->updateConfiguredVersion($sVersionTo);

        return true;
    }

    /**
     * @param string $sVersionTo
     *
     * @return void
     *
     * @throws Exception
     */
    protected function updateConfiguredVersion(string $sVersionTo): void
    {
        // Update BAIKAL_CONFIGURED_VERSION
        $oConfig = new Standard();
        $oConfig->set('configured_version', $sVersionTo);
        $oConfig->persist();
    }

    /**
     * @return void
     */
    protected function assertConfigWritable(): void
    {
        // Parsing the config also makes sure that it is not malformed
        $oConfig = new Standard();
        if ($oConfig->writable() === false) {
            throw new RuntimeException(PROJECT_PATH_CONFIG . 'baikal.yaml is not writable');
        }
    }
}
