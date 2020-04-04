# **PhpEcho**

`2020-04-03` `PHP 7+` `v.2.0.0`

## **A PHP template engine : One class to rule them all**

PhpEcho is very simple to use, it's very close to the native PHP way of rendering HTML/CSS/JS.<br>
**This very lightweight engine will automatically secure all your data.**<br> 
It is based on an OOP approach using one single class to get the job done.<br>
As you can imagine, using native PHP syntax, it's fast, really fast.<br>
No additional parsing, no additional syntax to learn !<br>
If you already have some basic knowledge with PHP, that's enough to use it out of the box.<br> 
 
Basically, you just need to define the path of a view file to include and pass to the
instance a set of key-values pairs that will be available on rendering.

The class will manage :
* files inclusions
* extracting and escaping values from the locally stored key-values pairs
* escaping any value on demand
* returning raw values (when you know what you're doing)
* the possibility to write directly plain html code instead of file inclusion
* escaping recursively keys and values in any array
* managing and rendering instance of class that implements the magic function `__toString()`

**NEW FEATURE IN PhpEcho v.2.0.0:**<br>
1. It's now possible to create, inject and use your own personal helpers (code snippet) to easily render your HTML/CSS/JS code.
2. Everything is secured and escaped by default. To get a raw value from a key, you must ask it explicitly using the helper `"$raw"` as shown below.   


**What you must know to use it**
1. Using array access notation or function notation will always return escaped values
2. Please note that inside an external view file, the instance of the class PhpEcho is always available through `$this`

**SHORT EXAMPLE**
```php
$block        = new PhpEcho();
$block['foo'] = 'abc " < >';   // store a key-value pair inside the instance

// get the escaped value stored in the block, simply ask for it :
$x = $block['foo'];   // $x = 'abc &quot; &lt; &gt;'

// escape on demand using a helper
$y = $block('$hsc', 'any value to escape');

// extract the raw value on demand using a helper
$z = $block('$raw', 'foo'); // $z = 'abc " < >'
```

## **Defining and using your own code snippets as helpers**
This version give you the possibility to use your own code generator as simply as a `Closure`.<br>
You have a small standard library of helpers that comes with PhpEcho : `stdHelpers.php`<br>

**The principle:**<br> 
Every helper is a `Closure` that can produce whatever you want.<br>
Every helper can be linked to an instance of PhpEcho or remain a standalone helper.<br>
If linked to an instance, inside the closure you can use `$this` to get an access to the caller's execution context.<br>
If standalone, this is just a simply function with parameters.<br>
It's possible for every helper to define 2 properties:
- if linked to a class instance use the constant `HELPER_BINDED_TO_CLASS_INSTANCE`
- if the generated code is already escaped (to avoid double quote) use the constant : `HELPER_RETURN_ESCAPED_DATA`  
  
For example, have a look at the helper that returns the HTML attribute `checked`:<br>
This helper compares two values and if they are equal return the string `" checked "`
```php
$checked = function($p, $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' checked ' : '';
};
$helpers['$checked'] = [$checked, HELPER_RETURN_ESCAPED_DATA];
```
This helper is a standalone closure, there's no need to have an access to an instance of PhpEcho.
As everything is escaped by default in PhpEcho, we can consider that the word "checked" is safe and does not need to be escaped again, 
this is why, with the helper definition, you have the flag `HELPER_RETURN_ESCAPED_DATA`.<br>
To call this helper inside your code : `$this('$checked', 'your value', 'ref value')`<br>
  
Now, have a look at the helper that return the raw value from the stored key-value pair `$raw`:
```php
$raw = function(string $key) {
    return $this->vars[$key] ?? null;
};
$helpers['$raw'] = [$raw, HELPER_RETURN_ESCAPED_DATA, HELPER_BINDED_TO_CLASS_INSTANCE];
```
As this helper extract data from the stored key-value pairs defined in every instance of PhpEcho, it needs an access to the caller's execution context (instance of PhpEcho)
that's why the helper definition has the flag `HELPER_BINDED_TO_CLASS_INSTANCE`.<br>
And as we want to get the value unescaped, we must tell the engine that the return value by the closure is already escaped.
We know that is not but this is goal of that helper.

To define a helper, there's 3 ways:
* `$helpers['$helper's id'] = $helper_closure`
* `$helpers['$helper's id'] = [$helper_closure, HELPER_RETURN_ESCAPED_DATA]`
* `$helpers['$helper's id'] = [$helper_closure, HELPER_RETURN_ESCAPED_DATA, HELPER_BINDED_TO_CLASS_INSTANCE]`



## **How to**
Here's a very simple example of login from:

1. First, we create a html file called `Layout.php`
Note the expected values for keys inside `$this[]` or `$this()`  
```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <?= implode('', $this('$raw', 'meta') ?? []) ?>
    <title><?= $this['title'] ?></title>
</head>
<body>
<?= $this('$raw', 'body') ?>
</body>
</html>
```
Note also the different ways of extracting the data from `$this` (array notation vs function notation (helpers))

Then, we prepare the body of the page `LoginForm.php` :
```php
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this['login'] ?>"><br>
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
    <input type="text" name="login" value="{$body['login']}"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
html
    );
$page['body'] = $body;
echo $page;
// Note how it's coded, in this use case : `$body` replace `$this`
```

## **Use id**

It's possible now to define automatically a closed context in the rendered view by using a html tag's id
Every instance of PhpEcho has an auto-generated id that can be linked to any html tag. This link will define a closed
context that will allow us to work with the current block without interfering with others.

How to use it: we will update the LoginForm.php file to see how to use this new feature.
For example, we'd like to test some new CSS on the block without changing the rendering of other parts of the page.
```php
<?php $id = $this->id() ?>
<style>
#<?= $id ?> label {
    color: blue;
    float: left;
    font-weight: bold;
    width: 30%;
}
#<?= $id ?> input {
    float: right;
}
</style>
<div id="<?= $id ?>">
  <p>Please login</p>
  <form method="post" action="<?= $this['url_submit'] ?>>">
    <label>Login</label>
    <input type="text" name="login" value="<?= $this['login'] ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
  </form>
</div>
```
See how it is possible to use the PhpEcho's id in the HTML context: we have now a closed context defined by `<div id="<?= $id ?>">`, that will let us to lead 
our css tests without interfering with others parts of HTML. It's also possible to use it for any javascript code related to the current instance of PhpEcho.

Here's exactly the same code omitting the file inclusion mechanism : 
```php
<?php

// previous code

$id = $body->id();
$body->setCode(<<<html
<style>
#{$id} > p {
    font-weight: bold;
}
#{$id} label {
    color: blue;
    float: left;
    font-weight: bold;
    width: 30%;
}
#{$id} input {
    float: right;
}
</style>
<div id="{$id}">
  <p>Please login:</p>
  <form method="post" action="{$body['url_submit']}>">
    <label>Login</label>
    <input type="text" name="login" value="{$body['login']}"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
  </form>
</div>
html
    );
```

That's all folks, nothing more to learn.