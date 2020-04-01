<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ConnectionFactory
{
    public static function create(string $databaseUrl): Connection
    {
        $config = new Configuration();
        $connectionParams = ['url' => $databaseUrl];
        $sqlConnection = DriverManager::getConnection($connectionParams, $config);

        return $sqlConnection;
    }
}
