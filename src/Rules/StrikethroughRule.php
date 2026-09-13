<?php

namespace Tempest\Markdown\Rules;

use Tempest\Markdown\Parser;
use Tempest\Markdown\ProvidesFirstChar;
use Tempest\Markdown\ProvidesStopChar;
use Tempest\Markdown\Rule;
use Tempest\Markdown\Token;
use Tempest\Markdown\Tokens\StrikethroughToken;

final class StrikethroughRule implements Rule, ProvidesFirstChar, ProvidesStopChar
{
    private(set) string $firstChar = '~';
    private(set) string $stopChar = '~';

    public function shouldParse(Parser $parser): bool
    {
        if (! $parser->comesNext('~', 1)) {
            return false;
        }

        $openingLength = strspn($parser->content, '~', $parser->position);

        return strpos($parser->content, '~', $parser->position + $openingLength) !== false;
    }

    public function parse(Parser $parser): Token
    {
        $parser->consumeWhile('~');
        $buffer = $parser->consumeUntil('~');
        $parser->consumeWhile('~');

        return new StrikethroughToken($buffer);
    }
}
