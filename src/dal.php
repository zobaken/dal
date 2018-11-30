<?php

define('DAL_PATH', realpath(__DIR__ . '/../'));

/**
 * Return time in database format
 * @param mixed $time Integer time or string date of nothing (for current time)
 * @return string
 */
function dbtime($time = false) {
    if (is_string($time)) {
        $time = strtotime($time);
    }
    return $time ? date('Y-m-d H:i:s', $time) : date('Y-m-d H:i:s');
}

/**
 * Return date in database format
 * @param mixed $time Integer time or string date of nothing (for current time)
 * @return string
 */
function dbdate($time = false) {
    if (is_string($time)) {
        $time = strtotime($time);
    }
    return $time ? date('Y-m-d', $time) : date('Y-m-d');
}

/**
 * Get database query.
 *
 * A shortcut for @see \Dal\Dal::getQuery method.
 *
 * @param string $profile
 * @return \Dal\Query\Basic
 * @throws \Dal\Exception
 */
function db($profile = 'default') {
    return \Dal\Dal::getQuery($profile);
}

/**
 * Generate random base32 string
 * @param int $len Length (optional)
 * @return string
 */
function uid($len = 24) {
    $res = '';
    while(strlen($res) < $len) {
        $res .= base_convert(mt_rand(), 10, 32);
    }
    return substr($res, 0, $len);
}

/**
 * Generate random integer
 * @return int
 */
function uint() {
    return mt_rand() << 16 | time();
}

