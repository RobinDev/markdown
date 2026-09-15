<?php

namespace Tempest\Markdown\Tests\Tokens;

use PHPUnit\Framework\Attributes\Test;
use Tempest\Markdown\Parser;
use Tempest\Markdown\Rules\BoldRule;
use Tempest\Markdown\Rules\TextRule;
use Tempest\Markdown\Tests\ParserTestCase;
use Tempest\Markdown\Tokens\TextToken;

class TextTokenTest extends ParserTestCase
{
    #[Test]
    public function test_parse(): void
    {
        $token = new TextToken('Hello, world!');

        $this->assertEquals('Hello, world!', $token->parse(new Parser()));
    }

    #[Test]
    public function parse_renders_a_hard_break_after_two_spaces(): void
    {
        $parser = new Parser(highlighter: null, rules: [new TextRule()]);

        $this->assertSame(
            "a<br />\nb",
            new TextToken("a  \nb")->parse($parser),
        );
        $this->assertSame(
            "a<br />\nb",
            new TextToken("a   \nb")->parse($parser),
        );
    }

    #[Test]
    public function parse_renders_a_hard_break_after_a_backslash(): void
    {
        $parser = new Parser(highlighter: null, rules: [new TextRule()]);

        $this->assertSame(
            "a<br />\nb",
            new TextToken("a\\\nb")->parse($parser),
        );
    }

    #[Test]
    public function parse_keeps_a_soft_break_as_is(): void
    {
        $parser = new Parser(highlighter: null, rules: [new TextRule()]);

        $this->assertSame("a\nb", new TextToken("a\nb")->parse($parser));
        $this->assertSame("a \nb", new TextToken("a \nb")->parse($parser));
    }

    #[Test]
    public function parse_drops_a_hard_break_at_the_end_of_the_content(): void
    {
        $parser = new Parser(highlighter: null, rules: [new TextRule()]);

        $this->assertSame("a  \n", new TextToken("a  \n")->parse($parser));
    }

    #[Test]
    public function lex_renders_a_hard_break_around_other_inline_tokens(): void
    {
        $html = (string) new Parser(highlighter: null, rules: [
            new BoldRule(),
            new TextRule(),
        ])->parse("a **b**  \nc");

        $this->assertSame("a <strong>b</strong><br />\nc", $html);
    }
}
