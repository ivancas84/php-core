<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");

class SqlOrder {

  public $container;
  public $entityName;

  public function main(array $order){
    $order = $this->init($order);
    return $this->order($order);
  }

  protected function default(){
    /**
     * Definir ordenamiento por defecto.
     * El ordenamiento por defecto se define en la clase Entity.
     * Si no existe ordenamiento por defecto,
     * se definen los campos principales nf de la entidad principal    
     */
    $e = $this->container->getEntity($this->entityName);
    if(!empty($of = $e->getOrderDefault())) return $of; //se retorna ordenamiento por defecto definido
        
    $fieldsMain = $e->main;
    return array_fill_keys($fieldsMain, "asc"); //se retorna ordenamiento por defecto considerando campos principales nf de la entidad principal
  }
  
  protected function init(array $order) {
    $orderDefault = $this->default();
    foreach($order as $key => $value){
      if(array_key_exists($key, $orderDefault)){
        unset($orderDefault[$key]);
      }
    }

    return array_merge($order, $orderDefault);
  }

  protected function order(array $order = null){
    $sql = '';

    foreach($order as $key => $value){
      $value = ((strtolower($value) == "asc") || ($value === true)) ? "asc" : "desc";
      $sql_ = "{$this->container->getSqlo($this->entityName)->mapping($key)} IS NULL, {$this->container->getSqlo($this->entityName)->mapping($key)} {$value}";
      $sql .= concat($sql_, ', ', ' ORDER BY', $sql);
    }

    return $sql;
  }


 
}