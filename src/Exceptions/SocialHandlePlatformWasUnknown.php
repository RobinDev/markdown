<?php

namespace Tempest\Markdown\Exceptions;

use Exception;
use Tempest\Markdown\MarkdownException;
use Tempest\Markdown\Parser;

final class SocialHandlePlatformWasUnknown extends Exception implements
    MarkdownException
{
    public function __construct(Parser $parser, string $platform)
    {
        // @todo(aidan-casey): I'm keeping this because I like it. Mago formatted it bad tho.
        $platform
            |> trim(...)
            |> (fn ($x) => sprintf(
                "The provided social platform was unknown:\n\n%s\n",
                $x,
            ))
            |> parent::__construct(...);
    }
}
