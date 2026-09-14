<?php

namespace Tempest\Markdown\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tempest\Markdown\Exceptions\MaximumNestingDepthWasExceeded;
use Tempest\Markdown\Parser;
use Tempest\Markdown\Rules\HeadingRule;
use Tempest\Markdown\Rules\ParagraphRule;
use Tempest\Markdown\Rules\SocialHandleRule;
use Tempest\Markdown\Rules\TextRule;

final class ParserTest extends ParserTestCase
{
    #[Test]
    public function test_lex_snippet(): void
    {
        $html = (string) new Parser()->parse(<<<'MD'
        # Test
        Hello **world**
        MD);

        $this->assertStringContainsString('<h1 id="test">Test</h1>', $html);
        $this->assertStringContainsString('<p>Hello <strong>world</strong></p>', $html);
    }

    #[Test]
    public function test_lookahead(): void
    {
        $parser = new Parser()->setContent(<<<'MD'
        | Test |
        | ---- |
        | Hello |
        MD);

        $result = $parser->lookaheadUntil(Parser::NEW_LINE, Parser::NEW_LINE);

        $this->assertCount(2, $result);
        $this->assertSame("| Test |\n", $result[0]);
        $this->assertSame("| ---- |\n", $result[1]);
        $this->assertSame(0, $parser->position);
    }

    #[Test]
    public function test_lookahead_with_mismatches(): void
    {
        $parser = new Parser()->setContent(<<<'MD'
        | Test |

        MD);

        $result = $parser->lookaheadUntil(Parser::NEW_LINE, Parser::NEW_LINE);

        $this->assertCount(1, $result);
        $this->assertSame("| Test |\n", $result[0]);
        $this->assertSame(0, $parser->position);
    }

    #[Test]
    public function test_lookahead_without_match(): void
    {
        $parser = new Parser()->setContent(<<<'MD'
        ABC
        MD);

        $result = $parser->lookaheadUntil('D');

        $this->assertEmpty($result);
    }

    #[Test]
    public function test_remove_rules_removes_rule(): void
    {
        $html = (string) new Parser()
            ->removeRules(HeadingRule::class)
            ->parse('# Not a heading');

        $this->assertStringNotContainsString('<h1', $html);
    }

    #[Test]
    public function test_remove_rules_does_not_affect_other_rules(): void
    {
        $html = (string) new Parser()
            ->removeRules(HeadingRule::class)
            ->parse("# Not a heading\n\nStill a paragraph");

        $this->assertStringNotContainsString('<h1', $html);
        $this->assertStringContainsString('<p>Still a paragraph</p>', $html);
    }

    #[Test]
    public function test_remove_multiple_rules(): void
    {
        $html = (string) new Parser()
            ->removeRules(HeadingRule::class, ParagraphRule::class)
            ->parse("# Heading\n\nParagraph");

        $this->assertStringNotContainsString('<h1', $html);
        $this->assertStringNotContainsString('<p>', $html);
    }

    #[Test]
    public function test_remove_rules_returns_clone(): void
    {
        $parser = new Parser();
        $modified = $parser->removeRules(HeadingRule::class);

        $this->assertNotSame($parser, $modified);
        $this->assertContains(HeadingRule::class, array_map(fn ($r) => $r::class, $parser->rules));
        $this->assertNotContains(HeadingRule::class, array_map(fn ($r) => $r::class, $modified->rules));
    }

    #[Test]
    public function test_comes_next_with_offset(): void
    {
        $parser = new Parser()->setContent('**__');

        $this->assertTrue($parser->comesNext('*'));
        $this->assertTrue($parser->comesNext('*', offset: 1));
        $this->assertFalse($parser->comesNext('*', offset: 2));
        $this->assertTrue($parser->comesNext('_', offset: 2));
        $this->assertFalse($parser->comesNext('_', offset: 10));
    }

    #[Test]
    public function test_comes_next_matches_single_character_case_insensitively(): void
    {
        $parser = new Parser(highlighter: null)->setContent('A');

        $this->assertFalse($parser->comesNext('a'));
        $this->assertTrue($parser->comesNext('a', caseSensitive: false));
    }

    #[Test]
    public function has_next(): void
    {
        $parser = new Parser()->setContent('hello apple; goodbye');

        $this->assertTrue($parser->hasNext('apple'));
        $this->assertFalse($parser->hasNext('banana'));

        $this->assertTrue($parser->hasNext('apple', ';'));
        $this->assertFalse($parser->hasNext('goodbye', ';'));
    }

