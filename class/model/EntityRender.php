<?php

require_once("function/to_string.php");


class EntityRender {

  public $container;
  public $entityName; //entidad principal a la que esta destinada la consulta
  public $condition = array(); //array multiple cuya raiz es [field,option,value], 
  /**
   * ejemplo:  [
   *    ["nombre","=","unNombre"],
   *    [
   *      ["apellido","=","unApellido"],
   *      ["apellido","=","otroApellido","OR"]
   *    ]
   * ]
   */  
  public $order = array();
  public $page = 1;
  public $size = 100;

  protected $fields = array(); //campos
  /**
   * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
   * . indica aplicacion de funcion de agregacion
   * - indica que pertenece a una relacion
   * Ej ["nombres", "horas_catedra.sum", "edad.avg", "com_cur-horas_catedra]
   */

  protected $groupConcat = array(); //campos a los cuales se aplica group_concat
  /**
   * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
   * . indica aplicacion de funcion de agregacion
   * - indica que pertenece a una relacion
   * Ej ["telefono", "nombres"], se traduce en  GROUP_CONCAT(DISTINCT telefono SEPARATOR ', ' ) AS telefono
   */

  protected $group = array(); //campos de agrupacion
  /**
   * Deben ser campos de consulta
   * Ej ["profesor", "cur_horario"]
   */

  protected $having = array(); //condicion avanzada de agrupamiento, similiar a condicion avanzadas
  /**
   * array multiple cuya raiz es [field,option,value], ejemplo: [["nombre","=","unNombre"],[["apellido","=","unApellido"],["apellido","=","otroApellido","OR"]]]
   */

  public static function getInstance($render = null){
    /**
     * @param String | Object | Array | EntityRender En función del tipo de parámetro define el render
     * @return EntityRender Clase de presentacion
     */
    if(gettype($render) == "object") return $render;

    $r = new EntityRender();
    if(gettype($render) == "string") $r->setCondition(["_search","=~",$render]);
    elseif (gettype($render) == "array") $r->setCondition($render);
    return $r;
  }

  public static function getInstanceDisplay(array $display = null){
    /**
     * Instanciar render a partir de un display
     * Importante: Define las condiciones y parametros como condiciones generales
     */
    $render = new EntityRender;
    $render->display($display);
    return $render;
  }

  public function display(array $display = []){
    if(isset($display["size"])) $this->setSize($display["size"]);
    /**
     * puede ser 0 o false para indicar todas las filas
     */
    if(!empty($display["page"])) $this->setPage($display["page"]);
    if(!empty($display["order"])) $this->setOrder($display["order"]);
    if(!empty($display["condition"])) $this->setCondition($display["condition"]);
    if(!empty($display["params"])) $this->setParams($display["params"]);
    if(!empty($display["fields"])) $this->setFields($display["fields"]);
    if(!empty($display["group"])) $this->setGroup($display["group"]);
    if(!empty($display["having"])) $this->setHaving($display["having"]);
    return $this;
  }

  public static function getInstanceParams(array $params = null){
    $render = new EntityRender;
    if(!empty($params)) $render->setParams($params);
    return $render;
  }

  public function cond ($condition = null) { 
    if(!empty($condition)) {
      array_push ( $this->condition, $condition );
    }
    return $this;
  }

  public function param($key, $value) { 
    return $this->cond([$key, "=", $value]); 
  }

  public function setCondition (array $condition = null) { 
    $this->condition = $condition; 
    return $this;
  }

  public function addCondition ($condition = null) { 
    if(!empty($condition)) {
      array_push ( $this->condition, $condition );
    }
    return $this;
  }

  


  public function getCondition(){ return $this->condition; }
  
  public function setParams (array $params = []) {
    foreach($params as $key => $value) {
      $this->addCondition([$key, "=", $value]); 
    }
    return $this;
  } 
  
  public function addParam ($key, $value) { return $this->cond([$key, "=", $value]); }


  public function setOrder (array $order) { 
    $this->order = $order;
    return $this;
  }
  /**
   * Ordenamiento
   * @param array $order Ordenamiento
   *  array(
   *    nombre_field => asc | desc,
   *  )
   */
  public function getOrder(){ return $this->order; }

  public function setPagination($size, $page) {
    $this->size = $size;
    $this->page = $page;
    return $this;
  }

  public function setSize($size) { 
    $this->size = $size; 
    return $this;
  }
  public function getSize(){ return $this->size; }

  public function setPage($page) { 
    $this->page = $page; 
    return $this;
  }
  public function getPage(){ return $this->page; }
  
  public function fieldAdd (array $fields = null) {
    $this->fields = array_merge($this->fields, $fields);
    return $this;
  }

