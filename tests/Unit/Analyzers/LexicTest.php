<?php declare (strict_types = 1);
namespace Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Skoflok\Resp\Analyzers\Lexic as LexicAnalyzer;

/**
 * @coversDefaultClass Skoflok\Resp\Analyzers\Lexic
 * @group unit
 * @group unit-lexic
 */
class LexicTest extends TestCase
{
    private $analyzer;

    public function setUp(): void
    {
        $this->analyzer = new LexicAnalyzer();
    }

    public function commandProvider()
    {
        return [
            'keys_wildcard' => [
                '*2\r\n$4\r\nkeys\r\n$1\r\n*\r\n',
                'keys *',
                ['*', '2', '\r\n',
                    '$', '4', '\r\n', 'keys', '\r\n',
                    '$', '1', '\r\n', '*', '\r\n'],
            ],
            'ok' => [
                '+OK\r\n',
                'OK',
                ['+', 'OK', '\r\n'],
            ],
            'long_list' => [
                '*4\r\n$6\r\nLRANGE\r\n$8\r\ntestlist\r\n$1\r\n0\r\n$2\r\n-1\r\n',
                'lrange testlist 0 -1',
                ['*', '4', '\r\n',
                    '$', '6', '\r\n', 'LRANGE', '\r\n',
                    '$', '8', '\r\n', 'testlist', '\r\n',
                    '$', '1', '\r\n', '0', '\r\n',
                    '$', '2', '\r\n', '-1', '\r\n'],
                // [
                //     '*13\r','\n','$13\r','\n','aaaaaaaaaaa13\r','\n','$3\r','\n','a12\r','\n','$3\r','\n','a11\r','\n','$3\r','\n','a10\r','\n','$2\r','\n','a9\r','\n','$2\r','\n','a8\r','\n','$2\r','\n','a7\r','\n','$2\r','\n','a6\r','\n','$2\r','\n','a5\r','\n','$2\r','\n','a4\r','\n','$2\r','\n','a3\r','\n','$2\r','\n','a2\r','\n','$2\r','\n','a1\r','\n'
                // ]
            ],
        ];
    }

    /**
     * @dataProvider commandProvider
     *
     * @param [type] $raw
     * @param [type] $command
     * @param [type] $tokens
     * @return void
     */
    public function testUnserialize($raw, $command, $tokens)
    {
        $this->markTestIncomplete();
        $out = $this->analyzer->unserialize($raw);
        $this->assertEquals($tokens, $out);
    }

    public function SimpleStringDataProvider()
    {
        return [
            'ok' => ["+OK\r\n", 1, "OK"],
            'long_int' => [":12345\r\n", 1, "12345"],
            'with_rand' => ["asd+OK\r\nadas\r\n", 4, "OK"],
            'negative_integer' => ["$-1\r\n", 1, "-1"],
            'long_string' => [":1\r\n:2\r\n:3\r\n", 1, "1"],
        ];
    }

    /**
     * @dataProvider SimpleStringDataProvider
     *
     * @return void
     */
    public function testCutStringToEnd($raw, $offset, $expected)
    {
        $out = $this->analyzer->cutStringToEnd($raw, $offset);
        $this->assertEquals($expected, $out);
    }

    public function failSimpleStringDataProvider()
    {
        return [
            'runtime_exception_r' => ["+1234\r", 1],
            'runtime_exception_n' => ["12+1234\n", 3],
            'runtime_exception' => ["++1234", 2],
            'runtime_exception_empty' => ["+\r\n", 1],
        ];
    }

    /**
     * @dataProvider failSimpleStringDataProvider
     *
     * @param [type] $raw
     * @param [type] $offset
     * @return void
     */
    public function testFailCutStringToEnd($raw, $offset)
    {
        $check = false;
        try {
            $this->analyzer->cutStringToEnd($raw, $offset);
        } catch (\Throwable $th) {
            $check = true;
            $this->assertInstanceOf(RuntimeException::class, $th);
        }

        $this->assertTrue($check);
    }

