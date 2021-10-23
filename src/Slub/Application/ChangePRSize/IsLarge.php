<?php

declare(strict_types=1);

namespace Slub\Application\ChangePRSize;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class IsLarge
{
    private int $prSizeLimit;

    public function __construct(int $prSizeLimit)
    {
        $this->prSizeLimit = $prSizeLimit;
    }

    public function execute(int $additions, int $deletions) : bool
    {
        return $additions > $this->prSizeLimit || $deletions > $this->prSizeLimit;
    }
}