    #[Test]
    #[DataProvider('provideSocialHandleInlineContexts')]
    public function test_registered_social_handles_render_in_inline_contexts(string $markdown, string $expectedHtml): void
    {
        $parser = new Parser(highlighter: null)->prependRules(new SocialHandleRule());

        $this->assertSame($expectedHtml, $parser->parse($markdown)->html);
    }

    public static function provideSocialHandleInlineContexts(): array
    {
        return [
            'bold' => [
                '**Hello {gh:alice}**',
                '<p><strong>Hello <a href="https://github.com/alice">@alice</a></strong></p>',
            ],
            'italic' => [
                '*Hello {gh:alice}*',
                '<p><em>Hello <a href="https://github.com/alice">@alice</a></em></p>',
            ],
            'heading' => [
                '# Hello {gh:alice}',
                '<h1 id="hello-gh-alice">Hello <a href="https://github.com/alice">@alice</a></h1>',
            ],
            'list' => [
                '- Hello {gh:alice}',
                '<ul><li>Hello <a href="https://github.com/alice">@alice</a></li></ul>',
            ],
            'quote' => [
                '> Hello {gh:alice}',
                '<blockquote>Hello <a href="https://github.com/alice">@alice</a></blockquote>',
            ],
            'bold and italic' => [
                '***Hello {gh:alice}***',
                '<p><strong><em>Hello <a href="https://github.com/alice">@alice</a></em></strong></p>',
            ],
            'strikethrough' => [
                '~~Hello {gh:alice}~~',
                '<p><s>Hello <a href="https://github.com/alice">@alice</a></s></p>',
            ],
            'ordered list' => [
                '1. Hello {gh:alice}',
                '<ol><li>Hello <a href="https://github.com/alice">@alice</a></li></ol>',
            ],
            'table' => [
                "| Person |\n| --- |\n| Hello {gh:alice} |",
                '<table><thead><tr><th>Person</th></tr></thead><tbody><tr><td>Hello <a href="https://github.com/alice">@alice</a></td></tr></tbody></table>',
            ],
            'div' => [
                ":::note\nHello {gh:alice}\n:::",
                "<div class=\"note\">Hello <a href=\"https://github.com/alice\">@alice</a>\n</div>",
            ],
            'html' => [
                '<span>Hello {gh:alice}</span>',
                '<span>Hello <a href="https://github.com/alice">@alice</a></span>',
            ],
        ];
    }

    #[Test]
    public function test_parsing_twice_as_many_social_handles_takes_less_than_three_times_as_long(): void
    {
        $parser = new Parser(highlighter: null, rules: [new SocialHandleRule(), new TextRule()]);
        $parser->parse('{gh:alice} ');

        $small = str_repeat('{gh:alice} ', 8000);
        $large = str_repeat('{gh:alice} ', 16_000);
        $smallTimes = [];
        $largeTimes = [];

        // Compare growth using the fastest of three interleaved samples to reduce scheduling noise.
        for ($sample = 0; $sample < 3; $sample++) {
            $start = hrtime(true);
            $smallHtml = $parser->parse($small)->html;
            $smallTimes[] = hrtime(true) - $start;

            $start = hrtime(true);
            $largeHtml = $parser->parse($large)->html;
            $largeTimes[] = hrtime(true) - $start;

            $this->assertSame(8000, substr_count($smallHtml, '<a href="https://github.com/alice">@alice</a>'));
            $this->assertSame(16_000, substr_count($largeHtml, '<a href="https://github.com/alice">@alice</a>'));
        }

        $growth = min($largeTimes) / min($smallTimes);

        $this->assertLessThan(3.0, $growth, sprintf('Doubling the number of handles took %.2fx as long; expected near-linear growth.', $growth));
    }

    #[Test]
    public function test_configuration_does_not_leak_between_instances(): void
    {
        $withHighlighter = new Parser();
        $withHighlighter->parse('`x`');

        $noHighlighter = new Parser(highlighter: null);

        $this->assertSame('<p><code>&lt;b&gt;x&lt;/b&gt;</code></p>', $noHighlighter->parse('`<b>x</b>`')->html);
    }

    #[Test]
    public function test_max_nesting_depth_limits_nested_tokens(): void
    {
        $this->expectException(MaximumNestingDepthWasExceeded::class);

        new Parser(maxNestingDepth: 3)->parse('Hello **world**');
    }

    #[Test]
    public function test_max_nesting_depth_limits_nested_lists(): void
    {
        $this->expectException(MaximumNestingDepthWasExceeded::class);

        new Parser(maxNestingDepth: 3)->parse("- one\n  - two\n    - three");
    }

    #[Test]
    public function test_default_max_nesting_depth_allows_normal_content(): void
    {
        $html = (string) new Parser()->parse("Hello **bold** and **more bold**\n\n- one\n  - two");

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<li>', $html);
    }
}
