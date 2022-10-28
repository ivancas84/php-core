<?php

require_once("function/to_string.php");


class EntityQuery {

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

  public function display(array $display = []){
    if(isset($display["size"])) $this->size($display["size"]);
    /**
     * puede ser 0 o false para indicar todas las filas
     */
    if(!empty($display["page"])) $this->page($display["page"]);
    if(!empty($display["order"])) $this->order($display["order"]);
    if(!empty($display["condition"])) $this->cond($display["condition"]);
    if(!empty($display["params"])) $this->params($display["params"]);
    if(!empty($display["fields"])) $this->fields($display["fields"]);
    if(!empty($display["group"])) $this->group($display["group"]);
    if(!empty($display["having"])) $this->having($display["having"]);
    return $this;
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


  public function params (array $params = []) {
    foreach($params as $key => $value) {
      $this->cond([$key, "=", $value]); 
    }
    return $this;
  } 
  

  public function order (array $order) { 
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

  public function pagination($size, $page) {
    $this->size = $size;
    $this->page = $page;
    return $this;
  }

  public function size($size) { 
    $this->size = $size; 
    return $this;
  }

  public function page($page) { 
    $this->page = $page; 
    return $this;
  }
  
  /**
   * Carga de un unico field
   * No admite alias
   */
  public function field(string $field) {
    array_push($this->fields, $field);
    return $this;
  }

  public function fields(array $fields = null) {
    $this->fields = array_merge($this->fields, $fields);
    return $this;
  }

  /**
   * Asigna el arbol de field
   * Directamente reemplaza todo el array de fields, usar previo a fieldAdd
   */
  public function fieldsTree(){
    $this->fields = $this->container->tools($this->entityName)->fieldNames();
    return $this;
  }

  public function group(array $group = null) { 
    $this->group = array_merge($this->group, $group); 
    return $this;
  }

  public function having(array $having = null) { 
    if(!empty($having)) array_push ( $this->having, $having );
    return $this;
  }

  public function entityName (array $entityName = null) { 
    $this->entityName = $entityName; 
    return $this;
  }

  protected function addPrefixRecursive(array &$condition, $prefix){
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

  protected function removePrefixRecursive(array &$condition, $prefix){
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

  public function unique(array $params){
    /**
     * definir condicion para campos unicos
     * $params:
     *   array("nombre_field" => "valor_field", ...)
     * los campos unicos simples se definen a traves del atributo Entity::$unique
     * los campos unicos multiples se definen a traves del atributo Entity::$uniqueMultiple
     */
    $uniqueFields = $this->container->entity($this->entityName)->unique;
    $uniqueFieldsMultiple = $this->container->entity($this->entityName)->uniqueMultiple;

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

    $this->cond($condition);
    return $this;
  }


  /**
     * Retorna la columna indicada en el parametro
     * @example $render->fields(["id","nombres"])->column();
     * @example $render->fields(["_count"])->column();
     */
  public function column($number = 0){
    $sql = $this->sql();
    $result = $this->container->db()->query($sql);
    $response = $this->container->db()->fetch_all_columns($result, $number);
    $result->free();
    return $response;
  }

  /**
   * Similar a column pero retorna un valor, error si no existe
   */
  public function columnOne($number = 0){
    /**
     * Retorna la primera columna definidas
     * @example $render->fields(["id"]);
     * @example $render->fields(["_count"]);
     */
    $response = $this->column($number);
    if(count($response) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($response) == 1) return $response[0];
    else throw new Exception("La consulta no arrojó resultados");
  }

  /**
   * Similar a columnOne, null si no existe
   */
  public function columnOneOrNull($number = 0){
    /**
     * Retorna la primera columna definidas
     * @example $render->fields(["id"]);
     * @example $render->fields(["_count"]);
     */
    $response = $this->column($number);
    if(count($response) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($response) == 1) return $response[0];
    else return null;
  }

  /**
   * ejecucion del sql sin control adicional
   */
  public function all(){
    $sql = $this->sql();
    $result = $this->container->db()->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;    
  }

  /**
   * retornar el primer elemento de la consulta, error si la consulta no retorna elementos
   */
  public function first(){
    $sql = $this->sql();
    $result = $this->container->db()->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    if(empty($rows)) throw new Exception("La consulta no arrojó resultados");
    return $rows[0];    
  }

  /**
   * retornar el primer elemento de la consulta, null si la consulta no retorna elementos
   */
  public function firstOrNull(){
    $sql = $this->sql();
    $result = $this->container->db()->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    if(empty($rows)) return null;
    return $rows[0];    
  }

  /**
   * consulta de un valor
   * error si la cantidad de elementos a retornar es distinto de 1
   */
  public function one(){
    $response = $this->all();
    if(count($response) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($response) == 1) return $response[0];
    else throw new Exception("La consulta no arrojó resultados");
  }

   /**
   * consulta de un valor
   * error si la cantidad de elementos es mayor a 1
   * null si la cantidad de elementos a retorar es 0
   */
  public function oneOrNull(){
    $response = $this->all();
    if(count($response) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($response) == 1) return $response[0];
    else return null;
  }

  /**
   * Definir SQL
   */
  public function sql() {
    $fieldsQuery = $this->fieldsQuery();
    $group = $this->groupBy();
    $having = $this->container->controller("sql_condition", $this->entityName)->main($this->having);    
    $condition = $this->container->controller("sql_condition", $this->entityName)->main($this->condition);
    $order = $this->container->controller("sql_order", $this->entityName)->main($this->order);

    $sql = "SELECT DISTINCT
{$fieldsQuery}
{$this->from()}
{$this->join()}
" . concat($condition, 'WHERE ') . "
{$group}
" . concat($having, 'HAVING ') . "
{$order}
{$this->limit($this->page, $this->size)}
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
      $entityName = $this->container->relations($this->entityName)[$f[0]]["entity_name"];
      $mapping = $this->container->mapping($entityName, $prefix_);
      $fieldName = $f[1];
    } else { 
      $mapping = $this->container->mapping($this->entityName, $prefix);
    }

    return [$mapping,$fieldName];
  }

  protected function fieldsQuery(){
    $fields = array_merge($this->group, $this->fields);

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
    $group_ = [];
    foreach($this->group as $key => $fieldName){
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
    $tree = $this->container->tree($this->entityName);
    $this->joinFk($tree, "", $sql);
    return $sql;
  }

  protected function joinfk(array $tree, $tablePrefix, &$sql){
    if (empty ($tablePrefix)) $tablePrefix = $this->container->entity($this->entityName)->getAlias();

    foreach ($tree as $prefix => $value) {      
      $entitySn =  $this->container->entity($value["entity_name"])->sn_();
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

" . $this->container->entity($this->entityName)->sn_() . "

 AS {$this->container->entity($this->entityName)->getAlias()}
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
