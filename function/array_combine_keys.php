<?php

function array_combine_keys(array $values, $key, $key2){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     * $key2: Llave del array asociativo que sera utilizada para combinar
     */
    $keys =  array_unique(array_column($values, $key));
    $values_ = array_column($values, $key2);
    if(count($keys) != count($values)) throw new Exception("No pueden combinarse las llaves indicadas, la cantidad de elementos difiere");
    return array_combine($keys, $values_);
}