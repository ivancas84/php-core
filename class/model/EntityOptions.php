<?php

class EntityOptions {

  public $fieldNames = [];
  public $prefix = "";
  public $entity;
  
  public function prf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; } 
  /**
   * prefijo fields
   */
  
  public function prt(){ return (empty($this->prefix)) ?  $this->entity->getAlias() : $this->prefix; }
  /**
   * prefijo tabla
   */
  
  function callRow($row, $method = ""){
    if(empty($row)) return;

    foreach($this->fieldNames as $fieldName){
      $call = snake_case_to("XxYy", $method).snake_case_to("XxYy", $fieldName);
      if(isset($row[$prefix.$fieldName])) $this->$call($row[$prefix.$fieldName]);
    }
  }

  function call($method = ""){
		foreach($this->fieldNames as $fieldName){
      $call = snake_case_to("XxYy", $method).snake_case_to("XxYy", $fieldName);
			$this->$call();
		}
  }

  function eval($fieldName){
    $count = 1;
    /**
     * Si no se especifica count como variable independiente dispara el error Notice: Only variables should be passed by reference in C:\xampp\htdocs\call.php on line 33 field1
     */
    $method = snake_case_to("xxYy", str_replace($this->prf(), "", $fieldName, $count));
    if(!method_exists($this, $method)) return;
	  return $this->$method();
  }

}