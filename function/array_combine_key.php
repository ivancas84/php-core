<?php

function array_combine_key(array $values, $key){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     */
    return array_combine(array_column($values, $key), $values);
}