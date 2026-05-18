<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use RuntimeException;

final class CoverageDiffCheckFailure extends RuntimeException
{

    public static function invalidCloverXml(string $reason): self
    {
        return new self(sprintf('Invalid Clover XML: %s', $reason));
    }

}
