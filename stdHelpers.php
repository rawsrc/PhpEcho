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
 * Works for scalar and array type and transform any object having __toString() function implemented to a escaped string
 * Otherwise, keep the object as it
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
        if (is_array($v)) {
            $data[$sk] = $hsc_array($v);
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
 * @param  $p
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


// return the array of helpers to PhpEcho
return $helpers;