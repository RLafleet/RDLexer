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