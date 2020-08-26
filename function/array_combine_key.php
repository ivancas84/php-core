<?php

function array_combine_key(array $values, $key){
    /**
     * Crear nuevo array, usando $key para las llaves y $values para los valores
     * $key: Debe ser unica
     */
    return array_combine(array_column($values, $key), $values);
}