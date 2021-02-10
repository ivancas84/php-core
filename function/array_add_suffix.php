<?php

function array_add_suffix(array $array, string $suffix)
{
    return array_map(function ($arrayValues) use ($suffix) {
        return $arrayValues . $suffix;
    }, $array);
}