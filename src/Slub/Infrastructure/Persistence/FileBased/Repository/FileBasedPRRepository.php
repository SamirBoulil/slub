<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\FileBased\Repository;

use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;

class FileBasedPRRepository implements PRRepositoryInterface
{
    /** @var string */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function save(PR $pr): void
    {
        $allPRs = $this->all();
        $allPRs[$pr->PRIdentifier()->stringValue()] = $pr;
        $this->saveAll($allPRs);
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

    public function resetFile(): void
    {
        unlink($this->filePath);
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
}
