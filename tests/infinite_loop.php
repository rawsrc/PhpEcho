<?php declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

$layout = new PhpEcho(file: 'layout_02.php', id: 'root');

$a = new PhpEcho(id: 'a');
$a->setCode('<p>Block a</p>');

$b = new PhpEcho(id: 'b');
$b->setCode('<p>Block b</p>');

$layout['a'] = $a;
$layout['b'] = $b;

$layout['block_01'] = $layout['a'];
$layout['block_02'] = $layout['a'];

ob_start();
echo $layout;
$html = ob_get_clean();

$pilot->run(
    id: 'infinite_loop_01',
    test: fn() => $html,
    description: 'infinite loop, multiple usage of the same block'
);
$pilot->assertIsString();
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  </head>
<body>
<p>Block a</p>
<p>Block b</p>
</body>
</html>
html);
