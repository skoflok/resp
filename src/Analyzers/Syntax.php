<?php

namespace Skoflok\Resp\Analyzers;

use RuntimeException as RuntimeException;
use Skoflok\Resp\Analyzers\AbstractAnalyzer;

class Syntax extends AbstractAnalyzer
{
    public function validate(array $array)
    {

        $token = $this->getToken($array[0]);

        if($this->simpleStringToken == $token) {
            
        } elseif ($this->errorToken == $token) {
            
        } elseif ($this->integerToken == $token) {
            
        } elseif ($this->bulkStringToken == $token) {
            
        } elseif ($this->arrayToken == $token) {
            
            
        } else {
            throw new RuntimeException("Token parse error");
        }
        
    }

    private function validateSimpleString(array $tokens)
    {
        $message = 'Bad simple string format.';

        $openToken = $this->simpleStringToken;
        $closeToken = $this->crlfToken;

        $payloadCallable = function($payload, $message) {
            if(!is_string($payload)) {
                throw new RuntimeException($message . 'Bad payload');
            }
        };
        
        $newTokens = $this->validateSimpleTokens($tokens, $openToken, $closeToken, $payloadCallable, $message);

        return $newTokens;

    }

    private function validateErrorString(array $tokens)
    {
        $message = 'Bad error string format.';

        $openToken = $this->errorToken;
        $closeToken = $this->crlfToken;

        $payloadCallable = function($payload, $message) {
            if(!is_string($payload)) {
                throw new RuntimeException($message . 'Bad payload');
            }
        };
        
        $newTokens = $this->validateSimpleTokens($tokens, $openToken, $closeToken, $payloadCallable, $message);

        return $newTokens;
    }

    private function validateSimpleInteger(array $tokens)
    {
        $message = 'Bad integer format.';

        $openToken = $this->integerToken;
        $closeToken = $this->crlfToken;

        $payloadCallable = function($payload, $message) {
            $var = filter_var($payload, FILTER_VALIDATE_INT);
            if(false === $var) {
                throw new RuntimeException($message . 'Bad payload');
            }
        };
        
        $newTokens = $this->validateSimpleTokens($tokens, $openToken, $closeToken, $payloadCallable, $message);

        return $newTokens;
    }

    private function validateSimpleTokens(array $tokens, $openToken, $payloadCallable, $closeToken, $message)
    {
        $token = $tokens[0];
        if($token != $openToken) {
            throw new RuntimeException($message . 'Bad opening token');
        }

        $payload = $tokens[1];
        $payloadCallable($payload, $message);

        $crlf = $tokens[2];
        if($crlf != $closeToken) {
            throw new RuntimeException($message . 'Bad closing token');
        }

        $newTokens = array_slice($tokens, 3);
        return $newTokens;
    }

    private function getToken($value)
    {
        $index = array_search($value, $this->tokens, true);
        if(!$index) {
            throw new RuntimeException('Bad token');
        }
        return $this->tokens[$index];
    }

}