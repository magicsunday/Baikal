<?php

declare(strict_types=1);

namespace Baikal\Core;

use PDO;
use Sabre\DAV\Auth\Backend\AbstractBasic;

use function count;

/**
 * This is an authentication backend that uses a database to manage passwords.
 *
 * Format of the database tables must match to the one of \Sabre\DAV\Auth\Backend\PDO
 *
 * @copyright Copyright (C) 2013 Lukasz Janyst. All rights reserved.
 * @author    Lukasz Janyst <ljanyst@buggybrain.net>
 * @license   http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class PDOBasicAuth extends AbstractBasic
{
    /**
     * Reference to PDO connection.
     *
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * PDO table name we'll be using.
     *
     * @var string
     */
    protected string $tableName;

    /**
     * Authentication realm.
     *
     * @var string
     */
    protected string $authRealm;

    /**
     * @var string
     */
    private string $currentUser;

    /**
     * Creates the backend object.
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     *
     * @param PDO    $pdo
     * @param        $authRealm
     * @param string $tableName The PDO table name to use
     */
    public function __construct(PDO $pdo, $authRealm, string $tableName = 'users')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->authRealm = $authRealm;
    }

    /**
     * Validates a username and password.
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function validateUserPass($username, $password): bool
    {
        $stmt = $this->pdo->prepare('SELECT username, digesta1 FROM ' . $this->tableName . ' WHERE username = ?');
        $stmt->execute([$username]);
        $result = $stmt->fetchAll();

        if (!count($result)) {
            return false;
        }

        $hash = md5($username . ':' . $this->authRealm . ':' . $password);
        if ($result[0]['digesta1'] === $hash) {
            $this->currentUser = $username;

            return true;
        }

        return false;
    }
}
