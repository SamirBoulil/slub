<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ConnectionFactory
{
    public static function create(string $databaseUrl): Connection
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = ['url' => $databaseUrl];
        $sqlConnection = DriverManager::getConnection($connectionParams, $config);

        return $sqlConnection;
    }
}
