# **PhpEcho**

`2021-03-23` `PHP 8.0+` `v.4.0.x`

## **A PHP template engine : One class to rule them all**
## **VERSION 4.X IS ONLY FOR PHP 8 AND ABOVE**
**THIS VERSION BREAKS THE COMPATIBILITY WITH THE PREVIOUS<br>**
**NOT TO BE USED IN PRODUCTION AS THE TESTS ARE NOT YET COMPLETED, AND THE CODE IS NOT FROZEN ALSO**

When you develop a web application, the rendering of views may be a real challenge.
Especially if you just want to use only native PHP and avoid external templating syntax.

This is exactly the goal of `PhpEcho`: providing a pure PHP template engine with no other dependencies.<br>

`PhpEcho` is very simple to use, it's very close to the native PHP way of rendering HTML/CSS/JS.<br>
It is based on an OOP approach using only one class to get the job done.<br>
As you can imagine, using native PHP syntax, it's fast, really fast. No cache is needed to get top-level performances<br>
No additional parsing, no additional syntax to learn !<br>
If you already have some basic knowledge with PHP, that's enough to use it out of the box.<br> 

Basically, you just need to define the path of a view file and pass to the
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
* let you create your own helpers
* let your IDE to list all your helpers natively just using PHPDoc syntax (see the PHPDoc of the class)

**REMOVED from PhpEcho v4.0.0:**<br>
1. Removed methods: `->issetAndTrue($offset)`, `issetAndFalse($offset)`, `->templateDirectory()`  

**CHANGES in PhpEcho v4.0.0:**<br>
1. Renamed methods: `->addChild()` and `->addChildFromCurrent()` by `->addBlock()`

**NEW FEATURES IN PhpEcho v4.0.0:**<br>
1. You have the possibility to define once for all a template directory root that will automatically prepend all block paths.
2. You can define and attach an array of PhpEcho blocks to a key. That makes life really easier with complex layout rendering (see below).
3. You can provide any default block view for a key. The default view will be rendered only if the key is not defined.  
4. To be able to override helpers, you have to inject once manually on bootstrap the standard library using `PhpEcho::injectStandardHelpers();`

**What you must know to use it**
1. Using array access notation or function notation will always return escaped values.
2. Please note that inside an external view file, the instance of the class PhpEcho is always available through `$this`.

**SHORT EXAMPLE**
```php
use rawsrc\PhpEcho\PhpEcho;

$block = new PhpEcho();
$block['foo'] = 'abc " < >';   // store a key-value pair inside the instance

// get the escaped value stored in the block, simply ask for it :
$x = $block['foo'];   // $x = 'abc &quot; &lt; &gt;'

// escape on demand using a helper
$y = $block('hsc', 'any value to escape'); // or
$y = $block->hsc('any value to escape');  // using IDE highlight    

// extract the raw value on demand using a helper
$z = $block('raw', 'foo'); // $z = 'abc " < >' or
$z = $block->raw('foo');   // $z = 'abc " < >' 

// the type of value is preserved, are escaped all strings and objects having __toString()
$block['bar'] = new stdClass();
$bar = $block['bar'];
```

## **Views in web applications and PhpEcho overview**
As a developer, you know that the complexity and size of web apps are growing. 
To be able to manage them, you must divide the view into small blocks of code that will be injected 
upon rendering. Blocks injected into containers that can be injected into others containers as well and so on.
<br><br>
It's highly recommended grouping the view files (layouts, pages, blocks) into a separated directory.<br>
Usually, the architecture is generic and quite simple: 
- a page is based on one layout
- a page contains as many blocks as necessary
- a block can be built on others blocks and so on

Remember: the unit of `PhpEcho` is the block. Others components are usually built with blocks.

