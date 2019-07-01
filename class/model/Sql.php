<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");
require_once("class/db/Interface.php");

abstract class EntitySql { //Definir SQL
  /**
   * Facilitar la definición de SQL
   * Definir una serie de metodos que son de uso comun para todas las consultas
   * Algunos métodos que requieren una conexion abierta a la base de datos, como por ejemplo "escapar caracteres"
   */

  public $prefix = ''; //string. prefijo de identificacion
  public $entity; //Entity. Configuracion de la tabla
  public $format; //FormatSql
  public $db; //DB. Conexion con la bse de datos
  /**
   * Para definir el sql es necesaria la existencia de una clase de acceso abierta, ya que ciertos metodos, como por ejemplo "escapar caracteres" lo requieren.
   * Ademas, ciertos metodos requieren determinar el motor de base de datos para definir la sintaxis SQL adecuada
   */

   public static function getInstanceFromString($entity, $prefix = NULL) { //crear instancias de sql
     /**
      * sql, a diferencia de sus pares entity y sqlo, no puede ser implementada como singleton porque utiliza prefijos de identificacion
      */
     $sqlName = snake_case_to("XxYy", $entity) . "Sql";
     $sql = new $sqlName;
     if($prefix) $sql->prefix = $prefix;
     return $sql;
   }


  public function __construct(){
    $this->db = Dba::dbInstance();
    $this->format = SqlFormat::getInstance();
  }

