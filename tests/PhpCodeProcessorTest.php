<?php

namespace App\Tests;

use App\PhpCodeProcessor;
use PhpParser\PrettyPrinter;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class PhpCodeProcessorTest extends TestCase
{
    public function processTestData()
    {
        return [
            [
                <<<'EOS'
                    <?php
                    
                    namespace Foo;
                    
                    class Bar
                    {
                        public function __construct(int $a, string $b, ?\DateTime $c = null)
                        {
                        }
                    }
                    EOS,
                <<<'EOS'
                    <?php
                    
                    namespace Foo;
                    
                    class Bar
                    {
                        public static function create(int $a, string $b, ?\DateTime $c = null): self
                        {
                            return new self($a, $b, $c);
                        }
                        
                        private function __construct(int $a, string $b, ?\DateTime $c = null)
                        {
                        }
                    }
                    EOS,
            ],
        ];
    }

    /**
     * @dataProvider processTestData
     * @return void
     */
    public function testProcess(string $inputPhpCode, string $expectedOutputPhpCode)
    {
        
        $phpCodeProcessor = new PhpCodeProcessor();
        var_dump('aricette le vita');
        var_dump(self::normalizePhpCode($phpCodeProcessor->process($inputPhpCode)));
        var_dump('/aricette le vita');
        var_dump('aricette le endrasana');
        var_dump(self::normalizePhpCode($expectedOutputPhpCode));
        var_dump('/aricette le endrasana');
        $this->assertEquals(
            self::normalizePhpCode($expectedOutputPhpCode),
            self::normalizePhpCode($phpCodeProcessor->process($inputPhpCode))
        );
    }

    private static function normalizePhpCode(string $code): string
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        return (new PrettyPrinter\Standard())->prettyPrintFile($ast);
    }
}
