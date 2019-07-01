<?php

function array_unique_key(array $values, $key){
    /**
     * $values: array asociativo
     * $key: Llave del array asociativo que sera utilizada para combinar, debe ser unica
     */
    return array_values(array_unique(array_column($values, $key)));
}