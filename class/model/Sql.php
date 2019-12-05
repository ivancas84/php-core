<?php

require_once("function/snake_case_to.php");
require_once("function/concat.php");
require_once("function/settypebool.php");
require_once("class/model/db/Interface.php");

/**
 * Se define el prefijo _ para indicar que el metodo no define relaciones
 * Los métodos _  habitulamente utilizan el atributo prefix para poder relacionarse con otras entidades
 * Los métodos no _ habitualmente accede a otras entidades para definir codigo
 */
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
  public function __construct(){
    $this->db = Dba::dbInstance();
    $this->format = SqlFormat::getInstance();
  }

  public static function getInstanceString($entity, $prefix = NULL) { //crear instancias de sql
    /**
     * sql, a diferencia de sus pares entity y sqlo, no puede ser implementada como singleton porque utiliza prefijos de identificacion
     */
    $sqlName = snake_case_to("XxYy", $entity) . "Sql";
    $sql = new $sqlName;
    if($prefix) $sql->prefix = $prefix;
    return $sql;
  }

  final public static function getInstance($prefix = null) {
    $className = get_called_class();
    $sql = new $className;
    if($prefix) $sql->prefix = $prefix;
    return $sql;    
  }

  final public static function getInstanceRequire($entity, $prefix = null) {    
    require_once("class/model/sql/" . snake_case_to("xxYy", $entity) . "/" . snake_case_to("XxYy", $entity) . ".php");
    $className = snake_case_to("XxYy", $entity) . "Sql";
    return call_user_func_array("{$className}::getInstance", [$prefix]);
  }

  public function prf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; }   //prefijo fields
  public function prt(){ return (empty($this->prefix)) ?  $this->entity->getAlias() : $this->prefix; } //prefijo tabla
  public function initializeInsert(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //inicializar valores para insercion
  public function initializeUpdate(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //inicializar valores para actualizacion
  public function format(array $row) { throw new BadMethodCallException ("Metodo abstracto no implementado"); } //formato de sql
  public function _json(array $row) { throw new BadMethodCallException("No implementado"); }

  public function formatIds(array $ids = []) {
    /**
     * Formato sql de ids
     */
    $ids_ = [];
    for($i = 0; $i < count($ids); $i++) {
      $r = $this->format(["id"=>$ids[$i]]);
      array_push($ids_, $r["id"]);
    }
    return implode(', ', $ids_);
  }

  public function _mappingField($field) {
    /**
     * Mapeo de fields de la entidad (todos los fields)
     * Recorre relaciones (si existen)
     */
    if($field_ = $this->_mappingFieldStruct($field)) return $field_;
    if($field_ = $this->_mappingFieldAggregate($field)) return $field_;
    if($field_ = $this->_mappingFieldDefined($field)) return $field_;
  }

  public function mappingField($field){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     * Recorre relaciones (si existen)
     */
    if($field_ = $this->_mappingField($field)) return $field_;
    //throw new Exception("Campo no reconocido");
  }

  public function _mappingFieldStruct($field){ throw new BadMethodCallException("Not Implemented"); } //traduccion local de campos
  public function _mappingFieldAggregate($field){ return null; } //traduccion local de campos de agregacion
  
  public function _mappingFieldDefined($field){
    /**
     * Traduccion local de campos generales
     */
    switch ($field) {
      case "_count": return "COUNT(*)";
      case "_identifier":
        if(empty($this->entity->getIdentifier())) throw new Exception ("Identificador no definido en la entidad ". $this->entity->getName()); 
        $identifier = [];
        foreach($this->entity->getIdentifier() as $id) array_push($identifier, $this->mappingField($id));
        return "CONCAT_WS(\"". UNDEFINED . "\"," . implode(",", $identifier) . ")
";
    }
  }

  public function condition(Render $render) { 
    /**
     * busqueda avanzada considerando relaciones
     */
    $condition = array_merge($render->condition, $render->generalCondition);

    /**
     * Array $advanced:
     *    [
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
    if(empty($condition)) return "";
    $conditionMode = $this->conditionRecursive($condition);
    return $conditionMode["condition"];
  }

  public function _condition(Render $render) {
    /**
     * Busqueda avanzada sin considerar relaciones
     * A diferencia del metodo que recorre relaciones, _condition no genera error si la condicion no existe
     */
    if(empty($render->getCondition())) return "";
    $conditionMode = $this->_conditionRecursive($render->getCondition());
    if (empty($conditionMode)) return "";
    return $conditionMode["condition"];
  }

  private function conditionRecursive(array $condition){
    /**
     * Metodo recursivo para definir condiciones avanzada (considera relaciones)
     * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
     * "condition": SQL
     * "mode": Concatenacion de condiciones "AND" | "OR"
     */

    if(is_array($condition[0])) return $this->conditionIterable($condition);
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */

    $option = (empty($condition[1])) ? "=" : $condition[1]; //por defecto se define "="
    $value = (!isset($condition[2])) ? null : $condition[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($condition[3])) ? "AND" : $condition[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->conditionField($condition[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    return ["condition" => $condicion, "mode" => $mode];
  }

  private function _conditionRecursive(array $advanced){
    /**
     * Metodo recursivo para definir condicines avanzadas (no considera relaciones)
     * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
     * "condition": SQL
     * "mode": Concatenacion de condiciones "AND" | "OR"
     */
    if(is_array($advanced[0])) return $this->_conditionIterable($advanced);
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */

    $option = (empty($advanced[1])) ? "=" : $advanced[1]; //por defecto se define "="
    $value = (!isset($advanced[2])) ? null : $advanced[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($advanced[3])) ? "AND" : $advanced[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->_conditionField($advanced[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    
    if(empty($condicion)) return "";
    return ["condition" => $condicion, "mode" => $mode];
  }

  private function conditionIterable(array $advanced) { 
    /**
     * metodo de iteracion para definir condiciones avanzadas (considera relaciones)
     */
    $conditionModes = array();

    for($i = 0; $i < count($advanced); $i++){
      $conditionMode = $this->conditionRecursive($advanced[$i]);
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

  private function _conditionIterable(array $advanced) {
    /**
     * metodo de iteracion para definir condiciones avanzadas (no considera relaciones)
     */
    $conditionModes = array();

    for($i = 0; $i < count($advanced); $i++){
      $conditionMode = $this->_conditionRecursive($advanced[$i]);
      if(empty($conditionMode)) continue;
      array_push($conditionModes, $conditionMode);
    }

    if(empty($conditionModes)) return "";

    $condition = "";
    foreach($conditionModes as $cm){
      if(empty($cm)) continue;
      $modeReturn = $cm["mode"];
      break;
    }

    foreach($conditionModes as $cm){
      if(empty($cm)) continue;
      $mode = $cm["mode"];
      if(!empty($condition)) $condition .= $mode . " ";
      $condition.= $cm["condition"];
    }

    return ["condition"=>"(".$condition.")", "mode"=>$modeReturn];
  }

  protected function conditionField($field, $option, $value){
    /**
     * se verifica inicialmente la condicion auxiliar. 
     * las condiciones auxiliares no siguen la estructura definida de condicion
     */    
    $condition = $this->conditionFieldAux($field, $option, $value);
    if($condition) return $condition;
    
    if(!is_array($value)) {
      $condition = $this->conditionFieldStruct($field, $option, $value);
      if($condition) return $condition;

      $condition = $this->conditionFieldHaving($field, $option, $value);
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
      $condition .= $condition_;
    }

    return "(".$condition.")";
  }

  protected function _conditionField($field, $option, $value) {
    /**
     * se verifica inicialmente la condicion auxiliar
     * las condiciones auxiliares no siguen la estructura definida de condicion
     */
    $condition = $this->_conditionFieldAux($field, $option, $value);
    if($condition) return $condition;
    
    if(!is_array($value)) {
      $condition = $this->_conditionFieldStruct($field, $option, $value);
      if(!$condition) $condition = $this->_conditionFieldHaving($field, $option, $value);
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

      $condition_ = $this->_conditionFieldStruct($field, $option, $v);
      if(!$condition_) return "";
      $condition .= $condition_;
    }

    if(empty($condition)) return "";
    return "(".$condition.")";
  }

  protected function _conditionFieldStruct($field, $option, $value) { 
    $p = $this->prf();

    switch($field){
      case "_identifier":
        /**
         * utilizar solo como condicion general
         * El identificador se define a partir de campos de la entidad principal y de entidades relacionadas
         * No utilizar prefijo para su definicion
         */
        $f = $this->mappingField("_identifier");
        return $this->format->conditionText($f, $value, $option);
      break;
      
      case $p."_search": 
        /**
         * define la misma condicion y valor para todos los campos de la entidad
        */
        return $this->_conditionSearch($option, $value);;
      break;
    }
  }

  protected function _conditionFieldHaving($field, $option, $value) { 
    //@TODO soporte para otro tipo de campos actualmente funciona con numeros
    $field = $this->_mappingField($field);
    return (empty($field))? "" : $this->format->conditionNumber($field, $value, $option);
  }

  protected function conditionFieldHaving($field, $option, $value){
    /**
     * Condicion de agregacionavanzada principal
     * Define una condicion avanzada que recorre todos los metodos independientes de condicion avanzadas de las tablas relacionadas
     * La restriccion de conditionFieldStruct es que $value no puede ser un array, ya que definirá un conjunto de condiciones asociadas
     * Si existen relaciones, este metodo debe reimplementarse para contemplarlas
     */
    if($c = $this->_conditionFieldHaving($field, $option, $value)) return $c;
  }
  
  protected function conditionFieldStruct($field, $option, $value){
    /**
     * Condicion avanzada principal
     * Define una condicion avanzada que recorre todos los metodos independientes de condicion avanzadas de las tablas relacionadas
     * La restriccion de conditionFieldStruct es que $value no puede ser un array, ya que definirá un conjunto de condiciones asociadas
     * Si existen relaciones, este metodo debe reimplementarse para contemplarlas
     */
    if($c = $this->_conditionFieldStruct($field, $option, $value)) return $c;
  }
  
  protected function conditionFieldAux($field, $option, $value) {
    /**
     * Condicion de field auxiliar (considera relaciones si existen)
     * Se sobrescribe si tiene relaciones
     */
    if($c = $this->_conditionFieldAux($field, $option, $value)) return $c;
  }

  protected function _conditionFieldAux($field, $option, $value){
    $p = $this->prf();

    switch($field){
      case "_compare": 
      /** USO SOLO COMO CONDICION GENERAL */  
      $f1 = $this->mappingField($value[0]);
        $f2 = $this->mappingField($value[1]);
        return "({$f1} {$option} {$f2})";
      break;

      case "_count": //campo de agregacion general: "_count" (no debería ser estructural?)
        $f = $this->mappingField($field);
        return $this->format->conditionNumber($f, $value, $option);
      break;
    }
  }

  public function _fields(){ throw new BadMethodCallException("Not Implemented"); } //Definir sql con los campos de la entidad (exclusivos y generales)
  
  public function _fieldsDb(){ throw new BadMethodCallException("Not Implemented"); } //Definir sql con los campos de la entidad (solo exclusivos)
  
  public function fieldId(){ return $this->entity->getAlias() . "." . $this->entity->getPk()->getName(); } //Se define el identificador en un metodo independiente para facilitar la reimplementacion para aquellos casos en que el id tenga un nombre diferente al requerido, para el framework es obligatorio que todas las entidades tengan una pk con nombre "id"



  public function from(){
    return " FROM " . $this->entity->sna_() . "
";
  }

  public function fromSubSql(Render $render){
    $t = $this->prt();    
    return " FROM (


" . $this->_subSql($render) . "


) AS {$t}
";
  }

  public function _from(){
    $t = $this->prt();    
    return " FROM " . $this->entity->sn_() . " AS {$t}
";
  }

  public function limit($page = 1, $size = false){
    if ($size) {
      return " LIMIT {$size} OFFSET " . ( ($page - 1) * $size ) . "
";
    }
    return "";
  }

  protected function _conditionSearch($option, $value){
    if(($option != "=~")) throw new Exception("Opción no permitida para condición " . $this->entity->getName("XxYy") . "Sql._conditionSearch([\"_search\",\"{$option}\",\"{$value}\"])");
    //condicion estructurada de busqueda que involucra a todos los campos
    $conditions = [];
    foreach($this->entity->getFields() as $field){
      $c = $this->_conditionFieldStruct($this->prf().$field->getName(),$option,$value);
      array_push($conditions, $c);
    }

    return implode(" OR ", $conditions);
  }

  public function conditionUniqueFields(array $params){
    /**
     * Filtrar campos unicos y definir condicion
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

    $render = new Render();
    $render->setCondition($advancedSearch);
    return $this->condition($render);
  }

  public function fields(){
    /**
     * Definir sql de campos
     * Sobrescribir si existen relaciones
     */
    return $this->_fields(); 
  }



  public function join(Render $render){ return ""; } //Sobrescribir si existen relaciones fk u_

  public function _join($field, $fromTable, Render $render){
    /**
     * Definir relacion como subconsulta
     * En funcion del campo pasado como parametro define una relacion
     * Por defecto define una relacion simple utilizando LEFT JOIN
     * Este método puede ser sobrescrito para dar soporte a campos derivados
     */
    $t = $this->prt();
    return "LEFT OUTER JOIN (


      " . $this->_subSql($render) . "


) AS $t ON ($fromTable.$field = $t.{$this->entity->getPk()->getName()})
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


  public function having($render) { //busqueda avanzada
    $condition = $render->getHaving();
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
    if(empty($condition)) return "";
    $conditionMode = $this->conditionRecursive($condition);
    return $conditionMode["condition"];
  }

  public function _having($render) { //busqueda avanzada
    $condition = $render->getHaving();
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
    $conditionMode = $this->_conditionRecursive($condition);
    return $conditionMode["condition"];
  }


  public function _subSql(Render $render){ //subconsulta sql (en construccion)
 return "SELECT DISTINCT
{$this->_fieldsDb()}
{$this->_from($render)}
" . concat($this->_condition($render), 'WHERE ') . "
";
  }
}