  /**
   * Asigna el arbol de field
   * Directamente reemplaza todo el array de fields, usar previo a fieldAdd
   */
  public function fieldTree(){
    $this->fields = $this->container->getEntityTools($this->entityName)->fieldNames();
    return $this;
  }

  public function setFields (array $fields = null) { 
    $this->fields = $fields;
    return $this;
  }
  public function addFields (array $fields = null) { 
    $this->fields = array_unique(array_merge($this->fields, $fields)); 
    return $this;
  }
  public function getFields () { return $this->fields; }

  public function setGroup (array $group = null) { 
    $this->group = $group;
    return $this;
  }
  public function addGroup (array $group = null) { 
    $this->group = array_unique(array_merge($this->group, $group)); 
    return $this;
  }
  public function getGroup () { return $this->group; }

  public function setHaving (array $having = null) { 
    $this->having = $having; 
    return $this;
  }
  public function getHaving () { return $this->having; }

  public function setEntityName (array $entityName = null) { 
    $this->entityName = $entityName; 
    return $this;
  }
  public function getEntityName () { return $this->entityName; }

  public function addPrefixRecursive(array &$condition, $prefix){
    if(!key_exists(0, $condition)) return;
    if(is_array($condition[0])) {
      foreach($condition as &$value) $this->addPrefixRecursive($value,$prefix);  
    } else {
        $condition[0] = $prefix.$condition[0];
    }
    return $this;
  }

  public function addPrefix($prefix){
    $this->addPrefixRecursive($this->condition, $prefix);
    
    foreach($this->order as $k=>$v){
      $this->order[$prefix.$k] = $v;
      unset($this->order[$k]);
    }
    return $this;
  }

  public function removePrefixRecursive(array &$condition, $prefix){
    if(!key_exists(0, $condition)) return;
    if(is_array($condition[0])) {
      foreach($condition as &$value) $this->removePrefixRecursive($value,$prefix);  
    } else {
      $count = 1;
      $condition[0] = str_replace($prefix, '', $condition[0], $count);
    }
    return $this;
  }

  public function removePrefix($prefix){
    $this->removePrefixRecursive($this->condition, $prefix);
    
    foreach($this->order as $k=>$v){
      $count = 1;
      $newk = str_replace($prefix, '', $k, $count);
      $this->order[$newk] = $v;
      unset($this->order[$k]);
    }
    return $this;
  }

