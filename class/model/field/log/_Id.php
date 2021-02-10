<?php

require_once("class/model/Field.php");

class _FieldLogId extends Field {

  public $type = "varchar";
  public $fieldType = "pk";
  public $default = null;
  public $name = "id";
  public $alias = "id";
  public $entityName = "log";
  public $dataType = "string";  
  public $subtype = "text";  
  public $length = "45";  


}