  public function prf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; }   //prefijo fields
  public function prt(){ return (empty($this->prefix)) ?  $this->entity->getAlias() : $this->prefix; } //prefijo tabla
  public function initializeInsert(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //inicializar valores para insercion
  public function initializeUpdate(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //inicializar valores para actualizacion
  public function format(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //formato de sql
  public function _json(array $row) { throw new BadMethodCallException("No implementado"); }

  public function json(array $row) { return $this->_json($row); }
    /**
     * @todo Metodo obsoleto, debe pasar a formar parte de Sqlo
     */

  public function jsonAll(array $rows){
    /**
     * @todo Metodo obsoleto, debe pasar a formar parte de Sqlo
     */
    $rows_ = [];

    foreach($rows as $row){
      $row_ = $this->json($row);
      array_push($rows_, $row_);
    }

    return $rows_;
  }

  public function formatIds(array $ids = []) { //formato sql de ids
    $ids_ = [];
    for($i = 0; $i < count($ids); $i++) {
      $r = $this->format(["id"=>$ids[$i]]);
      array_push($ids_, $r["id"]);
    }
    return implode(', ', $ids_);
  }

  public function _mappingFieldEntity($field) { //mapeo de fields de la entidad (todos los fields)
    /**
     * Recorre relaciones (si existen)
     */
    if($field_ = $this->_mappingField($field)) return $field_;
    if($field_ = $this->_mappingFieldAggregate($field)) return $field_;
    if($field_ = $this->_mappingFieldDefined($field)) return $field_;
  }

  public function mappingField($field){ //Traducir campo para ser interpretado correctamente por el SQL
    /**
     * Recorre relaciones (si existen)
     */
    if($field_ = $this->_mappingFieldEntity($field)) return $field_;
    throw new Exception("Campo no reconocido");
  }
  public function _mappingField($field){ throw new BadMethodCallException("Not Implemented"); } //traduccion local de campos
  public function _mappingFieldAggregate($field){ return null; } //traduccion local de campos de agregacion
  public function _mappingFieldDefined($field){ //traduccion local de campos generales
    switch ($field) {
      case '_cantidad': return "COUNT(*)";
    } 
  }
  public function _conditionSearch($search = ""){ throw new BadMethodCallException("Not Implemented"); } //traduccion local

  public function fieldsAll() { //todos los fields de consulta (incluye derivados estructurales)
    return ($this->fieldsAux()) ? "{$this->fieldsFull()},
{$this->fieldsAux()}" : "{$this->fieldsFull()}";
  }

  public function conditionSearch($search = ""){ //Definir condicion de busqueda simple
    /**
     * Este metodo sera sobrescrito si existen relaciones fk
     */
    if(empty($search)) return '';
    $condition = $this->_conditionSearch($search);
    return "(" . $condition . ")";
  }

  public function conditionAux(){ return $this->_conditionAux(); } //concatenacion de condicion auxiliar
  public function _conditionAux(){ return "";  } //Sobrescribir si existe condicion auxiliar obligatoria
  /**
   * No utilizar conditionAux para condiciones historicas (las condiciones historicas no deben ser definidas en cadena)
   * Ejemplo: "(alias.field = "value") " dejar un espacio despues de la condicion
   */

  public function conditionHistory(array $history = []) { //condicion para visualizar datos historicos
    if(!key_exists("history", $history)) $history["history"] = false;
    return $this->_conditionHistory($history);
  }
  public function _conditionHistory(array $history){ return "";  } //Sobrescribir si existe condicion auxiliar obligatoria
  /**
   * por defecto se define la entidad actual solo para mostrar los datos activos y las relacionadas todos los datos
   */

  public function conditionAdvanced(array $advanced = null) { //busqueda avanzada

    /**
     * Array $advanced:
     *  [
     *    0 => "field"
     *    1 => "=", "!=", ">=", "<=", "<", ">", "=="
     *    2 => "value" array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array)
     *    3 => "AND" | "OR" | null (opcional, por defecto AND)
     *  ]
     *  Array(
     *    Array("field" => "field", "value" => array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array) [, "option" => "="|"=~"|"!="|"<"|"<="|">"|">="|true (no nulos)|false (nulos)][, "mode" => "and"|"or"]
     *    ...
     *  )
     *  )
     */
    if(empty($advanced)) return "";
    $conditionMode = $this->conditionAdvancedRecursive($advanced);
    return $conditionMode["condition"];
  }






  private function conditionAdvancedRecursive(array $advanced){

    /**
     * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
     * "condition": SQL
     * "mode": Concatenacion de condiciones "AND" | "OR"
     */

    if(is_array($advanced[0])) return $this->conditionAdvancedIterable($advanced);
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */

    $option = (empty($advanced[1])) ? "=" : $advanced[1]; //por defecto se define "="
    $value = (!isset($advanced[2])) ? null : $advanced[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($advanced[3])) ? "AND" : $advanced[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

   $condicion = $this->conditionFieldValue($advanced[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    return ["condition" => $condicion, "mode" => $mode];
  }

  private function conditionAdvancedIterable(array $advanced) {
    $conditionModes = array();

    for($i = 0; $i < count($advanced); $i++){
      $conditionMode = $this->conditionAdvancedRecursive($advanced[$i]);
      array_push($conditionModes, $conditionMode);
    }

    $modeReturn = $conditionModes[0]["mode"];
    $condition = "";

    foreach($conditionModes as $cm){
      $mode = $cm["mode"];
      if(!empty($condition)) $condition .= $mode . " ";
      $condition.= $cm["condition"];
    }

    return ["condition"=>"(".$condition.")", "mode"=>$modeReturn];
  }

  protected function conditionFieldValue($field, $option, $value){
    //se verifica inicialmente la condicion auxiliar.
    //las condiciones auxiliares no siguen la estructura definida de condicion
    $condition = $this->conditionFieldAux($field, $option, $value);
    if($condition) return $condition;
    
    if(!is_array($value)) {
      $condition = $this->conditionField($field, $option, $value);
      if(!$condition) throw new Exception("No pudo definirse el SQL de la condicion del campo: {$field}");
      return $condition;
    }

    $condition = "";
    $cond = false;

    foreach($value as $v){
      if($cond) {
        if($option == "=") $condition .= " OR ";
        elseif($option == "!=") $condition .= " AND ";
        else throw new Exception("Error al definir opción");
      } else $cond = true;

      $condition_ = $this->conditionField($field, $option, $v);
      if(!$condition_) throw new Exception("No pudo definirse el SQL de la condicion del campo: {$field}");
      $condition .= $condition_;
    }

    return "(".$condition.")";
  }


  protected function conditionField($field, $option, $value){ //condicion avanzada principal
    if($c = $this->_conditionField($field, $option, $value)) return $c;
  }
  /**
   * Define una condicion avanzada que recorre todos los metodos independientes de condicion avanzadas de las tablas relacionadas
   * La restriccion de conditionField es que $value no puede ser un array, ya que definirá un conjunto de condiciones asociadas
   * Si existen relaciones, este metodo debe reimplementarse para contemplarlas
   */

  protected function conditionFieldAux($field, $option, $value){ //condicion de field auxiliar
    /**
     * Se sobrescribe si tiene relaciones
     */
    if($c = $this->_conditionFieldAux($field, $option, $value)) return $c;
  }

  protected function _conditionFieldAux($field, $option, $value){
    $p = $this->prf();

    switch($field){
      case "_compare": //no define prefijo, los prefijos pueden definirse en los valores y comparar campos de diferentes entidades
        $f1 = $this->_mappingFieldEntity($value[0]);
        $f2 = $this->_mappingFieldEntity($value[1]);
        return "({$f1} {$option} {$f2})";
      break;


      case "_cantidad": //campo de agregacion general: "_cantidad"
        $f = $this->_mappingFieldEntity($field);
        return $this->format->conditionNumber($f, $value, $option);
      break;
    }
  }


  public function fields(){ throw new BadMethodCallException("Not Implemented"); } //Definir sql con los campos

  public function fieldId(){ return $this->entity->getAlias() . "." . $this->entity->getPk()->getName(); } //Se define el identificador en un metodo independiente para facilitar la reimplementacion para aquellos casos en que el id tenga un nombre diferente al requerido, para el framework es obligatorio que todas las entidades tengan una pk con nombre "id"


  public function mappingFieldsAdvanced($fields, $method = NULL){ //Conexion de fields
    /**
     * Este metodo se creo inicialmente para facilitar la implementacion de consultas avanzadas
     */
    $arr = [];
    foreach($fields as $key => $value){
      $field_ = $this->mappingField($value);
      $alias_ = is_string($key) ? $key : $value; //si es un array asociativo, las llaves se definiran como alias
      $field = empty($method) ? "{$field_} AS {$alias_}" : "{$method}({$field_}) AS {$alias_}";
      array_push($arr, $field);
    }
    return implode(",", $arr);
  }


  public function from(){
    return " FROM " . $this->entity->sna_() . "
";
  }

  public function limit($page = 1, $size = false){
    if ($size) {
      return " LIMIT {$size} OFFSET " . ( ($page - 1) * $size ) . "
";
    }
    return "";
  }



  public function conditionAll(Render $render = null, $connect = "WHERE") { //definir todas las condiciones

    /**
     * $condition =
     *   "advanced": array de condiciones avanzadas
     *     array (["field","option","value"])
     *   "search": string de busqueda simple
     *   "historic": busqueda de datos historicos
     *   ""
     */
    $sqlCond = concat($this->conditionSearch($render->search), $connect);
    $sqlCond .= concat($this->conditionAdvanced($render->advanced), " AND", $connect, $sqlCond);
    $sqlCond .= concat($this->conditionHistory($render->history), " AND", $connect, $sqlCond);
    $sqlCond .= concat($this->conditionAux(), " AND", $connect, $sqlCond);
    return $sqlCond;
  }

  public function conditionUniqueFields(array $params){ //filtrar campos unicos y definir condicion
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     * campos unicos compuestos:
     *   cada campo unico compuesto poseera una entrada en el array $params con una llave (identificacion) y un valor que sera un array de valores
     *   si se desea trabajar con campos unicos compuestos, es necesario sobrescribir este metodo
     *   no definir campos unicos compuestos en la entidad en el metodo getFieldsUnique de Entity. El resto de las funciones que lo utilizan, pueden no estar preparados para soportarla
     *   if($key == "identificacion_campo_unico_compuesto")
     *     if(!empty($value)) {
     *       $advancedSearhCompound = []
     *       foreachconditionUniqueFields ($value as $k => $v) array_push($advancedSearhCompound, [$k, "=", $v]);
     *       array_push($advancedSearch, $advancedSearhCompound);
     *     }
     *   }
     */
    $uniqueFields = $this->entity->getFieldsUnique();

    $advancedSearch = array();
    foreach($params as $key => $value){
      foreach($uniqueFields as $field){
        if($key == $field->getName()) {
          if(!empty($value)) array_push($advancedSearch, [$key, "=", $value, "or"]);
        }
      }
    }
    return $this->conditionAdvanced($advancedSearch);
  }




  //Definir sql de campos
  public function fieldsFull(){ return $this->fields(); } //sobrescribir si existen relaciones


  //Definir sql con campos auxiliares
  public function fieldsAux() { return $this->_fieldsAux(); }
  public function _fieldsAux() { return ""; }

  //Definir sql con cadena de relaciones fk y u_
  public function join(){ return ""; } //Sobrescribir si existen relaciones fk u_


  public function _join($field, $fromTable){ //definir relacion
    /**
     * Por defecto define una relacion simple utilizando LEFT JOIN
     * Este método puede ser sobrescrito para dar soporte a campos derivados complejos
     */
    $t = $this->prt();
    return "LEFT OUTER JOIN {$this->entity->sn_()} AS $t ON ($fromTable.$field = $t.{$this->entity->getPk()->getName()})
";
  }

  //inner join basico (este metodo esta pensado para armar consultas desde la entidad actual)
  public function innerJoin($field, $table){
    $p = $this->prf();
    $t = $this->prt();
    return "INNER JOIN {$table} AS {$p}{$table} ON ({$p}{$table}.$field = $t.{$this->entity->getPk()->getName()})
";
  }

  //inner join basico desde la tabla actual (este metodo esta pensado para armar consultas desde otra entidad)
  public function _innerJoin($field, $fromTable){
    $t = $this->prt();
    return "INNER JOIN {$this->entity->sn_()} AS $t ON ($fromTable.$field = $t.{$this->entity->getPk()->getName()})
";
  }

  //Por defecto define una relacion simple utilizando LEFT JOIN pero este metodo puede ser sobrescrito para definir relaciones mas complejas e incluso decidir la relacion a definir en funcion del prefijo
  public function _joinR($field, $fromTable){
    $t = $this->prt();
    return "LEFT OUTER JOIN {$this->entity->sn_()} AS $t ON ($fromTable.{$this->entity->getPk()->getName()} = $t.$field)
";
  }

  //Por defecto define una relacion simple utilizando LEFT JOIN pero este metodo puede ser sobrescrito para definir relaciones mas complejas e incluso decidir la relacion a definir en funcion del prefijo
  public function _innerJoinR($field, $fromTable){
    $t = $this->prt();
    return "INNER JOIN {$this->entity->sn_()} AS $t ON ($fromTable.{$this->entity->getPk()->getName()} = $t.$field)
";
  }

  //Definir sql con relacion auxiliar
  //Utilizada generalmente para restringir visualización, CUIDADO CON LA PERSISTENCIA!!! Las restricciones de visualización son también aplicadas al persistir, pudiendo no tener el efecto deseado.
  public function joinAux() { return $this->_joinAux(); }
  public function _joinAux() { return ""; }

  //Ordenamiento de cadena de relaciones
  protected function orderDefault(){   //ordenamiento por defecto
    /**
     * por defecto se definen los campos principales nf de la tabla principal
     * Si se incluyen campos de relaciones, asegurarse de incluir las relaciones
     * TODO: El ordenamiento no deberia formar parte de las entidades de generacion de sql?
     */
    $fields = $this->entity->getFieldsNf();
    $orderBy = array();

    foreach($fields as $field){
      if($field->isMain()){
        $orderBy[$field->getName()] = "asc";
      }
    }

    return $orderBy;
  }

  protected function initOrder(array $order) {
    $orderDefault = $this->orderDefault();
    foreach($order as $key => $value){
      if(array_key_exists($key, $orderDefault)){
        unset($orderDefault[$key]);
      }
    }

    return array_merge($order, $orderDefault);
  }


  public function orderBy(array $order = null){
    $order = $this->initOrder($order);
    return $this->order($order);
  }

  public function order(array $order = null){
    $sql = '';

    if(strpos(DATA_DBMS, 'my') !== false) {
      foreach($order as $key => $value){
        $value = ((strtolower($value) == "asc") || ($value === true)) ? "asc" : "desc";
        $sql_ = "{$this->mappingField($key)} IS NULL, {$this->mappingField($key)} {$value}";
        $sql .= concat($sql_, ', ', ' ORDER BY', $sql);
      }
    } else {
      foreach($order as $key => $value) {
        $value = ((strtolower($value) == "asc") || ($value === true)) ? "asc" : "desc";
        if($value == "desc") $value = "desc NULLS LAST";
        $sql .= concat("{$key} {$value}", ', ', ' ORDER BY', $sql);
      }
    }

    return $sql;
  }


  //Ordenamiento de cadena de relaciones (metodo nuevo no independiente que reemplazara al orderBy, el orderBy sera redefinido en las subclases para facilitar el soporte a pg
  protected function _order($field, $value){
    $value = ((strtolower($value) == "asc") || ($value === true)) ? "asc" : "desc";
    return "{$field} IS NULL, {$field} {$value}";
  }



  public function isUpdatable(array $row){
    try {
      $row_ = $this->initializeUpdate($row);
      $this->format($row_);
      return true;
    } catch(Exception $exception){
      return $exception->getMessage();
    }
  }

  public function isInsertable(array $row){
    try {
      $row_ = $this->initializeInsert($row);
      $this->format($row_);
      return true;
    } catch(Exception $exception){
      return $exception->getMessage();
    }
  }


  public function having(array $having = null) { //busqueda avanzada

    /**
     * Array $advanced:
     *  [
     *    0 => "field"
     *    1 => "=", "!=", ">=", "<=", "<", ">", "=="
     *    2 => "value" array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array)
     *    3 => "AND" | "OR" | null (opcional, por defecto AND)
     *  ]
     *  Array(
     *    Array("field" => "field", "value" => array|string|int|boolean|date (si es null no se define busqueda, si es un array se definen tantas busquedas como elementos tenga el array) [, "option" => "="|"=~"|"!="|"<"|"<="|">"|">="|true (no nulos)|false (nulos)][, "mode" => "and"|"or"]
     *    ...
     *  )
     *  )
     */
    if(empty($having)) return "";
    $conditionMode = $this->havingRecursive($having);
    return $conditionMode["condition"];
  }


  private function havingRecursive(array $having){

    /**
     * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
     * "condition": SQL
     * "mode": Concatenacion de condiciones "AND" | "OR"
     */

    if(is_array($having[0])) return $this->havingIterable($having);
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */

    $option = (empty($having[1])) ? "=" : $having[1]; //por defecto se define "="
    $value = (!isset($having[2])) ? null : $having[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($having[3])) ? "AND" : $having[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->_conditionFieldAux($having[0], $option, $value);
    if(!$condicion) $condicion = $this->havingValue($having[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    return ["condition" => $condicion, "mode" => $mode];
  }


  private function havingIterable(array $having) {
    $hav = array();

    $condition = "";
    for($i = 0; $i < count($having); $i++) {
      $h = $this->havingRecursive($having[$i]);
      if($i == 0) $mode = $h["mode"];
      if(!empty($condition)) $condition .= $h["mode"] . " ";
      $condition .= $h["condition"];
    }

    return ["condition" => "({$condition})", "mode" => $mode];
  }

  protected function havingValue($field, $option, $value){
    if(!is_array($value)) {
      return $this->_conditionFieldAggregate($field, $option, $value);
    }

    $condition = "";
    $cond = false;

    foreach($value as $v){
      if($cond) {
        if($option == "=") $condition .= " OR ";
        elseif($option == "!=") $condition .= " AND ";
        else throw new Exception("Error al definir opción");
      } else $cond = true;
      $condition .= $this->_conditionFieldAggregate($field, $option, $v);
    }

    return "(".$condition.")";
  }
}
