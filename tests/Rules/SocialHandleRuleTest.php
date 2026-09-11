<?php

namespace Rules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tempest\Markdown\Parser;
use Tempest\Markdown\Rules\SocialHandleRule;
use Tempest\Markdown\Tests\ParserTestCase;

final class SocialHandleRuleTest extends ParserTestCase
{
    #[Test]
    #[DataProvider('provideSocialData')]
    public function test_lex($content, $expectedResult): void
    {
        $content = (string) new Parser(
            highlighter: null,
            rules: [
                new SocialHandleRule(),
            ],
        )->parse($content);

        $this->assertSame($expectedResult, $content);
    }

    public static function provideSocialData(): array
    {
        return [
            'github username' => [
                '{github:aidan-casey}',
                '<a href="https://github.com/aidan-casey">@aidan-casey</a>',
            ],

            'github username with a label' => [
                '{github:aidan-casey,Testing}',
                '<a href="https://github.com/aidan-casey">Testing</a>',
            ],

            'github username with shorthand' => [
                '{gh:aidan-casey}',
                '<a href="https://github.com/aidan-casey">@aidan-casey</a>',
            ],

            'uppercase github username' => [
                '{GITHUB:aidan-casey}',
                '<a href="https://github.com/aidan-casey">@aidan-casey</a>',
            ],

            'twitter username' => [
                '{twitter:JohnDoe}',
                '<a href="https://x.com/JohnDoe">@JohnDoe</a>',
            ],

            'twitter username with shorthand' => [
                '{x:JohnDoe,Hello John Doe}',
                '<a href="https://x.com/JohnDoe">Hello John Doe</a>',
            ],

            'bluesky username' => [
                '{bluesky:JohnDoe}',
                '<a href="https://bsky.app/profile/JohnDoe">@JohnDoe</a>',
            ],

            'bluesky username with shorthand' => [
                '{bsky:JohnDoe,Testing}',
                '<a href="https://bsky.app/profile/JohnDoe">Testing</a>',
            ],
        ];
    }
}
