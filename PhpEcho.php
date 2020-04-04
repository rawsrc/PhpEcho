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
 * @link        https://www.developpez.net/forums/blogs/32058-rawsrc/b8215/phpecho-moteur-rendu-php-classe-gouverner/
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
 */
class PhpEcho
implements ArrayAccess
{
    /**
     * @var string
     */
    private $id = '';
    /**
     * @var array
     */
    private $vars = [];
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
     * Return escaped value
     *
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (isset($this->vars[$offset])) {
            return $this('$hsc', $this->vars[$offset]);
        } else {
            return null;
        }
    }

    /**
     * Interface ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->vars[$offset] = $value;
    }

    /**
     * Interface ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->vars[$offset]);
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
            self::injectHelpers();
            if (self::isHelper($helper)) {
                self::bindHelpersTo($this);
                $escaped = self::isHelperOfType($helper, HELPER_RETURN_ESCAPED_DATA);
                $helper  = self::$helpers[$helper];
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
     * Magic method that returns a string instead of current instance of the class in a string context
     */
    public function __toString()
    {
        if (($this->file !== '') && is_file($this->file)) {
            ob_start();
            include $this->file;
            return ob_get_clean();
        } else {
            return $this->code;
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
                self::$helpers_file_path[$p] = true;
            }
        }
    }

    /**
     * Read the paths and inject only once all the helpers
     */
    public static function injectHelpers()
    {
        foreach (self::$helpers_file_path as $path => &$to_inject) {
            if ($to_inject && is_file($path)) {
                self::addHelpers(include $path);
                $to_inject = false;
            }
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
     * Only for helpers binded to a class instance
     *
     * @param object $p
     */
    public static function bindHelpersTo(object $p)
    {
        // link all helpers to the current context
        $helpers = self::$helpers;
        foreach (self::getHelpersByType([HELPER_BOUND_TO_CLASS_INSTANCE], false) as $name => $hlp) {
            $helpers[$name] = $hlp->bindTo($p, $p);
        }
        self::$helpers = $helpers;
    }
    //endregion
}

// make the class directly available on the global namespace
class_alias('rawsrc\PhpEcho\PhpEcho', 'PhpEcho', false);