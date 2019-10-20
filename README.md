# **PhpEcho**

`2019-10-20` `PHP 7+`

## **A PHP template engine : One class to rule them all**

PhpEcho is very simple to use, it's very close to the native PHP way of rendering views.
Basically, you just need to define the path of a view file to include and pass to the
instance a set of key-values pairs that will be available on rendering.

The class will manage :
* files inclusions
* extracting and escaping values from the locally stored key-values pairs
* escaping any value on demand
* returning raw values (when you know what you're doing)
* the possibility to write directly plain html code instead of file inclusion

If you read french, you will find a complete tutorial with tons of explanations on my blog : [rawsrc](https://www.developpez.net/forums/blogs/32058-rawsrc/b8215/phpecho-moteur-rendu-php-classe-gouverner/)
 
**What you must know to use it**
1. Using array access notation will return the raw value (no escaping)
2. Using the function notation will return the escaped value (with `htmlspecialchars('string', ENT_QUOTES, 'utf-8')`)
3. To escape any value on demand, you must use the function notation with 2 parameters :
first, `'hsc'`, second, `'the value you would like to escape'`
4. **Please note that inside the external view file, the instance of the class PhpEcho is always available through `$this`**

```php
$php_echo        = new PhpEcho();
$php_echo['foo'] = 'abc " < >';   // store a key-value pair inside the the instance

// now, look
$x = $php_echo['foo'];   // $x = 'abc " < >'             array notation, no escaping
$y = $php_echo('foo');   // $y = 'abc &quot; &lt; &gt;'  function notation, escaped value

// escape on demand
$z = $pho_echo('hsc', 'any value to escape');
```

## **How to**
Here's a very simple example

1. First, we create a html file called `Layout.php`
Note the expected values for keys inside `$this[]` or `$this()`  
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
Note also the different ways of extracting the data from `$this` (array notation vs function notation)

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
```
And finally, we're going to create the main file that will prepare the view to be sent :
```php
<?php

// here we include the PhpEcho class stored in www_root/vendor/rawsrc/PhpEcho
// instead of include, you can use any autoloading mechanism in your code
include __DIR__.'/vendor/rawsrc/PhpEcho/PhpEcho.php';

$page = new PhpEcho('Layout.php', [
    'title' => 'My first use case of PhpEcho',
    'meta'  => ['<meta name="keywords" content="PhpEcho, PHP template engine, easy to learn and use" />'],
    'body'  => new PhpEcho('LoginForm.php', [
        'login' => 'rawsrc',
        'url_submit' => 'any/path/for/connection'
    ])
]);

echo $page;
```

## **Use HEREDOC instead of file inclusion**

It's possible to use directly plain html code instead of file inclusion.
Because of PHP early binding value upon calling you must be sure that the values are defined before using them in the code.

We are going to omit the file `LoginForm.php` and inject directly the source code to the page builder `Login.php` : 
```php
<?php

include __DIR__.'/vendor/rawsrc/PhpEcho/PhpEcho.php';

$page = new PhpEcho('Layout.php', [
    'title' => 'My first use case of PhpEcho',
    'meta'  => ['<meta name="keywords" content="PhpEcho, PHP template engine, easy to learn and use" />']
]);

// here we define the needed values inside the plain html code before injecting them 
// another way to declare key-value pairs
$body               = new PhpEcho();
$body['url_submit'] = 'any/path/for/connection';
$body['login']      = 'rawsrc';

// and now we set directly the plain html code
$body->setCode(<<<html
<p>Please login : </p>
<form method=post action="{$body['url_submit']}>">
    <label>User</label>
    <input type="text" name="login" value="{$body('login')}"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
html
    );
echo $page;
// Note how it's coded, in this use case : `$body` replace `$this`, always the difference between 
// the array notation and function notation
```

That's all folks, nothing more to know.