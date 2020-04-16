<?php

declare(strict_types=1);

$helpers = [];

/**
 * Return the raw value from the key in parameter
 * CAREFUL : THIS VALUE IS NOT ESCAPED
 *
 * This helper is linked to an instance of PhpEcho
 *
 * @param string $key
 * @return mixed|null
 */
$raw = function(string $key) {
    return $this->vars[$key] ?? null;
};
$helpers['$raw'] = [$raw, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];


/**
 * This is a standalone helper
 *
 * @param $p
 * @return bool
 */
$is_scalar = function($p): bool {
    return is_scalar($p) || (is_object($p) && method_exists($p, '__toString'));
};
$helpers['$is_scalar'] = [$is_scalar, HELPER_RETURN_ESCAPED_DATA];
$helpers['isScalar']   = $helpers['$is_scalar']; // alias for method call


/**
 * Return an array of escaped values with htmlspecialchars(ENT_QUOTES, 'utf-8') for both keys and values
 *
 * Preserved types : true bool, true int, true float, PhpEcho instance, object without __toString()
 * Otherwise, the value is cast to a string and escaped
 *
 * This is a standalone helper that is not directly accessible
 * Use instead the common helper '$hsc' which is compatible with arrays
 *
 * @param  array $part
 * @return array
 */
$hsc_array = function(array $part) use (&$hsc_array, $is_scalar): array {
    $data = [];
    foreach ($part as $k => $v) {
        $sk = htmlspecialchars((string)$k, ENT_QUOTES, 'utf-8');
        if (is_string($v)) {
            $data[$sk] = htmlspecialchars((string)$v, ENT_QUOTES, 'utf-8');
        } elseif (is_array($v)) {
            $data[$sk] = $hsc_array($v);
        } elseif (is_bool($v) || is_int($v) || is_float($v) || ($v instanceof PhpEcho)) {
            $data[$sk] = $v;
        } elseif ($is_scalar($v)) {
            $data[$sk] = htmlspecialchars((string)$v, ENT_QUOTES, 'utf-8');
        } else {
            $data[$sk] = $v;
        }
    }
    return $data;
};


/**
 * This is a standalone helper
 *
 * When $p is an array, some types are preserved:
 * - true bool, true int, true float, PhpEcho instance, object without __toString()
 * Otherwise, the value is cast to a string and escaped
 *
 * @param  mixed $p
 * @return mixed
 */
$hsc = function($p) use ($hsc_array, $is_scalar) {
    if ($is_scalar($p)) {
        return htmlspecialchars((string)$p, ENT_QUOTES, 'utf-8');
    } elseif (is_array($p)) {
        return $hsc_array($p);
    } else {
        return '';
    }
};
$helpers['$hsc'] = [$hsc, HELPER_RETURN_ESCAPED_DATA];


/**
 * Return the html attribute "selected" if $p == $ref
 * This is a standalone helper
 *
 * @param $p        value to check
 * @param $ref      selected value ref
 * @return string
 */
$selected = function($p, $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' selected ' : '';
};
$helpers['$selected'] = [$selected, HELPER_RETURN_ESCAPED_DATA];


/**
 * Return the html attribute "checked" if $p == $ref
 * This is a standalone helper
 *
 * @param $p        value to check
 * @param $ref      checked value ref
 * @return string
 */
$checked = function($p, $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' checked ' : '';
};
$helpers['$checked'] = [$checked, HELPER_RETURN_ESCAPED_DATA];


/**
 * Format and secure a list of tag attributes
 *
 * if attribute_name is a true integer then the value is rendered as it
 *  - 1 => "async"    => will render async
 *  - 2 => "selected" => will render selected
 *
 * @param  array $p [attribute_name => value]
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
$helpers['$attributes'] = [$attributes, HELPER_RETURN_ESCAPED_DATA];


/**
 * Return the HTML code for a void tag: <tag>
 * Attributes are escaped
 *
 * @param string $tag
 * @param array  $attr
 * @return string
 */
$void_tag = function(string $tag, array $attr = []) use ($attributes): string {
    $str = $attributes($attr);
    if ($str !== '') {
        $str = ' '.$str;
    }
    return "<{$tag}{$str}>";
};
$helpers['$void_tag'] = [$void_tag, HELPER_RETURN_ESCAPED_DATA];
$helpers['voidTag']   = $helpers['$void_tag'];  // alias for method call


/**
 * Return the HTML code for a tag: <tag>content</tag>
 * Attributes and content are escaped
 *
 * To avoid double escaping on content : set $attr['escaped'] = true
 *
 * @param string $tag
 * @param string $content
 * @param array  $attr
 * @return string
 */
$tag = function(string $tag, string $content, array $attr = []) use ($void_tag, $hsc) {
    if (( ! isset($attr['escaped'])) || ($attr['escaped'] !== true)) {
        $content = $hsc($content);
    }
    unset($attr['escaped']);
    return $void_tag($tag, $attr).$content."</{$tag}>";
};
$helpers['$tag'] = [$tag, HELPER_RETURN_ESCAPED_DATA];


/**
 * HTML TAG : <link>
 *
 * @param  array $p  [rel => value, attribute => value] as many pair (attribute => value) as necessary
 * @return string
 *
 * @link https://www.w3schools.com/tags/tag_link.asp
 */
$link = function(array $p) use ($void_tag): string {
    if (empty($p['rel'])) {   // rel is required
        return '';
    }
    return $void_tag('link', $p);
};
$helpers['$link'] = [$link, HELPER_RETURN_ESCAPED_DATA];


/**
 * HTML TAG : <style></style>
 *
 * The url if defined goes over the plain code
 * @param  array $p  [href => url | code => plain css definition, attribute => value] as many pair (attribute => value) as necessary
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
        unset ($p['rel'], $p['href']);
        return $link($attr + $p);
    }

    $code = $p['code'];
    unset ($p['code'], $p['rel'], $p['href']);
    $p['escaped'] = true;
    return $tag('style', $code, $attr + $p);
};
$helpers['$style'] = [$style, HELPER_RETURN_ESCAPED_DATA];

/**
 * HTML TAG : <script></script>
 *
 * The url if defined goes over the plain code
 * @param  array $p  [src => url | code => plain javascript, attribute => value] as many pair (attribute => value) as necessary
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
        unset ($p['code'], $p['src']);
    }
    $p['escaped'] = true;
    return $tag('script', $code, $p);
};
$helpers['$script'] = [$style, HELPER_RETURN_ESCAPED_DATA];


/**
 * This helper will climb the tree of PhpEcho
 *
 * @param $key
 */
$seek_asc = function($key) {

};
$helpers['$seek_asc'] = [$seek_asc, HELPER_BOUND_TO_CLASS_INSTANCE, HELPER_RETURN_ESCAPED_DATA];
$helpers['seekAsc']   = $helpers['$seek_asc'];
// return the array of helpers to PhpEcho
return $helpers;