<?php

namespace rawsrc\PhpEcho;

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
implements \ArrayAccess
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
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->vars[$offset] ?? null;
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
     * This function return always escaped value with htmlspecialchars() from the array $vars
     *
     * You escape on demand anywhere in your code by calling this class like this :
     * $this('hsc', 'any scalar value you would like to escape');
     *
     * NOTE : a scalar value is a value that return true on PHP is_scalar() function
     * or an instance of class that implements the magic function __toString()
     *
     * @param  array  $args
     * @return mixed
     */
    public function __invoke(...$args)
    {
        $nb = count($args);

        if (empty($args) || ($nb > 2)) {
            return '';
        }

        /**
         * @param $p
         * @return bool
         */
        $is_scalar = function($p): bool {
            return is_scalar($p) || (is_object($p) && method_exists($p, '__toString'));
        };

        /**
         * @param  $p
         * @return string
         */
        $hsc = function($p): string {
            return htmlspecialchars((string)$p, ENT_QUOTES, 'utf-8');
        };

        /**
         * Return an array of escaped values with htmlspecialchars(ENT_QUOTES, 'utf-8') for both keys and values
         * Works for scalar and array type and transform any object having __toString() function implemented to a escaped string
         * Otherwise, keep the object as it
         *
         * @param  array $part
         * @return array
         */
        $hsc_array = function(array $part) use (&$hsc_array, $hsc, $is_scalar): array {
            $data = [];
            foreach ($part as $k => $v) {
                $sk = $hsc($k);
                if (is_array($v)) {
                    $data[$sk] = $hsc_array($v);
                } elseif ($is_scalar($v)) {
                    $data[$sk] = $hsc($v);
                } else {
                    $data[$sk] = $v;
                }
            }
            return $data;
        };

        $value = null;
        if (($nb === 1) && isset($this->vars[$args[0]])) {
            $value = $this->vars[$args[0]];
        } elseif ($args[0] === 'hsc') {
            $value = $args[1];
        }

        if ($is_scalar($value)) {
            return $hsc($value);
        } elseif (is_array($value)) {
            return $hsc_array($value);
        } else {
            return '';
        }
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