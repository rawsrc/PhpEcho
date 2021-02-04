<?php declare(strict_types=1);

namespace rawsrc\PhpEcho;

use ArrayAccess;

if ( ! defined('HELPER_BOUND_TO_CLASS_INSTANCE')) {
    define('HELPER_BOUND_TO_CLASS_INSTANCE', 1);
}

if ( ! defined('HELPER_RETURN_ESCAPED_DATA')) {
    define('HELPER_RETURN_ESCAPED_DATA', 2);
}

/**
 * PhpEcho : PHP Template engine : One class to rule them all ;-)
 *
 * @link        https://www.developpez.net/forums/blogs/32058-rawsrc/b9154/phpecho-version-2-0-0/
 * @author      rawsrc - https://www.developpez.net/forums/u32058/rawsrc/
 * @copyright   MIT License
 *
 *              Copyright (c) 2020 rawsrc
 *
 *              Permission is hereby granted, free of charge, to any person obtaining a copy
 *              of this software and associated documentation files (the "Software"), to deal
 *              in the Software without restriction, including without limitation the rights
 *              to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *              copies of the Software, and to permit persons to whom the Software is
 *              furnished to do so, subject to the following conditions:
 *
 *              The above copyright notice and this permission notice shall be included in all
 *              copies or substantial portions of the Software.
 *
 *              THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *              IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *              FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *              AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *              LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *              OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *              SOFTWARE.
 *
 * PhpEcho HELPERS
 * @method mixed   raw(string $key)                 Return the raw value from a PhpEcho block
 * @method bool    isScalar($p)
 * @method mixed   keyUp($keys, bool $strict_match) Climb the tree of PhpEcho instances while keys match
 * @method mixed   rootVar($keys)                   Extract the value from the top level PhpEcho block (the root)
 * @method PhpEcho root()                           Return the root PhpEcho instance of the tree
 *
 * HTML HELPERS
 * @method string  csrf(string $html_input_name = 'csrftoken')  Create an hidden input with an auto-generated csrf token
 * @method mixed   hsc($p)                          Escape the value in parameter (scalar, array, stringifyable)
 * @method string  attributes(array $p)             Return the values as escaped attributes: attribute="..."
 * @method string  selected($p, $ref)               Return " selected " if $p == $ref
 * @method string  checked($p, $ref)                Return " checked "  if $p == $ref
 * @method string  voidTag(string $tag, array $attributes = [])  Build a <tag>
 * @method string  tag(string $tag, array $attributes = [])      Build a <tag></tag>
 * @method string  link(array $attributes)          [rel => required, attribute => value]
 * @method string  style(array $attributes)         [href => url | code => plain css definition, attribute => value]
 * @method string  script(array $attributes)        [src => url | code => plain javascript, attribute => value]
 */
