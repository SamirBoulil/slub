<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\CLI;

use Slub\Infrastructure\Persistence\Sql\Repository\SqlGithubAPIResponseCacheRepository;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeGithubApiCacheCLI extends Command
{
    protected static $defaultName = 'slub:maintenance:purge-github-api-cache';

    public function __construct(
        private SqlGithubAPIResponseCacheRepository $responseCacheRepository,
        private SqlPRCommitsRepository $prCommitsRepository
    ) {
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

        $numberOfPurgedResponses = $this->responseCacheRepository->evictStale();
        $numberOfPurgedPRCommits = $this->prCommitsRepository->evictStale();

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
