<?php

namespace App;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\NodeVisitorAbstract;

class PhpCodeProcessor
{
    public function process(string $code): string
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);
        if($ast !== null && is_array($ast)){
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new class extends NodeVisitorAbstract{
                public function leaveNode(Node $node)
                {
                    if ($node instanceof Node\Stmt\Class_) {
                        // get class construct method 
                        $constructor = $this->findConstructor($node);
                        if ($constructor) {
                            // if there is __construct, we set the create method
                            $this->addCreateMethod($node);
                
                            // make public function __construct to private function __construct
                            $this->makeConstructorPrivate($constructor);

                            // the create methode must be before __construct
                            $this->orderConstructorAndCreateMethod($node, $node);

                        }
                    }

                    return null;
                }
                private function addCreateMethod(Node\Stmt\Class_ $classNode)
                {
                    $constructor = $this->findConstructor($classNode);

                    //get all __construct params
                    $constructorParams = $constructor->params;

                    // Create the 'create' method node with public and static modifier (by doc)
                    // it helps us to get as public static create(){}
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
                                    ['name' => 'self'] // it helps us to get a ' return new self() without args
                                )
                            )
                        )
                    ];

                    // Create a new 'self' expression like 'new self($a, $b, $c)'
                    // very important if there is constructorParams
                    $newSelf = new Node\Expr\New_(
                        new Node\Name(['self']),
                        // set all args for the create method result by using all of the __construct args
                        array_map (function($var) {
                            return new Node\Expr\Variable($var->var->name);
                        }, $constructorParams)
                    );

                    // Replace the return statement with the new expression
                    $createMethod->stmts = [
                        new Node\Stmt\Return_($newSelf)
                    ];
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

            return (new PrettyPrinter\Standard())->prettyPrintFile($ast);

        }else{
            return null;
        }
    }
}
