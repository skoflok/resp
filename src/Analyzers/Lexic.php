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

    private function explode($text, $init = []): array
    {
        foreach($string as $i => $s) {
            if(self::SIMPLE_STRING_TOKEN == $s) {
                $init[] = $s;
                $init[] = $this->cutStringToEnd($text, $i + 1);
            } elseif (self::ERROR_TOKEN == $s) {
                $init[] = $s;
                $init[] = $this->cutStringToEnd($text, $i + 1);
            } elseif (self::INTEGER_TOKEN == $s) {
                $init[] = $s;
                $init[] = (int) $this->cutStringToEnd($text, $i + 1);
            } elseif (self::BULK_STRINGS_TOKEN == $s) {
                $this->checkBulkString($text);
            }
        }
    }

    public function cutStringToEnd($text, $offset) {
        $end = mb_strpos($text, static::CRLF_TOKEN, $offset);
        if(false == $end) {
            throw new RuntimeException('Bad string: End token is not present');
        }
        $string = mb_substr($text, $offset, $end-$offset);
        return $string;
    }

    public function checkBulkString($text) : bool
    {
        if(self::BULK_STRINGS_TOKEN !=$text[0]) {
            throw new RuntimeException('Bad Bulk String format: Start token is not present');
        }

        $delimeter = substr($text, 1, strlen(static::CRLF_TOKEN));
        if(static::CRLF_TOKEN != $delimeter) {
            throw new RuntimeException('Bad Bulk String format: End token is not present');
        }

        return true;
    }
}