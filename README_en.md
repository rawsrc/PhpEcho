# **PhpEcho**

`2023-09-24` `PHP 8.0+` `6.1.0`

## **A native PHP template engine : One class to rule them all**
## **IS ONLY FOR PHP 8 AND ABOVE**

When you develop a web application, the rendering of views may be a real challenge.
Especially if you just want to use only native PHP syntax and avoid external templating language.

This is exactly the goal of `PhpEcho`: providing a pure NATIVE PHP TEMPLATE ENGINE with no other dependencies.<br>

`PhpEcho` is really easy to use, it's very close to the native PHP way of rendering HTML/CSS/JS.<br>
It is based on an OOP approach using only one class to get the job done.<br>
As you can imagine, using native PHP syntax, it's fast, really fast. No cache is needed to get top-level performances<br>
No additional parsing, no additional syntax to learn !<br>
If you already have some basic knowledge with PHP, that's enough to use it out of the box.<br> 

Basically, you just need to define the path of a view file and pass to the
instance a set of key-values pairs that will be available on rendering.

The class will manage :
* file inclusions
* extracting and escaping values from any key-values pairs stored in a `PhpEcho` instance 
* escaping any value on demand (including recursive array (keys and values))
* returning raw values on demand
* the possibility to write directly plain html code instead of using the file inclusion mechanism
* managing and rendering instance of class that implements the magic function `__toString()`
* grant access to the global HTML `<head></head>` from any child block
* detecting any infinite loop

You'll also be able to extend the engine features writing your own helpers and 
let your IDE list all your helpers natively just using PHPDoc syntax.

