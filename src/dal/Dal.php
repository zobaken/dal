<?php

namespace Dal;

/**
 * Class Dal
 *
 * Dal entry point
 */
class Dal
{

    /**
     * @var object Configuration @see setConfiguration
     */
    protected static $configuration;

    /**
     * @var array Query pool
     */
    protected static $queryPool = [];

    /**
     * Set database configuration
     *
     * $configuration object should contain property
     * with same name as profile object used in @see getQuery method.
     *
     * Each profile should contain 'host', 'user', 'password', 'dbname' and 'driver' properties.
     * 'driver' property currently should be 'mysql'.
     *
     * @param object $configuration
     */
    public static function setConfiguration($configuration) {
        static::$configuration = $configuration;
    }

    /**
     * Get database query.
     *
     * Configuration profile should be set before calling this method. @see setConfiguration.
     *
     * @param string $profile Profile to use
     * @return \Dal\Query\Basic A new query object
     * @throws \Dal\Exception
     */
    public static function getQuery($profile = 'default') {
        if (isset(static::$queryPool[$profile])) {
            return static::$queryPool[$profile] = (static::$queryPool[$profile])();
        } else {
            if (empty(static::$configuration->$profile)) {
                throw new Exception('Configuration profile not found');
            }
            $config = static::$configuration->$profile;
            $className = '\\Dal\\Query\\' . ucfirst($config->driver);
            return static::$queryPool[$profile] = new $className($config);
        }
    }

    /**
     * Reset all configuration and queries.
     * This will not close active connections.
     */
    public static function reset() {
        static::$queryPool = [];
        static::$configuration = null;
    }

}