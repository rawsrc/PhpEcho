<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;
use rawsrc\PhpEcho\ViewBuilder;

class Login extends ViewBuilder
{
    public function build(): PhpEcho
    {
        $layout = new PhpEcho('layout_01.php');
        $layout['body'] = new PhpEcho('block/block_01.php', ['block_01_text' => 'run_txt_block_01']);

        return $layout;
    }
}

/** @var Pilot $pilot */

$pilot->run(
    id: 'view.builder.001',
    test: function() {
        $view = new Login();

        return $view->build();
    },
    description: 'test the return value from view builder',
);
$pilot->assertIsInstanceOf(PhpEcho::class);

$login = new Login();
$login['foo'] = 'bar';
$login['abc'] = '" < > "';
$pilot->run(
    id: 'view.builder.002',
    test: fn() => $login['abc'],
    description: 'array access, value never escaped',
);
$pilot->assertIsString();
$pilot->assertEqual('" < > "');

$pilot->run(
    id: 'view.builder.003',
    test: fn() => (string)new Login(),
    description: 'return a view builder as a string',
);
$pilot->assertIsString();
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  </head>
<body>
<p>run_txt_block_01</p>
</body>
</html>
html);
