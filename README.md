# **PhpEcho**

`2020-04-14` `PHP 7+` `v.2.2.0`

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
* file inclusions
* extracting and escaping values from the locally stored key-values pairs
* escaping any value on demand
* returning raw values (when you know what you're doing)
* the possibility to write directly plain html code instead of using the file inclusion mechanism
* escaping recursively keys and values in any array
* managing and rendering instance of class that implements the magic function `__toString()`
* let you access to the global HTML `<head></head>` from any child block
* let your IDE to list all your helpers natively just using PHPDoc syntax (see the PHPDoc of the class)

**NEW FEATURE IN PhpEcho v.2.2.0:**<br>
1. You have now access to the global `<head></head>` from any PhpEcho child block
2. New helpers rendering HTML tags in a secure way
3. Use a relative path from the current instance to target any child PhpEcho file 

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
$y = $block('$hsc', 'any value to escape'); // or
$y = $block->hsc('any value to escape');    // since PhpEcho 2.1.0

// extract the raw value on demand using a helper
$z = $block('$raw', 'foo'); // $z = 'abc " < >' or
$z = $block->raw('foo');    // $z = 'abc " < >' since PhpEcho 2.1.0
```

## **Defining and using your own code snippets as helpers**
You have the possibility to use your own code generator as simply as a `Closure`.<br>
There's a small standard library of helpers that comes with PhpEcho : `stdHelpers.php`<br>

**About helpers:**<br> 
Each helper is a `Closure` that can produce whatever you want.<br>
Each helper can be linked to an instance of PhpEcho or remain a standalone helper.<br>
If linked to an instance, inside the closure you can use `$this` to get an access to the caller's execution context.<br>
If standalone, this is just a simply function with parameters.<br>
It's possible for each helper to define 2 properties:
- if linked to a class instance use the constant `HELPER_BOUND_TO_CLASS_INSTANCE`
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
To call this helper inside your code (2 ways) : <br>
* `$this('$checked', 'your value', 'ref value');`
* `$this->checked('your value', 'ref value');    // since PhpEcho 2.1.0`
 
Now, have a look at the helper that return the raw value from the stored key-value pair `$raw`:
```php
$raw = function(string $key) {
    return $this->vars[$key] ?? null;
};
$helpers['$raw'] = [$raw, HELPER_RETURN_ESCAPED_DATA, HELPER_BOUND_TO_CLASS_INSTANCE];
```
As this helper extract data from the stored key-value pairs defined in each instance of PhpEcho, it needs an access to the caller's execution context (instance of PhpEcho)
that's why the helper definition has the flag `HELPER_BOUND_TO_CLASS_INSTANCE`.<br>
And as we want to get the value unescaped, we must tell the engine that the return value by the closure is already escaped.
We know that is not but this is goal of that helper.<br>
To call this helper inside your code (2 ways) : <br>
* `$this('$raw', 'key');`
* `$this->raw('key');    // since PhpEcho 2.1.0`

To define a helper, there're 3 ways:
* `$helpers["$helper's name"] = $helper_closure`
* `$helpers["$helper's name"] = [$helper_closure, HELPER_RETURN_ESCAPED_DATA]`
* `$helpers["$helper's name"] = [$helper_closure, HELPER_RETURN_ESCAPED_DATA, HELPER_BOUND_TO_CLASS_INSTANCE]`


## **How to**
Here's a very simple example of login from:

1. First, we create a view file called `Layout.php`
Note the expected values for keys inside `$this[]` or `$this()`.<br>
Do not forget that all values returned by the array notation (`$this[]`) are safe in HTML context
```php
<?php /** @var PhpEcho $this */ ?>
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
To get a handy help from your IDE, you can also write the code above (since PhpEcho 2.1.0) like this: `<?= $this->raw('body') ?>`<br> 
To get the IDE autocompletion, just add as the first line of your view file `<?php /** @var PhpEcho $this */ ?>` to tell the IDE the 
right type for `$this`.<br> 
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

## **Let's play with helpers**
As mentioned above, there's some new helpers that have been added to the standard helpers library `stdHelpers.php`.
These helpers will help you to render any HTML code and/or interact with any PhpEcho instance.
By default, everything in PhpEcho is escaped, so this is also true for the HTML code generated by the helpers.

As helpers are small snippets of code, you can read their source code to understand easily what they will return.
The helpers are also documented, so RTFM ;-) 
 
Examples:
* You need to create a `<input>` tag 
```php
$this->voidTag('input', ['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']);
```
You do not have to worry about any dangerous character in this tag, all are escaped. Here's the rendered HTML code:<br>
```html
<input type="text" name="name" required value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;">
```
It is also possible to do like this:
```php
<input <?= $this->attributes(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']) ?>>
```
As you see, there're tons of methods to get the expected result, just using and creating your own helpers.
It's higly recommended creating and using your own helpers and ask to get them included by default in the package for the next release. 

  
## **Accessing the top `<head></head>` from any child PhpEcho block**

When you code the view part of a website, you will create plenty of small blocks that will be inserted at their right place on rendering.
As everybody knows, the best design is to keep your blocks in the most independent way from the others. Sometimes you will need to add some dependencies
directly in the header of the page. This is also possible using PhpEcho as your main template engine.

In any instance of PhpEcho, you have a method named `head()` which is designed for this purpose.

Now, imagine you're in the depth of the DOM, you're coding a view block and need to tell the header to declare a link to your library.
In the current block, you will do:
```php
<?php $this->head()->add('<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous">') ?>
```  
or using a helper `script` that will secure all your values
```php
<?php $this->head()->add('script', [
    'src'         => "https://code.jquery.com/jquery-3.4.1.min.js", 
    'integrity'   => "sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=", 
    'crossorigin' => "anonymous"]) ?>
```
Now in the block that renders the `<head></head>`, you just have to code:
```php
<head>
    <?= $this->head()->render(); ?>
</head>
``` 
The engine will compile all the `<head></head>` parameters from all the child blocks to render the header.

The concept of child block is easy to understand: when you define a PhpEcho class as a variable of one another, you create a child block.
```php
$page = new PhpEcho('Layout.php');
$page['body'] = new PhpEcho('Body.php');    // here's the child block 
```

## **Using a relative path to target any child PhpEcho block**
When you create a PhpEcho instance, usually you will pass to the constructor the filepath of the view file.
You can get directly the last template directory using `templateDirectory()` and use it as a root to build other dynamics paths 

Enjoy!

**rawsrc**