    public function prepareBulkStringDataProvider()
    {
        return [
            'ok' => [
                "$6\r\nfoobar\r\n", 6, "foobar",
            ],
            'long_string' => [
                "$27\r\ncheckBulkStringDataProvider\r\n", 27, "checkBulkStringDataProvider",
            ],
            'null' => [
                "$-1\r\n", -1, null,
            ],
            'empty' => [
                "$0\r\n\r\n", 0, "",
            ],
        ];
    }

    /**
     * @dataProvider prepareBulkStringDataProvider
     *
     * @return void
     */
    public function testPrepareBulkString($raw, $expectedLength, $expectedString)
    {
        [$length, $string] = $this->analyzer->prepareBulkString($raw);
        $this->assertEquals($expectedLength, $length);
        $this->assertEquals($expectedString, $string);

    }

    public function failPrepareBulkStringDataProvider()
    {
        return [
            'without_bad_bulk_token' => ["+-1\r\n"],
            'without_any_token' => ["-1\r\n"],
        ];
    }

    /**
     * @dataProvider failPrepareBulkStringDataProvider
     *
     * @param [type] $raw
     * @return void
     */
    public function testFailPrepareBulkString($raw)
    {
        $check = false;
        try {
            $this->analyzer->prepareBulkString($raw);
        } catch (\Throwable $th) {
            $check = true;
            $this->assertInstanceOf(RuntimeException::class, $th);
        }

        $this->assertTrue($check);
    }

    public function arrayProvider()
    {
        return [
            'empty' => ["*0\r\n", ["*", 0, "\r\n"]],
            'null' => ["*-1\r\n", ["*", -1, null, "\r\n"]],
            'three_integers' => [
                "*3\r\n:1\r\n:2\r\n:3\r\n",
                [
                    "*", 3, "\r\n",
                    ":", 1, "\r\n",
                    ":", 2, "\r\n",
                    ":", 3, "\r\n",
                ],
            ],
            'five_elements' => [
                "*5\r\n:1\r\n:2\r\n:3\r\n+Foo\r\n-Bar\r\n",
                [
                    "*", 5, "\r\n",
                    ":", 1, "\r\n", 
                    ":", 2, "\r\n", 
                    ":", 3, "\r\n", 
                    "+", "Foo", "\r\n", 
                    "-", "Bar", "\r\n"
                ],
            ],

            'five_elements_with_bulk_string' => [
                "*5\r\n$27\r\ncheckBulkStringDataProvider\r\n:2\r\n:3\r\n+Foo\r\n-Bar\r\n",
                [
                    "*", 5, "\r\n",
                    "$", 27, "\r\n", "checkBulkStringDataProvider", "\r\n",
                    ":", 2, "\r\n", 
                    ":", 3, "\r\n", 
                    "+", "Foo", "\r\n", 
                    "-", "Bar", "\r\n"
                ],
            ],
        ];
    }

    /**
     * @dataProvider arrayProvider
     *
     * @param [type] $raw
     * @param [type] $expected
     * @return void
     */
    public function testExtractArray($raw, $expected)
    {
        $tokens = $this->analyzer->extractArray($raw);
        $this->assertEquals($expected, $tokens);
    }

    public function extractSimpleElementProvider()
    {
        return [
            'three_integer' => [":1\r\n:2\r\n:3\r\n", 1, "int", [":", 1, "\r\n"]],
        ];
    }

    /**
     * @dataProvider extractSimpleElementProvider
     *
     * @param [type] $raw
     * @param [type] $postion
     * @param [type] $return
     * @param [type] $expected
     * @return void
     */
    public function testExtractSimpleElement($raw, $postion, $return, $expected)
    {
        $tokens = $this->analyzer->extractSimpleElement($raw, $postion, $return);
        $this->assertEquals($expected, $tokens);
    }

    public function testExplode()
    {
        $this->markTestIncomplete();
    }

}
