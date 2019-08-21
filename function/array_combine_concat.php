<?php

function array_combine_concat(array $values, array $keys){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     */
    $ret = [];
    foreach($values as $value){
        $id = [];
        foreach($keys as $key){
            array_push($id, $value[$key]);
        }
        $ret[implode(UNDEFINED,$id)] = $value;
    }
    return $ret;
}