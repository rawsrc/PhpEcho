<?php

declare(strict_types=1);

namespace rawsrc\PhpEcho;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use InvalidArgumentException;

use function array_key_exists;
use function array_map;
use function array_pop;
use function array_push;
use function array_shift;
use function bin2hex;
use function chr;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_file;
use function is_string;
use function mt_rand;
use function ob_get_clean;
use function ob_start;
use function random_bytes;
use function str_contains;
use function str_replace;
use function str_shuffle;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * PhpEcho : Native PHP Template engine: One class to rule them all ;-)
 *
 * @author      rawsrc
 * @copyright   MIT License
 *
 *              Copyright (c) 2020-2022 rawsrc
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
 * @method mixed raw(string $key)                 Return the raw value from a PhpEcho block
 * @method bool isScalar($p)
 * @method mixed keyUp($keys, bool $strict_match) Climb the tree of PhpEcho instances while keys match
 * @method mixed rootVar($keys)                   Extract the value from the top level PhpEcho block (the root)
 * @method PhpEcho root()                         Return the root PhpEcho instance of the tree
 *
 * HTML HELPERS
 * @method mixed hsc($p)                          Escape the value in parameter (scalar, array, stringifyable)
 * @method string attributes(array $p)            Return the values as escaped attributes: attribute="..."
 * @method string selected($p, $ref)              Return " selected " if $p == $ref
 * @method string checked($p, $ref)               Return " checked "  if $p == $ref
 * @method string voidTag(string $tag, array $attributes = [])  Build a <tag />
 * @method string tag(string $tag, array $attributes = [])      Build a <tag></tag>
 * @method string link(array $attributes)         [rel => required, attribute => value]
 * @method string style(array $attributes)        [href => url | code => plain css definition, attribute => value]
 * @method string script(array $attributes)       [src => url | code => plain javascript, attribute => value]
 */
