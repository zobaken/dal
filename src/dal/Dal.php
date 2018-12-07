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
     * @var string Default database profile
     */
    protected static $defaultProfile = 'default';

    /**
     * Set database configuration
     *
     * $configuration object should contain property
     * with same name as profile object used in @see getQuery method.
     *
     * Each profile should contain 'host', 'user', 'password', 'dbname' and 'driver' properties.
     * 'driver' property can be 'mysql' or 'pgsql'.
     *
     * @param object|array $configuration
     */
    public static function setConfiguration($configuration) {
        if (is_array($configuration)) {
            $configuration = json_decode(json_encode($configuration));
        }
        if (isset($configuration->host)) {
            $configuration = (object)[
                'default' => $configuration
            ];
        }
        static::$configuration = $configuration;
    }

    /**
     * Returns current configuration object
     * @return object
     */
    public static function getConfiguration() {
        return static::$configuration;
    }

    /**
     * Load database configuration from file. In case of PHP config
     * return statement should be used.
     * @param string $filename Can be php file (should return object or array) or JSON
     * @throws Exception
     */
    public static function loadConfiguration($filename) {
        if (pathinfo($filename, PATHINFO_EXTENSION ) == 'json') {
            // Reading json config
            $config = json_decode(file_get_contents($filename));
        } elseif (pathinfo($filename, PATHINFO_EXTENSION ) == 'php') {
            // Reading php config
            $config = require $filename;
        } elseif (pathinfo($filename, PATHINFO_EXTENSION ) == 'ini') {
            $config = parse_ini_file($filename);
        }
        if (empty($config)) {
            throw new \Dal\Exception('Error loading config file');
        }
        static::setConfiguration($config);
    }

    /**
     * Set default profile for using with getQuery method
     * @param $profile Profile name
     */
    public static function setDefaultProfile($profile) {
        static::$defaultProfile = $profile;
    }

    /**
     * Get database query.
     *
     * Configuration profile should be set before calling this method. @see setConfiguration.
     *
     * @param string|null $profile Profile to use
     * @return \Dal\Query\Basic A new query object
     * @throws \Dal\Exception
     */
    public static function getQuery($profile = null) {
        if (!$profile) {
            $profile = static::$defaultProfile;
        }
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
        static::$defaultProfile = 'default';
    }

}