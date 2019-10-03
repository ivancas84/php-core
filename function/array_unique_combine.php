<?php

function array_unique_combine(array $values, $key1, $key2){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     */
    $k = array_values(array_unique(array_column($values, $key1)));
    $v = array_values(array_unique(array_column($values, $key2)));
    return array_combine($k,$v);
}