In the bootstrap of your webapp, you just have to tell `PhpEcho` where is the root directory:<br>
Example:
```txt
www
 |--- Controller
 |--- Model
 |--- View
 |     |--- Template01
 |     |     |--- block
 |     |     |     |--- contact.php
 |     |     |     |--- err404.php
 |     |     |     |--- footer.php
 |     |     |     |--- header.php
 |     |     |     |--- home.php
 |     |     |     |--- navbar.php
 |     |     |     |--- ...
 |     |     |--- layout
 |     |     |     |--- err.php
 |     |     |     |--- main.php
 |     |     |     |--- ...
 |     |     |--- page
 |     |     |     |--- about.php
 |     |     |     |--- cart.php
 |     |     |     |--- err.php
 |     |     |     |--- homepage.php
 |     |     |     |--- ...
 |     |--- Template02
 |     |     |--- ...
 |--- bootstrap.php
 |--- index.php
```
In your `bootstrap.php` file, you must inject the standard helpers and set up the main template directory:<br>
```php

use rawsrc\PhpEcho\PhpEcho;

PhpEcho::injectStandardHelpers();
PhpEcho::setTemplateDirRoot(__DIR__.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.'Template01');
```
Then you will code for example the homepage `page homepage.php` based on `layout main.php` like that:
```php
<?php

declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$homepage = new PhpEcho('layout main.php', [
    'header' => new PhpEcho('block header.php', [
        'user' => 'rawsrc',
        'navbar' => new PhpEcho('block navbar.php'),
    ]),
    'body' => new PhpEcho('block home.php'),
    'footer' => new PhpEcho('block footer.php'),      
]);

echo $homepage;
```
As you can see, you compose your whole page with blocks. Yous should try to keep the blocks as much as possible independent.
In a view context, absolutely every component is an instance of `PhpEcho`.
Everything is autowired in the background and automatically escaped by the engine when necessary.
As `PhpEcho` is highly flexible, you can even compose any element with others. It's up to you to decide.

## **Defining and using your own code snippets as helpers**
You have the possibility to use your own code generator as simply as a `Closure`.<br>
There's a small standard library of helpers that comes with PhpEcho : `stdPhpEchoHelpers.php`<br>

**About helpers:**<br> 
Each helper is a `Closure` that can produce whatever you want.<br>
Each helper can be linked to an instance of PhpEcho or remain a standalone helper.<br>
If linked to an instance, inside the closure you can use `$this` to get access to the caller's execution context.<br>
If standalone, this is just a simple function with parameters.<br>
It's possible for each helper to define 2 properties:
- if linked to a class instance use the constant `HELPER_BOUND_TO_CLASS_INSTANCE`
- if the generated code is already escaped (to avoid double quote) use the constant: `HELPER_RETURN_ESCAPED_DATA`  
  
For example, have a look at the helper that returns the HTML attribute `checked`:<br>
This helper compares two values and if they are equal return the string `" checked "`
```php
$checked = function($p, $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' checked ' : '';
};
$helpers['checked'] = [$checked, HELPER_RETURN_ESCAPED_DATA];
```
This helper is a standalone closure, there's no need to have access to an instance of PhpEcho.
As everything is escaped by default in PhpEcho, we can consider the word "checked" is safe and does not need to be escaped again, 
this is why, with the helper definition, you have the flag `HELPER_RETURN_ESCAPED_DATA`.<br>
To call this helper inside your code (2 ways) : <br>
* `$this('checked', 'your value', 'ref value');`
* `$this->checked('your value', 'ref value');`
 
Now, have a look at the helper that returns the raw value from the stored key-value pair `raw`:
```php
$raw = function(string $key) {
    return $this->vars[$key] ?? null;
};
$helpers['raw'] = [$raw, HELPER_RETURN_ESCAPED_DATA, HELPER_BOUND_TO_CLASS_INSTANCE];
```
As this helper extract data from the stored key-value pairs defined in each instance of PhpEcho, it needs access to the caller's execution context
that's why the helper definition has the flag `HELPER_BOUND_TO_CLASS_INSTANCE`.<br>
And as we want to get the value unescaped, we must tell the engine that the returned value by the closure is already escaped.
We know that is not but this is goal of that helper.<br>    
* `$this('raw', 'key');`
* `$this->raw('key');`

To define a helper, there are 3 ways:
* `$helpers["helper's name"] = $helper_closure`
* `$helpers["helper's name"] = [$helper_closure, HELPER_RETURN_ESCAPED_DATA]`
* `$helpers["helper's name"] = [$helper_closure, HELPER_RETURN_ESCAPED_DATA, HELPER_BOUND_TO_CLASS_INSTANCE]`

When you write a new helper that will be bound to a class instance and needs to use another bound helper,
you must use this syntax `$existing_helper = $this->bound_helpers['$existing_helper_name'];` inside your code. 
Please have a look at the `$root_var` helper (how the link to another bound helper is created: `$root`).

## **Simple example**

We're going to create a simple login form based ont the same architecture described just above.

