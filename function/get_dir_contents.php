<?php

function get_dir_contents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
          get_dir_contents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}