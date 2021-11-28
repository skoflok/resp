<?php

namespace Skoflok\Resp\Analyzers;
use RuntimeException as RuntimeException;

final class Lexic
{
    const SIMPLE_STRING_TOKEN = '+';
    const ERROR_TOKEN = '-';
    const INTEGER_TOKEN = ':';
    const BULK_STRINGS_TOKEN = '$';
    const ARRAY_TOKEN = '*';
    const CRLF_TOKEN = "\r\n";

    const TOKENS = [
        self::SIMPLE_STRING_TOKEN,
        self::ERROR_TOKEN,
        self::INTEGER_TOKEN,
        self::BULK_STRINGS_TOKEN,
        self::ARRAY_TOKEN,
        self::CRLF_TOKEN,
    ];

    const TOKENS_WITH_COUNTER_POSTFIX = [
        self::ARRAY_TOKEN,
        self::BULK_STRINGS_TOKEN,
    ];

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
            $s = $text[0];
            if(self::SIMPLE_STRING_TOKEN == $s) {
                $tokens = $this->extractSimpleElement($text, $i + 1, "string");
            } elseif (self::ERROR_TOKEN == $s) {
                $tokens = $this->extractSimpleElement($text, $i + 1, "string");
            } elseif (self::INTEGER_TOKEN == $s) {
                $tokens = $this->extractSimpleElement($text, $i + 1, "int");
            } elseif (self::BULK_STRINGS_TOKEN == $s) {
                $tokens = $this->extractBulkString($text);
            } elseif (self::ARRAY_TOKEN == $s) {
                $this->extractArray($text);
            }
        }

        $result = array_merge($init, $tokens);
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
        $tokens[] = $text[0];
        if($return == 'string') {
            $tokens[] = $this->cutStringToEnd($text, $postion + 1);
        } elseif ($return == 'int') {
            $tokens[] = (int) $this->cutStringToEnd($text, $postion + 1);
        } else {
            throw new RuntimeException('Bad type returned value');
        }
        $tokens[] = self::CRLF_TOKEN;
        return $tokens;
    }

    public function extractBulkString(string $text) : array
    {
        $tokens = [];
        [$length, $string] = $this->prepareBulkString($text);
        $tokens[] = $text[0];
        $tokens[] = $length;
        $tokens[] = self::CRLF_TOKEN;
        $tokens[] = $string;
        $tokens[] = self::CRLF_TOKEN;
        return $tokens;
    }

    public function extractArray(string $text) : array
    {
        $tokens = [];
        if(self::ARRAY_TOKEN != $text[0]) {
            throw new RuntimeException('RESP: Bad array token');
        }
        $tokens[] = self::ARRAY_TOKEN;
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

        $tokens[] = $length;
        $tokens[] = self::CRLF_TOKEN;

        $offset = strlen(self::ARRAY_TOKEN) + $lengthOfLength + strlen(self::CRLF_TOKEN);

        $endOfString = mb_strpos($text, $offset);

        if(self::CRLF_TOKEN == $endOfString) {
            $tokens[] = self::CRLF_TOKEN;
            return $tokens;
        } else {
            $elementTokens = $this->explode($endOfString);
            return array_merge($tokens, $elementTokens);
        }

        
    }

    public function prepareArray(array $text) : array
    {

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
        $end = mb_strpos($text, static::CRLF_TOKEN, $offset);
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
        if(self::BULK_STRINGS_TOKEN !=$text[0]) {
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
            $delimeter = substr($text, 1 + $lengthOfSuffix, strlen(static::CRLF_TOKEN));
            $bulkString = substr($text, 1 + $lengthOfSuffix + strlen(static::CRLF_TOKEN), $bulkStringLength);
            if(static::CRLF_TOKEN != $delimeter) {
                throw new RuntimeException('Bad Bulk String format: End token is not present');
            }
        }


        return [$bulkStringLength, $bulkString];
    }
}