1. First, we create a layout file in `View/Template01/layout` called `main.php`
Do not forget that all values returned are safe in HTML context.<br>
In the layout, some values are required:
* a description (string)
* a title (string)
* a PhpEcho block in charge of rendering the body part of the page<br> 
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // MAIN LAYOUT ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="<?= $this['description]'] ?>">
    <title><?= $this['title'] ?></title>
</head>
<body>
<?= $this['body'] ?>
</body>
</html>
```
As every PhpEcho instances are returned as it and transformed into a string when it's necessary, you can call them directly in your HTML code (as above).
Then, we create a block view in `View/Template01/block` called `login.php` containing the html form:<br>
Please note that `$this['url_submit']` and `$this['login']` are automatically escaped<br> 
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this['login'] ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```
Finally, we create a page `page/login.php` based on `layout/main.php` 
and inject the body `block/login.php`. All are sought from the template directory root:<br>
```php
<?php // LOGIN PAGE

// instead of use, you can also include once the code source of PhpEcho
use rawsrc\PhpEcho\PhpEcho;

echo new PhpEcho('layout main.php', [
    'title' => 'My first use case of PhpEcho',
    'description' => 'PhpEcho, PHP template engine, easy to learn and use',
    'body' => new PhpEcho('block login.php', [
        'login' => 'rawsrc',
        'url_submit' => 'any/path/for/connection',
    ]),
]);
```
This is also equivalent:<br>
```php
<?php

declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout main.php');
$page['title'] = 'My first use case of PhpEcho';
$page['description'] = 'PhpEcho, PHP template engine, easy to learn and use';

$body = new PhpEcho('block login.php');
$body['login'] = 'rawsrc';
$body['url_submit'] = 'any/path/for/connection';

$page['body'] = $body;

echo $page;
```
As you can see, `PhpEcho` is highly flexible. You can use plenty of ways rendering your HTML/CSS/JS code. The syntax is always very 
readable and easy to understand. 

## **Child blocks: autowiring vars**
The engine fills the vars attached to a block automatically if there's no other values defined. 
In that case the child gets a copy of the vars defined in the parent block.<br>
```php
<?php

declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout main.php');
$page['title'] = 'My first use case of PhpEcho';
$page['description'] = 'PhpEcho, PHP template engine, easy to learn and use';
$page['login'] = 'rawsrc';
$page['url_submit'] = 'any/path/for/connection';

// no vars are attached to the block as the second parameter is omitted
$body = new PhpEcho('block login.php');  

// when we inject the block into the parent, the autowiring will automatically
// pass a copy of parent's vars to the child   
$page['body'] = $body; // $body['login'] and $body['url_submit'] are well defined

echo $page;
```

## **New feature: array of PhpEcho blocks**
You can define many strategies for views especially regarding the level of details (the granularity) of complex layouts and pages.
Suppose you render the body part of a page like that:
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this['body'] ?>
</body>
```
or like this:
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this['top_header'] ?>
<?= $this['navbar'] ?>
<?= $this['navbar_mobile'] ?>
<?= $this['preloader'] ?>
<?= $this['body'] ?>
<?= $this['footer'] ?>
<?= $this['copyright'] ?>
</body>
```
The first code is abstract, and the second is really explicit about what is expected.
When you want to preserve some flexibility using the abstract code, since v4 it is possible to use an array of `PhpEcho` blocks for a key.<br>
```php

use rawsrc\PhpEcho\PhpEcho;

$page['body'] = [
    new PhpEcho('block preloader.php'),
    new PhpEcho('block top_header.php'),
    new PhpEcho('block navbar.php'),
    new PhpEcho('block navbar_mobile.php'),
    new PhpEcho('block body.php'),
    new PhpEcho('block footer.php'),
    new PhpEcho('block copyright.php'),
];
```
The blocks are rendered in the order they appear. You can omit one or many, swap them. You are free to render the code as you need.

## **New feature: render default view if not defined**
Since v4, it's possible to define a default block view to render:

```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this->renderByDefault('preloader', 'block preloader.php') ?>
<?= $this->renderByDefault('top_header', 'block top_header.php') ?>
<?= $this->renderByDefault('navbar', 'block navbar.php') ?>
<?= $this->renderByDefault('navbar_mobile', 'block navbar_mobile.php') ?>
<?= $this['body'] ?>
<?= $this->renderByDefault('footer', 'block footer.php') ?>
<?= $this->renderByDefault('copyright', 'block copyright.php') ?>
</body>
```
All keys except `body` are optional.

## **Use HEREDOC instead of file inclusion**

