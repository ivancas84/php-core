<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");

class EntitySql {
  /**
   * Facilitar la definición de SQL
   * Se define el prefijo _ para indicar que el metodo no define relaciones
   */

  public $prefix = '';
  /**
   * Prefijo de identificacion
   */

  public $container;
  public $entityName;
    
  public function prf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; }   //prefijo fields
  public function prt(){ return (empty($this->prefix)) ?  $this->container->getEntity($this->entityName)->getAlias() : $this->prefix; } //prefijo tabla

  public function formatIds(array $ids = []) {
    /**
     * Formato sql de ids
     */
    $ids_ = [];
    $value = $this->container->getValue($this->entityName);
    for($i = 0; $i < count($ids); $i++) {
      $value->_set("id",$ids[$i]);
      array_push($ids_, $value->_sql("id"));
    }
    return implode(', ', $ids_);
  }

  public function condition(Render $render) { 
    return $this->container->getControllerEntity("sql_condition", $this->entityName)->main(array_merge($render->condition, $render->generalCondition));
  }

  public function _condition(Render $render) {
    return $this->container->getControllerEntity("sql_condition_rel", $this->entityName)->main($render->getCondition());
  }
  
  public function having($render) {
    return $this->container->getControllerEntity("sql_condition", $this->entityName)->main($render->getHaving());    
  }

  public function _having($render) { //busqueda avanzada
    return $this->container->getControllerEntity("sql_condition_rel", $this->entityName)->main($render->getHaving());    
  }

  
  public function from(){
    return " FROM " . $this->container->getEntity($this->entityName)->sna_() . "
";
  }

  public function fromSubSql(Render $render){    
    return " FROM 

" . $this->_subSql($render) . "

 AS {$this->prt()}
";
  }

  public function _from(){
    return " FROM " . $this->container->getEntity($this->entityName)->sn_() . " AS {$this->prt()}
";
  }

  public function limit($page = 1, $size = false){
    if ($size) {
      return " LIMIT {$size} OFFSET " . ( ($page - 1) * $size ) . "
";
    }
    return "";
  }

  public function conditionUniqueFields(array $params){
    /**
     * definir condicion para campos unicos
     * $params:
     *   array("nombre_field" => "valor_field", ...)
     * los campos unicos simples se definen a traves del atributo Field::$unique
     * los campos unicos multiples se definen a traves del meotodo Entity::getFieldsUniqueMultiple();
     */
    $uniqueFields = $this->container->getEntity($this->entityName)->getFieldsUnique();
    $uniqueFieldsMultiple = $this->container->getEntity($this->entityName)->getFieldsUniqueMultiple();

    $condition = array();

    foreach($uniqueFields as $field){
      foreach($params as $key => $value){
        if($key == "id" && empty($value)) continue; //para el id no se permiten valores nulos
        if($key == $field->getName()) {
          array_push($condition, [$key, "=", $value, "or"]);
        }
      }
    }

    if($uniqueFieldsMultiple) {
      $conditionMultiple = [];
      $first = true;
      foreach($uniqueFieldsMultiple as $field){
        foreach($params as $key => $value){
          if($key == $field->getName()) {
            if($first) {
              $con = "or";
              $first = false;
            } else {
              $con = "and";
            }
            array_push($conditionMultiple, [$key, "=", $value, $con]);
          }
        }
      }

      if(!empty($conditionMultiple)) array_push($condition, $conditionMultiple);
    }

    $render = new Render();
    $render->setCondition($condition);
    return $this->condition($render);
  }

  public function _join($field, $fromTable, Render $render){
    /**
     * Definir relacion como subconsulta
     * En funcion del campo pasado como parametro define una relacion
     * Por defecto define una relacion simple utilizando LEFT JOIN
     * Este método puede ser sobrescrito para dar soporte a campos derivados
     */
    $t = $this->prt();
    return "LEFT OUTER JOIN 


      " . $this->_subSql($render) . "


 AS $t ON ($fromTable.$field = $t.{$this->container->getEntity($this->entityName)->getPk()->getName()})
";
  }



  public function orderBy(array $order = null){
    return $this->container->getControllerEntity("sql_order", $this->entityName)->main($order);
  }

  public function _subSql(Render $render){
    return $this->container->getEntity($this->entityName)->sn_();
 
 /*
 $fieldNamesExclusive = $this->container->getController("struct_tools")->getFieldNamesExclusive();
 $fields = implode(",", $this->container->getFieldAlias($this->entityName, $this->prefix)->_toArrayFields($fieldNamesExclusive);
 return "( SELECT DISTINCT
{$fields}
{$this->_from($render)}
" . concat($this->_condition($render), 'WHERE ') . ")
";*/
  }
}