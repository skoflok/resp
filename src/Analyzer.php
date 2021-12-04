<?php

namespace Skoflok\Resp;

use Skoflok\Resp\Analyzers\Lexic as LexicAnalyzer;
use Skoflok\Resp\Analyzers\Syntax as SyntaxAnalyzer;
use Skoflok\Resp\Analyzers\Parser as Parser;

class Analyzer
{

    public function __construct(
        LexicAnalyzer $lexic,
        SyntaxAnalyzer $syntax,
        Parser $parser
        ) {

    }
}