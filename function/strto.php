<?php



function strto($string, $format, $delimiter = " "){
  //metodo avanzado de formato de strings
  if($delimiter != " ") $string = str_replace($delimiter, " ", $string);
  switch($format){
    case "X": case "X Y": case "XX YY": $s = preg_replace('/\s\s+/', ' ', mb_strtoupper($string)); break;
    case "x": case "x y": case "xx yy": $s = preg_replace('/\s\s+/', ' ', mb_strtolower($string)); break;
    case "XxYy": $s = preg_replace('/\s\s+/', '', mb_convert_case($string, MB_CASE_TITLE)); break;
    case "xxyy": $s = preg_replace('/\s\s+/', '', mb_strtolower($string)); break;
    case "Xx Yy": $s = preg_replace('/\s\s+/', ' ', mb_convert_case($string, MB_CASE_TITLE)); break;
    case "xxYy": 
      $s = preg_replace('/\s\s+/', '', lcfirst(mb_convert_case($string, MB_CASE_TITLE)));
      $s = mb_strtlower(mb_substr($s,0,1)).mb_substr($s,1);
    break;
  }
  if($delimiter != " ") $s = str_replace(" ", $delimiter, $s);
  return $s;
}
