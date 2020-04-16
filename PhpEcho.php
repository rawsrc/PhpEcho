<?php

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
 *
 * @method mixed  raw(string $key)      Return the raw value from a PhpEcho block
 * @method mixed  hsc($p)               Escape the value in parameter (scalar, array, stringifyable)
 * @method bool   isScalar($p)
 *
 * HTML HELPERS
 * @method string attributes(array $p)  Return the values as escaped attributes: attribute="..."
 * @method string selected($p, $ref)    Return " selected " if $p == $ref
 * @method string checked($p, $ref)     Return " checked "  if $p == $ref
 * @method string voidTag(string $tag, array $attributes = [])  Build a <tag>
 * @method string tag(string $tag, array $attributes = [])      Build a <tag></tag>
 * @method string link(array $attributes)   [rel => required, attribute => value]
 * @method string style(array $attributes)  [href => url | code => plain css definition, attribute => value]
 * @method string script(array $attributes) [src => url | code => plain javascript, attribute => value]
 * @method mixed  keyUp($keys, bool $strict_match) Climb the tree of PhpEcho instances while keys match
 * @method mixed  param($keys)              Extract a value from the root PhpEcho instance of the tree
 * @method PhpEcho root()                   Return the root PhpEcho instance of the tree
 */
class PhpEcho
    implements ArrayAccess
{
    private static $ALPHANUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

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
     * @param mixed  $file   see setFile() below
     * @param array  $vars
     * @param string $id     if empty then auto-generated
     */
    public function __construct($file = '', array $vars = [], string $id = '')
    {
        if ($file !== '') {
            $this->setFile($file);
        }

        if ($id === '') {
            $this->generateId();
        } else {
            $this->id = $id;
        }

        $this->vars = $vars;
        self::addPathToHelperFile(__DIR__.DIRECTORY_SEPARATOR.'stdHelpers.php');
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
     * Generate an unique execution id based on random_bytes()
     * Always start with a letter
     */
    public function generateId()
    {
        $this->id = chr(mt_rand(97, 122)).bin2hex(random_bytes(4));
    }

    //region ARRAY ACCESS
    /**
     * Interface ArrayAccess
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->vars);
    }

    /**
     * Interface ArrayAccess
     * The returned value is escaped
     *
     * Some types are preserved : true bool, true int, true float, PhpEcho instance, object without __toString()
     * Otherwise, the value is cast to a string and escaped
     *
     * If object: return the object
     *
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (isset($this->vars[$offset])) {
            $v = $this->vars[$offset];
            if ($this('$to_escape', $v)) {
                return $this('$hsc', $v);
            } else {
                return $v;
            }
        }
        return null;
    }

    /**
     * Interface ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->vars[$offset] = $value;
        if ($value instanceof PhpEcho) {
            $this->has_children = true;
            $value->parent      = $this;
        }
    }

    /**
     * Interface ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->vars[$offset]);
    }
    //endregion

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
     * @return string
     */
    public function templateDirectory(): string
    {
        return $this->file === '' ? '' : dirname($this->file);
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
     * @return bool
     */
    public function hasParent(): bool
    {
        return ($this->parent instanceof PhpEcho);
    }

    /**
     * If  = 1 arg  => plain html code (string or array of html code)
     * If >= 2 args => first=helper + the rest=helper's params
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
     * @param int $length
     * @return string
     */
    private static function token(int $length = 12): string
    {
        return substr(str_shuffle(self::$ALPHANUM.mt_rand(100000000, 999999999)), 0, $length);
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
                // if you don't want so, use the specific helper '$raw'
                if ($escaped) {
                    return $result;
                } else {
                    return $this('$hsc', $result);
                }
            }
        }
        return null;
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (self::isHelper($name)) {
            return $this->__invoke($name, ...$arguments);
        } elseif (self::isHelper('$'.$name)) {
            return $this->__invoke('$'.$name, ...$arguments);
        } else {
            return null;
        }
    }

    /**
     * Magic method that returns a string instead of current instance of the class in a string context
     */
    public function __toString()
    {
        $this->render();
        $root = $this('$root');
        if (($root === $this) && $this->head_token) {
            if ($this->head_escape) {
                $head = implode('', $this('$hsc', $this->head));
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
     * @var array  [path to the helpers file to include]
     */
    private static $helpers_file_path = [];
    /**
     * @var array [helper's name => [type]]
     */
    private static $helpers_types = [];
    /**
     * @var array   [helpers filepath to inject]
     */
    private static $helpers_file_to_inject = [];

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
     * Path to the file that contains helpers closure definition
     * The helpers are common to all instances and will be included only once
     *
     * @param string ...$path
     */
    public static function addPathToHelperFile(string ...$path)
    {
        foreach ($path as $p) {
            if ( ! isset(self::$helpers_file_path[$p])) {
                self::$helpers_file_path[$p]    = true;
                self::$helpers_file_to_inject[] = $p;
            }
        }
    }

    /**
     * Read the paths and inject only once all the helpers
     */
    public static function injectHelpers()
    {
        foreach (self::$helpers_file_to_inject as $path) {
            if (is_file($path)) {
                self::addHelpers(include $path);
            }
        }
        self::$helpers_file_to_inject = [];
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
        if (isset(self::$helpers[$helper_name])) {
            return true;
        } else {
            self::injectHelpers();
            return isset(self::$helpers[$helper_name]);
        }
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
        return isset(self::$helpers_types[$helper_name])
            ? in_array($type, self::$helpers_types[$helper_name])
            : false;
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

// make the class directly available on the global namespace
class_alias('rawsrc\PhpEcho\PhpEcho', 'PhpEcho', false);