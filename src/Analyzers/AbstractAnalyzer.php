<?php

namespace Skoflok\Resp\Analyzers;

use Skoflok\Resp\RespTokenConstants as Constants;

abstract class AbstractAnalyzer
{
    protected $simpleStringToken;
    protected $errorToken;
    protected $integerToken;
    protected $bulkStringToken;
    protected $arrayToken;
    protected $crlfToken;

    public function __construct()
    {
        $this->simpleStringToken = Constants::SIMPLE_STRING_TOKEN;
        $this->errorToken = Constants::ERROR_TOKEN;
        $this->integerToken = Constants::INTEGER_TOKEN;
        $this->bulkStringToken = Constants::BULK_STRING_TOKEN;
        $this->arrayToken = Constants::ARRAY_TOKEN;
        $this->crlfToken = Constants::CRLF_TOKEN;
    }
}