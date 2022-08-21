<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
<?= $this->addBlock('block block_01.php', ['block_01_text' => 'run_01_text']) ?>
<?= $this->addBlock('block block_02.php', ['block_02_text' => 'run_02_text']) ?>
<?= $this->addBlock('block block_03.php', ['block_03_text' => 'run_03_text']) ?>
</body>
</html>