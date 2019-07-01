<?php

function array_combine_keys(array $values, $key, $key2){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     */
    $keys =  array_column($values, $key);
    $values_ = array_column($values, $key2);    
    return array_combine($keys, $values_);
}