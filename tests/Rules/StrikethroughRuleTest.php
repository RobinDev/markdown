<?php

namespace Tempest\Markdown\Tests\Rules;

use PHPUnit\Framework\Attributes\Test;
use Tempest\Markdown\Markdown;
use Tempest\Markdown\Parser;
use Tempest\Markdown\Rules\StrikethroughRule;
use Tempest\Markdown\Rules\TextRule;
use Tempest\Markdown\Tests\ParserTestCase;

class StrikethroughRuleTest extends ParserTestCase
{
    #[Test]
    public function test_lex(): void
    {
        $html = (string) new Parser(highlighter: null, rules: [new StrikethroughRule()])->parse('~~strikethrough~~');

        $this->assertSame('<s>strikethrough</s>', $html);
    }

    #[Test]
    public function test_lex_single_tilde(): void
    {
        $html = (string) new Parser(highlighter: null, rules: [new StrikethroughRule()])->parse('~strikethrough~');

        $this->assertSame('<s>strikethrough</s>', $html);
    }

    #[Test]
    public function unmatched_tildes_remain_literal(): void
    {
        $parser = new Parser(highlighter: null, rules: [new StrikethroughRule(), new TextRule()]);

        $this->assertSame('Duration: ~2h30.', (string) $parser->parse('Duration: ~2h30.'));
        $this->assertSame('About ~~5 km', (string) $parser->parse('About ~~5 km'));
        $this->assertSame('~~~', (string) $parser->parse('~~~'));
        $this->assertSame('<p>Duration: ~2h30.</p>', new Markdown(highlighter: null)->parse('Duration: ~2h30.')->html);
    }
}
