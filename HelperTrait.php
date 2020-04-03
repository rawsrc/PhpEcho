<?php

declare(strict_types=1);

namespace rawsrc\PhpEcho;

if ( ! defined('HELPER_BINDED_TO_CLASS_INSTANCE')) {
    define('HELPER_BINDED_TO_CLASS_INSTANCE', 1);
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
trait HelperTrait
{
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
     * @param int      ...$types
     */
    public static function addHelper(string $name, \Closure $closure, int ...$types)
    {
        self::$helpers[$name] = $closure;

        foreach ($types as $t) {
            self::$helpers_types[$name][] = $t; // HELPER_BINDED_TO_CLASS_INSTANCE || HELPER_RETURN_ESCAPED_DATA
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
        foreach (self::getHelpersByType([HELPER_BINDED_TO_CLASS_INSTANCE], false) as $name => $hlp) {
            $helpers[$name] = $hlp->bindTo($p, $p);
        }
        self::$helpers = $helpers;
    }
}
