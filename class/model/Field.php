<?php

require_once("function/snake_case_to.php");


abstract class Field {

  protected static $instances = [];

  public $container;
  public $name;
  public $alias;
  public $default;
  /**
   * puede ser false para booleanos
   */
  
  public $length;
  /**
   * longitud maxima del field
   * false: El dato no tiene definida longitud
   */

  public $minLength; 
   /**
    * longitud minima del field
    * false: El dato no tiene definida longitud
    */

  public $notNull;
  /**
   * Flag para indicar si es campo no nulo
   * true | false
   */

  public $type;
  /**
   * string. 
   * Tipo de datos definido en la base de datos.
   */

  public $dataType;
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

  public $unique; //flag para indicar si es un campo unico

  public $subtype = null; //tipo de datos avanzado
    //text texto simple
    //textarea texto grande
    //checkbox Booleanos
    //date
    //timestamp
    //select_int Conjunto de enteros definido, los valores se definen en el atributo "selectValues"
    //select_text Conjunto de strings definido, los valores se definen en el atributo "selectValues"
    //cuil Texto para cuil
    //dni Texto para dni
    //select (fk) Conjunto de opciones definidas mediante los valores de las claves foraneas
    //typeahead (fk)
    //file (fk)

  public $main = false; //flag para indicar si es un campo principal.
    //Por defecto se define la clave primaria como campo principal. En versiones anteriores se hacia la siguiente logica:
    // Si tiene algun campo main, se define el main
    // Si no tiene campo main, se define el unique
    // Si no tiene campo unique, se define la pk.
    // Pero debido a la complicacion en la logica y a la confusion que generaba se decidio dejar por defecto a la pk como campo principal siempre y definir adicionalmente a la pk los campos unique. El desarrollador debera cambiar este comportamiento manualmente.

  public $selectValues = array();
    //si subtype = "select_text", deben asignarse valores "text"
    //si subtype = "select_int", deben asignarse valores "int"

  public $admin = true;
  /** 
   * Administracion
   * si = false, no se incluye en los formularios de administracion 
   * si = false, no se incluye en la persistencia
   */

  public $exclusive = true;
  /**
   * Campo exclusivo
   * Un campo exclusivo puede definirse internamente con los campos de la entidad.
   * Un campo no exlusivo debe definirse con alguna relación independiente.
   * Esta pensado para separar los fields de una entidad de las que no son para el caso que haya que definirse subconsulta 
   * Los campos no exclusivos habitualmente se definen como admin = false
   */

  public function __construct() {
    $this->defineDataType();
    $this->defineSubtype();
    $this->defineNotNull();
    $this->defineLength();
  }

  //Retornar instancia de Entity correspondiente al field
  abstract function getEntity();
  public function getEntityRef(){ return null; } //Retornar instancia de Entity referenciado por el field
  /**
   * Debe sobrescribirse para aquellos fields que sean fk
   */
  public function getDefault(){ return $this->default; }
  public function getFieldType(){ return $this->fieldType; }
  public function getLength(){ return $this->length; }
  public function getMinLength(){ return $this->minLength; }
  public function getSubtype(){ return $this->subtype; }
  public function getDataType(){ return $this->dataType; }
  public function getSelectValues(){ return $this->selectValues; }
  public function getType() { return $this->type; }
  public function isMain(){ return $this->main; }
  public function isNotNull(){ return $this->notNull; }
  public function isUnique(){ return $this->unique; }  
  public function isAdmin(){ return $this->admin; }
  public function isExclusive(){ return $this->exclusive; }
  
  public function isUniqueMultiple(){ 
    $fields = $this->getEntity()->getFieldsUniqueMultiple();
    foreach($fields as $field){
      if($this->getName() == $field->getName()) return true;
    }
    return false;
  }

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

  protected function defineNotNull(){
    if ( is_null($this->notNull) ) {
      $this->notNull = ( ( $this->subtype == "checkbox" ) ) ? true : false;
    }
  }

  protected function defineDataType(){
    if (is_null($this->dataType)) {
      switch ( $this->type ) {
        case "smallint":
        case "mediumint":
        case "int":
        case "integer":
        case "serial":
        case "bigint": $this->dataType = "integer"; break;
        case "tinyblob":
        case "blob":
        case "mediumblob":
        case "longblog": $this->dataType = "blob"; break;
        case "varchar":
        case "char":
        case "string":
        case "tinytext": $this->dataType = "string"; break;
        case "boolean":
        case "bool":
        case "tinyint": $this->dataType = "boolean"; break;
        case "float":
        case "real":
        case "decimal": $this->dataType = "float"; break;
        case "text": $this->dataType = "text"; break;
        case "datetime":
        case "timestamp": $this->dataType = "timestamp"; break;
        default: $this->dataType = $this->type;
      }
    }
  }

  protected function defineSubtype(){
    if(is_null($this->subtype)){
      switch($this->fieldType){
        case "pk":
        case "nf":
          switch($this->dataType){
            case "string": $this->subtype = "text"; break;
            case "integer": $this->subtype = "integer"; break;
            case "float": $this->subtype = "float"; break;
            case "date": $this->subtype = "date"; break;
            case "timestamp": $this->subtype = "timestamp"; break;
            case "text": $this->subtype = "textarea"; break;
            case "blob": $this->subtype = "file_db"; break;
            case "boolean": $this->subtype = "checkbox"; break;
            case "time": $this->subtype = "time"; break;
            case "year": $this->subtype = "year"; break;
            default: $this->subtype = false; break;
          }
        break;

        case "fk": case "mu": case "_u":
          $this->subtype = "typeahead";
        break;
      }
    }
  }

  protected function defineLength(){
    if(!empty($this->length) ) return $this->length;
    switch ($this->type) {
      case "tinyint": return $this->length = 3;
      case "smallint": return $this->length = 5;
      case "mediumint": return $this->length = 8;
      case "int": return $this->length = 10;
      case "integer": return $this->length = 10;
      case "serial": return $this->length = 10;
      case "bigint": return $this->length = 20;
      case "tinyblob": return $this->length = 255; //bytes
      case "blob": return $this->length = 65535; //bytes (64KB)
      case "mediumblob": return $this->length = 16777215; //bytes (16MB)
      case "longblog": return $this->length = 4294967295; //bytes (4GB)
    }

    switch($this->subtype){
      case "text": return $this->length = 45;
      case "cuil": return $this->length = 11;
      case "dni": return $this->length = 8;
    }
  }
}
