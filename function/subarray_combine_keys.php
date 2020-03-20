<?php

function subarray_combine_keys(array $values, $keys){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     * $key2: Llave del array asociativo que sera utilizada para combinar
     */
    $values_ = array();
    foreach($keys as $key) $values_[$key] = array_column($values, $key);

    $newArray = [];
    for($i = 0; $i < count($values); $i++){
        $newElement = [];
        foreach($keys as $key) $newElement[$key] = $values_[$key][$i];
        array_push($newArray, $newElement);
    }
        
    return array_values(
        array_map("unserialize", array_unique(array_map("serialize", $newArray)))
    );
}