class PhpEcho
    implements ArrayAccess
{
    private static $ALPHANUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * @var array
     */
    private static $tokens = [];
    /**
     * @var string
     */
    private $id = '';
    /**
     * @var array
     */
    private $vars = [];
    /**
     * @var array
     */
    private $params = [];
    /**
     * @var array
     */
    private $head = [];
    /**
     * @var string
     */
    private $head_token = '';
    /**
     * @var bool
     */
    private $head_escape = true;
    /**
     * Full resolved filepath to the external view file
     * @var string
     */
    private $file = '';
    /**
     * @var string
     */
    private $code = '';
    /**
     * @var array [helper's id => bound closure]
     */
    private $bound_helpers = [];
    /*
     * Indicates if the current instance contains in its vars other PhpEcho instance(s)
     * @var bool
     */
    private $has_children = false;
    /**
     * @var PhpEcho
     */
    private $parent;
    /**
     * @var bool
     */
    private static $std_helpers_injected = false;
    /**
     * If true then tke keys should never contain a space between words
     * Each space will be transformed into a sub-array: 'abc def' => ['abc']['def']
     * @var bool
     */
    private static $use_space_notation = true;
    /**
     * @var string The full path to the template root directory
     */
    private static $template_dir;

    /**
     * @param mixed  $file   see setFile() below
     * @param array  $vars
     * @param string $id     if empty then auto-generated
     */
    public function __construct($file = '', array $vars = [], string $id = '')
    {
        // injecting only once the stdPhpEchoHelpers.php
        if (self::$std_helpers_injected === false) {
            self::injectHelpers(__DIR__.DIRECTORY_SEPARATOR.'stdPhpEchoHelpers.php');
            self::$std_helpers_injected = true;
        }

        if ($file !== '') {
            $this->setFile($file);
        }

        if ($id === '') {
            $this->generateId();
        } else {
            $this->id = $id;
        }

        $this->vars = $vars;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @param        $value
     */
    public function setParam(string $name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * The param is never escaped
     *
     * @param string $name
     * @return mixed|null
     */
    public function param(string $name)
    {
        return $this('seekParam', $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    /**
     * Generate an unique execution id based on random_bytes()
     * Always start with a letter
     */
    public function generateId()
    {
        $this->id = chr(mt_rand(97, 122)).bin2hex(random_bytes(4));
    }

    //region ARRAY ACCESS
    /**
     * If "support the space notation for array and sub-arrays" is activated then
     * if $offset = 'abc def' then the engine will search for the key in $vars['abc']['def']
     *
     * Interface ArrayAccess
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (self::$use_space_notation) {
            $keys = explode(' ', $offset);
            if (count($keys) > 1) {
                $last = array_pop($keys);
                $data = $this->vars;
                foreach ($keys as $k) {
                    if (isset($data[$k])) {
                        $data = $data[$k];
                    } else {
                        return false;
                    }
                }
                return array_key_exists($last, $data);
            }
        }
        return array_key_exists($offset, $this->vars);
    }

    /**
     * Interface ArrayAccess
     * The returned value is escaped
     *
     * Some types are preserved : true bool, true int, true float, PhpEcho instance, object without __toString()
     * Otherwise, the value is cast to a string and escaped
     *
     * If "support the space notation for array and sub-arrays" is activated then
     * if $offset = 'abc def' then the engine will search for the key in $vars['abc']['def']
     *
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (self::$use_space_notation) {
            $keys = explode(' ', $offset);
            $data = $this->vars;
            foreach ($keys as $k) {
                if (isset($data[$k])) {
                    $data = $data[$k];
                } else {
                    return null;
                }
            }
            $v = $data;
        } elseif (isset($this->vars[$offset])) {
            $v = $this->vars[$offset];
        } else {
            return null;
        }

        if ($this('toEscape', $v)) {
            return $this('hsc', $v);
        } else {
            return $v;
        }
    }

    /**
     * If "support the space notation for array and sub-arrays" is activated then
     * if $offset = 'abc def' then the engine will define an array and a sub-array: $vars['abc']['def']
     *
     * Interface ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if ($value instanceof PhpEcho) {
            $this->has_children = true;
            $value->parent      = $this;
        }

        if (self::$use_space_notation) {
            $keys = explode(' ', $offset);
            if (count($keys) > 1) {
                $data =& $this->vars;
                foreach ($keys as $k) {
                    $data[$k] = [];
                    $data     =& $data[$k];
                }
                $data = $value;
                return;
            }
        }
        $this->vars[$offset] = $value;
    }

    /**
     * If "support the space notation for array and sub-arrays" is activated then
     * if $offset = 'abc def' then the engine will unset the key in $vars['abc']['def']
     *
     * Interface ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (self::$use_space_notation) {
            $keys = explode(' ', $offset);
            if (count($keys) > 1) {
                $last = array_pop($keys);
                $data =& $this->vars;
                foreach ($keys as $k) {
                    if (isset($data[$k])) {
                        $data =& $data[$k];
                    } else {
                        return;
                    }
                }
                unset ($data[$last]);
                return;
            }
        }
        unset ($this->vars[$offset]);
    }
    //endregion

    /**
     * @param $offset
     * @return bool
     */
    public function issetAndTrue($offset): bool
    {
        return $this->offsetExists($offset) && ($this->offsetGet($offset) === true);
    }

    /**
     * @param $offset
     * @return bool
     */
    public function issetAndFalse($offset): bool
    {
        return $this->offsetExists($offset) && ($this->offsetGet($offset) === false);
    }

    /**
     * Define the filepath to the external view file to include
     *
     * Rule R001 : Any space inside a name will be automatically converted to DIRECTORY_SEPARATOR
     *
     * For strings : $parts = 'www user view login.php';
     *  - become "www/user/view/login.php"  if DIRECTORY_SEPARATOR = '/'
     *  - become "www\user\view\login.php"  if DIRECTORY_SEPARATOR = '\'
     *
     * For arrays, same rule (R001) for all values inside : $parts = ['www/user', 'view login.php'];
     *  - become "www/user/view/login.php"  if DIRECTORY_SEPARATOR = '/'
     *  - become "www/user\view\login.php"  if DIRECTORY_SEPARATOR = '\'
     *
     * File inclusion remove the inline code
     *
     * @param mixed $parts string|array
     */
    public function setFile($parts)
    {
        $file  = [];
        $parts = is_string($parts) ? explode(' ', $parts) : $parts;
        foreach ($parts as $p) {
            $file[] = str_replace(' ', DIRECTORY_SEPARATOR, $p);
        }
        $this->file = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, $file));
        $this->code = '';
    }

    /**
     * @param string $p
     */
    public static function setTemplateDir(string $p)
    {
        // remove trailing slash
        if (mb_substr($p, -1, 1) === DIRECTORY_SEPARATOR) {
            self::$template_dir = mb_substr($p, 0, -1);
        } else {
            self::$template_dir = $p;
        }
    }

    /**
     * Instead on including an external file, use inline code for the view
     *
     * CAREFUL : when you use inline code with dynamic values from the array $vars, you must
     * be absolutely sure that the values are already defined before, otherwise you will only have empty strings
     *
     * Inline code remove the included file
     *
     * @param string $code
     */
    public function setCode(string $code)
    {
        $this->code = $code;
        $this->file = '';
    }

    /**
     * Indicates if the current instance contains in its vars other PhpEcho instance(s)
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->has_children;
    }

    /**
     * @param string     $from
     * @param string     $var_name
     * @param string     $file
     * @param array|null $vars
     * @param string     $id
     * @return PhpEcho
     */
    private function addChildFrom(string $from, string $var_name, string $file, ?array $vars = null, string $id = ''): PhpEcho
    {
        $parts = explode(' ', $file);
        if ($from !== '') {
            array_unshift($parts, $from);
        }
        return $this->addChild($var_name, implode(DIRECTORY_SEPARATOR, $parts), $vars, $id);
    }

    /**
     * Create a new instance of PhpEcho from a file path and link them each other
     * You must never use a space in any part of the real file path: space is read as DIRECTORY_SEPARATOR
     *
     * @param string     $var_name  the var used in the current template targeting the child block
     * @param string     $file      you must provide the full path to the PhpEcho file to include using space as directory separator
     * @param array|null $vars      if null then the parent transfers its internal vars to the child
     * @param string     $id
     * @return PhpEcho              Return the new instance
     */
    public function addChild(string $var_name, string $file, ?array $vars = null, string $id = ''): PhpEcho
    {
        $block = new PhpEcho($file, $vars ?? $this->vars, $id);
        $block->parent         = $this;
        $this->has_children    = true;
        $this->vars[$var_name] = $block;
        return $block;
    }

    /**
     * Create a new instance of PhpEcho using the current view directory as root for the file to include
     * You must never use a space in any part of the real file path: space is read as DIRECTORY_SEPARATOR
     *
     * @param string     $var_name  the var used in the current template targeting the child block
     * @param string     $file      you must provide the relative path to the PhpEcho file to include using space as directory separator
     * @param array|null $vars      if null then the parent transfers its internal vars to the child
     * @param string     $id
     * @return PhpEcho              Return the new instance
     */
    public function addChildFromCurrent(string $var_name, string $file, ?array $vars = null, string $id = ''): PhpEcho
    {
        $from = ($this->file === '') ? '' : dirname($this->file);
        return $this->addChildFrom($from, $var_name, $file, $vars, $id);
    }

    /**
     * Create a new instance of PhpEcho using the template directory root
     * You must never use a space in any part of the real file path: space is read as DIRECTORY_SEPARATOR
     *
     * @param string     $var_name  the var used in the current template targeting the child block
     * @param string     $file      you must provide the relative path to the PhpEcho file to include using space as directory separator
     * @param array|null $vars      if null then the parent transfers its internal vars to the child
     * @param string     $id
     * @return PhpEcho              Return the new instance
     */
    public function addChildFromTplDir(string $var_name, string $file, ?array $vars = null, string $id = ''): PhpEcho
    {
        return $this->addChildFrom(self::$template_dir, $var_name, $file, $vars, $id);
    }

    /**
     * @return bool
     */
    public function hasParent(): bool
    {
        return ($this->parent instanceof PhpEcho);
    }

    /**
     * If only 1 arg       => plain html code (string or array of html code)
     * If more than 1 args => first=helper + the rest=helper's params
     * @param mixed ...$args
     */
    public function addHead(...$args)
    {
        // the head is only stored in the root of the tree
        $root = $this->root();
        $nb   = count($args);
        if ($nb === 1) {
            if (is_string($args[0])) {
                $root->head[] = $args[0];
            } elseif (is_array($args[0])) {
                array_push($root->head, ...$args[0]);
            }
        } elseif ($nb >= 2) {
            // the first param should be a helper
            $helper = array_shift($args);
            if (self::isHelper($helper)) {
                $root->head[] = $this($helper, ...$args);
            }
        }
    }

    /**
     * @param bool $escape  If you dont want to escape the head, set it to false
     * @return string
     */
    public function head(bool $escape): string
    {
        // generate a token that will be replaced after rendering the whole HTML
        $this->head_token  = self::token(26);
        $this->head_escape = $escape;
        return $this->head_token;
    }

    /**
     * @param int $length   min = 12 chars
     * @return string
     */
    private static function token(int $length = 12): string
    {
        $length = ($length < 12) ? 12 : $length;
        do {
            $token = substr(str_shuffle(self::$ALPHANUM.mt_rand(100000000, 999999999)), 0, $length);
        } while (isset(self::$tokens[$token]));
        self::$tokens[$token] = true;
        return $token;
    }

    /**
     * @param bool $p
     */
    public static function useSpaceNotationForKeys(bool $p)
    {
        self::$use_space_notation = $p;
    }

    //region MAGIC METHODS
    /**
     * This function call a helper defined elsewhere or dynamically
     * Auto-escape if necessary
     *
     * @param string $helper
     * @param array  $args
     * @return mixed
     */
    public function __invoke(string $helper, ...$args)
    {
        if ($helper !== '') {
            if (self::isHelper($helper)) {
                if (empty($this->bound_helpers)) {
                    $this->bound_helpers = self::bindHelpersTo($this);
                }
                $escaped = self::isHelperOfType($helper, HELPER_RETURN_ESCAPED_DATA);
                $helpers = $this->bound_helpers + self::$helpers;
                $helper  = $helpers[$helper];
                $result  = $helper(...$args);
                // being in a HTML context: in any case, the returned data should be escaped
                // if you don't want so, use the specific helper 'raw'
                if ($escaped) {
                    return $result;
                } else {
                    return $this('hsc', $result);
                }
            }
        }
        return null;
    }

    /**
     * Magic method that returns a string instead of current instance of the class in a string context
     */
    public function __toString()
    {
        $this->render();
        $root = $this('root');
        if (($root === $this) && $this->head_token) {
            if ($this->head_escape) {
                $head = implode('', $this('hsc', $this->head));
            } else {
                $head = implode('', $this->head);
            }
            return str_replace($this->head_token, $head, $this->code);
        } else {
            return $this->code;
        }
    }
    //endregion

    /**
     * Manually render the block
     */
    public function render()
    {
        if ($this->code === '') {
            if (($this->file !== '') && is_file($this->file)) {
                ob_start();
                include $this->file;
                $this->code = ob_get_clean();
            }
        }
    }

    //region HELPER ZONE
    /**
     * @var array  [name => closure]
     */
    private static $helpers = [];
    /**
     * @var array [helper's name => [type]]
     */
    private static $helpers_types = [];

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (isset(self::$helpers[$name])) {
            return $this->__invoke($name, ...$arguments);
        } else {
            return null;
        }
    }

    /**
     * @param string   $name
     * @param \Closure $closure
     * @param int      ...$types  HELPER_RETURN_ESCAPED_DATA HELPER_BOUND_TO_CLASS_INSTANCE
     */
    public static function addHelper(string $name, \Closure $closure, int ...$types)
    {
        self::$helpers[$name] = $closure;
        foreach ($types as $t) {
            self::$helpers_types[$name][] = $t; // HELPER_BOUND_TO_CLASS_INSTANCE HELPER_RETURN_ESCAPED_DATA
        }
    }

    /**
     * @param array $helpers [name => Closure | name => [Closure, ...type]]
     */
    public static function addHelpers(array $helpers)
    {
        foreach ($helpers as $name => $h) {
            if ($h instanceof \Closure) {
                self::$helpers[$name] = $h;
            } elseif (is_array($h)) {
                self::addHelper($name, array_shift($h), ...$h);
            }
        }
    }

    /**
     * @return array [name => closure]
     */
    public static function helpers(): array
    {
        return self::$helpers;
    }

    /**
     * @param string $path
     */
    public static function injectHelpers(string $path)
    {
        if (is_file($path)) {
            self::addHelpers(include $path);
        }
    }

    /**
     * @param  string   $helper_name
     * @return array    [int]
     */
    public static function getHelperTypes(string $helper_name): array
    {
        return self::$helpers_types[$helper_name] ?? [];
    }

    /**
     * @param  string $helper_name
     * @return bool
     */
    public static function isHelper(string $helper_name): bool
    {
        return isset(self::$helpers[$helper_name]);
    }

    /**
     * Check if the helper has the defined type
     *
     * @param  string $helper_name
     * @param  int    $type
     * @return bool
     */
    public static function isHelperOfType(string $helper_name, int $type): bool
    {
        return isset(self::$helpers_types[$helper_name]) && in_array($type, self::$helpers_types[$helper_name]);
    }

    /**
     * @param  array $type      array of types [type]
     * @param  bool  $strict    when match, check if the helper has only the asked types
     * @return array            [helper's name => closure]
     */
    public static function getHelpersByType(array $type, bool $strict = false): array
    {
        $data = [];
        foreach (self::$helpers_types as $name => $v) {
            $intersect = array_intersect($type, $v);
            if (( ! empty($intersect)) && (count($type) === count($intersect))) {
                if ($strict) {
                    if  (count($type) === count($v)) {
                        $data[$name] = self::$helpers[$name];
                    }
                } else {
                    $data[$name] = self::$helpers[$name];
                }
            }
        }
        return $data;
    }

    /**
     * Change the helper's binding context to the given one in parameter
     * Only for helpers bound to a class instance
     *
     * @param object $p
     * @return array        [helper's id => bound closure]
     */
    public static function bindHelpersTo(object $p): array
    {
        $helpers = [];
        foreach (self::getHelpersByType([HELPER_BOUND_TO_CLASS_INSTANCE], false) as $name => $hlp) {
            $helpers[$name] = $hlp->bindTo($p, $p);
        }
        return $helpers;
    }
    //endregion
}
