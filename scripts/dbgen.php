<?php
/**
 * Generate database models
 * Usage:
 * dbgen <config> <target directory> [profile]
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../src/dal.php';
}

function usage() {
    echo "Usage: dbgen <config> <target directory> [profile]\n";
    exit(1);
}

$configFile = isset($argv[1]) ? $argv[1] : null;
$targetDirectory = isset($argv[2]) ? $argv[2]: null;
$profile = isset($argv[3]) ? $argv[3] : 'default';
$namespace = isset($argv[4]) ? $argv[4] : null;

if (empty($configFile) || empty($targetDirectory)) {
    usage();
}

if (!file_exists($configFile)) {
    echo "Config file not found\n";
    exit(1);
}

if (!is_dir($targetDirectory)) {
//    echo "Target directory not found";
//    exit(1);
}

if (pathinfo($configFile, PATHINFO_EXTENSION ) == 'json') {
    // Reading json config
    $config = json_decode(file_get_contents($configFile));
} elseif (pathinfo($configFile, PATHINFO_EXTENSION ) == 'php') {
    // Reading php config
    $config = require $configFile;
} elseif (pathinfo($configFile, PATHINFO_EXTENSION ) == 'ini') {
    $config = parse_ini_file($configFile);
}

if (empty($config)) {
    echo "Error reading config\n";
    exit(1);
}

if (is_array($config)) {
    $config = json_decode(json_encode($config));
}

// Fix no profiles
if (isset($config->host)) {
    $config = (object)[
        'default' => $config
    ];
}

if (empty($config->$profile)) {
    echo "Profile '$profile' not found\n";
    exit(1);
}

$generator = \Dal\Model\GeneratorFactory::createGenerator($config, $targetDirectory, $profile);
$generator->run();