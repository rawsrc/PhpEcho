<?php

namespace rawsrc\PhpEcho;

use ArrayAccess;
use rawsrc\PhpEcho\HelperTrait;

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
    use HelperTrait;

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
}

// make the class directly available on the global namespace
class_alias('rawsrc\PhpEcho\PhpEcho', 'PhpEcho', false);