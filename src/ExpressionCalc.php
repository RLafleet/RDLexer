<?php
require_once 'Lexer.php';
require_once 'Parser.php';

class ExpressionCalc {
    public function calc($input) {
        $lexer = new Lexer($input);
        $tokens = $lexer->tokenize();
        $parser = new Parser($tokens);
        return $parser->parse();
    }
}