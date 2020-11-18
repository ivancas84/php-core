<?php

require_once("class/model/Field.php");

class _FieldLogCreated extends Field {

  public $type = "timestamp";
  public $fieldType = "nf";
  public $default = "current_timestamp()";
  public $name = "created";
  public $alias = "cre";
  public $entityName = "log";
  public $dataType = "timestamp";  
  public $subtype = "timestamp";  


}
