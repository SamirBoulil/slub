<?php

declare(strict_types=1);

namespace Slub\Domain\Entity\Document;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PutToReviewAt;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Webmozart\Assert\Assert;

class Document
{
    private const IDENTIFIER_KEY = 'IDENTIFIER';
    private const URL_KEY = 'URL';
    private const AUTHOR_ID_KEY = 'AUTHOR_IDENTIFIER';
    private const CHANNEL_IDS = 'CHANNEL_IDS';
    private const WORKSPACE_IDS = 'WORKSPACE_IDS';
    private const MESSAGE_IDS = 'MESSAGE_IDS';
    private const PUT_TO_REVIEW_AT = 'PUT_TO_REVIEW_AT';

    /**
     * @param \Slub\Domain\Entity\Channel\ChannelIdentifier[]     $channelIdentifiers
     * @param \Slub\Domain\Entity\Workspace\WorkspaceIdentifier[] $workspaceIdentifiers
     * @param \Slub\Domain\Entity\PR\MessageIdentifier[]          $messageIdentifiers
     */
    private function __construct(
        private DocumentIdentifier $documentIdentifier,
        private DocumentURL $url,
        private array $channelIdentifiers,
        private array $workspaceIdentifiers,
        private array $messageIdentifiers,
        private AuthorIdentifier $authorIdentifier,
        private PutToReviewAt $putToReviewAt,
    ) {
    }

    public static function create(
        DocumentIdentifier $documentIdentifier,
        DocumentURL $url,
        ChannelIdentifier $channelIdentifier,
        WorkspaceIdentifier $workspaceIdentifier,
        MessageIdentifier $messageIdentifier,
        AuthorIdentifier $authorIdentifier,
    ): self {
        return new self(
            $documentIdentifier,
            $url,
            [$channelIdentifier],
            [$workspaceIdentifier],
            [$messageIdentifier],
            $authorIdentifier,
            PutToReviewAt::create(),
        );
    }

    public static function fromNormalized(array $normalizedDocument): self
    {
        Assert::keyExists($normalizedDocument, self::IDENTIFIER_KEY);
        Assert::keyExists($normalizedDocument, self::URL_KEY);
        Assert::keyExists($normalizedDocument, self::AUTHOR_ID_KEY);
        Assert::keyExists($normalizedDocument, self::CHANNEL_IDS);
        Assert::keyExists($normalizedDocument, self::WORKSPACE_IDS);
        Assert::keyExists($normalizedDocument, self::MESSAGE_IDS);
        Assert::keyExists($normalizedDocument, self::PUT_TO_REVIEW_AT);
        Assert::isArray($normalizedDocument[self::MESSAGE_IDS]);

        $channelIdentifiers = array_map(
            static fn (string $channelIdentifier) => ChannelIdentifier::fromString($channelIdentifier),
            $normalizedDocument[self::CHANNEL_IDS]
        );
        $workspaceIdentifiers = array_map(
            static fn (string $workspaceIdentifier) => WorkspaceIdentifier::fromString($workspaceIdentifier),
            $normalizedDocument[self::WORKSPACE_IDS]
        );
        $messageIdentifiers = array_map(
            static fn (string $messageId) => MessageIdentifier::fromString($messageId),
            $normalizedDocument[self::MESSAGE_IDS]
        );

        return new self(
            DocumentIdentifier::fromString($normalizedDocument[self::IDENTIFIER_KEY]),
            new DocumentURL($normalizedDocument[self::URL_KEY]),
            $channelIdentifiers,
            $workspaceIdentifiers,
            $messageIdentifiers,
            AuthorIdentifier::fromString($normalizedDocument[self::AUTHOR_ID_KEY]),
            PutToReviewAt::fromTimestamp($normalizedDocument[self::PUT_TO_REVIEW_AT]),
        );
    }

    public function normalize(): array
    {
        return [
            self::IDENTIFIER_KEY => $this->documentIdentifier->stringValue(),
            self::URL_KEY => $this->url->asString(),
            self::AUTHOR_ID_KEY => $this->authorIdentifier->stringValue(),
            self::CHANNEL_IDS => array_map(
                static fn (ChannelIdentifier $channelIdentifier) => $channelIdentifier->stringValue(),
                $this->channelIdentifiers
            ),
            self::WORKSPACE_IDS => array_map(
                static fn (WorkspaceIdentifier $workspaceIdentifier) => $workspaceIdentifier->stringValue(),
                $this->workspaceIdentifiers
            ),
            self::MESSAGE_IDS => array_map(
                static fn (MessageIdentifier $messageIdentifier) => $messageIdentifier->stringValue(),
                $this->messageIdentifiers
            ),
            self::PUT_TO_REVIEW_AT => $this->putToReviewAt->toTimestamp(),
        ];
    }

    public function documentIdentifier(): DocumentIdentifier
    {
        return $this->documentIdentifier;
    }

    public function url(): DocumentURL
    {
        return $this->url;
    }

    public function authorIdentifier(): AuthorIdentifier
    {
        return $this->authorIdentifier;
    }

    /**
     * @return ChannelIdentifier[]
     */
    public function channelIdentifiers(): array
    {
        return $this->channelIdentifiers;
    }

    /**
     * @return MessageIdentifier[]
     */
    public function messageIdentifiers(): array
    {
        return $this->messageIdentifiers;
    }
}
