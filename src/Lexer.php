<?php

require_once 'Token.php';

class Lexer {
    const WS = 'ws';
    const NUMBER = 'num';
    const PLUS = 'plus';
    const MINUS = 'minus';
    const MULTIPLY = 'mul';
    const DIVIDE = 'div';
    const MOD = 'mod';
    const LEFT_PAR = 'lpar';
    const RIGHT_PAR = 'rpar';
    const IDENTIFIER = 'id';
    const UNKNOWN = 'unknown';
    const END_OF_FILE = 'eof';
    const BEGIN = 'begin';
    const END = 'end';
    const SEMICOLON = 'semicolon';

    private static $matchers = [
        ['type' => self::NUMBER, 're' => '/^[0-9\.]+/'],
        ['type' => self::PLUS, 're' => '/^\+/'],
        ['type' => self::MINUS, 're' => '/^[-?]/'],
        ['type' => self::MULTIPLY, 're' => '/^[*×?]/'],
        ['type' => self::DIVIDE, 're' => '/^\//'],
        ['type' => self::MOD, 're' => '/^%/'],
        ['type' => self::WS, 're' => '/^[\s]+/'],
        ['type' => self::LEFT_PAR, 're' => '/^\(/'],
        ['type' => self::RIGHT_PAR, 're' => '/^\)/'],
        ['type' => self::END_OF_FILE, 're' => '/^$/'],
        ['type' => self::BEGIN, 're' => '/^begin/i'],
        ['type' => self::END, 're' => '/^end/i'],
        ['type' => self::SEMICOLON, 're' => '/^;/'],
        ['type' => self::IDENTIFIER, 're' => '/^[_a-zA-Z][_a-zA-Z0-9]*/'],
    ];

    private $input;
    private $pos;

    public function __construct($input) {
        $this->input = $input;
        $this->pos = 0;
    }

    public function tokenize() {
        $tokens = [];
        $unknownFrom = -1;

        while ($this->pos < strlen($this->input)) {
            $posBefore = $this->pos;
            $matched = false;

            foreach (self::$matchers as $matcher) {
                $type = $matcher['type'];
                $re = $matcher['re'];
                if (preg_match($re, substr($this->input, $this->pos), $matches)) {
                    $match = $matches[0];
                    if ($unknownFrom >= 0) {
                        $tokens[] = new Token(self::UNKNOWN, $unknownFrom, substr($this->input, $unknownFrom, $this->pos - $unknownFrom));
                        $unknownFrom = -1;
                    }
                    $tokens[] = new Token($type, $this->pos, $match);
                    $this->pos += strlen($match);
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                if ($unknownFrom < 0) {
                    $unknownFrom = $this->pos;
                }
                $this->pos++;
            }
        }

        $tokens[] = new Token(self::END_OF_FILE, $this->pos);
        return $tokens;
    }
}