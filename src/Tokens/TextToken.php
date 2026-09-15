<?php

namespace Tempest\Markdown\Tokens;

use Tempest\Markdown\Parser;
use Tempest\Markdown\Token;

final class TextToken implements Token
{
    public function __construct(
        private(set) string $content,
    ) {}

    public function parse(Parser $parser): string
    {
        // A line ending preceded by two or more spaces, or by a backslash,
        // is a hard line break — unless it ends the block, where it is
        // dropped rather than rendered.
        if (
            ! str_contains($this->content, "\n")
            && ! str_contains($this->content, "\r")
        ) {
            return $this->content;
        }

        return (
            preg_replace(
                '/(?: {2,}|\\\\)(\r\n|\n|\r)(?!\z)/',
                '<br />$1',
                $this->content,
            ) ?? $this->content
        );
    }

    public function append(string $content): void
    {
        $this->content .= $content;
    }
}
