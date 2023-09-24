<?php declare(strict_types=1);

namespace rawsrc\PhpEcho;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use InvalidArgumentException;

use function array_intersect;
use function array_key_exists;
use function array_push;
use function array_reduce;
use function array_shift;
use function array_walk_recursive;
use function bin2hex;
use function chr;
use function count;
use function implode;
use function in_array;
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

use const COUNT_RECURSIVE;
use const DIRECTORY_SEPARATOR;

/**
 * PhpEcho : Native PHP Template engine: One class to rule them all ;-)
 *
 * @author      rawsrc
 * @copyright   MIT License
 *
 *              Copyright (c) 2020-2023+ rawsrc
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
 * @method mixed raw(string $key) // Return the raw value from a PhpEcho block
 * @method bool isScalar(mixed $p)
 * @method mixed keyUp(array|string $keys, bool $strict_match) // Climb the tree of PhpEcho instances while keys match
 * @method mixed rootVar(array|string $keys) // Extract the value from the top level PhpEcho block (the root)
 * @method PhpEcho root() // Return the root PhpEcho instance of the tree
 * @method mixed seekParam(string $name) // Seek the parameter from the current block to the root
 * @method mixed renderIfNotSet(string $key, mixed $default_value) // If the key is not defined then render the default value
 *
 * HTML HELPERS
 * @method mixed hsc($p) // Escape the value in parameter (scalar, array, stringable)
 * @method string attributes(array $p, bool $escape_url = true) // Return the values as escaped attributes: attribute="..."
 * @method string selected($p, $ref) // Return " selected " if $p == $ref
 * @method string checked($p, $ref) // Return " checked "  if $p == $ref
 * @method string voidTag(string $tag, array $attributes = [], bool $escape_url = true) // Build a <tag />
 * @method string tag(string $tag, string $content, array $attr = [], bool $escape_url = true) // Build a <tag></tag>
 * @method string link(array $attributes, bool $escape_url = true) // [rel => required, attribute => value]
 * @method string style(array $attributes, bool $escape_url = true) // [href => url | code => plain css definition, attribute => value]
 * @method string script(array $attributes, bool $escape_url = true) // [src => url | code => plain javascript, attribute => value]
 */
