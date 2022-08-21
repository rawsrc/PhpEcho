<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // MAIN LAYOUT ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <?= $this->getHead() ?>
</head>
<body>
<?= $this->renderByDefault('abc', 'block block_03.php', ['block_03_text' => 'default value for block']) ?>
</body>
</html>