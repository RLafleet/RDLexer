<?php
require_once 'Token.php';

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

    private function block() {
        $result = null;
        do {
            // TODO L_3 Добавить вложенности блоков begin...end
            // TODO L_4 Добавить базовые ключевые слова помимо типа var, write, read
            // TODO L_5 Добавить базовые операторы типа: :=, if, case
            // TODO L_6 Реализовать обработку процедур и функций
            // TODO L_7 Добавить поддержку типов данных (integer, real, boolean, char, string)
            // TODO L_8 Реализовать обработку циклов (for, while, repeat...until)
            // TODO L_9 Добавить поддержку массивов и записей
            // TODO L_10 Добавить поддержку модулей и unit'ов
            if ($this->currentToken && $this->currentToken->type === Lexer::END) {
                break;
            }

            $result = $this->expression();
            if ($this->accept(Lexer::SEMICOLON)) {
                continue;
            } else {
                break;
            }
        } while (true);

        $this->expect(Lexer::END);
        $this->expect(Lexer::SEMICOLON);
        return $result;
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
        // TODO L_2 добавить обработку функций. Пример: fn(a) - ок.   fn() - зависит от реализации, может быть как ошибкой так и ок
        if ($this->accept(Lexer::BEGIN)) {
            return $this->block();
        } else {
            $result = $this->expression();
            $this->expect(Lexer::END_OF_FILE);
            return $result;
        }
    }
}