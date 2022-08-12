<?php

declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$helpers = [];

//region STANDALONE HELPERS
//region is_scalar
/**
 * This is a standalone helper
 *
 * @param mixed $p
 * @return bool
 */
$is_scalar = function(mixed $p): bool {
    return is_scalar($p) || (is_object($p) && method_exists($p, '__toString'));
};
$helpers['isScalar'] = [$is_scalar, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region to_escape
/**
 * Check if the value in parameter should be escaped or not
 *
 * @param mixed $p
 * @return bool
 */
$to_escape = function(mixed $p) use ($is_scalar): bool  {
    if (is_string($p)) {
        return true;
    } elseif (is_bool($p) || is_int($p) || is_float($p) || ($p instanceof PhpEcho)) {
        return false;
    } elseif ($is_scalar($p)) {
        return true;
    } elseif (is_object($p)) {
        return false;
    } else {
        return true;
    }
};
$helpers['toEscape'] = [$to_escape, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region hsc_array
/**
 * Return an array of escaped values with htmlspecialchars(ENT_QUOTES, 'utf-8') for both keys and values
 *
 * Preserved types : true bool, true int, true float, PhpEcho instance, object without __toString()
 * Otherwise, the value is cast to a string and escaped
 *
 * This is a standalone helper that is not directly accessible
 * Use instead the common helper 'hsc' which is compatible with arrays
 *
 * @param array $part
 * @return array
 */
$hsc_array = function(array $part) use (&$hsc_array, $to_escape): array {
    $data = [];
    foreach ($part as $k => $v) {
        $sk = htmlspecialchars((string)$k, ENT_QUOTES, 'utf-8');
        if ($to_escape($v)) {
            if (is_array($v)) {
                $data[$sk] = $hsc_array($v);
            } else {
                $data[$sk] = htmlspecialchars((string)$v, ENT_QUOTES, 'utf-8');
            }
        } else {
            $data[$sk] = $v;
        }
    }

    return $data;
};
//endregion

//region hsc
/**
 * This is a standalone helper
 * Alias for htmlspecialchars(), better version
 *
 * When $p is an array, some types are preserved:
 * - true bool, true int, true float, PhpEcho instance, object without __toString()
 *
 * Otherwise, the value is cast to string and escaped
 *
 * @param mixed $p
 * @return array|string
 */
$hsc = function(mixed $p) use ($hsc_array, $is_scalar): array|string {
    if ($is_scalar($p)) {
        return htmlspecialchars((string)$p, ENT_QUOTES, 'utf-8');
    } elseif (is_array($p)) {
        return $hsc_array($p);
    } else {
        return '';
    }
};
$helpers['hsc'] = [$hsc, HELPER_RETURN_ESCAPED_DATA];
//endregion
//endregion

//region PHPECHO INSTANCE HELPERS
//region raw
/**
 * Return the raw value from the key in parameter
 * CAREFUL : THIS VALUE IS NOT ESCAPED
 *
 * This helper is linked to an instance of PhpEcho
 *
 * Support the key space notation for array and sub-arrays
 * if $key = 'abc def' then the engine will search for
 * the value as $vars['abc']['def']
 *
 * @param string $key
 * @return mixed
 */
$raw = function(string $key): mixed {
    /** @var PhpEcho $this */
    return $this->getOffsetRawValue($key);
};
$helpers['raw'] = [$raw, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region key_up
/**
 * This helper will climb the tree of PhpEcho instances from the current block while the key match
 *
 * A string will be split in parts using the space for delimiter
 * If one of the keys contains a space, use an array of keys instead
 *
 * If $strict_match === true then every key must be found in the parent blocks tree as they are listed
 * If $strict_match === false then if the current key is not found in the parent block, then the search will continue
 * until reaching the root or stop at the first match
 *
 * @param string|array $keys
 * @param bool $strict_match
 * @return mixed null if not found
 */
$key_up = function(array|string $keys, bool $strict_match = true) use ($to_escape, $hsc) {
    /** @var PhpEcho $this */
    if ( ! $this->hasParent()) {
        return null;
    }
    $keys = is_string($keys) ? explode(' ', $keys) : $keys;
    /** @var PhpEcho $block */
    $block = $this->parent;
    $nb = count($keys);
    $i = 0;
    while ($i < $nb) {
        $k = $keys[$i];
        if (isset($block[$k])) {
            if ($i + 1 >= $nb) {
                if ($to_escape($block[$k])) {
                    return $hsc($block[$k]);
                } else {
                    return $block[$k];
                }
            } else {
                ++$i;
            }
        } elseif ($strict_match) {
            return null;
        }
        if ($block->hasParent()) {
            $block = $block->parent;
        } else {
            return null;
        }
    }
};
$helpers['keyUp'] = [$key_up, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region root
/**
 * @return PhpEcho
 */
$root = function(): PhpEcho {
    // climbing to the root
    /** @var PhpEcho $block */
    $block = $this;
    while ($block->hasParent()) {
        $block = $block->parent;
    }

    return $block;
};
$helpers['root'] = [$root, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region root_var
/**
 * This helper will extract a value from a key stored in the root of the tree of PhpEcho instances
 * and go down while the key match. This function does not render the PhpEcho blocks
 *
 * A string will be split in parts using the space for delimiter
 * If one of the keys contains a space, use an array of keys instead
 *
 * @param array|string $keys
 * @return mixed null if not found
 */
$root_var = function(array|string $keys) use ($to_escape, $hsc): mixed {
    /** @var PhpEcho $this */
    $root = $this->bound_helpers['root'];
    /** @var PhpEcho $block */
    $block = $root();   // get the root PhpEcho block
    $keys = is_string($keys) ? explode(' ', $keys) : $keys;
    $nb = count($keys);
    $i = 0;
    while ($i < $nb) {
        $k = $keys[$i];
        if (isset($block[$k])) {
            if ($i + 1 >= $nb) {
                if ($to_escape($block[$k])) {
                    return $hsc($block[$k]);
                } else {
                    return $block[$k];
                }
            } else {
                $block = $block[$k];
                ++$i;
            }
        } else {
            return null;
        }
    }
};
$helpers['rootVar'] = [$root_var, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region seek_param
/**
 * Seek the parameter from the current block to the root
 *
 * @param string $name
 * @return null
 */
$seek_param = function(string $name) {
    /** @var PhpEcho $block */
    $block = $this;
    while (true) {
        if ($block->hasParam($name)) {
            return $block->params[$name];
        } elseif ($block->hasParent()) {
            $block = $block->parent;
        } else {
            return null;
        }
    }
};
$helpers['seekParam'] = [$seek_param, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];
//endregion
//endregion

//region HTML HELPERS
//region selected
/**
 * Return the html attribute "selected" if $p == $ref
 * This is a standalone helper
 *
 * @param mixed $p scalar value to check
 * @param mixed $ref scalar value ref
 * @return string
 */
$selected = function(mixed $p, mixed $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' selected ' : '';
};
$helpers['selected'] = [$selected, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region checked
/**
 * Return the html attribute "checked" if $p == $ref
 * This is a standalone helper
 *
 * @param mixed $p scalar value to check
 * @param mixed $ref scalar value ref
 * @return string
 */
$checked = function(mixed $p, mixed $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' checked ' : '';
};
$helpers['checked'] = [$checked, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region attribute
/**
 * Format and secure a list of tag attributes
 *
 * if attribute_name is a true integer then the value is rendered as it
 *  - 1 => "async"    => will render async
 *  - 2 => "selected" => will render selected
 *
 * @param array $p [attribute_name => value]
 * @return string
 */
$attributes = function(array $p): string {
    $data = [];
    foreach ($p as $attr => $value) {
        if (is_int($attr)) {
            $data[] = $value;
        } elseif ($value !== '') {
            $str = null;
            // consider that href or src are already escaped
            if (in_array($attr, ['href', 'src'], true)) {
                $str = $value;
                // intercept js for DOMEvent : starting with onXXX
            } elseif (mb_substr($attr, 0, 2, 'utf-8') === 'on') {
                $str = str_replace('"', '&quot;', $value);
            } elseif (ctype_alpha($attr) || (mb_substr($attr, 0, 5) === 'data-')) {
                $str = htmlspecialchars($value, ENT_QUOTES, 'utf-8');
            }
            if ($str !== null) {
                $data[] = $attr.'="'.$str.'"';
            }
        }
    }

    return implode(' ', $data);
};
$helpers['attributes'] = [$attributes, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region void_tag
/**
 * Return the HTML code for a void tag: <tag>
 * Attributes are escaped
 *
 * @param string $tag
 * @param array $attr
 * @return string
 */
$void_tag = function(string $tag, array $attr = []) use ($attributes): string {
    $str = $attributes($attr);
    if ($str !== '') {
        $str = ' '.$str;
    }

    return "<{$tag}{$str}>";
};
$helpers['voidTag'] = [$void_tag, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region tag
/**
 * Return the HTML code for a tag: <tag>content</tag>
 * Attributes and content are escaped
 *
 * To avoid double escaping on content : set $attr['escaped'] = true
 *
 * @param string $tag
 * @param string $content
 * @param array $attr
 * @return string
 */
$tag = function(string $tag, string $content, array $attr = []) use ($void_tag, $hsc) {
    if (( ! isset($attr['escaped'])) || ($attr['escaped'] !== true)) {
        $content = $hsc($content);
    }
    unset($attr['escaped']);

    return $void_tag($tag, $attr).$content."</{$tag}>";
};
$helpers['tag'] = [$tag, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region link
/**
 * HTML TAG : <link>
 *
 * @param array $p  [rel => value, attribute => value] as many pair (attribute => value) as necessary
 * @return string
 *
 * @link https://www.w3schools.com/tags/tag_link.asp
 */
$link = function(array $p) use ($void_tag): string {
    if (empty($p['rel'])) {   // rel is required
        return '';
    } else {
        return $void_tag('link', $p);
    }
};
$helpers['link'] = [$link, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region style
/**
 * HTML TAG : <style></style>
 *
 * The url if defined goes over the plain code
 * @param array $p  [href => url | code => plain css definition, attribute => value] as many pair (attribute => value) as necessary
 * @return string
 *
 * @link https://www.w3schools.com/tags/tag_style.asp
 */
$style = function(array $p) use ($tag, $link): string {
    if (empty($p['href']) && empty($p['code'])) {
        return '';
    }

    $attr = ['type' => 'text/css'];

    if (isset($p['href'])) {
        $attr += ['rel' => 'stylesheet', 'href' => $p['href']];
        unset($p['rel'], $p['href']);

        return $link($attr + $p);
    }

    $code = $p['code'];
    unset($p['code'], $p['rel'], $p['href']);
    $p['escaped'] = true;

    return $tag('style', $code, $attr + $p);
};
$helpers['style'] = [$style, HELPER_RETURN_ESCAPED_DATA];
//endregion

//region script
/**
 * HTML TAG : <script></script>
 *
 * The url if defined goes over the plain code
 * @param array $p  [src => url | code => plain javascript, attribute => value] as many pair (attribute => value) as necessary
 * @return string
 *
 * @link https://www.w3schools.com/tags/tag_script.asp
 */
$script = function(array $p) use ($tag): string {
    if (empty($p['src']) && empty($p['code'])) {
        return '';
    }
    if (isset($p['src'])) {
        $code = '';
    } else {
        $code = $p['code'];
        unset($p['code'], $p['src']);
    }
    $p['escaped'] = true;

    return $tag('script', $code, $p);
};
$helpers['script'] = [$script, HELPER_RETURN_ESCAPED_DATA];
//endregion
//endregion

// return the array of helpers to PhpEcho
return $helpers;