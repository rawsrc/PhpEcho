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

PhpEcho::setDetectInfiniteLoop(true);

$pilot->run(
    id: 'infinite_loop_01',
    test: fn() => $html,
    description: 'multiple usage of the same block, no infinite loop'
);
$pilot->assertIsString();
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  </head>
<body>
<p>Block a</p><p>Block a</p></body>
</html>
html);


$layout = new PhpEcho(file: 'layout_07.php', id: 'root');
$layout['block'] = new PhpEcho(file: 'block/block_07.php');

$pilot->run(
    id: 'infinite_loop_02',
    test: fn() => (string)$layout,
    description: 'infinite loop, block calling each others, infinite loop detection is on',
);
$pilot->assertException(InvalidArgumentException::class);


/* SERVER CRASH: INFINITE LOOP IS NOT DETECTED
PhpEcho::setDetectInfiniteLoop(false);
$layout = new PhpEcho(file: 'layout_07.php', id: 'root');
$layout['block'] = new PhpEcho(file: 'block/block_07.php');

$pilot->run(
    id: 'infinite_loop_03',
    test: fn() => (string)$layout,
    description: 'infinite loop, block calling each others, infinite loop detection is off',
);
$pilot->assertException(InvalidArgumentException::class);
*/
