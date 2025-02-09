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

    public function __toString() {
        return "Token(type={$this->type}, pos={$this->pos}, match='{$this->match}')";
    }
}

class ExpressionCalc {
    private $stream;
    private $token;

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

    public function __construct($input) {
        $this->stream = $this->tokenize($input);
        $this->next();
    }

    private function tokenize($input) {
        $pos = 0;
        $unknownFrom = -1;
        $tokens = [];

        echo "Tokenizing input: \"$input\"\n";

        while ($input) {
            $posBefore = $pos;
            foreach (self::$matchers as $matcher) {
                $type = $matcher['type'];
                $re = $matcher['re'];
                if (preg_match($re, substr($input, $pos), $matches)) {
                    $match = $matches[0];
                    if ($unknownFrom >= 0) {
                        $tokens[] = new Token(self::UNKNOWN, $unknownFrom, substr($input, $unknownFrom, $pos));
                        $unknownFrom = -1;
                    }
                    $tokens[] = new Token($type, $pos, $match);
                    echo "Created token: {$tokens[count($tokens) - 1]}\n";
                    if ($type === self::END_OF_FILE) {
                        $input = null;
                    }
                    $pos += strlen($match);
                    break;
                }
            }
            if ($input && $posBefore === $pos) {
                if ($unknownFrom < 0) {
                    $unknownFrom = $pos;
                }
                $pos++;
            }
        }

        return $tokens;
    }

    public function calc() {
        echo "Starting calculation...\n";
        $result = $this->expression();
        $this->expect(self::END_OF_FILE);
        echo "Calculation result: $result\n";
        return $result;
    }

    private function error($err) {
        throw new Exception("$err, pos={$this->token->pos}, token={$this->token->match}, type={$this->token->type}");
    }

    private function next() {
        do {
            $this->token = array_shift($this->stream);
            echo "Next token: " . ($this->token ? $this->token : "EOF") . "\n";
        } while ($this->token && $this->token->type === self::WS);
    }

    private function accept($type) {
        if ($this->token && $this->token->type === $type) {
            $match = $this->token->match;
            echo "Accepted token: {$this->token}\n";
            $this->next();
            return $match ?: true;
        }
        echo "Failed to accept token of type: $type (current token: " . ($this->token ? $this->token : "EOF") . ")\n";
        return false;
    }

    private function expect($type) {
        if (!$this->accept($type)) {
            $this->error("expected $type");
        }
        echo "Expected token of type: $type accepted\n";
    }

    private function factor() {
        echo "Entering factor()\n";
        $result = null;
        $text = null;

        if ($this->accept(self::LEFT_PAR)) {
            echo "Found left parenthesis\n";
            $result = $this->expression();
            $this->expect(self::RIGHT_PAR);
            echo "Found right parenthesis\n";
        } elseif ($text = $this->accept(self::NUMBER)) {
            echo "Found number: $text\n";
            $result = floatval($text);
        } elseif ($this->accept(self::PLUS)) {
            echo "Found unary plus\n";
            $result = +$this->factor();
        } elseif ($this->accept(self::MINUS)) {
            echo "Found unary minus\n";
            $result = -$this->factor();
        } elseif ($text = $this->accept(self::IDENTIFIER)) {
            echo "Found identifier: $text\n";
            $text = strtolower($text);

            if ($text === 'pi') {
                $result = pi();
                echo "Resolved pi() to $result\n";
            } elseif ($text === 'e') {
                $result = exp(1);
                echo "Resolved e to $result\n";
            } elseif (function_exists($text)) {
                echo "Found function: $text\n";
                $this->expect(self::LEFT_PAR);
                $result = $text($this->expression());
                $this->expect(self::RIGHT_PAR);
            } else {
                $this->error("unknown id $text");
            }
        } else {
            $this->error('unexpected input');
        }

        echo "Exiting factor(), result: $result\n";
        return $result;
    }

    private function term() {
        echo "Entering term()\n";
        $result = $this->factor();

        while (true) {
            if ($this->accept(self::MULTIPLY)) {
                echo "Found multiply\n";
                $result *= $this->term();
            } elseif ($this->accept(self::DIVIDE)) {
                echo "Found divide\n";
                $result /= $this->term();
            } elseif ($this->accept(self::MOD)) {
                echo "Found mod\n";
                $result %= $this->term();
            } else {
                break;
            }
        }

        echo "Exiting term(), result: $result\n";
        return $result;
    }

    private function expression() {
        echo "Entering expression()\n";
        $result = $this->term();

        while (true) {
            if ($this->accept(self::PLUS)) {
                echo "Found plus\n";
                $result += $this->term();
            } elseif ($this->accept(self::MINUS)) {
                echo "Found minus\n";
                $result -= $this->term();
            } else {
                break;
            }
        }

        echo "Exiting expression(), result: $result\n";
        return $result;
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
    ['expression' => 'cos(2*pi())', 'expected' => cos(2 * pi())],
    ['expression' => '-2.1+ .355 / (cos(pi() % 3) + sin(0.311))', 'expected' => -2.1 + .355 / (cos(pi() % 3) + sin(0.311))],
    ['expression' => '+-+-', 'expected' => null],
    ['expression' => ')(', 'expected' => null],
    ['expression' => 'ab.5', 'expected' => null],
    ['expression' => 'ab.', 'expected' => null],
    ['expression' => '5a', 'expected' => null],
    ['expression' => 'fn(a,)', 'expected' => null],
    ['expression' => 'fn(a,b)', 'expected' => null],
    ['expression' => '.', 'expected' => null],
];

foreach ($tests as $test) {
    $expression = $test['expression'];
    $expected = $test['expected'];

    echo "\nRunning test for expression: \"$expression\"\n";
    try {
        $result = (new ExpressionCalc($expression))->calc();
        if (abs($result - $expected) < 0.000001) {
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