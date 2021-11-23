<?php declare (strict_types = 1);
namespace Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Skoflok\Resp\Analyzers\Lexic as LexicAnalyzer;

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
            'with_rand' => ["asd+OK\r\nadas\r\n", 4, "OK"],
            'negative_integer' => ["$-1\r\n", 1, "-1"],
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
        try {
            $this->analyzer->cutStringToEnd($raw, $offset);
        } catch (\Throwable $th) {
            $this->assertInstanceOf(RuntimeException::class, $th);
        }
    }

    public function checkBulkStringDataProvider()
    {
        return [
            'ok' => [
                "$6\r\nfoobar\r\n",
            ],
            'long_string' => [
                "$27\r\checkBulkStringDataProvider\r\n",
            ],
            'null' => [
                "$-1\r\n",
            ],
            'empty' => [
                "$0\r\n\r\n",
            ],
        ];
    }

    /**
     * @dataProvider checkBulkStringDataProvider
     *
     * @return void
     */
    public function testCheckBulkString($raw)
    {
        $this->markTestIncomplete();
        $this->assertTrue($this->analyzer->checkBulkString($raw));
    }
}
