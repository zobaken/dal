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
    echo "Usage: dbgen <config file> <target directory> [profile]\n";
    exit(1);
}

$configFile = isset($argv[1]) ? $argv[1] : null;
$targetDirectory = isset($argv[2]) ? $argv[2]: null;
$profile = isset($argv[3]) ? $argv[3] : 'default';

if (empty($configFile) || empty($targetDirectory)) {
    usage();
}

if (!file_exists($configFile)) {
    echo "Config file not found\n";
    exit(1);
}

try {
    \Dal\Dal::loadConfiguration($configFile);
    $generator = \Dal\Model\GeneratorFactory::createGenerator($targetDirectory, $profile);
    $generator->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}