<?php

require_once("function/snake_case_to.php");

class Field {

  protected static $instances = [];

  public $container;
  public $name;
  public $entityName;
  public $entityRefName;
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

  public $dataType = "string";
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

  public $fieldType; //string con el tipo de field
    //"pk": Clave primaria
    //"nf": Field normal
    //"mu": Clave foranea muchos a uno
    //"_u": Clave foranea uno a uno

  public $subtype = null; //tipo de datos avanzado
  /**
   * text texto simple
   * textarea texto grande
   * checkbox Booleanos
   * date
   * timestamp
   * cuil Texto para cuil
   * dni Texto para dni
   * select Conjunto de opciones definidas
   * Para fk las opciones se definen mediante los valores de las claves foraneas
   * typeahead (fk)
   * file (fk)
   */
  
  public $selectValues = array();
    //si subtype = "select_text", deben asignarse valores "text"
    //si subtype = "select_int", deben asignarse valores "int"

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
      $this->$key = $value;
    }
  }
  
  //Retornar instancia de Entity correspondiente al field
  public function getEntity() {
    return $this->container->entity($this->entityName);
  }

  public function getEntityRef(){
    return ($this->entityRefName) ? $this->container->entity($this->entityRefName) : null;
  }
  /**
   * Debe sobrescribirse para aquellos fields que sean fk
   */
  public function getDefault(){ return $this->default; }
  public function getFieldType(){ return $this->fieldType; }
  public function getLength(){ return $this->length; }
  public function getMax(){ return $this->max; }
  public function getMin(){ return $this->min; }
  public function getSubtype(){ return $this->subtype; }
  public function getDataType(){ return $this->dataType; }
  public function getSelectValues(){ return $this->selectValues; }
  public function getType() { return $this->type; }
  public function getCondition() { return $this->condition; }
  public function getValue() { return $this->value; }

  public function isAdmin(){ return (in_array($this->getName(), $this->getEntity()->noAdmin)) ? false : true; }
  public function isNotNull(){  return (in_array($this->getName(), $this->getEntity()->notNull)) ? true : false; }
  public function isUnique(){ return (in_array($this->getName(), $this->getEntity()->unique)) ? true : false; }
  public function isMain(){ return (in_array($this->getName(), $this->getEntity()->main)) ? true : false; }
  public function isUniqueMultiple(){  return (in_array($this->getName(), $this->getEntity()->uniqueMultiple)) ? true : false; }

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
