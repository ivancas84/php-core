<?php
 /*
$array = [
  0 => [
    "field1" => "value1", 
    "field2" => "a", 
    "field3" => "b"
  ],
  
  1 => [
    "field1" => "value1", 
    "field2" => "c", 
    "field3" => "d"
  ],
  
  2 => [
    "field1" => "value2", 
    "field2" => "a", 
    "field3" => "d"
  ],
];

$response = array_group_value($array, "field1").
$response = [
  "value1" => [
    0 => [
      "field1" => "value1", 
      "field2" => "a", 
      "field3" => "b"
    ],
    
    1 => [
      "field1" => "value1", 
      "field2" => "a", 
      "field3" => "b"
    ],
  ],
  
  "value2" => [
    0 => [
      "field1" => "value2", 
      "field2" => "a", 
      "field3" => "d"
    ],   
  ]
];
*/
function array_group_value(array $array, $key){    
    $value = null;
    $return = [];
    foreach($array as $subarray) {
        $v = $subarray[$key];
        if(!key_exists($v, $return)) $return[$v] = [];            
        array_push($return[$v], $subarray);
    }
    return $return;
}