class PhpEcho
implements ArrayAccess
{
    private const ALPHA_NUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const TOKEN_MIN_LENGTH = 16;

    private string $id = '';
    private array $vars = [];
    private array $params = [];
    private array $head = [];
    private string $head_token = '';
    /**
     * Partial file path to the external view file (from the template dir root)
     * @var string
     */
    private string $file = '';
    /**
     * @var string
     */
    private string $code = '';
    /**
     * @var array [helper's id => bound closure]
     */
    private array $bound_helpers = [];
    /**
     * Indicates if the current instance contains in its vars other PhpEcho instance(s)
     * @var bool
     */
    private bool $has_children = false;
    /**
     * @var PhpEcho
     */
    private PhpEcho $parent;
    /**
     * If true then the array keys should never contain a space between words
     * Each space will be transformed into a sub-array: 'abc def' => ['abc']['def']
     * @var bool
     */
    private static bool $use_space_notation = true;
    /**
     * @var string
     */
    private static string $template_dir_root = '';
    /**
     * @var array  [name => closure]
     */
    private static array $helpers = [];
    /**
     * Array of bindable helpers to the class instance
     * @var array [helper's name => true]
     */
    private static array $bindable_helpers = [];
    /**
     * @var array [helper's name => true]
     */
    private static array $helpers_result_escaped = [];
    /**
     * Used tokens
     * @var array [token => true]
     */
    private static array $tokens = [];
    /**
     * @var array
     */
    private static array $global_params = [];

    //region MAGIC METHODS
    /**
     * @param string $file path from the template dir root
     * @param array $vars
     * @param string $id if empty then auto-generated
     */
    public function __construct(string $file = '', array $vars = [], string $id = '')
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
     * This function call a helper defined elsewhere or dynamically
     * Auto-escape if necessary
     *
     * @param string $helper
     * @param mixed $args
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __invoke(string $helper, mixed ...$args): mixed
    {
        if ($helper === '') {
            throw new InvalidArgumentException('helper.cannot.be.empty');
        }

        if (self::isHelper($helper, true)) {
            if ($this->bound_helpers === []) {
                $this->bindHelpersTo($this);
            }
            $helpers = $this->bound_helpers + self::$helpers;
            $hlp = $helpers[$helper];
            $result = $hlp(...$args);

            if ($this('toEscape', $result)) {
                if (is_string($result)) {
                    if (self::isHelperResultEscaped($helper)) {
                        return $result;
                    } else {
                        return $this('hsc', $result);
                    }
                }
            }

            return $result;
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->__invoke($name, ...$arguments);
    }

    /**
     * Magic method that returns a string instead of current instance of the class in a string context
     * @throws BadMethodCallException
     */
    public function __toString(): string
    {
        $this->render();
        $root = $this('root');
        if (($root === $this) && ( ! empty($this->head_token))) {
            $head = implode('', $this->head);

            return str_replace($this->head_token, $head, $this->code);
        } else {
            return $this->code;
        }
    }
    //endregion

    /**
     * Return the full path to a view file, prepend it with the template dir root
     *
     * @param string $path space separated of path segments
     * @return string
     */
    public static function getFullFilepath(string $path): string
    {
        $path = str_replace(' ', DIRECTORY_SEPARATOR, $path);
        if ((self::$template_dir_root !== '') && ( ! str_contains($path, self::$template_dir_root))) {
            $path = self::$template_dir_root.DIRECTORY_SEPARATOR.$path;
        }

        return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param bool $p
     */
    public static function setUseSpaceNotation(bool $p): void
    {
        self::$use_space_notation = $p;
    }

    /**
     * @return bool
     */
    public static function useSpaceNotation(): bool
    {
        return self::$use_space_notation;
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
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Define a local parameter
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParam(string $name, mixed $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Get the value of a local parameter
     * The value of the parameter is never escaped
     *
     * if the parameter does not exist then throw an exception
     *
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getParam(string $name): mixed
    {
        return array_key_exists($name, $this->params)
            ? $this->params[$name]
            : throw new InvalidArgumentException("unknown.parameter.{$name}");
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
     * Parameter that is available through all PhpEcho instances
     *
     * @param string $name
     * @param mixed $value
     */
    public static function setGlobalParam(string $name, mixed $value)
    {
        self::$global_params[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function getGlobalParam(string $name): mixed
    {
        return array_key_exists($name, self::$global_params)
            ? self::$global_params[$name]
            : throw new InvalidArgumentException("unknown.parameter.{$name}");
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasGlobalParam(string $name): bool
    {
        return array_key_exists($name, self::$global_params);
    }

    /**
     * Generate a unique execution id based on random_bytes()
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
     * @param  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
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
     * For array of PhpEcho blocks, the array is imploded and the blocks are rendered int the order they appear
     * Otherwise, the value is cast to a string and escaped
     *
     * If "support the space notation for array and sub-arrays" is activated then
     * if $offset = 'abc def' then the engine will search for the key in $vars['abc']['def']
     *
     * @param $offset
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function offsetGet($offset): mixed
    {
        $v = $this->getOffsetRawValue($offset);

        if ($v === null) {
            return null;
        } elseif ($this->isArrayOfPhpEchoBlocks($v)) {
            // intercept the case where $v is an array of PhpEcho blocks
            return implode('', array_map('strval', $v));
        } elseif ($this('toEscape', $v)) {
            return $this('hsc', $v);
        } else {
            return $v;
        }
    }

    /**
     * @param $offset
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function getOffsetRawValue($offset): mixed
    {
        if (self::$use_space_notation) {
            $data = $this->vars;
            $keys = explode(' ', $offset);
            $last = array_pop($keys);
            foreach ($keys as $k) {
                if (isset($data[$k])) {
                    $data = $data[$k];
                } else {
                    throw new InvalidArgumentException("unknown.offset.{$k}");
                }
            }
            if (array_key_exists($last, $data)) {
                return $data[$last];
            } else {
                throw new InvalidArgumentException("unknown.offset.{$last}");
            }
        } elseif (isset($this->vars[$offset])) {
            return $this->vars[$offset];
        } else {
            throw new InvalidArgumentException("unknown.offset.{$offset}");
        }
    }

    /**
     * If "support the space notation for array and sub-arrays" is activated then
     * if $offset = 'abc def' then the engine will define an array and a sub-array: $vars['abc']['def']
     *
     * Interface ArrayAccess
     * @param $offset
     * @param mixed $value
     */
    public function offsetSet($offset, mixed $value): void
    {
        // we keep the rule from addBlock():
        // if $value is a PhpEcho instance and if there's no defined vars
        // then the parent block transfers its internal vars to the child
        if ($value instanceof PhpEcho) {
            $this->has_children = true;
            $value->parent = $this;
            if (empty($value->vars)) {
                $value->vars = $this->vars;
            }
        } elseif ($this->isArrayOfPhpEchoBlocks($value)) {
            $this->has_children = true;
            /** @var self $block */
            foreach ($value as $block) {
                $block->parent = $this;
                if (empty($block->vars)) {
                    $block->vars = $this->vars;
                }
            }
        }

        if (self::$use_space_notation) {
            $keys = explode(' ', $offset);
            if (count($keys) > 1) {
                $data =& $this->vars;
                foreach ($keys as $k) {
                    $data[$k] = [];
                    $data =& $data[$k];
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
     * @param $offset
     */
    public function offsetUnset($offset): void
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
                        throw new InvalidArgumentException("unknown.offset.{$k}");
                    }
                }

                if (array_key_exists($last, $data)) {
                    unset($data[$last]);
                } else {
                    throw new InvalidArgumentException("unknown.offset.{$last}");
                }
            }
        } elseif (array_key_exists($offset, $this->vars)) {
            unset($this->vars[$offset]);
        } else {
            throw new InvalidArgumentException("unknown.offset.{$offset}");
        }
    }
    //endregion ARRAY ACCESS

    //region HELPER ZONE
    /**
     * @param int $length min = 12 chars
     * @return string
     */
    public static function getToken(int $length = self::TOKEN_MIN_LENGTH): string
    {
        $length = max(self::TOKEN_MIN_LENGTH, $length);
        do {
            $token = substr(str_shuffle(self::ALPHA_NUM.mt_rand(100000000, 999999999)), 0, $length);
        } while (isset(self::$tokens[$token]));

        self::$tokens[$token] = true;

        return $token;
    }

    public static function injectStandardHelpers(): void
    {
        self::injectHelpers(__DIR__.DIRECTORY_SEPARATOR.'stdPhpEchoHelpers.php');
    }

    /**
     * Inject the helpers from a file
     * @param string $path full path file from the root
     */
    public static function injectHelpers(string $path): void
    {
        if (is_file($path)) {
            include $path;
        } else {
            throw new InvalidArgumentException("helpers.file.not.found.{$path}");
        }
    }

    /**
     * @param string $name
     * @param Closure $helper
     * @param bool $result_escaped
     */
    public static function addHelper(string $name, Closure $helper, bool $result_escaped = false): void
    {
        self::$helpers[$name] = $helper;
        if ($result_escaped) {
            self::$helpers_result_escaped[] = $name;
        }
    }

    /**
     * @param string $name
     * @param Closure $helper
     * @param bool $result_escaped
     */
    public static function addBindableHelper(string $name, Closure $helper, bool $result_escaped = false): void
    {
        self::$helpers[$name] = $helper;
        self::$bindable_helpers[$name] = true;
        if ($result_escaped) {
            self::$helpers_result_escaped[] = $name;
        }
    }

    /**
     * @param string $name
     * @return Closure
     * @throws InvalidArgumentException
     */
    public static function getHelper(string $name): Closure
    {
        if (self::isHelper($name, true)) {
            return self::$helpers[$name];
        }
    }

    /**
     * @return array [name => closure]
     */
    public static function getHelpers(): array
    {
        return self::$helpers;
    }

    /**
     * @param string $name
     * @param bool $throw_if_not
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function isHelper(string $name, bool $throw_if_not = false): bool
    {
        if (isset(self::$helpers[$name])) {
            return true;
        } else {
            return $throw_if_not ? throw new InvalidArgumentException("unknown.helper.{$name}") : false;
        }
    }

    /**
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function isHelperResultEscaped(string $name): bool
    {
        return self::isHelper($name, true) && isset(self::$helpers_result_escaped[$name]);
    }

    /**
     * Check if the helper must be bound to a class instance
     *
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function isHelperBindable(string $name): bool
    {
        return self::isHelper($name, true) && isset(self::$bindable_helpers[$name]);
    }

    /**
     * Change the helper's binding context to the given one in parameter
     * Only for helpers bound to a class instance
     *
     * @param object $p
     */
    private function bindHelpersTo(object $p): void
    {
        $helpers = [];
        foreach (array_keys(self::$bindable_helpers) as $name) {
            $hlp = self::$helpers[$name];
            $helpers[$name] = $hlp->bindTo($p, $p);
        }
        $this->bound_helpers = $helpers;
    }
    //endregion HELPER ZONE

    /**
     * @param mixed $p
     * @return bool
     */
    private function isArrayOfPhpEchoBlocks(mixed $p): bool
    {
        if (is_array($p)) {
            foreach ($p as $v) {
                if ( ! ($v instanceof self)) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $p The path to the root directory of the view files
     */
    public static function setTemplateDirRoot(string $p): void
    {
        self::$template_dir_root = $p;
    }

    /**
     * Define the filepath to the external view file to include from the template dir root
     * The full resolved filepath is built using the template directory root
     *
     * Rule R001 : Any space inside a name will be automatically converted to DIRECTORY_SEPARATOR
     *
     * For strings : $path = 'www user view login.php';
     *  - become "www/user/view/login.php"  if DIRECTORY_SEPARATOR = '/'
     *  - become "www\user\view\login.php"  if DIRECTORY_SEPARATOR = '\'
     *
     * File inclusion remove the inline code
     *
     * @param string $path
     */
    public function setFile(string $path): void
    {
        $this->file = $path;
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
    public function setCode(string $code): void
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
     * Create a new instance of PhpEcho from a file path and link them each other
     * You must never use a space in any part of the real file path: space is read as DIRECTORY_SEPARATOR
     *
     * If a template dir root is defined then the path is automatically prepend with
     *
     * @param string $var_name the var used in the current template targeting the child block
     * @param string $path path from template dir root
     * @param array|null $vars if null then the parent transfers its internal vars to the child
     * @param string $id
     * @return self
     */
    public function addBlock(string $var_name, string $path, ?array $vars = null, string $id = ''): self
    {
        $block = new PhpEcho($path, $vars ?? $this->vars, $id);
        $block->parent = $this;
        $this->has_children = true;
        $this->vars[$var_name] = $block;

        return $block;
    }

    /**
     * @param string $var_name
     * @param string $path path from template dir root
     * @param array|null $vars
     * @param string $id
     * @return self
     * @throws InvalidArgumentException
     */
    public function renderByDefault(string $var_name, string $path, ?array $vars = null, string $id = ''): self
    {
        if (isset($this->vars[$var_name])) {
            if ($this->vars[$var_name] instanceof self) {
                return $this->vars[$var_name];
            } else {
                throw new InvalidArgumentException('a.partial.view.must.be.a.PhpEcho.block');
            }
        } else {
            return $this->addBlock($var_name, $path, $vars, $id);
        }
    }

    /**
     * @return bool
     */
    public function hasParent(): bool
    {
        return isset($this->parent);
    }

    /**
     * If 1 arg => plain html code (string or array of html code)
     * If more or equal 2 args => first=helper + the rest=helper's params
     * @param mixed ...$args
     */
    public function addHead(mixed ...$args): void
    {
        // the head is only stored in the root of the tree
        $root = $this->root();
        $nb = count($args);
        if ($nb === 1) {
            if (is_string($args[0])) {
                $root->head[] = $args[0];
            } elseif (is_array($args[0])) {
                array_push($root->head, ...$args[0]);
            }
        } elseif ($nb >= 2) {
            // the first param should be a helper
            $helper = array_shift($args);
            if (self::isHelper($helper, true)) {
                $root->head[] = $this($helper, ...$args);
            }
        }
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        // generate a token that will be replaced after rendering the whole HTML
        $this->head_token = self::getToken(26);

        return $this->head_token;
    }

    /**
     * @throws BadMethodCallException
     */
    public function render(): void
    {
        if ($this->code === '') {
            if ($this->file !== '') {
                $path = self::getFullFilepath($this->file);
                if (is_file($path)) {
                    ob_start();
                    include $path;
                    $this->code = ob_get_clean();
                } else {
                    throw new BadMethodCallException("unknown.view.file.{$this->file}");
                }
            } else {
                throw new BadMethodCallException('no.view.to.render');
            }
        }
    }
}
