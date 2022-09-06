<?php

declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::setTemplateDirRoot(__DIR__.DIRECTORY_SEPARATOR.'view');

$block = new PhpEcho();
$block['body'] = new PhpEcho('block block_01.php', ['block_01_text' => 'run_txt_block_01']);

$view = <<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  {$block->getHead()}
</head>
<body>
{$block['body']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_01',
    test : fn() => $html,
    description : 'heredoc notation passing one clear value to the block'
);
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

$block = new PhpEcho();
$block['body'] = new PhpEcho('block block_01.php', ['block_01_text' => 'abc " < >']);

$view = <<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  {$block->getHead()}
</head>
<body>
{$block['body']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_02',
    test : fn() => $html,
    description : 'heredoc notation passing dangerous value to the value'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  
</head>
<body>
<p>abc &quot; &lt; &gt;</p>

</body>
</html>
html);


$block = new PhpEcho();
$block['body'] = [
    new PhpEcho('block block_01.php', ['block_01_text' => 'run_txt_block_01']),
    new PhpEcho('block block_02.php', ['block_02_text' => 'abc " < >']),
];

$view = <<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  {$block->getHead()}
</head>
<body>
{$block['body']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_03',
    test : fn() => $html,
    description : 'heredoc notation passing array of blocks'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  
</head>
<body>
<p>run_txt_block_01</p>
<p>abc &quot; &lt; &gt;</p>

</body>
</html>
html);


$block = new PhpEcho(vars: ['body' => [
    new PhpEcho('block block_01.php', ['block_01_text' => 'run_txt_block_01']),
    new PhpEcho('block block_02.php', ['block_02_text' => 'abc " < >']),
]]);

$view = <<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  {$block->getHead()}
</head>
<body>
{$block['body']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_04',
    test : fn() => $html,
    description : 'heredoc notation passing array of blocks to the constructor'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  
</head>
<body>
<p>run_txt_block_01</p>
<p>abc &quot; &lt; &gt;</p>

</body>
</html>
html);


$block = new PhpEcho();
$block['block_01'] = new PhpEcho('block block_01.php', ['block_01_text' => 'run_txt_block_01']);
$block['block_02'] = new PhpEcho('block block_02.php', ['block_02_text' => 'abc " < >']);

$view = <<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  {$block->getHead()}
</head>
<body>
{$block['block_01']}
{$block['block_02']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_05',
    test : fn() => $html,
    description : 'heredoc notation with 2 vars'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  
</head>
<body>
<p>run_txt_block_01</p>

<p>abc &quot; &lt; &gt;</p>

</body>
</html>
html);



$block = new PhpEcho();

$view = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {$block->getHead()}
</head>
<body>
{$block->addBlock('abc', 'block block_04.php')}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_06',
    test : fn() => $html,
    description : 'heredoc notation with block loading another block'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
</head>
<body>
<p>abc &quot; &lt; &gt;</p>

</body>
</html>
html);



$block = new PhpEcho();

$view = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {$block->getHead()}
</head>
<body>
{$block->renderByDefault('abc', 'block block_03.php', ['block_03_text' => 'default value for block'])}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_07',
    test : fn() => $html,
    description : 'heredoc notation rendering a block by default because it is not defined'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
</head>
<body>
<p>default value for block</p>
</body>
</html>
html);



$block = new PhpEcho();
$block['abc'] = new PhpEcho('block block_02.php', ['block_02_text' => 'abc']);

$view = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {$block->getHead()}
</head>
<body>
{$block->renderByDefault('abc', 'block block_03.php', ['block_03_text' => 'default value for block'])}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_08',
    test : fn() => $html,
    description : 'heredoc notation providing a block to a code that renders a block by default if not provided'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
</head>
<body>
<p>abc</p>

</body>
</html>
html);


$block = new PhpEcho();
$block['abc'] = new PhpEcho('block block_02.php', ['block_02_text' => 'abc']);

$view = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {$block->getHead()}
</head>
<body>
{$block->renderBlock('block block_04.php')}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_09',
    test : fn() => $html,
    description : 'heredoc notation providing an unreachable block by var'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
</head>
<body>
<p>abc &quot; &lt; &gt;</p>

</body>
</html>
html);



$block = new PhpEcho();
$block['block_03_text'] = 'foo_text';
$block->addBlock('block', 'block block_03.php'); // bloc_03 expects to have a value for 'block_03_text' which is defined in the layout

$view = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {$block->getHead()}
</head>
<body>
{$block['block']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_10',
    test : fn() => $html,
    description : 'heredoc notation passing vars to child blocks if no vars defined in the constructor'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
</head>
<body>
<p>foo_text</p>
</body>
</html>
html);



$block = new PhpEcho();
$block['block'] = new PhpEcho('block block_05.php');

$view = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {$block->getHead()}
</head>
<body>
{$block->addHead('<meta name="description" content="dummy description">')}
{$block['block']}
</body>
</html>
html;

$block->setCode($view);
ob_start();
echo $block;
$html = ob_get_clean();
$pilot->run(
    id : 'heredoc_11',
    test : fn() => $html,
    description : 'heredoc notation inserting data into the <head></head> section from the depths of a PhpEcho tree'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="dummy description"><meta name="keywords" content="one two three words">
</head>
<body>

<p>abcdef</p>

</body>
</html>
html);