1. [Installation](#installation)
2. [Configuration](#configuration)
   1. [View root dir](#view-root-dir)
   2. [Seek option](#seek-option)
3. [Parameters](#parameters)
4. [Principles and overview](#principles-and-overview)
5. [Let's start](#lets-start)
   1. [Basic usage](#basic-usage)
   2. [Standard usage](#standard-usage)
   3. [HTML usage](#html-usage)
      1. [Layout](#layout)
      2. [Form](#form)
      3. [Page](#page)
6. [Child blocks](#child-blocks)
7. [Manipulating and Access to the head tag](#manipulating-and-access-to-the-head-tag)
8. [User values](#user-values)
   1. [Seeking the keys](#seeking-the-keys)
   2. [Not found key](#not-found-key)
9. [Auto escaping vars](#auto-escaping-vars)
10. [Array of PhpEcho instances](#array-of-phpecho-instances)
11. [Using a default view](#using-a-default-view)
12. [Use HEREDEOC for HTML](#use-heredoc-for-html)
13. [Use the auto-generated block's id](#use-the-auto-generated-blocks-id)
14. [Use the component `ViewBuilder](#use-the-component-viewbuilder) 
15. [Advance use: create your own helpers](#advance-use-create-your-own-helpers)
    1. [Helpers](#helpers) 
    2. [Study: standalone helper `$checked`](#study-standalone-helper-checked)
    3. [Study: bindable helper `$raw`](#study-bindable-helper-raw)
    4. [Defining a helper and complex binding](#defining-a-helper-and-complex-binding)
16. [Let's play with helpers](#lets-play-with-helpers)

## **INSTALLATION**
```bash
composer require rawsrc/phpecho
```

## **CONFIGURATION**
### **VIEW ROOT DIR**
To use it out of the box, once you have included the class using `include_once` or use any autoloader, 
you must tell the engine where is the root directory of the view files (resolved filepath).<br> 
Please note that the only admitted directory separator is `/` (slash).
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

// eg: from the webroot directory
PhpEcho::setTemplateDirRoot(__DIR__.DIRECTORY_SEPARATOR.'View');
```

### **SEEK OPTION**
By default, the engine tries to find first the value of any asked key in each local 
array of values attached to every `PhpEcho` instance; then if not found, it will seek in the 
parent blocks until it reaches the root of the tree. The seek is only made at the 
first level of the array of vars attached to any `PhpEcho` block.

More: [User values](#user-values)

## **PARAMETERS**

To help you to pilot the rendering, you can store in any `PhpEcho` as many parameters
as needed. There are two levels of parameters: local and global.<br>
Please note that the parameters are never escaped.

If a parameter is unknown then you'll have an `Exception`.
```php
// for a specific block (local parameter)
$this->setParam('document.isPopup', true);
$is_popup = $this->getParam('document.isPopup'); // true
$has = $this->hasParam('document.isPopup'); // true
$this->unsetParam('document.isPopup'); 
```
```php
// for all blocks (global parameter)
PhpEcho::setGlobalParam('document.isPopup', true);
$is_popup = PhpEcho::getGlobalParam('document.isPopup'); // true
$has = PhpEcho::hasGlobalParam('document.isPopup');
PhpEcho::unsetGlobalParam('document.isPopup');;
```

If you want the parameter's local value first then the global one if not defined
```php
$is_popup = $this->getAnyParam(name: 'document.isPopup', seek_order: 'local');
```
If you want the parameter's global value first then the local one if not defined
```php
$is_popup = $this->getAnyParam(name: 'document.isPopup', seek_order: 'global');
```
You can check if a param is defined either in local or global context:
```php
$this->hasAnyParam('document.isPopup'); // order: local then global context
```
You can set a local and global parameter at once
```php
$this->setAnyParam('document.isPopup', true); // the value is available in both contexts (local and global)
```
It's also possible to unset a parameter from the local and global context at once:
```php
$this->unsetAnyParam('document.isPopup');
```

## **PRINCIPLES AND OVERVIEW**

1. All values read from a PhpEcho instance are escaped and safe in HTML context
2. Inside a view file or inside a helper, the instance of the class `PhpEcho` is always available through `$this`
3. For complex creation, there's a `ViewBuilder` class that comes with `PhpEcho`  
4. Parameters stored in any `PhpEcho` instance are **NEVER** escaped
5. `PhpEcho` comes with many pre-built code generators called helpers. They are helpful tools to make your job easier

As a developer, you know that the complexity and size of web apps are growing.
To be able to manage them, you must divide the view into small blocks of code that will be injected
upon rendering. Blocks injected into containers that can be injected into other containers as well and so on.
<br><br>
You must keep in mind the structure of an HTML page, it's a huge tree, `PhpEcho` does exactly the same.
<br><br>
It's highly recommended to group the view files (layouts, pages, blocks) into a separated directory.<br>
Usually, the architecture is generic and quite simple:
- a page is based on one layout
- a page contains as many blocks as necessary
- a block can be built on others blocks and so on

Remember: the unit of `PhpEcho` is the block.<br>
Others components are usually built with blocks, even layouts and pages are seen as `PhpEcho` instances.

Easy, isn't it?

## **LET'S START**

Here is the classical view part of a webapp:
```txt
www
 |--- Controller
 |--- Model
 |--- View
 |     |--- block
 |     |     |--- contact.php
 |     |     |--- err404.php
 |     |     |--- footer.php
 |     |     |--- header.php
 |     |     |--- home.php
 |     |     |--- navbar.php
 |     |     |--- login.php
 |     |     |--- ...
 |     |--- layout
 |     |     |--- err.php
 |     |     |--- main.php
 |     |     |--- ...
 |     |--- page
 |     |     |--- about.php
 |     |     |--- cart.php
 |     |     |--- err.php
 |     |     |--- homepage.php
 |     |     |--- login.php
 |     |     |--- ...
 |--- bootstrap.php
 |--- index.php
```

### **BASIC USAGE**
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

$block = new PhpEcho();
$block['foo'] = 'abc " < >';   // store a key-value pair inside the instance

// get the escaped value stored in the block, simply ask for it :
$x = $block['foo'];   // $x = 'abc &quot; &lt; &gt;'

// escape on demand using a HELPER
$y = $block('hsc', 'any value to escape'); // or
$y = $block->hsc('any value to escape');  // using IDE highlight    

// extract the raw value on demand using the helper 'raw()'
$z = $block->raw('foo');   // $z = 'abc " < >' 

// the type of value is preserved, are escaped all strings and objects having __toString()
$block['bar'] = new stdClass();
$bar = $block['bar'];
```

### **STANDARD USAGE**

Rendering a homepage using several `PhpEcho` blocks separated in many files.
To understand how the files are found, the filepath for each inclusion is prepended with 
the view root directory defined using `PhpEcho::setTemplateDirRoot()`. 
```php
<?php declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$homepage = new PhpEcho('layout/main.php', [
    'header' => new PhpEcho('block/header.php', [
        'user' => 'rawsrc',
        'navbar' => new PhpEcho('block/navbar.php'),
    ]),
    'body' => new PhpEcho('block/home.php'),
    'footer' => new PhpEcho('block/footer.php'),      
]);

echo $homepage;
```
As you can see, you compose the whole page with blocks. Yous should try to keep the blocks 
as much as possible independent. In a view context, absolutely every component is an instance of `PhpEcho`.
Everything is autowired in the background and automatically escaped by the engine when necessary.
As `PhpEcho` is highly flexible, you can even compose any element with others.

### **HTML usage**
### **LAYOUT**
We're going to create a simple login form based on the same architecture described just above.<br>
First, we create a layout file in `View/layout` called `main.php` with some required values:
* a description (string)
* a title (string)
* a `PhpEcho` block in charge of rendering the body part of the page
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // MAIN LAYOUT ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="<?= $this['description'] ?>">
    <title><?= $this['title'] ?></title>
</head>
<body>
<?= $this['body'] ?>
</body>
</html>
```
As every PhpEcho instances are returned as it and transformed into a string when necessary, 
you can call them directly in your HTML code (as above).

### **FORM**

Then, we create a block view in `View/block` called `login.php` containing the html form:<br>
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

### **PAGE**

Finally, we create a page `page/login.php` based on `layout/main.php` and 
we inject the body part using a `PhpEcho` block `block/login.php`.<br>
```php
<?php declare(strict_types=1); // LOGIN PAGE

use rawsrc\PhpEcho\PhpEcho;

echo new PhpEcho('layout main.php', [
    'title' => 'My first use case of PhpEcho',
    'description' => 'PhpEcho, PHP template engine, easy to learn and use',
    'body' => new PhpEcho('block/login.php', [
        'login' => 'rawsrc',
        'url_submit' => 'any/path/for/connection',
    ]),
]);
```
This is also equivalent:<br>
```php
<?php declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout/main.php');
$page['title'] = 'My first use case of PhpEcho';
$page['description'] = 'PhpEcho, PHP template engine, easy to learn and use';

$body = new PhpEcho('block/login.php');
$body['login'] = 'rawsrc';
$body['url_submit'] = 'any/path/for/connection';

$page['body'] = $body;

echo $page;
```
As you can see, `PhpEcho` is highly flexible. You can use plenty of ways rendering your HTML/CSS/JS code. 
The syntax is always clear, very readable and easy to understand.

## **CHILD BLOCKS**

To compose a view using many child blocks, there are three ways to declare them:
* `$this->renderBlock()`: the child block is anonymous in parent's block and unreachable once rendered 
* `$this->addBlock()`: the child block has a name and can be reached from the parent context using its name
* `$this->renderByDefault()`: the child block has a name, and if the parent does not provide a specific block 
with the same name, then the engine will render the default block as specified in the parameter 
<br>
I repeat, please note, that the whole view must be seen as a huge tree and the blocks are linked all together.
You must never declare a totally independent block into another.
This is not allowed:
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */
use rawsrc\PhpEcho\PhpEcho; // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= new PhpEcho('block/login_input_text.php') ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```
it must be replaced with one of the methods described just above:
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this->renderBlock('block/login_input_text.php') ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```
This way, you do not cut the tree ;-)

## **MANIPULATING AND ACCESS TO THE HEAD TAG**

When you code the view part of a website, you will create plenty of small blocks that will be inserted at their right place on rendering.
As everybody knows, the best design is to try to keep the blocks as most independent as possible. Sometimes you will need to add some dependencies
directly in the header of the page. In any instance of `PhpEcho`, you have a method named `addhead()` which is designed for this purpose.

Now, imagine you're in the depths of the DOM, you need to tell the header to declare a link to your library.
In the current block, you have to code:
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
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<head>
    <?= $this->getHead(); ?>
</head>
``` 
The engine will compile the `<head></head>` data from all the child blocks to render the global header.

## **USER VALUES**
### **SEEKING THE KEYS**

The engine is able to retrieve on rendering your values in different ways.
When you ask to render a specific value identified by a key, the engine will try 
to seek for it through all the values stored locally, then if not found, it will seek
in the parent blocks climbing the tree to the root (the very first `PhpEcho` instance).

This logic is parameterizable, using the method `setSeekValueMode(string $mode)`.<br>
By default, the mode is set to `parents`.<br>
The different modes are:
- `current`: the engine will only seek for a key in the current block
- `parents`: the engine will seek for a key in the current block and then will climb the blocks one after one to the root while not found
- `root`: the engine will seek for a key in the current block and if not found in the root only

Please note that the search for the key is only made at the first level of the array of vars. 
```php
// e.g.: you have an array of vars in a block
[
    'k.abc' => 'v.abc', 
    'k.def' => [
        'k.ghi' => 'v.ghi', // second level
        'k.jkl' => 'v.jkl', // second level
    ],
];
// the visible keys for the block are 'k.abc' and 'k.def' (first level),
// the keys in the sub-array are not automatically readable unless you use a foreach()
// if you ask directly for $block['k.ghi'] the engine will not seek for the value in the sub-array 
```
Here's how it works:
```php
<?php declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

// $page is the root of the tree
$page = new PhpEcho('layout/main.php');
$page['title'] = 'My first PhpEcho project';
$page['description'] = 'PhpEcho, a PHP template engine, easy to learn and use';
$page['login'] = 'rawsrc';
$page['url_submit'] = 'any/path/for/connection';

$body = new PhpEcho('block/login.php');  // login block expects two values (login and url_submit)

$page['body'] = $body; // $body['login'] and $body['url_submit'] are well-defined
// both are sought from the root block: $page

echo $page;
```

### **NOT FOUND KEY**

By default, if the key is not found, the engine will throw an `Exception`.
You can change that behavior, your can tell the engine to return `null` using the method `setNullIfNotExists(bool $p)`. 

## **AUTO ESCAPING VARS**

The auto-escaping feature works for keys and values. So please be careful:
```php
<?php
// suppose you have data like that:
$data = ['"name"' => 'rawsrc'];
// now we inject the data into a PhpEcho block
$block = new PhpEcho('dummy_block.php', ['my_data' => $data]);
// inside the block (in HTML context), we have to test the value of the key
// something like that:
?>
<?php foreach ($this['my_data'] as $key => $value) {
    // wrong code
    if ($key === '"name"') { // this will never be true as the key has been automatically escaped
        echo $value; // $value is automatically escaped
    }
    // correct code
    if ($key === '&quot;name&quot;') {  
        echo $value; // $value is automatically escaped
    }    
}
```
Or you can do it manually using the helper `raw()` and do not forget to escape the value:
```php
foreach ($this->raw('my_data') as $key => $value) {
    if ($key === '"name"') { 
        echo $this->hsc($value); // $value is manually escaped
    }   
}
```
Or you can also create a helper for this purpose that will not escape the keys but only values.
We will see the advanced use of `PhpEcho` below.

## **ARRAY OF PhpEcho INSTANCES**

You can define many strategies for views, especially regarding the level of details (the granularity) of complex layouts and pages.
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
<?= $this['preloader'] ?>
<?= $this['top_header'] ?>
<?= $this['navbar'] ?>
<?= $this['navbar_mobile'] ?>
<?= $this['body'] ?>
<?= $this['footer'] ?>
<?= $this['copyright'] ?>
</body>
```
The first code is abstract, and the second is really explicit about what is expected.
When you want to preserve some flexibility using the abstract code, it is possible 
to use an array of `PhpEcho` instances for a key.<br>
```php

use rawsrc\PhpEcho\PhpEcho;

$page['body'] = [
    new PhpEcho('block/preloader.php'),
    new PhpEcho('block/top_header.php'),
    new PhpEcho('block/navbar.php'),
    new PhpEcho('block/navbar_mobile.php'),
    new PhpEcho('block/body.php'),
    new PhpEcho('block/footer.php'),
    new PhpEcho('block/copyright.php'),
];
```
The blocks are rendered in the order they appear. 
Since 6.1.0, the engine is now able to render a recursive array of `PhpEcho` blocks. 
All are rendered in the order they appear.

## **USING A DEFAULT VIEW**

You can define a default block view to render for not defined key:
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this->renderByDefault('preloader', 'block/preloader.php') ?>
<?= $this->renderByDefault('top_header', 'block/top_header.php') ?>
<?= $this->renderByDefault('navbar', 'block/navbar.php') ?>
<?= $this->renderByDefault('navbar_mobile', 'block/navbar_mobile.php') ?>
<?= $this['body'] ?>
<?= $this->renderByDefault('footer', 'block/footer.php') ?>
<?= $this->renderByDefault('copyright', 'block/copyright.php') ?>
</body>
```
All keys except `body` are optional.

## **USE HEREDOC FOR HTML**

It's possible to use directly plain html code instead of file inclusion using HEREDOC.
Because of PHP early binding value upon calling, you must be sure that the values 
are defined before using them in the code.

Remember the layout block, we are going to omit the file `block/login.php` and 
directly inject the source code into the body part using the HEREDOC notation:<br>
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout/main.php', [
    'title' => 'My first use case of PhpEcho',
    'description' => 'PhpEcho, PHP template engine, easy to learn and use',
]);

// CAREFUL: you must define your values before injecting them into a HEREDOC block 
$body = new PhpEcho(vars: [
    'login' => 'rawsrc',
    'url_submit' => 'any/path/for/connection',
]);

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
// Note how it's coded, in this use case: `$body` replace `$this`
```

## **USE THE AUTO-GENERATED BLOCK'S ID**

Every instance of `PhpEcho` has an auto-generated id that can be linked to any html tag. 
This link will define a closed context that will allow us to work with the current block 
without interfering with others.

We'd like to test some new CSS on the block without interfering with other parts of the page.
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<?php $id = $this->getId() ?>
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
We have now a closed context defined by `<div id="<?= $id ?>">`.

## **USE THE COMPONENT `ViewBuilder`**

In an object oriented design, it's often easier to manipulate the whole view as an object too.
Let's have a look at the example about the login page.
You can now consider this view as a class using `ViewBuilder`:
```php
<?php

namespace YourProject\View\Page;

use rawsrc\PhpEcho\PhpEcho;
use rawsrc\PhpEcho\ViewBuilder;

class Login extends ViewBuilder
{
    public function build(): PhpEcho
    {
        $layout = new PhpEcho('layout/main.php');
        $layout['description'] = 'dummy.description';
        $layout['title'] = 'dummy.title';
        $layout['body'] = new PhpEcho('block/login.php', [
            'login' => 'rawsrc',
            'url_submit' => 'any/path/for/connection',
            /**
             * Note that the ViewBuilder implements the ArrayAccess interface, 
             * so you have plenty of ways to pass your values to the view.
             */
        ]);
        
        return $layout;
    } 
}
```
In a controller that must render the login page, you can now code something like that:
```php
<?php declare(strict_types=1);

namespace YourProject\Controller\Login;

use YourProject\View\Page\Login;

class Login
extends YourAbstractController 
{
    public function invoke(array $url_data = []): void
    {
        $page = new YourProject\View\Page\Login;
        // we pass some values to the page builder
        $page['name'] = 'rawsrc';
        $page['postal.code'] = 'foo.bar';
        
        // an example of ending the process sought from a framework
        $this->task->setResponse(Response::html($page));
    }
}
```

## **ADVANCE USE: CREATE YOUR OWN HELPERS**
You have the possibility to use your own code generators as simply as a `Closure`.<br>
There's a small standard library of helpers that comes with `PhpEcho`: `stdPhpEchoHelpers.php`<br>

### **HELPERS**
Each helper is a `Closure` that can produce whatever you want.<br>
Each helper can be linked to an instance of PhpEcho or remain a standalone helper.<br>
If linked to an instance, inside the closure you can use `$this` to get access to the caller's execution context.<br>
If standalone, this is just a simple function with parameters.<br>

If a helper needs to get access to the `PhpEcho` instance to whom it's linked, you must use
`PhpEcho::addBindableHelper()` to declare it otherwise just use `PhpEcho::addHelper()`.<br>
If the code generated by the helper is already escaped (to avoid double quote) set the third parameter `$result_escaped` to `true`<br>

### **STUDY: STANDALONE HELPER `$checked`**
This helper compares two scalar values and returns the string `" checked "` if they are equal.
```php
$checked = function($p, $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' checked ' : '';
};
PhpEcho::addHelper(name: 'checked', helper: $checked, result_escaped: true);
```
This helper is a standalone closure, there's no need to have access to an instance of PhpEcho.
As everything is escaped by default in PhpEcho, we can consider the word `" checked "` is safe 
and does not need to be escaped again, this is why, with the helper definition, the third parameter is set to `true`.<br>
To call this helper inside your views (two ways):
* `$this('checked', 'your value', 'ref value'); // based on __invoke`
* `$this->checked('your value', 'ref value'); // based on __call`

### **STUDY: BINDABLE HELPER `$raw`**
This helper returns the raw value from the stored key-value pair into each `PhpEcho` instance:
```php
$raw = function(string $key) {
    /** @var PhpEcho $this */
    return $this->getOffsetRawValue($key);
};
PhpEcho::addBindableHelper(name: 'raw', closure: $raw, result_escpaed: true);
```
As this helper extract data from the stored key-value pairs defined in each instance of PhpEcho, 
it needs access to the caller's execution context that's why the helper definition 
is created using `PhpEcho::addBindableHelper()`.<br>
And as we want to get the value unescaped, we must tell the engine that the returned value by the 
closure is already escaped. We know that is not, but this is the goal of that helper.
To call this helper inside your views (two ways):
* `$this('raw', 'key');`
* `$this->raw('key');`

### **DEFINING A HELPER AND COMPLEX BINDING**
To define a helper, there are two ways:
* `PhpEcho::addHelper(string $name, Closure $helper, bool $result_escaped = false)`
* `PhpEcho::addBindableHelper(string $name, Closure $helper, bool $result_escaped = false)`

When you code a new helper that will be bound to a class instance and needs to use another bound helper,
to be sure the two helpers refer to the same context, you must use this syntax 
`$existing_helper = $this->bound_helpers['$existing_helper_name'];` inside your code.
Please have a look at the `$root_var` helper (how the link to another bound helper `$root` is created).

## **LET'S PLAY WITH HELPERS**

As mentioned above, the standard library `stdPhpEchoHelpers.php` contains helpers for data processing and
also for HTML rendering. As helpers are small snippets of code, you can read their source code to understand 
easily what they will return. 
 
Examples:
* You need to create a `<input>` tag using the helper `voidTag()`:
```php
$this->voidTag('input', ['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']);
```
You do not have to worry about any dangerous character in this tag, all are escaped. Here's the rendered HTML code:<br>
```html
<input type="text" name="name" required value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;">
```
It is also possible to do like that (using the helper `attributes()`:
```php
<input <?= $this->attributes(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']) ?>>
```
As you see, there are tons of methods to get the expected result.<br>

Remember the problem with the auto-escape key value? Here's the helper that
returns the raw key and the escaped value at once.
```php
<?php 

use rawsrc\PhpEcho\PhpEcho;

/**
 * Return an array of raw keys and escaped values for HTML
 * Careful: keys are not safe in HTML context
 * 
 * @param array $part
 * @return array
 */
$hsc_array_values = function(array $part) use (&$hsc_array_values): array {
    $hsc = PhpEcho::getHelperBase('hsc');
    $to_escape = PhpEcho::getHelperBase('toEscape')    
    $data = [];
    foreach ($part as $k => $v) {
        if ($to_escape($v)) {
            if (is_array($v)) {
                $data[$k] = $hsc_array_values($v);
            } else {
                $data[$k] = $hsc($v);
            }
        } else {
            $data[$k] = $v;
        }
    }

    return $data;
};
PhpEcho::addBindableHelper('hscArrayValues', $hsc_array_values, true);
```

**rawsrc**