<?php

namespace App;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
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

        // create the a traverser for the $ast object 
        $traverser = new NodeTraverser();
        $visitor = new PhpCodeProcessorVisitor();
        $traverser->addVisitor($visitor);

        $ast = $traverser->traverse($ast);

        var_dump('aricette');
        var_dump($ast);
        var_dump('/aricette');
        return (new PrettyPrinter\Standard())->prettyPrintFile($ast);
    }
}

class PhpCodeProcessorVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        /*if ($node instanceof Node\Stmt\Class_) {
            $this->processClass($node);
        }*/
        if ($node instanceof Node\Stmt\Class_) {
            // Recherche du constructeur de la classe
            $constructor = $this->findConstructor($node);

            if ($constructor) {
                
                // Transformation du constructeur en privé
                $this->makeConstructorPrivate($constructor);

                // Ajout de la méthode 'create'
                $this->addCreateMethod($node);
            }
        }

        return null;
    }

    private function processClass(Node\Stmt\Class_ $class)
    {

        $constructor = $this->findConstructor($class);

        if ($constructor) {
            $this->makeConstructorPrivate($constructor);
        }
    }

    private function addCreateMethod(Node\Stmt\Class_ $classNode)
    {
        // Création du nœud de méthode 'create'
        $createMethod = new Node\Stmt\ClassMethod('create', [
            'type' => Node\Stmt\Class_::MODIFIER_STATIC,
            'returnType' => new Node\Name\FullyQualified($classNode->name),
        ]);

        // Ajout du nœud de méthode 'create' à la liste des membres de la classe
        $classNode->stmts[] = $createMethod;
    }

    private function findConstructor(Node\Stmt\Class_ $class)
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === '__construct') {
                return $stmt;
            }
        }

        return null;
    }

    private function makeConstructorPrivate(Node\Stmt\ClassMethod $constructor)
    {
        $constructor->flags = ($constructor->flags & ~Node\Stmt\Class_::MODIFIER_PUBLIC) | Node\Stmt\Class_::MODIFIER_PRIVATE;
    }
}
