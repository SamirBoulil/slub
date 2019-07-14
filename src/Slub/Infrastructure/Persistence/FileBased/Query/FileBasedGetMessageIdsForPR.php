<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\FileBased\Query;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Repository\PRNotFoundException;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FileBasedGetMessageIdsForPR implements GetMessageIdsForPR
{
    /** @var string */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $PRInformation = $this->getPRInformation($PRIdentifier);
        $messageIds = array_map(function (string $messageId) {
            return MessageIdentifier::fromString($messageId);
        }, $PRInformation['MESSAGE_IDS']);

        return $messageIds;
    }

    private function getPRInformation(PRIdentifier $PRIdentifier): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $fileContent = file_get_contents($this->filePath);
        if (empty($fileContent)) {
            throw PRNotFoundException::create($PRIdentifier);
        }
        $result = json_decode($fileContent, true);
        $PRInformation = $result[$PRIdentifier->stringValue()] ?? null;
        if (null === $PRInformation) {
            throw PRNotFoundException::create($PRIdentifier);
        }

        return $PRInformation;
    }
}
