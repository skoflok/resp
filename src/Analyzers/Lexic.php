<?php

namespace Skoflok\Resp\Analyzers;
use RuntimeException as RuntimeException;
use Skoflok\Resp\Analyzers\AbstractAnalyzer;

class Lexic extends AbstractAnalyzer
{

    public function unserialize(string $text) : array
    {
        $raw = $this->explode($text);

        return [];
    }

    public function explode($text, $init = []): array
    {
        $tokens = [];
        $length = mb_strlen($text);
        for ($i=0; $i < $length ; $i++) { 
            $s = $text[$i];
            if($this->simpleStringToken == $s) {
                $newTokens = $this->extractSimpleElement($text, $i + 1, "string");
            } elseif ($this->errorToken == $s) {
                $newTokens = $this->extractSimpleElement($text, $i + 1, "string");
            } elseif ($this->integerToken == $s) {
                $newTokens = $this->extractSimpleElement($text, $i + 1, "int");
            } elseif ($this->bulkStringToken == $s) {
                $newTokens = $this->extractBulkString($text);
            } elseif ($this->arrayToken == $s) {
                $subText = mb_substr($text, $i);
                $newTokens = $this->extractArray($subText);
            } else {
                throw new RuntimeException("Token parse error");
            }
            $tokens = array_merge($tokens, $newTokens);

            $offset = $this->calcOffset($tokens);
            $i = $offset - 1;
        }

        $result = array_merge($init, $tokens);

        return $result;
    }

    private function calcOffset(array $tokens) : int
    {
        $string = implode('', $tokens);
        return strlen($string);
    }

    /**
     * Undocumented function
     *
     * @param string $text
     * @param integer $postion
     * @param [type] $return
     * @return array
     */
    public function extractSimpleElement(string $text, int $postion, $return) : array
    {
        $tokens = [];
        // по-идее, предыдущий элемент должен быть токеном.
        $tokens[] = $text[$postion - 1];
        if($return == 'string') {
            $tokens[] = $this->cutStringToEnd($text, $postion);
        } elseif ($return == 'int') {
            $tokens[] = (int) $this->cutStringToEnd($text, $postion);
        } else {
            throw new RuntimeException('Bad type returned value');
        }
        $tokens[] = $this->crlfToken;
        return $tokens;
    }

    public function extractBulkString(string $text) : array
    {
        $tokens = [];
        [$length, $string] = $this->prepareBulkString($text);
        $tokens[] = $text[0];
        $tokens[] = $length;
        $tokens[] = $this->crlfToken;
        $tokens[] = $string;
        $tokens[] = $this->crlfToken;
        return $tokens;
    }

    public function extractArray(string $text) : array
    {
        $tokens = [];
        if($this->arrayToken != $text[0]) {
            throw new RuntimeException('RESP: Bad array token');
        }
        $tokens[] = $this->arrayToken;
        $length = $this->cutStringToEnd($text, 1);
        if(is_int($length)) {
            throw new RuntimeException('RESP: Bad length array');
        } else {
            $length = (int) $length;
        }
        $lengthOfLength = strlen(strval($length));

        $tokens[] = $length;

        if(-1 == $length) {
            // For instance when the BLPOP command times out, 
            // it returns a Null Array that has a count of -1 as in the following example: "*-1\r\n"
            $tokens[] = null;
        } elseif(0 == $length) {
            // So an empty Array is just the following: "*0\r\n"

        } elseif ($length > 0) {

        } else {
            throw new RuntimeException('RESP: Bad length of array');
        }

        $tokens[] = $this->crlfToken;

        $offset = strlen($this->arrayToken) + $lengthOfLength + strlen($this->crlfToken);

        $endOfString = mb_substr($text, $offset);

        if($this->crlfToken == $endOfString || !$endOfString) {
            return $tokens;
        } else {
            $elementTokens = $this->explode($endOfString);
            return array_merge($tokens, $elementTokens);
        }

        
    }

    
    /**
     * Undocumented function
     *
     * @param string $text
     * @param integer $offset
     * @return string
     */
    public function cutStringToEnd(string $text, int $offset): string
    {
        $end = mb_strpos($text, $this->crlfToken, $offset);
        if(false == $end) {
            throw new RuntimeException('Bad string: End token is not present');
        }
        $string = mb_substr($text, $offset, $end-$offset);
        if("" === $string) {
            throw new RuntimeException('Bad string: Empty string');
        }
        return $string;
    }

    public function prepareBulkString($text) : array
    {
        if($this->bulkStringToken !=$text[0]) {
            throw new RuntimeException('Bad Bulk String format: Start token is not present');
        }

        $bulkStringLength = (int) $this->cutStringToEnd($text, 1);
        if(0 === $bulkStringLength) {
            // When an empty string is just: "$0\r\n\r\n"
            $bulkString = "";
        } elseif (-1 === $bulkStringLength) {
            // "$-1\r\n"
            // This is called a Null Bulk String
            $bulkString = null;
        } else {
            $lengthOfSuffix = strlen(strval($bulkStringLength));
            $delimeter = substr($text, 1 + $lengthOfSuffix, strlen($this->crlfToken));
            $bulkString = substr($text, 1 + $lengthOfSuffix + strlen($this->crlfToken), $bulkStringLength);
            if($this->crlfToken != $delimeter) {
                throw new RuntimeException('Bad Bulk String format: End token is not present');
            }
        }


        return [$bulkStringLength, $bulkString];
    }
}