<?php

namespace Tempest\Markdown\Rules;

use RuntimeException;
use Tempest\Markdown\Parser;
use Tempest\Markdown\ProvidesFirstChar;
use Tempest\Markdown\ProvidesStopChar;
use Tempest\Markdown\Rule;
use Tempest\Markdown\Token;
use Tempest\Markdown\Tokens\LinkToken;

/**
 * Adapted from the original handle parser by innocenzi.
 * https://github.com/tempestphp/tempestphp.com/blob/48b58184b43e80ab17f4375ac95affdf6d7a3bf2/src/Markdown/HandleParser.php
 */
final class SocialHandleRule implements Rule, ProvidesFirstChar, ProvidesStopChar
{
    private(set) string $firstChar = '{';
    private(set) string $stopChar = '{';

    public function shouldParse(Parser $parser): bool
    {
        return (
            $parser->comesNext('{x:', caseSensitive: false)
            || $parser->comesNext('{gh:', caseSensitive: false)
            || $parser->comesNext('{bsky:', caseSensitive: false)
            || $parser->comesNext('{github:', caseSensitive: false)
            || $parser->comesNext('{twitter:', caseSensitive: false)
            || $parser->comesNext('{bluesky:', caseSensitive: false)
        );
    }

    public function parse(Parser $parser): Token
    {
        // Consume the opening bracket ("{").
        $parser->consume();

        // Consume until the ending bracket ("}").
        $content = $parser->consumeUntilString('}');

        $matches = [];

        // Match the format: {github:aidan-casey,Label}
        // Where "Label" is optional.
        $matched = preg_match(
            pattern: '/\A(twitter|x|bluesky|bsky|github|gh):([^,\r\n]+)(?:,([^\r\n]+))?\z/i',
            subject: $content,
            matches: $matches,
        );

        if ($matched !== 1) {
            throw new RuntimeException("Invalid social handle: {$content}");
        }

        [, $platform, $handle, $text] = $matches + [null, null, null, null];

        $platform = $platform ? strtolower($platform) : '';
        $handle ??= '';

        return new LinkToken(
            content: $text ?? '@' . $handle,
            href: $this->createSocialUrl($platform, $handle),
        );
    }

    private function createSocialUrl(?string $platform, ?string $handle): string
    {
        return match ($platform) {
            'bluesky', 'bsky' => "https://bsky.app/profile/{$handle}",
            'gh', 'github' => "https://github.com/{$handle}",
            'x', 'twitter' => "https://x.com/{$handle}",
            default => throw new RuntimeException("Unknown platform: {$platform}"),
        };
    }
}
