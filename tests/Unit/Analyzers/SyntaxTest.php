<?php declare (strict_types = 1);
namespace Tests\Unit\Analyzers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Skoflok\Resp\Analyzers\Syntax as SyntaxAnalyzer;

/**
 * @coversDefaultClass Skoflok\Resp\Analyzers\Syntax
 * @group unit
 * @group unit-syntax
 */
class SyntaxTest extends TestCase
{

    public function setUp(): void
    {
        $this->analyzer = new SyntaxAnalyzer();
    }


    public function validateProvider()
    {
        return [
            'simple_string' => [
                ["+", "OK", "\r\n"]
            ],
            'bulk_string' => [
                ["$", 27, "\r\n", "checkBulkStringDataProvider", "\r\n"],
            ],
        ];
    }
    

    /**
     * @dataProvider validateProvider
     *
     * @return void
     */
    public function testValidate()
    {
        $this->markTestIncomplete();
    }

    public function failFirstTokenProvider()
    {
        return [
            'fist' => [
                ["?", "ada"],
            ],
            'second' => [
                ["a", "sadasd"]
            ],
        ];
    }

    /**
     * @dataProvider failFirstTokenProvider
     *
     * @param [type] $input
     * @return void
     */
    public function testFailFirstToken($input)
    {
        $status = false;
        try {
            $this->analyzer->validate($input);
        } catch (\Throwable $th) {
            $status = true;
            $this->assertInstanceOf(RuntimeException::class, $th);
        }
        $this->assertTrue($status);
    }

    public function failProvider()
    {
        return [
            'bad_bulk_string' => [
                ["$", 2,  "OK", "\r\n"],
                "Bad bulk string format"
            ],
            'bad_lentgh_bulk_string' => [
                ["$", 4, "\r\n",  "OK", "\r\n"],
                "Bad length of string"
            ],
            'bad_simple_string' => [
                ["+", "\r\n", "OK", "\r\n"],
                "Bad simple string format"
            ]
        ];
    }

    /**
     * @dataProvider failProvider
     *
     * @param [type] $input
     * @param [type] $message
     * @return void
     */
    public function testFailValidate($input, $message)
    {
        $this->markTestIncomplete();
    }


}