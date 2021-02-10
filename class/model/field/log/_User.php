<?php

require_once("class/model/Field.php");

class _FieldLogUser extends Field {

  public $type = "varchar";
  public $fieldType = "nf";
  public $default = null;
  public $name = "user";
  public $alias = "use";
  public $entityName = "log";
  public $dataType = "string";  
  public $subtype = "text";  
  public $length = "255";  


}
