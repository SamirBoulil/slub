<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Installer\CLI;

use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class InstallerCLI extends Command
{
    protected static $defaultName = 'slub:install';

    /** @var PRRepositoryInterface */
    private $repository;

    public function __construct(PRRepositoryInterface $repository)
    {
        parent::__construct(self::$defaultName);
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this->setDescription('Installs the application')
            ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->repository->reset();
        $output->writeln('Slub installed.');
    }
}
