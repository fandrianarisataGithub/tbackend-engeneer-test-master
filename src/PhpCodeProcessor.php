<?php

namespace App;

use PhpParser\Builder\Param;
use PhpParser\Node;
use PhpParser\NodeDumper;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\BuilderFactory;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Name\FullyQualified;

class PhpCodeProcessor
{
    public function process(string $code): string
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);
        if($ast !== null && is_array($ast)){
            //var_dump($ast);
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new class extends NodeVisitorAbstract{
                public function leaveNode(Node $node)
                {
                    if ($node instanceof Node\Stmt\Class_) {
                        // Recherche du constructeur de la classe
                        $constructor = $this->findConstructor($node);
                        if ($constructor) {
                            // Ajout de la méthode 'create'
                            $this->addCreateMethod($node);
                            
                            // Transformation du constructeur en privé
                            $this->makeConstructorPrivate($constructor);

                            // order of stmts
                            $this->orderConstructorAndCreateMethod($node, $node);

                        }
                    }

                    return null;
                }
                private function addCreateMethod(Node\Stmt\Class_ $classNode)
                {
                    $constructor = $this->findConstructor($classNode);
                    $constructorParams = $constructor->params;
                    // Create the 'create' method node with static modifier
                    $createMethod = new Node\Stmt\ClassMethod('create', [
                        'type' => (Node\Stmt\Class_::MODIFIER_PUBLIC | Node\Stmt\Class_::MODIFIER_STATIC),
                        'returnType' => 'self',
                        'params' => $constructorParams
                    ]);
                    // Add the 'create' method node to the class
                    
                    $classNode->stmts[] = $createMethod;
                    $createMethod->stmts = [
                        new Node\Stmt\Return_(
                            new Node\Expr\New_(
                                new Node\Name(
                                    ['name' => 'self']
                                )
                            )
                        )
                    ];
                    //var_dump($createMethod);
                }

                private function findConstructor(Node\Stmt\Class_ $class)
                {
                    foreach ($class->stmts as $stmt) {
                        if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === '__construct') {
                            $constructorParams = $stmt->params;
                            return $stmt;
                        }
                    }

                    return null;
                }

                private function orderConstructorAndCreateMethod(Node\Stmt\Class_ $class, Node\Stmt\Class_ $classNode)
                {
                    $constructor = null;
                    $createMethod = null;
                    foreach ($class->stmts as $stmt) {
                        
                        if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === '__construct') {
                            $constructor = $stmt;
                        }
                        else if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === 'create') {
                            $createMethod = $stmt;
                        }

                    }
                    $classNode->stmts = [];
                    $classNode->stmts[] = $createMethod;
                    $classNode->stmts[] = $constructor;
                    return null;
                }

                private function makeConstructorPrivate(Node\Stmt\ClassMethod $constructor)
                {
                    $constructor->flags &= ~Node\Stmt\Class_::MODIFIER_PUBLIC; // clear the public modifier
                    $constructor->flags |= Node\Stmt\Class_::MODIFIER_PRIVATE; // set the private modifier
                }
            });
            $ast = $traverser->traverse($ast);
            /*$dumper = new NodeDumper;
            echo $dumper->dump($ast) . "\n";*/
            return (new PrettyPrinter\Standard())->prettyPrintFile($ast);
        }else{
            var_dump('tsy izy');
        }
    }
}
