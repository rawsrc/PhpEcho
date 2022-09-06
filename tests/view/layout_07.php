<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // MAIN LAYOUT ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <?= $this->getHead() ?>
</head>
<body>
<?= $this->addHead('<meta name="description" content="dummy description">') ?>
<?= $this['block'] ?>
</body>
</html>