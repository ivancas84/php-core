<?php

class Aliases{
  /**
   * Creacion de alias no repetidos
   */

  var $name;
  /**
   * Nombre del cual se definira el alias
   */
  
  var $aliases; 
  /**
   * Alias existentes para evitar duplicados
   */

  var $reserved;
  /**
   * Palabras reservadas para que no se definan como alias
   */

  var $length;

  public function __construct($name, array $aliases = NULL, $length = 3) {
    $this->name = $name;
    $this->aliases = $aliases;
    if(is_null($aliases)){
      $this->aliases = array();
    }
    $this->length = $length;
    $this->reserved = array("not", "as", "div", "mod", "sum", "avg", "count", "max", "min");
  }

  public static function createAndGetAlias($name, array $aliases = NULL, $length = 3){
    $self = new Aliases($name, $aliases, $length);
    return $self->getAlias();
  }

  /**
   * Definir alias
   * @param array $this->aliases Array de alias
   * @return array con los alias definidos para evitar duplicados
   */
   function getAlias () {
     $pieces = explode("_", $this->name);

     $nameAux = "";
     if(count($pieces) > 1) {
       foreach($pieces as $piece){
         $nameAux .= $piece{0};
       }
       $this->name = $nameAux;
     }

     $aliasAux = substr($this->name, 0, $this->length);

     $i = "a";
     while (in_array($aliasAux, array_merge($this->aliases, $this->reserved))) {
       $aliasAux = "";

       for($j = 0; $j < 2; $j++) {
         if(isset($this->name{$j})){
           $aliasAux .= $this->name{$j};
         }
       }

       $aliasAux .= $i;
       $i++;
     }

     array_push($this->aliases, $aliasAux);
     return $aliasAux;
   }
}
