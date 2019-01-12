<?php

define('DAL_PATH', realpath(__DIR__ . '/../'));

/**
 * Get database query.
 *
 * A shortcut for @see \Dal\Dal::getQuery method.
 *
 * @param string|null $profile
 * @return \Dal\Query\Basic
 * @throws \Dal\Exception
 */
function db($profile = null) {
    return \Dal\Dal::getQuery($profile);
}
