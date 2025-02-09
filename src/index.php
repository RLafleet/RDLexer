<?php

class Token {
    public $type;
    public $pos;
    public $match;

    public function __construct($type, $pos, $match = '') {
        $this->type = $type;
        $this->pos = $pos;
        $this->match = $match;
    }
}

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
        ['type' => self::IDENTIFIER, 're' => '/^[_a-zA-Z][_a-zA-Z0-9]*/'],
        ['type' => self::END_OF_FILE, 're' => '/^$/']
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

class Parser {
    private $tokens;
    private $currentToken;

    public function __construct($tokens) {
        $this->tokens = $tokens;
        $this->currentToken = array_shift($this->tokens);
    }

    private function error($err) {
        throw new Exception("$err, pos={$this->currentToken->pos}, token={$this->currentToken->match}, type={$this->currentToken->type}");
    }

    private function next() {
        do {
            $this->currentToken = array_shift($this->tokens);
        } while ($this->currentToken && $this->currentToken->type === Lexer::WS);
    }

    private function accept($type) {
        if ($this->currentToken && $this->currentToken->type === $type) {
            $match = $this->currentToken->match;
            $this->next();
            return $match ?: true;
        }
        return false;
    }

    private function expect($type) {
        return $this->accept($type) ?: $this->error("expected $type");
    }

    private function factor() {
        $result = null;
        $text = null;

        if ($this->accept(Lexer::LEFT_PAR)) {
            $result = $this->expression();
            $this->expect(Lexer::RIGHT_PAR);
        } elseif ($text = $this->accept(Lexer::NUMBER)) {
            $result = floatval($text);
        } elseif ($this->accept(Lexer::PLUS)) {
            $result = +$this->factor();
        } elseif ($this->accept(Lexer::MINUS)) {
            $result = -$this->factor();
        } elseif ($text = $this->accept(Lexer::IDENTIFIER)) {
            $text = strtolower($text);

            if ($text === 'pi') {
                $result = pi();
            } elseif ($text === 'e') {
                $result = exp(1);
            } elseif (function_exists($text)) {
                $this->expect(Lexer::LEFT_PAR);
                $result = $text($this->expression());
                $this->expect(Lexer::RIGHT_PAR);
            } else {
                $this->error("unknown id $text");
            }
        } else {
            $this->error('unexpected input');
        }

        return $result;
    }

    private function term() {
        $result = $this->factor();

        while (true) {
            if ($this->accept(Lexer::MULTIPLY)) {
                $result *= $this->term();
            } elseif ($this->accept(Lexer::DIVIDE)) {
                $result /= $this->term();
            } elseif ($this->accept(Lexer::MOD)) {
                $result %= $this->term();
            } else {
                break;
            }
        }

        return $result;
    }

    private function expression() {
        $result = $this->term();

        while (true) {
            if ($this->accept(Lexer::PLUS)) {
                $result += $this->term();
            } elseif ($this->accept(Lexer::MINUS)) {
                $result -= $this->term();
            } else {
                break;
            }
        }

        return $result;
    }

    public function parse() {
        $result = $this->expression();
        $this->expect(Lexer::END_OF_FILE);
        return $result;
    }
}

class ExpressionCalc {
    public function calc($input) {
        $lexer = new Lexer($input);
        $tokens = $lexer->tokenize();
        $parser = new Parser($tokens);
        return $parser->parse();
    }
}

$tests = [
    ['expression' => '2+2', 'expected' => 4],
    ['expression' => '2+3-4', 'expected' => 1],
    ['expression' => '2+2*2', 'expected' => 6],
    ['expression' => '3+4*5', 'expected' => 23],
    ['expression' => '3/2+4*5', 'expected' => 21.5],
    ['expression' => '(2+2)*2', 'expected' => 8],
    ['expression' => '(02. + 0002.) * 002.000', 'expected' => 8],
    ['expression' => '3%2', 'expected' => 1],
    ['expression' => '+1', 'expected' => 1],
    ['expression' => '-(2+3)', 'expected' => -5],
    ['expression' => 'cos(2*pi)', 'expected' => cos(2 * M_PI)],
    ['expression' => '-2.1+ .355 / (cos(pi % 3) + sin(0.311))', 'expected' => -1.8281798930831],
    ['expression' => '+-+-', 'expected' => null],
    ['expression' => ')(', 'expected' => null],
    ['expression' => 'ab.5', 'expected' => null],
    ['expression' => 'ab.', 'expected' => null],
    ['expression' => '5a', 'expected' => null],
    ['expression' => 'fn(a,)', 'expected' => null],
    ['expression' => 'fn(a,b)', 'expected' => null],
    ['expression' => ')', 'expected' => null],
];

foreach ($tests as $test) {
    $expression = $test['expression'];
    $expected = $test['expected'];

    try {
        $result = (new ExpressionCalc())->calc($expression);
        if (is_float($expected) && abs($result - $expected) < 0.000001) {
            echo "Тест пройден для выражения \"$expression\": $result == $expected\n";
        } elseif ($result == $expected) {
            echo "Тест пройден для выражения \"$expression\": $result == $expected\n";
        } else {
            echo "Ошибка в тесте для выражения \"$expression\": $result !== $expected\n";
        }
    } catch (Exception $err) {
        if ($expected === null) {
            echo "Ожидаемая ошибка для выражения \"$expression\": {$err->getMessage()}\n";
        } else {
            echo "Неожиданная ошибка для выражения \"$expression\": {$err->getMessage()}\n";
        }
    }
}