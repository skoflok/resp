<?php

namespace Skoflok\Resp;


class RespTokenConstants
{
    const SIMPLE_STRING_TOKEN = '+';
    const ERROR_TOKEN = '-';
    const INTEGER_TOKEN = ':';
    const BULK_STRING_TOKEN = '$';
    const ARRAY_TOKEN = '*';
    const CRLF_TOKEN = "\r\n";

    const TOKENS = [
        self::SIMPLE_STRING_TOKEN,
        self::ERROR_TOKEN,
        self::INTEGER_TOKEN,
        self::BULK_STRING_TOKEN,
        self::ARRAY_TOKEN,
        self::CRLF_TOKEN,
    ];

    const TOKENS_WITH_COUNTER_POSTFIX = [
        self::ARRAY_TOKEN,
        self::BULK_STRING_TOKEN,
    ];
}