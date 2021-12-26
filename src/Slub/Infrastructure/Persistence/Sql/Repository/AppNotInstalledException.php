<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class AppNotInstalledException extends \RuntimeException
{
    public static function onRepository(string $repositoryIdentifier): self
    {
        $message = sprintf('There was no app installation found for repository %s', $repositoryIdentifier);
        return new self($message);
    }
}
