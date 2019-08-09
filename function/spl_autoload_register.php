<?php
spl_autoload_register(function ($class) {
    if (file_exists('class/model/entity/{$class}.php')) {
        require $file;
        return true;
    } elseif (file_exists('class/model/entity/{$class}.php')) {
        require $file;
        return true;
    } elseif (file_exists('class/model/field/{$class}.php')) {
        require $file;
        return true;
    } elseif (file_exists('class/model/sqlo/{$class}.php')) {
        require $file;
        return true;
    } elseif (file_exists('class/model/sql/{$class}.php')) {
        require $file;
        return true;
    }
    return false;
});
?>