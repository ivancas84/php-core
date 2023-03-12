<?php

require_once("function/snake_case_to.php");

class Field {

  protected static $instances = [];

  public $container;
  public $name;
  public $entity_name;
  public $entity_ref_name;
  public $alias;
  public $default;
  /**
   * puede ser false para booleanos
   */
  
  public $length = null;
  /**
   * longitud del field
   */

  public $max = null; 
  /**
   * Valor maximo
   */
   
  public $min = null; 
  /**
  * valor minimo
  */

  public $type;
  /**
   * string. 
   * Tipo de datos definido en la base de datos.
   */

  public $data_type = "string";
  /**
   * Tipo de datos generico.
   * El framework trabaja solo con los siguientes datos.
   * Cualquier tipo definido en la base de datos es transformado a uno de los siguientes
   *   integer
   *   blob
   *   string
   *   boolean
   *   float
   *   text
   *   timestamp
   *   date 
   */

  public $field_type; //string con el tipo de field
    //"pk": Clave primaria
    //"nf": Field normal
    //"mu": Clave foranea muchos a uno
    //"_u": Clave foranea uno a uno


  public $condition = null;
  /**
   * Tipo de condicion a evaluar
   */

  public $value = null;
  /**
   * Tipo de valor a definir
   * string, integer, float, boolean, datetime
   */

  public function __construct(array $array){
    foreach ($array as $key => $value) {
      if(preg_match('[\+]', $key))  {
        $key = rtrim($key,"+");
        $this->$key = array_values(array_merge($this->$key, $value));
      } elseif(preg_match('[\-]', $key))  {
        $key = rtrim($key,"-");
        $this->$key = array_values(array_diff($this->$key, $value));
      } else {
        $this->$key = $value;
      }
    }
  }
  
  //Retornar instancia de Entity correspondiente al field
  public function getEntity() {
    return $this->container->entity($this->entity_name);
  }

  public function getEntityRef(){
    return ($this->entity_ref_name) ? $this->container->entity($this->entity_ref_name) : null;
  }
  /**
   * Debe sobrescribirse para aquellos fields que sean fk
   */
  public function getDefault(){ return $this->default; }
  public function getFieldType(){ return $this->field_type; }
  public function getLength(){ return $this->length; }
  public function getMax(){ return $this->max; }
  public function getMin(){ return $this->min; }
  public function getDataType(){ return $this->data_type; }
  public function getSelectValues(){ return $this->select_values; }
  public function getType() { return $this->type; }
  public function getCondition() { return $this->condition; }
  public function getValue() { return $this->value; }

  public function isAdmin(){ return (in_array($this->getName(), $this->getEntity()->no_admin)) ? false : true; }
  public function isNotNull(){  return (in_array($this->getName(), $this->getEntity()->not_null)) ? true : false; }
  public function isUnique(){ return (in_array($this->getName(), $this->getEntity()->unique)) ? true : false; }
  public function isMain(){ return (in_array($this->getName(), $this->getEntity()->main)) ? true : false; }
  public function isUniqueMultiple(){  return (in_array($this->getName(), $this->getEntity()->unique_multiple)) ? true : false; }

  public function getAlias($format = null) {
    switch($format){
      case "Xx": return ucfirst(strtolower($this->alias));
      case ".": return (!empty($this->alias)) ? $this->alias . '.' : '';
      case "_Xx": return $this->getEntity()->getAlias("Xx") . $this->getAlias("Xx");
      case "_": return $this->getEntity()->getAlias() . $this->getAlias();

      default: return $this->alias;
    }
  }

  public function getName($format = null) {
    switch($format){
      case "XxYy": return str_replace(" ", "", ucwords(str_replace("_", " ", strtolower($this->name))));
      case "xxyy": return strtolower(str_replace("_", "", $this->name));
      case "Xx Yy": return ucwords(str_replace("_", " ", strtolower($this->name)));
      case "xxYy": return str_replace(" ", "", lcfirst(ucwords(str_replace("_", " ", strtolower($this->name)))));
      case "Xx yy": return ucfirst(str_replace("_", " ", strtolower($this->name)));
      case "_XxYy": return $this->getEntity()->getName("XxYy") . $this->getName("XxYy");
      case "_.":  return $this->getEntity()->getName() . "." . $this->getName();

      default: return $this->name;
    }
  }

}
