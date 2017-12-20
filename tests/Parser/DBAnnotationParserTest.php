<?php
namespace BrainDiminished\Test\DBAnnotation\Parser;

use BrainDiminished\DBAnnotation\DBAnnotation;
use BrainDiminished\DBAnnotation\Parser\DBAnnotationParser;
use PHPUnit\Framework\TestCase;

class DBAnnotationParserTest extends TestCase
{
    /** @var DBAnnotationParser */
    private $parser;

    protected function setUp()
    {
        $this->parser = new DBAnnotationParser([
            'Foo' => FooDBAnnotation::class,
            'Bar' => BarDBAnnotation::class,
        ]);
    }

    public function testSimple()
    {
        $annotations = $this->parser->parse('@Foo');
        self::assertEquals(1, count($annotations));
        self::assertInstanceOf(FooDBAnnotation::class, $annotations[0]);
    }

    public function testEmptyArgs()
    {
        $annotations = $this->parser->parse('@Foo(  )');
        self::assertEquals(1, count($annotations));
        self::assertInstanceOf(FooDBAnnotation::class, $annotations[0]);
    }

    public function testParseArgs()
    {
        $annotations = $this->parser->parse('@Bar(1, [2, 3 ] ) ');
        self::assertEquals(1, count($annotations));
        self::assertInstanceOf(BarDBAnnotation::class, $annotations[0]);
        /** @var BarDBAnnotation $barAnnot */
        $barAnnot = $annotations[0];
        self::assertEquals(2, count($barAnnot->values));
        self::assertEquals(1, $barAnnot->values[0]);
        self::assertEquals([2, 3], $barAnnot->values[1]);
    }

    public function testParseRecursive()
    {
        $annotations = $this->parser->parse('@Bar( @Foo, @Bar(@Foo) )');
        self::assertEquals(1, count($annotations));
        self::assertInstanceOf(BarDBAnnotation::class, $annotations[0]);
        /** @var BarDBAnnotation $barAnnot */
        $barAnnot = $annotations[0];
        self::assertEquals(2, count($barAnnot->values));
        self::assertInstanceOf(FooDBAnnotation::class, $barAnnot->values[0]);
        self::assertInstanceOf(BarDBAnnotation::class, $barAnnot->values[1]);
        /** @var BarDBAnnotation $barAnnot2 */
        $barAnnot2 = $barAnnot->values[1];
        self::assertEquals(1, count($barAnnot2->values));
        self::assertInstanceOf(FooDBAnnotation::class, $barAnnot2->values[0]);
    }
}

class FooDBAnnotation implements DBAnnotation { }

class BarDBAnnotation implements DBAnnotation
{
    public $values;
    public function __construct(...$values)
    {
        $this->values = $values;
    }
}
