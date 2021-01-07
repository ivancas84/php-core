<?php
function array_add_prefix(array $array, string $prefix)
{
    return array_map(function ($arrayValues) use ($prefix) {
        return $prefix . $arrayValues;
    }, $array);
}