It's possible to use directly plain html code instead of file inclusion.
Because of PHP early binding value upon calling you must be sure that the values are defined before using them in the code.

We are going to omit the file `block login.php` and inject directly the source code into the layout:
Remember, the layout:<br>
```php
<?php /** @var \rawsrc\PhpEcho\PhpEcho $this */ // MAIN LAYOUT ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="<?= $this['description]'] ?>">
    <title><?= $this['title'] ?></title>
</head>
<body>
<?= $this['body'] ?>
</body>
</html>
```
Remember the login form:<br>
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this['login'] ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```
Let's swap the login form file:
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout main.php', [
    'title' => 'My first use case of PhpEcho',
    'description' => 'PhpEcho, PHP template engine, easy to learn and use',
]);

// here we define the needed values inside the plain html code before injecting them 
// another way to declare key-value pairs
$body = new PhpEcho();
$body['login'] = 'rawsrc';
$body['url_submit'] = 'any/path/for/connection';

// we set directly the plain html code
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

## **Parameters**

Since PhpEcho 2.3.1, each instance of PhpEcho can now have their own parameters.<br>
Please note that the parameters are never escaped. 
```php
// In any block
// set a parameter
$this->setParam('document.isPopup', true);

// get the parameter
$is_popup = $this->getParam('document.isPopup'); // true
```
There's an interesting point to keep in mind, when the parameter is not defined in the current instance
then the engine will automatically seek for it through the parent PhpEcho instances. It will climb the other leaves to the root 
and stop if the parameter is found or return null.

## **Let's play with helpers**
As mentioned above, there's some new helpers that have been added to the standard helpers library `stdPhpEchoHelpers.php`.
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
As you see, there're tons of methods to get the expected result.
It's highly recommended creating and using your own helpers and ask to get them included by default in the package for 
the next release. 


About some helpers:

**Access the root**

The very first PhpEcho instance is a special object that is available for any child PhpEcho instance.

You have direct access to it using the helper `root` or `$this->root()`. This helper return the top-level instance of a tree of PhpEcho classes.

**Accessing a value stored in the root**

As any other PhpEcho instance, you can store inside any value and retrieve it from any child block using the helper `rootVar` or 
the corresponding method : `rootVar()`. Now, you can define some global values and interact with them from any child block.<br>
These values behave like any standard value and are of course escaped when necessary.

**Climbing the tree of blocks**

The last is `keyUp` with the corresponding method `keyUp()`. 
From a given list of keys (string or array, string: the delimiter for each key is space), the engine will start to climb the tree 
of blocks while the key is found. And will return the value corresponding to the last key or null if not found.<br>
With the parameter `$strict_match`, it possible to tell the engine to continue to climb if the current key is still not found.
```php
// imagine you have a tree of PhpEcho blocks corresponding to a part of the DOM
$block1['abc'] = 'rawsrc';
$block1['form1'] = $block2;
    $block2['def'] = 'github';
    $block2['tab'] = $block3; 
        $block3['tab_footer'] = $block4; 

// now from the $block4 you want to read the value for the key 'abc'
// with $strict_match === true you must define the whole path to the block
$x = $this->keyUp('tab abc', true); 
// this is equivalent
$x = $this->keyUp('def abc', true);

// with $strict_match === false you know there's a parent block having the key
// but you don't know the path to get it out
$x = $this->keyUp('abc', false);
```
  
## **Accessing the top `<head></head>` from any child PhpEcho block**

When you code the view part of a website, you will create plenty of small blocks that will be inserted at their right place on rendering.
As everybody knows, the best design is to keep your blocks in the most independent way from the others. Sometimes you will need to add some dependencies
directly in the header of the page. This is also possible using PhpEcho as your main template engine.

In any instance of PhpEcho, you have a method named `addhead()` which is designed for this purpose.

Now, imagine you're in the depths of the DOM, you're coding a block and need to tell the header to declare a link to your library.
In the current block, you will do:
```php
<?php $this->addHead('<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous">') ?>
```  
or using a helper `script` that will secure all your values
```php
<?php $this->addHead('script', [
    'src'         => "https://code.jquery.com/jquery-3.4.1.min.js", 
    'integrity'   => "sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=", 
    'crossorigin' => "anonymous"]) ?>
```
Now in the block that renders the `<head></head>`, you just have to code:
```php
<head>
    <?= $this->getHead(); ?>
</head>
``` 
The engine will compile the `<head></head>` parameters from all the child blocks to render the global header.


Enjoy!

**rawsrc**