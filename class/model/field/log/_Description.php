<?php

require_once("class/model/Field.php");

class _FieldLogDescription extends Field {

  public $type = "mediumtext";
  public $fieldType = "nf";
  public $default = null;
  public $name = "description";
  public $alias = "des";
  public $entityName = "log";
  public $dataType = "string";  
  public $subtype = "text";  
  public $length = "255";  


}
