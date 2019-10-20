# **PhpEcho**

`2019-10-20` `PHP 7+`

**A PHP template engine : One class to rule them all**


PhpEcho is very simple to use, it's very close to the native PHP way of rendering views.
Basically, you just need to define the path of a view file to include and pass to the
instance a set of key-values pairs that will be available on rendering.

The class will manage :
* including the files
* escaping values from the stored key-values pairs
* escaping any value on demand
* returning raw values (when you know what you're doing)

**What you must know to use it**
1. Using array access notation will return the raw value (no escaping)
2. Using the function notation will return the escaped value (with `htmlspecialchars('string', ENT_QUOTES, 'utf-8')`)
3. To escape any value on demand, you must use the function notation with 2 parameters :
first, `'hsc'`, second, `'the value you would like to escape'`
4. **Please note that inside the view file, the instance of the class PhpEcho is always available through `$this`**

```php
$php_echo        = new PhpEcho();
$php_echo['foo'] = 'abc " < >';   // store a key-value pair inside the the instance

// now, look
$x = $php_echo['foo'];   // $x = 'abc " < >'             array notation, no escaping
$y = $php_echo('foo');   // $y = 'abc &quot; &lt; &gt;'  function notation, escaped value

// escape on demand
$z = $pho_echo('hsc', 'any value to escape');
```

# **How to**
Here's a very simple example

1. First, we create a html file called `Layout.php`
```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <?= implode('', $this['meta'] ?? []) ?>
    <title><?= $this('title') ?></title>
</head>
<body>
<?= $this['body'] ?>
</body>
</html>
```
Note the different way of extracting the data from `$this` (array notation vs function notation)

Then, we prepare the body of the page `LoginForm.php` :
```php
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this('login') ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
<br>
<p style="display:<?= $this['show_error'] ?? 'none' ?>"><strong><?= $this('err_msg') ?></strong></p>
```
And finally, we're going to create the main file that will prepare the view to be sent :
```php
<?php

// here we include the PhpEcho class stored in www_root/vendor/rawsrc/PhpEcho
// instead of include, you can use any autoloading mechanism in your code
include __DIR__.'/vendor/rawsrc/PhpEcho/PhpEcho.php';

$page = new PhpEcho('Layout.php', [
    'title' => 'My first use case of PhpEcho',
    'meta'  => [
        '<meta name="keywords" content="PhpEcho, PHP template engine, easy to use" />'
    ],
    'body' => new PhpEcho('LoginForm.php', [
        'login' => 'rawsrc',
        'url_submit' => 'any/path/for/connection'
    ])
]);

echo $page;
```

That's it guys, nothing more to know.

Enjoy :D