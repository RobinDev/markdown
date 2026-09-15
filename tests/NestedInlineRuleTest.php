<?php

namespace Tempest\Markdown\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tempest\Markdown\Markdown;

/**
 * Every token that holds inline content runs the same set of inline rules, so
 * the same Markdown renders the same way whichever container it sits in.
 */
final class NestedInlineRuleTest extends ParserTestCase
{
    #[Test]
    #[DataProvider('provideNestedInline')]
    public function inline_rules_apply_inside_every_container(
        string $markdown,
        string $expected,
    ): void {
        $this->assertSame(
            $expected,
            trim(new Markdown(highlighter: null)->parse($markdown)->html),
        );
    }

    public static function provideNestedInline(): iterable
    {
        yield 'code in a paragraph' => [
            '`code`',
            '<p><code>code</code></p>',
        ];

        yield 'code in a heading' => [
            '# `code`',
            '<h1 id="code"><code>code</code></h1>',
        ];

        yield 'code in a blockquote' => [
            '> `code`',
            '<blockquote><code>code</code></blockquote>',
        ];

        yield 'code in bold' => [
            '**`code`**',
            '<p><strong><code>code</code></strong></p>',
        ];

        yield 'code in italic' => [
            '*`code`*',
            '<p><em><code>code</code></em></p>',
        ];

        yield 'code in bold italic' => [
            '***`code`***',
            '<p><strong><em><code>code</code></em></strong></p>',
        ];

        yield 'code in strikethrough' => [
            '~~`code`~~',
            '<p><s><code>code</code></s></p>',
        ];

        yield 'image in a paragraph' => [
            '![alt](img.png)',
            '<p><img src="img.png" alt="alt"></p>',
        ];

        yield 'image in a heading' => [
            '# ![alt](img.png)',
            '<h1 id="alt-img-png"><img src="img.png" alt="alt"></h1>',
        ];

        yield 'image in bold' => [
            '**![alt](img.png)**',
            '<p><strong><img src="img.png" alt="alt"></strong></p>',
        ];

        yield 'image in italic' => [
            '*![alt](img.png)*',
            '<p><em><img src="img.png" alt="alt"></em></p>',
        ];

        yield 'image in bold italic' => [
            '***![alt](img.png)***',
            '<p><strong><em><img src="img.png" alt="alt"></em></strong></p>',
        ];

        yield 'image in strikethrough' => [
            '~~![alt](img.png)~~',
            '<p><s><img src="img.png" alt="alt"></s></p>',
        ];

        yield 'strikethrough in a paragraph' => [
            '~~struck~~',
            '<p><s>struck</s></p>',
        ];

        yield 'strikethrough in a list item' => [
            '- ~~struck~~',
            '<ul><li><s>struck</s></li></ul>',
        ];

        yield 'strikethrough in an ordered list item' => [
            '1. ~~struck~~',
            '<ol><li><s>struck</s></li></ol>',
        ];

        yield 'strikethrough in a table cell' => [
            "| h |\n| - |\n| ~~struck~~ |",
            '<table><thead><tr><th>h</th></tr></thead>'
                . '<tbody><tr><td><s>struck</s></td></tr></tbody></table>',
        ];

        yield 'strikethrough in a blockquote' => [
            '> ~~struck~~',
            '<blockquote><s>struck</s></blockquote>',
        ];

        yield 'strikethrough in a div' => [
            ":::\n~~struck~~\n:::\n",
            "<div><s>struck</s>\n</div>",
        ];
    }

    #[Test]
    #[DataProvider('provideSelfNesting')]
    public function a_rule_does_not_nest_in_the_token_it_produces(
        string $markdown,
        string $expected,
    ): void {
        $this->assertSame(
            $expected,
            trim(new Markdown(highlighter: null)->parse($markdown)->html),
        );
    }

    public static function provideSelfNesting(): iterable
    {
        yield 'a link does not nest in a link' => [
            '[a [b](https://b.test) c](https://a.test)',
            '<p><a href="https://a.test">a [b](https://b.test) c</a></p>',
        ];

        yield 'a social handle does not nest in a link' => [
            '[{x:tempestphp}](https://a.test)',
            '<p><a href="https://a.test">{x:tempestphp}</a></p>',
        ];
    }
}
