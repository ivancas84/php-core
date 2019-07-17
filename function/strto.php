<?php



function strto($string, $format){
  switch($format){
    case "XxYy": return str_replace(" ", "", mb_convert_case($string, MB_CASE_TITLE));
    case "xxyy": return str_replace(" ", "", mb_strtolower($string));
    case "Xx Yy": return mb_convert_case($string, MB_CASE_TITLE);
    case "xxYy": 
      $s = str_replace(" ", "", lcfirst(mb_convert_case($string, MB_CASE_TITLE)));
      return mb_strtlower(mb_substr($s,0,1)).mb_substr($s,1);
    
  }
}
