<?php
require_once 'ExpressionCalc.php';

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
    ['expression' => 'begin 2+2; end;', 'expected' => 4],
    ['expression' => 'begin 2+3-4; end;', 'expected' => 1],
    ['expression' => 'begin (2+2)*2; end;', 'expected' => 8],
    ['expression' => 'begin 2+2; 3+3; end;', 'expected' => 6], // Если что, возращает последний результат
    ['expression' => 'begin end;', 'expected' => null],
    ['expression' => 'begin 2+2 end;', 'expected' => null],
    ['expression' => 'begin 2+2;', 'expected' => null],
    ['expression' => '2+2; end;', 'expected' => null],
];

// TODO L_2 need to do the function processing. EXAMPLE fn()

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