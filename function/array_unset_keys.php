<?php

function array_unset_keys(array $array, array $keys){
    foreach($keys as $key) unset($array[$key]);
    return $array;
}