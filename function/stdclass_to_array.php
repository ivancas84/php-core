<?php

  function stdclass_to_array_recursive($param){
    if(is_object($param)) $param = (array) $param;
    if(!is_array($param)) return $param;
    
    $ret = array();
    foreach($param as $key => $value){
      $ret[$key] = stdclass_to_array_recursive($value);
    }
    return $ret;
  }
  
  function stdclass_to_array($object){
    return stdclass_to_array_recursive($object);
  }
