<?php

/**
 * Configuracion de una tabla
 * Esta clase no deberia poseer seters publicos. Una vez definidos sus atributos, no deberian poder modificarse.
 * Entity debe poseer toda la configuracion necesaria, no importa el contexto en que se este trabajando. Si un determinado contexto posee cierta configuracion se define en la clase Entity, por ejemplo, el atributo "schema" es exclusivo de un contexto de acceso a traves de Sistemas de Administracion de Base de Datos.
 */
abstract class Entity {

  protected static $structure = NULL; //array. Estructura de tablas.
  protected static $instances = [];

  /**
   * Debido a que la estructura utiliza clases concretas, debe asignarse luego de finalizada la generacion de archivos y solo cuando se requiera su uso.
   */

  public $name;
  public $alias;
  public $schema = DATA_SCHEMA;
  public $table = null; //nombre de la tabla de la base de datos
  /**
   * En ocasiones el nombre de la tabla de la base de datos puede ser diferente del original
   * Si es null, se considera el mismo nombre que la entidad
   */

  //http://php.net/manual/en/language.oop5.late-static-bindings.php
  //public static function name(){ return null; }
  //public static function alias(){ return null; }
  //public static function schema(){ return DATA_SCHEMA; }


  //Metodo auxiliares para facilitar la definicion de consultas sql (se definen como metodos estaticos para facilitar la sintaxis)
  //public static function n_(){ return $this->name; } //nombre de la tabla. El nombre de la tabla puede no coincidir con el de la entidad


  final public static function getInstance() {
    $className = get_called_class();
    if (!isset(self::$instances[$className])) { self::$instances[$className] = new $className; }
    return self::$instances[$className];
  }


  final public static function getInstanceFromString($entity) {
    $className = snake_case_to("XxYy", $entity) . "Entity";
    return call_user_func("{$className}::getInstance");
  }


  /**
   * Metodos para facilitar la sintaxis del sql
   */
  public function n_(){ return (!empty($this->table)) ?  $this->table : $this->name; } //name
  public function s_(){ return (!empty($this->schema)) ?  $this->schema . '.' : ""; } //schema.
  public function sn_(){ return $this->s_() . $this->n_(); } //schema.nombre
  public function sna_(){ return $this->s_() . $this->n_() . " AS " . $this->alias; } //schema.nombre AS alias
  public function a_(){ return $this->alias . "."; }

  function getPk() { throw new BadMethodCallException("Not Implemented"); }

  //Debido a que la estructura utiliza clases concretas, debe asignarse luego de finalizada la generacion de archivos
  public static function setStructure(array $structure){ self::$structure = $structure; }
  public static function getStructure(){ return self::$structure; }


  public function getName($format = null) {
    switch($format){
      case "XxYy": return str_replace(" ", "", ucwords(str_replace("_", " ", strtolower($this->name))));
      case "xxyy": return strtolower(str_replace("_", "", $this->name));
      case "Xx Yy": return ucwords(str_replace("_", " ", strtolower($this->name)));
      case "Xx yy": return ucfirst(str_replace("_", " ", strtolower($this->name)));
      case "xxYy": return str_replace(" ", "", lcfirst(ucwords(str_replace("_", " ", strtolower($this->name)))));
      case "xx-yy": return strtolower(str_replace("_", "-", $this->name));
      default: return $this->name;
    }
  }


  public function getAlias($format = null) {
    switch($format){
     case ".": return (!empty($this->alias)) ?  $this->alias . '.' : "";
     case "Xx"; return ucfirst(strtolower($this->alias));
     default: return $this->alias;
    }

    return $this->alias;
  }

  public function getSchema() { return $this->schema; }

  public function getFields(){ //pk, nf, fk
    $merge =  array_merge($this->getFieldsNf(), $this->getFieldsFk());
    array_unshift($merge, $this->getPk());
    return $merge;
  }

  public function getFieldsByName($fieldName){
    foreach($this->getFields() as $field){
      if($field->getName() == $fieldName) return $field;
    }
    return null;
  }

