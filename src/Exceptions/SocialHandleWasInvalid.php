<?php

namespace Tempest\Markdown\Exceptions;

use Exception;
use Tempest\Markdown\MarkdownException;
use Tempest\Markdown\Parser;
use Tempest\Markdown\RendersSnippet;

final class SocialHandleWasInvalid extends Exception implements
    MarkdownException
{
    use RendersSnippet;

    public function __construct(Parser $parser)
    {
        parent::__construct(sprintf(
            "The provided social handle was invalid:\n\n%s\n",
            trim($this->renderSnippet($parser)),
        ));
    }
}
