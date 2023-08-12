<?php declare(strict_types=1);

use Exacodis\Pilot;
use rawsrc\PhpEcho\PhpEcho;

/** @var Pilot $pilot */

PhpEcho::setTemplateDirRoot(__DIR__.DIRECTORY_SEPARATOR.'view');

$layout = new PhpEcho('layout_01.php');
$layout['body'] = new PhpEcho('block/block_01.php', ['block_01_text' => 'run_txt_block_01']);
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_01',
    test : fn() => $html,
    description : 'layout with one block passing one clear value to a block'
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


$layout = new PhpEcho('layout_01.php');
$layout['body'] = new PhpEcho('block/block_01.php', ['block_01_text' => 'abc " < >']);
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_02',
    test : fn() => $html,
    description : 'layout with one block passing one dangerous value to a block - test auto-escaping'
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


$layout = new PhpEcho('layout_01.php');
$layout['body'] = [
    new PhpEcho('block/block_01.php', ['block_01_text' => 'run_txt_block_01']),
    new PhpEcho('block/block_02.php', ['block_02_text' => 'abc " < >']),
];
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_03',
    test : fn() => $html,
    description : 'layout with array of PhpEcho blocks defined as value of array - test auto-escaping'
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


$layout = new PhpEcho('layout_01.php', ['body' => [
    new PhpEcho('block/block_01.php', ['block_01_text' => 'run_txt_block_01']),
    new PhpEcho('block/block_02.php', ['block_02_text' => 'abc " < >']),
]]);

ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_04',
    test : fn() => $html,
    description : 'layout with array of PhpEcho blocks defined in the constructor - test auto-escaping'
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


$layout = new PhpEcho('layout_02.php');
$layout['block_01'] = new PhpEcho('block/block_01.php', ['block_01_text' => 'run_txt_block_01']);
$layout['block_02'] = new PhpEcho('block/block_02.php', ['block_02_text' => 'abc " < >']);

ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_05',
    test : fn() => $html,
    description : 'layout with two blocks defined - test auto-escaping'
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

$layout = new PhpEcho('layout_03.php');
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_06',
    test : fn() => $html,
    description : 'layout with a block loading another block - test auto-escaping'
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


$layout = new PhpEcho('layout_04.php');
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_07',
    test : fn() => $html,
    description : 'layout with rendering a block by default because it is not defined'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    </head>
<body>
<p>default value for block</p></body>
</html>
html);


$layout = new PhpEcho('layout_04.php');
$layout['abc'] = new PhpEcho('block/block_02.php', ['block_02_text' => 'abc']);
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_08',
    test : fn() => $html,
    description : 'providing a block to a layout that renders a block by default if not provided'
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


$layout = new PhpEcho('layout_05.php');
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_09',
    test : fn() => $html,
    description : 'layout with rendering a unreachable block by var'
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


$layout = new PhpEcho('layout_06.php');
$layout['block_03_text'] = 'foo_text';
$layout->addBlock('block', 'block/block_03.php'); // bloc_03 expects to have a value for 'block_03_text' which is defined in the layout
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_10',
    test : fn() => $html,
    description : 'passing vars to child blocks if no vars defined in the constructor'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    </head>
<body>
<p>foo_text</p></body>
</html>
html);


$layout = new PhpEcho('layout_07.php');
$layout['block'] = new PhpEcho('block/block_05.php');
ob_start();
echo $layout;
$html = ob_get_clean();
$pilot->run(
    id : 'view_11',
    test : fn() => $html,
    description : 'inserting data into the <head></head> section from the depths of a PhpEcho tree'
);
$pilot->assertEqual(<<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="dummy description"><meta name="keywords" content="one two three words"></head>
<body>
<p>abcdef</p>
</body>
</html>
html);