  public function getFieldsNf(){ return array(); }
  public function getFieldsFk(){ return array_merge($this->getFieldsMu(), $this->getFields_U()); } //fk (mu y _u)
  public function getFieldsMu(){ return array(); } //mu
  public function getFields_U(){ return array(); } //_u
  public function getFieldsRef(){ return array_merge($this->getFieldsUm(), $this->getFieldsU_()); } //ref (um y u_)

  public function getFieldsUm(){ //um
    if(self::getStructure() == NULL) throw new Exception("Debe setearse la estructura");
    $fields = array();
    foreach(self::getStructure() as $entity){
      foreach($entity->getFieldsMu() as $fieldConfig){
        if($fieldConfig->getEntityRef()->getName() == $this->getName()){
          array_push($fields, $fieldConfig);
        }
      }
    }
    return $fields;
  }

  public function getFieldsU_(){ //u_
    if(self::getStructure() == NULL) throw new Exception("Debe setearse la estructura");
    $fields = array();
    foreach(self::getStructure() as $entity){
      foreach($entity->getFields_U() as $fieldConfig){
        if($fieldConfig->getEntityRef()->getName() == $this->getName()){
          array_push($fields, $fieldConfig);
        }
      }
    }
    return $fields;
  }

  public function getFieldsFkNotReferenced(array $referencedNames){ //fk no referenciadas
    /**
     * Fields fk cuyo nombre de tabla referenciada no se encuentre en el parametro
     */
    $fieldsAux = $this->getFieldsFk();
    $fields = array();

    foreach($fieldsAux as $fieldAux){
      if(!in_array($fieldAux->getEntityRef()->getName(), $referencedNames)){
        array_push($fields, $fieldAux);
      }
    }

    return $fields;
  }

  public function getFieldsU_NotReferenced(array $referencedNames){ //u_ no referenciadas
    /**
     * Fields u_ cuyo nombre de tabla no se encuentre en el parametro)
     */
    $fieldsAux = $this->getFieldsU_();
    $fields = array();

    foreach($fieldsAux as $fieldAux){
      if(!in_array($fieldAux->getEntity()->getName(), $referencedNames)){
        array_push($fields, $fieldAux);
      }
    }

    return $fields;
  }

  public function getFieldsByType(array $types){ //fields por tipo
    $fields = array();

    foreach($types as $type){
      switch($type){
        case "pk": array_push($fields, $this->getPk()); break;
        case "nf": $fields = array_merge($fields, $this->getFieldsNf()); break;
        case "fk": $fields = array_merge($fields, $this->getFieldsFk()); break;
        case "ref": $fields = array_merge($fields, $this->getFieldsRef()); break;
        case "u_": $fields = array_merge($fields, $this->getFieldsU_()); break;
        case "um": $fields = array_merge($fields, $this->getFieldsUm()); break;
        case "_u": $fields = array_merge($fields, $this->getFieldsU_()); break;
        case "mu": $fields = array_merge($fields, $this->getFieldsUm()); break;
      }
    }

    return $fields;
  }

  public function getFieldsUnique(){ //devolver campos unicos
    //solo se pueden definir campos unicos simples
    $unique = array();
    foreach($this->getFields() as $field){
      if($field->isUnique()) array_push($unique, $field);
    }
    return $unique;
  }

  public function getFieldHistory(){ //Field historico
    /**
     * Puede definirse por entidad un unico field historico del tipo boolean, date o timestamp
     */
    foreach($this->getFieldsNf() as $field){
      if($field->isHistory()) return $field;
    }

    return null;
  }


  /**
   * Tiene relaciones?
   * Utilizado generalmente para verificar si es viable la generacion de cierto codigo que requiere relaciones
   */
  public function hasRelations(){ return ($this->hasRelationsFk() || $this->hasRelationsU_()) ? true : false; }
  public function hasRelationsFk(){ return (count($this->getFieldsFk())) ? true : false; }
  public function hasRelationsU_(){ return (count($this->getFieldsU_())) ? true : false; }

  public function hasRelationsFk_(){
    $fk = $this->getFieldsFk();
    foreach($fk as $field ) {
      if($field->getEntityRef()->getName() != $this->getName()) return true;
    }
    return false;
  }

}
