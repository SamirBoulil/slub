<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\CLI;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeGithubApiCacheCLI extends Command
{
    protected static $defaultName = 'slub:maintenance:purge-github-api-cache';

    private const RESPONSE_CACHE_RETENTION_IN_DAYS = 7;
    private const PR_COMMITS_RETENTION_IN_DAYS = 30;

    public function __construct(private Connection $connection)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setDescription('Maintenance operation consisting in purging the stale github API caches (cached responses and PR commits) from the database to keep its size minimal')
            ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting to purge the stale github API caches from the database</info>');

        $numberOfPurgedResponses = $this->connection->executeUpdate(
            sprintf(
                'DELETE FROM github_api_response_cache WHERE REFRESHED_AT < NOW() - INTERVAL %d DAY;',
                self::RESPONSE_CACHE_RETENTION_IN_DAYS
            )
        );
        $numberOfPurgedPRCommits = $this->connection->executeUpdate(
            sprintf(
                'DELETE FROM pr_commits WHERE CREATED_AT < NOW() - INTERVAL %d DAY;',
                self::PR_COMMITS_RETENTION_IN_DAYS
            )
        );

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>✅ Purge of %d stale github API cached responses and %d stale PR commits done</info>',
                $numberOfPurgedResponses,
                $numberOfPurgedPRCommits
            )
        );

        return 0;
    }
}
