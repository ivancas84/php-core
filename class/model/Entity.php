<?php

require_once("function/snake_case_to.php");
/**
 * Configuracion de una tabla
 * Esta clase no deberia poseer seters publicos. Una vez definidos sus atributos, no deberian poder modificarse.
 * Entity debe poseer toda la configuracion necesaria, no importa el contexto en que se este trabajando. Si un determinado contexto posee cierta configuracion se define en la clase Entity, por ejemplo, el atributo "schema" es exclusivo de un contexto de acceso a traves de Sistemas de Administracion de Base de Datos.
 */
abstract class Entity {

  public $structure = NULL; //array. Estructura de tablas, se asigna en el contenedor.

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
  
  public $nf = [];
  public $mu = [];
  public $_u = [];

  public $identifier = [];
  /**
   * Define un nuevo campo "identifier" para facilitar las consultas, busquedas, e importacion
   * El campo identifier consiste en un array de string con los atributos que identifican univocamente a la entidad, 
   * pueden pertenecer a otra entidad, en este caso utilizar los prefijos correspondientes
   * no utilizar ids para su definicion
   * por ejemplo:
   *   public $identifier = ["fecha_anio", "fecha_semestre","alu_per_numero_documento"];
   */

  public $orderDefault = [];
  /**
   * Valores por defecto para ordenamiento
   * Array asociativo, ejemplo: ["field1"=>"asc","field2"=>"desc",...];
   */

  public $noAdmin = [];
  /**
   * Valores no administrables
   * Array, ejemplo: ["field1","field2",...];
   */

  public $noExclusive = [];
  /**
   * Valores no exclusivos
   * Array, ejemplo: ["field1","field2",...];
   */

  public $main = ["id"];
  /**
   * Valores principales
   * Array, ejemplo: ["field1","field2",...];
   */

  public $unique = ["id"];
  /**
   * Valores unicos
   * Array, ejemplo: ["field1","field2",...];
   */
  
  public $uniqueMultiple = [];
  /**
   * Valores unicos multiples
   * Array, ejemplo: ["field1","field2",...];
   * Se habia pensado poner un atributo uniqueMultiple en el field, pero es mas sencillo indicarlo en la entidad.
   * se modifica un solo archivo
   */

  public $container;

  /**
   * Metodos para facilitar la sintaxis del sql
   */
  public function n_(){ return (!empty($this->table)) ?  $this->table : $this->name; } //name
  public function s_(){ return (!empty($this->schema)) ?  $this->schema . '.' : ""; } //schema.
  public function sn_(){ return $this->s_() . $this->n_(); } //schema.nombre
  public function sna_(){ return $this->s_() . $this->n_() . " AS " . $this->alias; } //schema.nombre AS alias
  public function a_(){ return $this->alias . "."; }

  function getPk() { return $this->container->getField($this->getName(), "id"); }

  //Debido a que la estructura utiliza clases concretas, debe asignarse luego de finalizada la generacion de archivos en el contenedor
  public function setStructure(array $structure){ $this->structure = $structure; }
  public function getStructure(){ return $this->structure; }


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

  public function getIdentifier() { return $this->identifier; }

  public function getFields(){ //pk, nf, fk
    $merge =  array_merge($this->getFieldsNf(), $this->getFieldsFk());
    
    array_unshift($merge, $this->getPk());
    return $merge;
  }

  public function getFieldsNoPk(){
    return array_merge($this->getFieldsNf(), $this->getFieldsFk());
  }

  public function getFieldsNf(){
    $fields = [];
    foreach($this->nf as $fieldName) array_push($fields, $this->container->getField($this->getName(), $fieldName));
    return $fields;
  }

  public function getFieldsFk(){ return array_merge($this->getFieldsMu(), $this->getFields_U()); }
  /**
   * fk (mu y _u)
   */

  public function getFieldsMu(){
    $fields = [];
    foreach($this->mu as $fieldName) array_push($fields, $this->container->getField($this->getName(), $fieldName));
    return $fields;
  }
  
  public function getFields_U(){
    $fields = [];
    foreach($this->_u as $fieldName) array_push($fields, $this->container->getField($this->getName(), $fieldName));
    return $fields;
  }

  public function getFieldsRef(){ return array_merge($this->getFieldsUm(), $this->getFieldsU_()); }
  /**
   * ref (um y u_)
   */

  public function getFieldsUm(){
    $fields = array();
    foreach($this->getStructure() as $entity){
      foreach($entity->getFieldsMu() as $field){
        if($field->getEntityRef()->getName() == $this->getName()){
          array_push($fields, $field);
        }
      }
    }
    return $fields;
  }

  public function getFieldsU_(){
    $fields = array();
    foreach($this->getStructure() as $entity){
      foreach($entity->getFields_U() as $field){
        if($field->getEntityRef()->getName() == $this->getName()){
          array_push($fields, $field);
        }
      }
    }
    return $fields;
  }

  public function getFieldsFkNotReferenced(array $referencedNames){
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

  public function getFieldsU_NotReferenced(array $referencedNames){
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

  public function getFieldsByType(array $types){
    /**
     * filtrar campos por tipo
     */
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

  /**
   * Tiene relaciones?
   * Utilizado generalmente para verificar si es viable la generacion de cierto codigo que requiere relaciones
   */
  public function hasRelations(){ return ($this->hasRelationsFk() || $this->hasRelationsU_()) ? true : false; }
  public function hasRelationsFk(){ return (count($this->getFieldsFk())) ? true : false; }
  public function hasRelationsU_(){ return (count($this->getFieldsU_())) ? true : false; }
  public function hasRelationsRef(){ return (count($this->getFieldsRef())) ? true : false; }

  public function getFieldNames(){ return array_merge(["id"], $this->nf, $this->mu, $this->_u); }

  public function getOrderDefault() { return $this->orderDefault; }

  public function getFieldsUnique(){
    /**
     * campos unicos simples
     */
    $unique = array();
    foreach($this->unique as $fieldName){
      array_push($unique, $this->container->getField($this->getName(), $fieldName));
    }
    return $unique;
  }

  public function getFieldsMain(){
    /**
     * campos principales
     */
    $fields = array();
    foreach($this->main as $fieldName){
      array_push($fields, $this->container->getField($this->getName(), $fieldName));
    }
    return $fields;
  }

  public function getFieldsNotNull(){
    /**
     * campos principales
     */
    $fields = array();
    foreach($this->notNull as $fieldName){
      array_push($fields, $this->container->getField($this->getName(), $fieldName));
    }
    return $fields;
  }

  public function getFieldsAdmin(){
    /**
     * campos principales
     */
    $fields = array();
    foreach($this->admin as $fieldName){
      array_push($fields, $this->container->getField($this->getName(), $fieldName));
    }
    return $fields;
  }

  public function getFieldsUniqueMultiple(){ 
    /**
     * campos unicos multiples
     * se habia pensando en poner un atributo "uniqueMultiple" en el field
     * pero es mas sencillo indicarlo directamente en la entidad
     */
    $uniqueMultiple = [];
    foreach($this->uniqueMultiple as $fieldName){
      array_push($uniqueMultiple, $this->container->getField($this->getName(), $fieldName));
    }
    return $uniqueMultiple;      
  }
}
