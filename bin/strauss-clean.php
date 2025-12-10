#!/usr/bin/env php
<?php
/**
 * Clean up problematic directories before running Strauss
 */

$dirs_to_remove = [
    __DIR__ . '/../vendor/j7-dev/wp-utils/examples',
];

foreach ($dirs_to_remove as $dir) {
    if (is_dir($dir)) {
        echo "Removing: $dir\n";
        deleteDirectory($dir);
    }
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

echo "Cleanup completed!\n";
