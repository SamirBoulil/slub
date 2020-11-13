<?php

declare(strict_types=1);

namespace Slub\Infrastructure\UI\CLI;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCIStatusCLI extends Command
{
    protected static $defaultName = 'slub:debug:ci-status';

    /** @var GetPRInfo */
    private $getPRInfo;

    public function __construct(GetPRInfo $getCIStatus)
    {
        parent::__construct(self::$defaultName);
        $this->getPRInfo = $getCIStatus;
    }

    protected function configure(): void
    {
        $this->setDescription('Fetch the CI status for a given PR')
            ->addArgument('pull_request_link', InputArgument::REQUIRED, 'Link for the pull request')
            ->setHidden(false);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $PRLink = $input->getArgument('pull_request_link');
        if (!is_string($PRLink)) {
            throw new \LogicException('Expect PR Link to be a string');
        }
        $output->writeln(sprintf('<info>Fetching the CI status for PR: %s</info>', $PRLink));

        preg_match('#https://github.com/(.*)/pull/(\d+)#', $PRLink, $matches);
        array_shift($matches);
        $PRInfo = $this->getPRInfo->fetch($this->PRIdentifier($matches));

        $output->writeln(sprintf('<info>CI status: %s</info>', $PRInfo->CIStatus->status));
        $output->writeln(sprintf('<info>CI closed: %s</info>', $PRInfo->isClosed ? 'yes' : 'no'));
        $output->writeln(sprintf('<info>CI isMerged: %s</info>', $PRInfo->isClosed ? 'yes' : 'no'));
    }

    private function PRIdentifier($matches): PRIdentifier
    {
        return PRIdentifier::fromString(sprintf('%s/%s', $matches[0], $matches[1]));
    }
}
