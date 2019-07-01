<?

//@param $value Instancia de datetime o fecha en formato "Y-m-d"
function formatDate($value = null, $format = "d/m/Y"){
  if(gettype($value) === "string") $value = SpanishDateTime::createFromFormat("Y-m-d", $value);
  return ($value) ? $value->format($format) : null;
}
