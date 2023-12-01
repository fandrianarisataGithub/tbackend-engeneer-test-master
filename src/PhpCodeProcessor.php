<?php

namespace App;

use PhpParser\Builder\Method;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class PhpCodeProcessor
{
    public function process(string $code): string
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        // process the AST in order to make the test passing

        return (new PrettyPrinter\Standard())->prettyPrintFile($ast);
    }
}
