<?php

require_once("function/snake_case_to.php");
/**
 * Configuracion de una tabla
 * Esta clase no deberia poseer seters publicos. Una vez definidos sus atributos, no deberian poder modificarse.
 * Entity debe poseer toda la configuracion necesaria, no importa el contexto en que se este trabajando. Si un determinado contexto posee cierta configuracion se define en la clase Entity, por ejemplo, el atributo "schema" es exclusivo de un contexto de acceso a traves de Sistemas de Administracion de Base de Datos.
 */
class Entity {

  public $structure = NULL; //array. Estructura de tablas, se asigna en el contenedor.

  /**
   * Debido a que la estructura utiliza clases concretas, debe asignarse luego de finalizada la generacion de archivos y solo cuando se requiera su uso.
   */

  public $name;
  public $alias;
  public $schema = DATA_SCHEMA;

  public $nf = [];
  public $om = [];
  public $oo = [];

  public $identifier = [];
  /**
   * Define un nuevo campo "identifier" para facilitar las consultas, busquedas, e importacion
   * El campo identifier consiste en un array de string con los atributos que identifican univocamente a la entidad, 
   * pueden pertenecer a otra entidad, en este caso utilizar los prefijos correspondientes
   * no utilizar ids para su definicion
   * por ejemplo:
   *   public $identifier = ["fecha_anio", "fecha_semestre","alu_per-numero_documento"];
   * Habitualmente $identifier se define en tiempo de ejecucion, para cada bloque de codigo se puede necesitar un identifier diferente
   */

  public $order_default = [];
  /**
   * Valores por defecto para ordenamiento
   * Array asociativo, ejemplo: ["field1"=>"asc","field2"=>"desc",...];
   */

  public $no_admin = [];
  /**
   * Valores no administrables
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
  
  public $unique_multiple = [];
  /**
   * Valores unicos multiples
   * Array, ejemplo: ["field1","field2",...];
   * Se habia pensado poner un atributo uniqueMultiple en el field, pero es mas sencillo indicarlo en la entidad, se modifica un solo archivo
   */

  public $container;

  /**
   * @param $array Array de atributos
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

  /**
   * Metodos para facilitar la sintaxis del sql
   */
  public function n_(){ return $this->name; } //name
  public function s_(){ return (!empty($this->schema)) ?  $this->schema . '.' : ""; } //schema.
  public function sn_(){ return $this->s_() . $this->n_(); } //schema.nombre
  public function sna_(){ return $this->s_() . $this->n_() . " AS " . $this->alias; } //schema.nombre AS alias
  public function a_(){ return $this->alias . "."; }

  function getPk() { return $this->container->field($this->getName(), "id"); }

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
    foreach($this->nf as $field_name) array_push($fields, $this->container->field($this->getName(), $field_name));
    return $fields;
  }

  public function getFieldsFk(){ return array_merge($this->getFieldsMo(), $this->getFieldsOo()); }
  /**
   * fk (mo y oo)
   */

  public function getFieldsMo(){
    $fields = [];
    foreach($this->mo as $field_name) array_push($fields, $this->container->field($this->getName(), $field_name));
    return $fields;
  }
  
  public function getFieldsOo(){
    $fields = [];
    foreach($this->oo as $field_name) array_push($fields, $this->container->field($this->getName(), $field_name));
    return $fields;
  }

  public function getFieldsRef(){ return array_merge($this->getFieldsOm(), $this->getFieldsOon()); } //ref (um y u_)

  public function getFieldsOm(){
    $fields = array();
    foreach($this->getStructure() as $entity){
      foreach($entity->getFieldsMo() as $field){
        if($field->getEntityRef()->getName() == $this->getName()){
          array_push($fields, $field);
        }
      }
    }
    return $fields;
  }

  public function getFieldsOon(){
    $fields = array();
    foreach($this->getStructure() as $entity){
      foreach($entity->getFieldsOo() as $field){
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

  public function getFieldsOonNotReferenced(array $referencedNames){
    /**
     * Fields oo cuyo nombre de tabla no se encuentre en el parametro)
     */
    $fieldsAux = $this->getFieldsOon();
    $fields = array();

    foreach($fieldsAux as $fieldAux){
      if(!in_array($fieldAux->getEntity()->getName(), $referencedNames)){
        array_push($fields, $fieldAux);
      }
    }

    return $fields;
  }

  /**
   * Tiene relaciones?
   * Utilizado generalmente para verificar si es viable la generacion de cierto codigo que requiere relaciones
   */
  public function hasRelations(){ return ($this->hasRelationsFk() || $this->hasRelationsOon()) ? true : false; }
  public function hasRelationsFk(){ return (count($this->getFieldsFk())) ? true : false; }
  public function hasRelationsOon(){ return (count($this->getFieldsOon())) ? true : false; }
  public function hasRelationsRef(){ return (count($this->getFieldsRef())) ? true : false; }

  public function getFieldNames(){ return array_merge(["id"], $this->nf, $this->mo, $this->oo); }

  public function getOrderDefault() { return $this->order_default; }

}