  public function setConditionUniqueFields(array $params){
    /**
     * definir condicion para campos unicos
     * $params:
     *   array("nombre_field" => "valor_field", ...)
     * los campos unicos simples se definen a traves del atributo Entity::$unique
     * los campos unicos multiples se definen a traves del atributo Entity::$uniqueMultiple
     */
    $uniqueFields = $this->container->getEntity($this->entityName)->unique;
    $uniqueFieldsMultiple = $this->container->getEntity($this->entityName)->uniqueMultiple;

    $condition = array();
    if(array_key_exists("id",$params) && !empty($params["id"])) array_push($condition, ["id", "=", $params["id"]]);

    foreach($uniqueFields as $field){
      foreach($params as $key => $value){
        if(($key == $field) && !empty($value)) {
          array_push($condition, [$key, "=", $value, "or"]);
        }
      }
    }

    if($uniqueFieldsMultiple) {
      $conditionMultiple = [];
      $first = true;
      $existsConditionMultiple = true; //si algun campo de la condicion multiple no se encuentra definido,  se carga en true.
      foreach($uniqueFieldsMultiple as $field){
        if(!$existsConditionMultiple) break;
        $existsConditionMultiple = false;
        
        foreach($params as $key => $value){
          if($key == $field) {
            $existsConditionMultiple = true;
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

      if($existsConditionMultiple && !empty($conditionMultiple)) array_push($condition, $conditionMultiple);
    }

    if(empty($condition)) throw new Exception("Error al definir condicion unica");

    $this->addCondition($condition);
    return $this;
  }


  public function column(){
    /**
     * Retorna la primera columna definida
     * @example $render->fieldAdd(["id"]);
     * @example $render->fieldAdd(["_count"]);
     */
    $sql = $this->sql();
    $result = $this->container->getDb()->query($sql);
    $response = $this->container->getDb()->fetch_all_columns($result, 0);
    $result->free();
    return $response;
  }

  public function columnOne(){
    /**
     * Retorna la primera columna definidas
     * @example $render->fieldAdd(["id"]);
     * @example $render->fieldAdd(["_count"]);
     */
    $response = $this->column();
    if(count($response) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($response) == 1) return $response[0];
    else throw new Exception("La consulta no arrojó resultados");
  }

  public function all(){
    /**
     * consulta avanzada
     * Reduce la cantidad de campos a consultar
     * No se debe utilizar storage
     */
    $sql = $this->sql();
    $result = $this->container->getDb()->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;    
  }

  public function one(){
    /**
     * consulta avanzada
     * Reduce la cantidad de campos a consultar
     * No se debe utilizar storage
     */
    $response = $this->all();
    if(count($response) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($response) == 1) return $response[0];
    else throw new Exception("La consulta no arrojó resultados");
  }


  /**
   * Definir SQL
   */
  public function sql() {
    $fieldsQuery = $this->fieldsQuery();
    $group = $this->groupBy();
    $having = $this->container->getControllerEntity("sql_condition", $this->entityName)->main($this->getHaving());    
    $condition = $this->container->getControllerEntity("sql_condition", $this->entityName)->main($this->condition);
    $order = $this->container->getControllerEntity("sql_order", $this->entityName)->main($this->getOrder());

    $sql = "SELECT DISTINCT
{$fieldsQuery}
{$this->from()}
{$this->join()}
" . concat($condition, 'WHERE ') . "
{$group}
" . concat($having, 'HAVING ') . "
{$order}
{$this->limit($this->getPage(), $this->getSize())}
";

    return $sql;
  }

  public function mapping($fieldName, $prefix = ""){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     */
    $map = $this->_mapping($fieldName, $prefix);
    return $map[0]->_($map[1]);
  }

  protected function _mapping($fieldName, $prefix = ""){
     /**
     * Interpretar prefijo y obtener mapping
     */
    $f = explode("-",$fieldName);
    if(count($f) == 2) {
      $prefix_ = (empty($this->prefix)) ? $f[0] : $prefix . "_" . $f[0];
      $entityName = $this->container->getEntityRelations($this->entityName)[$f[0]]["entity_name"];
      $mapping = $this->container->getMapping($entityName, $prefix_);
      $fieldName = $f[1];
    } else { 
      $mapping = $this->container->getMapping($this->entityName, $prefix);
    }

    return [$mapping,$fieldName];
  }

  protected function fieldsQuery(){
    $fields = array_merge($this->getGroup(), $this->getFields());

    $fieldsQuery_ = [];
    foreach($fields as $key => $fieldName){
      if(is_array($fieldName)){
        if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
        $map_ = [];
        foreach($fieldName as $fn){
          array_push($map_, $this->mapping($fn));
        } 
        $f = "CONCAT_WS(', ', " . implode(",",$map_) . ") AS " . $key;
      } else {
        $map = $this->_mapping($fieldName);
        $alias = (is_integer($key)) ? $map[0]->_pf() . str_replace(".","_",$map[1]) : $key;
        $f = $map[0]->_($map[1]) . " AS " . $alias;
      }
      array_push($fieldsQuery_, $f);
    }

    return implode(', ', $fieldsQuery_);
  }


  protected function groupBy(){
    $fields = $this->getGroup();

    $group_ = [];
    foreach($fields as $key => $fieldName){
      if(is_array($fieldName)){
        if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
        $f = $key;
      } else {
        $map = $this->_mapping($fieldName);
        $f = (is_integer($key)) ? $map[0]->_pf() . str_replace(".","_",$map[1]) : $key;
      }
      array_push($group_, $f);
    }

    return empty($group_) ? "" : "GROUP BY " . implode(", ", $group_) . "
";
  }

  protected function join(){
    $sql = "";
    $tree = $this->container->getEntityTree($this->entityName);
    $this->joinFk($tree, "", $sql);
    return $sql;
  }

  protected function joinfk(array $tree, $tablePrefix, &$sql){
    if (empty ($tablePrefix)) $tablePrefix = $this->container->getEntity($this->entityName)->getAlias();

    foreach ($tree as $prefix => $value) {      
      $entitySn =  $this->container->getEntity($value["entity_name"])->sn_();
      $sql .= $this->_join($entitySn, $value["field_name"], $tablePrefix, $prefix) . "
";

      if(!empty($value["children"])) $this->joinfk($value["children"], $prefix, $sql);
    }
  }

  protected function limit($page = 1, $size = false){
    if ($size) {
      return " LIMIT {$size} OFFSET " . ( ($page - 1) * $size ) . "
";
    }
    return "";
  }

  protected function from(){    
    return " FROM 

" . $this->container->getEntity($this->entityName)->sn_() . "

 AS {$this->container->getEntity($this->entityName)->getAlias()}
";
  }

  /**
   * Definir SQL de relacion 
   */
  protected function _join($entitySn, $field, $fromTable, $table){
    return "LEFT OUTER JOIN " . $entitySn . " AS $table ON ($fromTable.$field = $table.id)
";
  }

}