class PhpEcho
implements ArrayAccess
{
    private string $id = '';
    private array $vars = [];
    private array $params = [];
    /** @var array<PhpEcho> */
    private array $children = [];
    private array $head = [];
    private string $head_token = '';
    /**
     * Partial file path to the external view file (from the template dir root)
     */
    private string $file = '';
    private string $code = '';
    /**
     * @var array<string, Closure> [helper's id => bound closure]
     */
    private array $bound_helpers = [];
    private PhpEcho $parent;
    private static string $template_dir_root = '';
    /**
     * @var array<string, Closure> [helper's name => closure]
     */
    private static array $helpers = [];
    /**
     * @var array<string, true> [helper's name => true]
     */
    private static array $bindable_helpers = [];
    /**
     * @var array<string, true> [helper's name => true]
     */
    private static array $helpers_result_escaped = [];
    /**
     * @var array<string, true> [token => true]
     */
    private static array $used_tokens = [];
    private static array $global_params = [];
    private static bool $std_helpers_injected = false;
    private static bool $opt_return_null_if_not_exist = false;
    private static string $opt_seek_value_mode = 'parents'; // parents | root

    //region MAGIC METHODS
    /**
     * Expect: the partial filepath from the template directory root
     */
    public function __construct(string $file = '', array $vars = [], string $id = '')
    {
        if (self::$std_helpers_injected === false) {
            self::injectStandardHelpers();
        }

        if ($file !== '') {
            $this->setFile($file);
        }

        if ($id === '') {
            $this->generateId();
        } else {
            $this->id = $id;
        }

        foreach ($vars as $k => $v) {
            $this->offsetSet($k, $v);
        }
    }

    /**
     * This function calls a helper defined elsewhere or dynamically
     * Auto-escape if necessary
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(string $helper, mixed ...$args): mixed
    {
        $hlp = $this->getHelper($helper);
        $result = $hlp(...$args);

        if (self::isHelperResultEscaped($helper)) {
            return $result;
        } elseif ($this('toEscape', $result)) {
            return $this('hsc', $result);
        } else {
            return $result;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->__invoke($name, ...$arguments);
    }

    /**
     * @throws BadMethodCallException
     */
    public function __toString(): string
    {
        $this->render();
        $root = $this('root');
        if (($root === $this) && ($this->head_token !== '')) {
            $head = implode('', $this->head);

            return str_replace($this->head_token, $head, $this->code);
        } else {
            return $this->code;
        }
    }

    /**
     * @throws BadMethodCallException
     */
    public function __clone(): void
    {
        throw new BadMethodCallException('cloning.a.phpecho.instance.is.not.permitted');
    }
    //endregion

    /**
     * Return the full path to a view file, prepend it with the template dir root
     */
    public static function getFullFilepath(string $path): string
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }
        if ((self::$template_dir_root !== '') && ( ! str_contains($path, self::$template_dir_root))) {
            $path = self::$template_dir_root.DIRECTORY_SEPARATOR.$path;
        }

        return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    }

    public function getFilepath(): string
    {
        return $this->file;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    //region PARAMETERS
    /**
     * Define a local parameter
     */
    public function setParam(string $name, mixed $value): void
    {
        $this->params[$name] = $value;
    }

    /**
     * Get the value of a local parameter
     * The value of the parameter is never escaped
     *
     * @throws InvalidArgumentException
     */
    public function getParam(string $name): mixed
    {
        if ($this->hasParam($name)) {
            return $this->params[$name];
        } else {
            throw new InvalidArgumentException("unknown.parameter.{$name}");
        }
    }

    public function unsetParam(string $name): void
    {
        if ($this->hasParam($name)) {
            unset ($this->params[$name]);
        } else {
            throw new InvalidArgumentException("unknown.parameter.{$name}");
        }
    }

    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    /**
     * Parameter available through all PhpEcho instances
     */
    public static function setGlobalParam(string $name, mixed $value): void
    {
        self::$global_params[$name] = $value;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getGlobalParam(string $name): mixed
    {
        if (self::hasGlobalParam($name)) {
            return self::$global_params[$name];
        } else {
            throw new InvalidArgumentException("unknown.parameter.{$name}");
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function unsetGlobalParam(string $name): void
    {
        if (self::hasGlobalParam($name)) {
            unset (self::$global_params[$name]);
        } else {
            throw new InvalidArgumentException("unknown.parameter.{$name}");
        }
    }

    public static function hasGlobalParam(string $name): bool
    {
        return array_key_exists($name, self::$global_params);
    }

    /**
     * Define or override a local and global parameter value at once
     */
    public function setAnyParam(string $name, mixed $value): void
    {
        $this->setParam($name, $value);
        self::setGlobalParam($name, $value);
    }

    /**
     * Try to find and return a parameter value from the local or global parameters array
     *
     * if $seek_order is
     * local: seek the value from the local context first, if not found, seek from the global context
     * global: seek the value from the global context first, if not found, seek from the local context
     *
     * Finally, if the parameter is not found, throw an InvalidArgumentException
     *
     * @param string $seek_order local|global
     * @throws InvalidArgumentException
     */
    public function getAnyParam(string $name, string $seek_order = 'local'): mixed
    {
        if ($seek_order === 'local') {
            if ($this->hasParam($name)) {
                return $this->params[$name];
            } elseif (self::hasGlobalParam($name)) {
                return self::$global_params[$name];
            }
        } elseif ($seek_order === 'global') {
            if (self::hasGlobalParam($name)) {
                return self::$global_params[$name];
            } elseif ($this->hasParam($name)) {
                return $this->params[$name];
            }
        } else {
            throw new InvalidArgumentException("unknown.seek.order.{$seek_order}");
        }

        throw new InvalidArgumentException("unknown.parameter.{$name}");
    }

    /**
     * Check if the parameter is defined either in the local array storage or in the global one
     */
    public function hasAnyParam(string $name): bool
    {
        return $this->hasParam($name) || self::hasGlobalParam($name);
    }

    /**
     * Unset a parameter value from the local and global context
     *
     * @throws InvalidArgumentException
     */
    public function unsetAnyParam(string $name): void
    {
        if ($this->hasAnyParam($name)) {
            unset ($this->params[$name]);
            unset (self::$global_params[$name]);
        } else {
            throw new InvalidArgumentException("unknown.parameter.{$name}");
        }
    }
    //endregion PARAMETERS

    //region OPTIONS
    /**
     * If a key is not defined then return null instead of throwing an Exception
     */
    public static function setNullIfNotExists(bool $p): void
    {
        self::$opt_return_null_if_not_exist = $p;
    }

    /**
     * 3 modes
     * @param string $mode among current|parents|root
     * @throws InvalidArgumentException
     */
    public static function setSeekValueMode(string $mode): void
    {
        if (in_array($mode, ['current', 'parents', 'root'])) {
            self::$opt_seek_value_mode = $mode;
        } else {
            throw new InvalidArgumentException("unknown.seek.mode.value.{$mode}");
        }
    }
    //endregion OPTIONS

    /**
     * Generate a unique execution id based on random_bytes()
     * Always start with a letter
     */
    public function generateId(): void
    {
        $this->id = chr(mt_rand(97, 122)).bin2hex(random_bytes(4));
    }

    /**
     * Local values
     */
    public function setVars(array $vars): void
    {
        if ($vars === []) {
            foreach ($this->children as $v) {
                $this->unsetChild($v);
            }
            $this->vars = [];
        } else {
            foreach ($vars as $k => $v) {
                $this->offsetSet($k, $v);
            }
        }
    }

    /**
     * Local values only
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Values are stored ine the root of the tree
     */
    public function injectVars(array $vars): void
    {
        $root = $this('root');
        foreach ($vars as $k => $v) {
            $root->offsetSet($k, $v);
        }
    }

    //region ARRAY ACCESS INTERFACE
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->vars);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $for_array = function(array $p) use (&$for_array): array {
            $data = [];
            foreach ($p as $k => $v) {
                if ($v instanceof PhpEcho) {
                    $this->addChild($v);
                    $data[$k] = $v;
                } elseif (is_array($v)) {
                    $data[$k] = $for_array($v);
                } else {
                    $data[$k] = $v;
                }
            }

            return $data;
        };

        if ($value instanceof self) {
            $this->addChild($value);
            $this->vars[$offset] = $value;
        } elseif (is_array($value)) {
            $this->vars[$offset] = $for_array($value);
        } else {
            $this->vars[$offset] = $value;
        }
    }

    /**
     * The returned value is escaped
     *
     * Some types are preserved : true bool, true int, true float, PhpEcho instance, object without __toString()
     * For array of PhpEcho blocks, the array is imploded and the blocks are rendered int the order they appear
     * Otherwise, the value is cast to a string and escaped
     *
     * @throws InvalidArgumentException
     */
    public function offsetGet(mixed $offset): mixed
    {
        $v = $this->getOffsetRawValue($offset);

        $get_escaped = function(mixed $p): mixed {
            if ($p instanceof PhpEcho) {
                return $p;
            } elseif ($this('toEscape', $p)) {
                return $this('hsc', $p);
            } else {
                return $p;
            }
        };

        $for_array = function(array $p) use (&$for_array, $get_escaped): array {
            $data = [];
            foreach ($p as $k => $z) {
                if (is_array($z) && ($z !== [])) {
                    $data[$get_escaped($k)] = $for_array($z);
                } else {
                    $data[$get_escaped($k)] = $get_escaped($z);
                }
            }

            return $data;
        };

        $recursive_array_of_blocks = function(array $p) use (&$recursive_array_of_blocks, $get_escaped): string {
            $data = [];
            foreach ($p as $k => $z) {
                if (is_array($z) && ($z !== [])) {
                    $data[$get_escaped($k)] = $recursive_array_of_blocks($z);
                } else {
                    $data[$get_escaped($k)] = (string)$z;
                }
            }

            $str = '';
            array_reduce($data, function($str, $v) { $str .= $v; return $str; }, '');

            return $str;
        };

        if ($v === null) {
            return null;
        } elseif (is_array($v)) {
            if ($this->isArrayOfPhpEchoBlocks($v)) {
                // simple array of PhpEcho blocks (one level)
                if (count($v) === count($v, COUNT_RECURSIVE)) {
                    return implode('', $v);
                } else {
                    // recursive array of PhpEcho blocks
                    return $recursive_array_of_blocks($v);
                }
            } else {
                return $for_array($v);
            }
        } else {
            return $get_escaped($v);
        }
    }

    /**
     * Only local value
     */
    public function offsetUnset(mixed $offset): void
    {
        if (array_key_exists($offset, $this->vars)) {
            if ($this->vars[$offset] instanceof self) {
                $this->unsetChild($offset);
            }
            unset($this->vars[$offset]);
        } else {
            throw new InvalidArgumentException("unknown.offset.{$offset}");
        }
    }
    //endregion ARRAY ACCESS

    /**
     * @throws InvalidArgumentException only if $return_null_if_not_exist is set to false
     */
    private function getOffsetRawValue(mixed $offset): mixed
    {
        if (array_key_exists($offset, $this->vars)) {
            return $this->vars[$offset];
        } elseif (self::$opt_seek_value_mode === 'parents') {
            $block = $this;
            while (isset($block->parent)) {
                if (array_key_exists($offset, $block->vars)) {
                    return $block->vars[$offset];
                }
                $block = $block->parent;
            }
            if (array_key_exists($offset, $block->vars)) {
                return $block->vars[$offset];
            }
        } elseif (self::$opt_seek_value_mode === 'root') {
            $root = $this('root');
            if ($root !== $this) {
                if (array_key_exists($offset, $root->vars)) {
                    return $root->vars[$offset];
                }
            }
        }

        return self::$opt_return_null_if_not_exist ? null : throw new InvalidArgumentException("unknown.offset.{$offset}");
    }

    /**
     * @return array<string>
     */
    private function getParentsId(): array
    {
        $data = [];
        $block = $this;
        while (isset($block->parent) && ($block->parent !== $this)) {
            $data[] = $block->parent->id;
            $block = $block->parent;
        }

        return $data;
    }

    /**
     * @return array<string>
     */
    private function getParentsFilepath(): array
    {
        $data = [];
        $block = $this;
        while (isset($block->parent) && ($block->parent !== $this)) {
            if ($block->parent->file !== '') {
                $data[] = $block->parent->file;
            }
            $block = $block->parent;
        }

        return $data;
    }

    /**
     * @return array<string>
     */
    private function getChildrenId(): array
    {
        $data = [];
        foreach ($this->children as $v) {
            $data[] = $v->id;
            array_push($data, ...$v->getChildrenId());
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addChild(PhpEcho $p): void
    {
        $this->children[] = $p;
        $p->parent = $this;
        $this->detectInfiniteLoop();
    }

    private function unsetChild(mixed $offset): void
    {
        /** @var PhpEcho $block */
        $block = $this->vars[$offset];
        unset($this->children[$offset]);
        $block->parent = $block;  // the block is now orphan
    }

    /**
     * @throws InvalidArgumentException
     */
    private function detectInfiniteLoop(): void
    {
        if ($this->file !== '') {
            if (in_array($this->file, $this->getParentsFilepath(), true)) {
                throw new InvalidArgumentException('infinite.loop.detected');
            }
        }

        // the current block and its childs must not refer one of their parents id
        $ids = [$this->id, ...$this->getChildrenId()];
        if (array_intersect($ids, $this->getParentsId()) !== []) {
            throw new InvalidArgumentException('infinite.loop.detected');
        }
    }

    //region HELPER ZONE
    public static function getToken(int $length = 16): string
    {
        $length = max(16, $length);
        do {
            $token = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'.mt_rand(100000000, 999999999)), 0, $length);
        } while (isset(self::$used_tokens[$token]));

        self::$used_tokens[$token] = true;

        return $token;
    }

    public static function injectStandardHelpers(): void
    {
        self::injectHelpers(__DIR__.DIRECTORY_SEPARATOR.'stdPhpEchoHelpers.php');
        self::$std_helpers_injected = true;
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

    public static function addHelper(string $name, Closure $helper, bool $result_escaped = false): void
    {
        self::$helpers[$name] = $helper;
        if ($result_escaped) {
            self::$helpers_result_escaped[$name] = true;
        }
    }

    public static function addBindableHelper(string $name, Closure $helper, bool $result_escaped = false): void
    {
        self::$helpers[$name] = $helper;
        self::$bindable_helpers[$name] = true;
        if ($result_escaped) {
            self::$helpers_result_escaped[$name] = true;
        }
    }

    /**
     * Return the helper closure (already bound to the current instance if bindable) otherwise the base closure
     * @throws InvalidArgumentException
     */
    public function getHelper(string $name): Closure
    {
        $helper = self::getHelperDetails($name);
        // return the bound version (if available)
        if ($helper['bindable']) {
            if ($this->bound_helpers === []) {
                $this->bindHelpersTo($this);
            }
            $helpers = $this->bound_helpers + self::$helpers;

            return $helpers[$name];
        } else {
            return $helper['closure'];
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getHelperBase(string $name): Closure
    {
        return self::getHelperDetails($name)['closure'];
    }

    /**
     * @return array{ name:string, closure:Closure, bindable:bool, escaped:bool }
     * @throws InvalidArgumentException
     */
    protected static function getHelperDetails(string $name): array
    {
        if ($name === '') {
            throw new InvalidArgumentException('helper.cannot.be.empty');
        } elseif (self::isHelper($name)) {
            return [
                'name' => $name,
                'closure' => self::$helpers[$name],
                'bindable' => isset(self::$bindable_helpers[$name]),
                'escaped' => isset(self::$helpers_result_escaped[$name]),
            ];
        } else {
            throw new InvalidArgumentException("unknown.helper.{$name}");
        }
    }

    /**
     * @return array<string, Closure> [name => closure]
     */
    public static function getHelpers(): array
    {
        return self::$helpers;
    }

    public static function isHelper(string $name): bool
    {
        return isset(self::$helpers[$name]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function isHelperResultEscaped(string $name): bool
    {
        return self::getHelperDetails($name)['escaped'];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function isHelperBindable(string $name): bool
    {
        return self::getHelperDetails($name)['bindable'];
    }

    /**
     * Change the helper's binding context to the given one in parameter
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

    private function isArrayOfPhpEchoBlocks(mixed $p): bool
    {
        if (is_array($p) && ($p !== [])) {
            $status = true;
            array_walk_recursive($p, function($v) use (&$status) {
                if ($status) {
                    if ( ! $v instanceof PhpEcho) {
                        $status = false;
                    }
                }
            });

            return $status;
        } else {
            return false;
        }
    }

    /**
     * The full path to the root directory of the view files
     */
    public static function setTemplateDirRoot(string $full_path): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            self::$template_dir_root = str_replace('/', DIRECTORY_SEPARATOR, $full_path);
        } else {
            self::$template_dir_root = $full_path;
        }
    }

    /**
     * Define the filepath to the external view file to include from the template dir root
     * The full resolved filepath is built using the template directory root
     *
     * Watch carefully the directory separators as they will be replaced by the system constant: DIRECTORY_SEPARATOR
     *
     * Including space in the directory separators: $path = 'www user view login.php';
     *  - become "www/user/view/login.php"  if DIRECTORY_SEPARATOR = '/'
     *  - become "www\user\view\login.php"  if DIRECTORY_SEPARATOR = '\'
     *
     * File inclusion remove the inline code
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
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
        $this->file = '';
    }

    /**
     * Indicates if the current instance contains in its vars other PhpEcho instance(s)
     */
    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /**
     * Create a new instance of PhpEcho from a file path and link them each other
     * You must never use a space in any part of the real file path: space is read as DIRECTORY_SEPARATOR
     *
     * If a template dir root is defined then the path is automatically prepend with
     */
    public function addBlock(string $var_name, string $path, ?array $vars = null, string $id = ''): self
    {
        $block = new PhpEcho($path, $vars ?? [], $id);
        $this->offsetSet($var_name, $block);

        return $block;
    }

    /**
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
     * Same as addBlock() but without having a $var_name to define
     * The block is not accessible using a variable
     */
    public function renderBlock(string $path, ?array $vars = null, string $id = ''): self
    {
        return $this->addBlock(self::getToken(), $path, $vars, $id);
    }

    public function hasParent(): bool
    {
        return isset($this->parent);
    }

    /**
     * If 1 arg => plain html code (string or array of html code)
     * If more or equal 2 args => first=helper + the rest=helper's params
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
            if (self::isHelper($helper)) {
                $root->head[] = $this($helper, ...$args);
            }
        }
    }

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
                    throw new BadMethodCallException("unknown.view.file.'{$this->file}'");
                }
            } else {
                throw new BadMethodCallException('no.view.to.render');
            }
        }
    }
}
