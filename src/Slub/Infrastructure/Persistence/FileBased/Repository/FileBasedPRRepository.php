<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\FileBased\Repository;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FileBasedPRRepository implements PRRepositoryInterface
{
    /** @var string */
    private $filePath;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, string $filePath)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->filePath = $filePath;
    }

    public function save(PR $PR): void
    {
        $allPRs = $this->all();
        $allPRs[$PR->PRIdentifier()->stringValue()] = $PR;
        $this->saveAll($allPRs);
        $this->dispatchEvents($PR);
    }

    /**
     * @return PR[]
     */
    private function all(): array
    {
        $normalizedPRs = $this->readFile();
        $result = $this->denormalizePRs($normalizedPRs);

        return $result;
    }

    private function readFile(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $fileContent = file_get_contents($this->filePath);
        if (empty($fileContent)) {
            return [];
        }
        $result = json_decode($fileContent, true);

        return $result;
    }

    /**
     * @return PR[]
     */
    private function denormalizePRs(array $normalizedPRs): array
    {
        $result = [];
        foreach ($normalizedPRs as $normalizedPR) {
            $PR = PR::fromNormalized($normalizedPR);
            $result[$PR->PRIdentifier()->stringValue()] = $PR;
        }

        return $result;
    }

    /**
     * @param PR[] $allPRs
     */
    private function saveAll(array $allPRs): void
    {
        $normalizedAllPRs = $this->normalizePRs($allPRs);
        $this->writeFile($normalizedAllPRs);
    }

    /**
     * @param PR[] $prs
     *
     * @return array
     */
    private function normalizePRs(array $prs): array
    {
        $result = [];
        foreach ($prs as $pr) {
            $result[$pr->PRIdentifier()->stringValue()] = $pr->normalize();
        }

        return $result;
    }

    private function writeFile(array $normalizedAllPRs): void
    {
        if (!file_exists($this->filePath)) {
            touch($this->filePath);
        }

        $fp = fopen($this->filePath, 'w');
        if (false === $fp) {
            throw new \Exception(sprintf('Impossible to open the file at path "%s"', $this->filePath));
        }
        $serializedAllPRs = json_encode($normalizedAllPRs);
        if (false === $serializedAllPRs) {
            throw new \Exception('Impossible to serialize all PRs');
        }
        fwrite($fp, $serializedAllPRs);
        fclose($fp);
    }

    private function dispatchEvents(PR $PR): void
    {
        foreach ($PR->getEvents() as $event) {
            $this->eventDispatcher->dispatch(get_class($event), $event);
        }
    }

    public function getBy(PRIdentifier $PRidentifier): PR
    {
        $allPRs = $this->all();
        $result = $this->findPR($PRidentifier, $allPRs);

        if (null === $result) {
            throw PRNotFoundException::create($PRidentifier);
        }

        return $result;
    }

    /**
     * @param PR[] $allPRs
     */
    private function findPR(PRIdentifier $identifier, array $allPRs): ?PR
    {
        $result = current(
            array_filter(
                $allPRs,
                function (PR $pr) use ($identifier) {
                    return $pr->PRIdentifier()->equals($identifier);
                }
            )
        );

        if (!$result) {
            return null;
        }

        return $result;
    }

    public function resetFile(): void
    {
        unlink($this->filePath);
        touch($this->filePath);
    }
}
