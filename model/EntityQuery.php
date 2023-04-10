<?php

require_once("function/to_string.php");
require_once("function/concat.php");



class EntityQuery {

    public Container $container;
    public string $entity_name; //entidad principal a la que esta destinada la consulta
    public array $condition = array(); //array multiple cuya raiz es [field,option,value], 
    /**
     * ejemplo:  [
     *    ["nombre","=","unNombre"],
     *    [
     *      ["apellido","=","unApellido"],
     *      ["apellido","=","otroApellido","OR"]
     *    ]
     * ]
     */  
    public array $order = array();
    public int $page = 1;
    public int $size = 100;

    
    protected array $fields = array(); //campos (array de strings)
    /**
     * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
     * . indica aplicacion de funcion de agregacion
     * - indica que pertenece a una relacion
     * Ej ["nombres", "horas_catedra.sum", "edad.avg", "com_cur-horas_catedra]
     */

    protected array $fields_concat = array(); //campos (array asociativo)
    /**
     * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
     * . indica aplicacion de funcion de agregacion
     * - indica que pertenece a una relacion
     * Ej ["nombres", "horas_catedra.sum", "edad.avg", "com_cur-horas_catedra]
     */

    
    protected array $str_agg = array(); //campos a los cuales se aplica str_agg
    /**
     * Array multiple definido por alias y los campos que se aplica str_agg
     * Deben estar definidos en el mapping field, se realizará la traducción correspondiente
     * . indica aplicacion de funcion de agregacion
     * - indica que pertenece a una relacion
     * [
     *    "alias" => ["field1","field2"] se traduce simiar a GROUP_CONCAT(DISTINCT field1_map, " ", field2_map) AS "alias"
     * ]
     * Para aplicar GROUP_CONCAT a un solo valor, se puede utilizar como alterna-
     * tiva la funcion de agregacion, por ejemplo persona.str_agg se traduce a GROUP_CONCAT(DISTINCT persona) 
     */

    /**
     * @todo Implementar group_concat como sqlorganize_py
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

      public function fields(array $fields = []) {
        if(empty($fields)) return $this->fieldsTree();
        $this->fields = array_merge($this->fields, $fields);
        return $this;
  }

    public function fields_concat(array $fields_concat = []): EntityQuery {
        $this->fields_concat = array_merge($this->fields_concat, $fields_concat);
        return $this;
    }

    /**
     * Asigna el arbol de field
     * Directamente reemplaza todo el array de fields, usar previo a fieldAdd
     */
    public function fieldsTree(){
        $this->fields = $this->container->tools($this->entity_name)->field_names();
        return $this;
    }

    public function group(array $group = null): EntityQuery { 
        $this->group = array_merge($this->group, $group); 
        return $this;
    }

    public function group_concat(array $group_concat = null): EntityQuery { 
        $this->group_concat = array_merge($this->group_concat, $group_concat);
        return $this;
    }

    public function str_agg(array $str_agg): EntityQuery {
        $this->str_agg = $str_agg;
        return $this;
    }


  public function having(array $having = null) { 
    if(!empty($having)) array_push ( $this->having, $having );
    return $this;
  }

  public function entity_name (array $entity_name = null) { 
    $this->entity_name = $entity_name; 
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

    /**
      * definir condicion para campos unicos
      * $params:
      *   array("nombre_field" => "valor_field", ...)
      * los campos unicos simples se definen a traves del atributo Entity::$unique
      * los campos unicos multiples se definen a traves del atributo Entity::$uniqueMultiple
      */
    public function unique(array $params){
        $uniqueFields = $this->container->entity($this->entity_name)->unique;
        $uniqueFieldsMultiple = $this->container->entity($this->entity_name)->unique_multiple;

        $condition = array();
        // if(array_key_exists("id",$params) && !empty($params["id"])) array_push($condition, ["id", "=", $params["id"]]);
        $first = true;

        foreach($uniqueFields as $field){
            foreach($params as $key => $value){
                if ($key == $field && $value){
                    if ($first) {
                        $con = OR_;
                        $first = false;
                    } else {
                        $con = AND_;    
                    }

                    array_push($condition, [$key, "=", $value, $con]);
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
                          $con = OR_;
                          $first = false;
                        } else {
                          $con = AND_;
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
    $sql_fields = $this->sql_fields();
    $group = $this->groupBy();
    $having = $this->condition($this->having);    
    $condition = $this->condition($this->condition);
    $order = $this->_order();
    $sql = "SELECT DISTINCT
{$sql_fields}
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


    protected function mapping($field_name){
        /**
         * Interpretar prefijo y obtener mapping
         */
        $f = $this->container->explode_field($this->entity_name, $field_name);
        $m = $this->container->mapping($f["entity_name"], $f["field_id"]);
        return [$m, $f["field_name"]];
    }

    protected function sql_fields(){
        $fields = array_merge($this->group, $this->fields);

        $sql_fields = [];
        
        foreach($fields as $key => $field_name){
            if(is_array($field_name)){
                if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
                $map_ = [];
                foreach($field_name as $fn){
                    $f = $this->container->explode_field($this->entity_name, $fn);
                    $m = $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]);
                    array_push($map_, $m);
                } 
                $f = "CONCAT_WS(', ', " . implode(",",$map_) . ") AS " . $key;
            } else {
                $f = $this->container->explode_field($this->entity_name, $field_name);
                $map = $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]);
                $prefix = (!empty($f["field_id"])) ? $f["field_id"] . "-" : "";
                $alias = (is_integer($key)) ? $prefix . $f["field_name"] : $key;
                $f = $map . " AS \"" . $alias . "\"";
            }
            array_push($sql_fields, $f);
        }

        foreach($this->str_agg as $alias => $field_names){
            if(is_integer($alias)) throw new Exception("Debe string de alias para llave de str_agg, ej ['alias'=>['field1','field2',...]]");
            if(!is_array($field_names)) throw new Exception("Definir array para valor str_agg, ej ['alias'=>['field1','field2',...]], para un valor puede utilizar field.str_agg");
            $map_ = [];
            foreach($field_names as $fn){
                $f = $this->container->explode_field($this->entity_name, $fn);
                $m = $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]);
                array_push($map_, $m);
            } 
            $f = "GROUP_CONCAT(DISTINCT " . implode(", ' ', ",$map_) . ") AS " . $alias;
            array_push($sql_fields, $f);
        }

        return implode(', 
        ', $sql_fields);
    }


  protected function groupBy(){
    $group_ = [];
    foreach($this->group as $key => $field_name){
      if(is_array($field_name)){
        if(is_integer($key)) throw new Exception("Debe definirse un alias para la concatenacion (key must be string)");
        $f = $key;
      } else {
        $f = $this->container->explode_field($this->entity_name, $field_name);
        $map = $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]);
      }
      array_push($group_, $map);
    }

    return empty($group_) ? "" : "GROUP BY " . implode(", ", $group_) . "
";
  }

  protected function join(){
    $sql = "";
    $tree = $this->container->tree($this->entity_name);
    $this->joinFk($tree, "", $sql);
    return $sql;
  }

  protected function joinFk(array $tree, $tablePrefix, &$sql){
    if (empty ($tablePrefix)) $tablePrefix = $this->container->entity($this->entity_name)->getAlias();

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

" . $this->container->entity($this->entity_name)->sn_() . "

 AS {$this->container->entity($this->entity_name)->getAlias()}
";
  }

  /**
   * Definir SQL de relacion 
   */
  protected function _join($entitySn, $field, $fromTable, $table){
    return "LEFT OUTER JOIN " . $entitySn . " AS $table ON ($fromTable.$field = $table.id)
";
  }


  protected function condition($condition){
    if(empty($condition)) return "";
    $conditionMode = $this->conditionRecursive($condition);
    return $conditionMode["condition"];
  }

  /**
   * Metodo recursivo para definir condiciones avanzada (considera relaciones)
   * Para facilitar la definicion de condiciones, retorna un array con dos elementos:
   * "condition": SQL
   * "mode": Concatenacion de condiciones "AND" | "OR"
   */
  protected function conditionRecursive(array $condition){
    /**
     * si en la posicion 0 es un string significa que es un campo a buscar, caso contrario es un nuevo conjunto (array) de campos que debe ser recorrido
     */
    if(is_array($condition[0])) return $this->conditionIterable($condition);
    
    $option = (empty($condition[1])) ? "=" : $condition[1]; //por defecto se define "="
    $value = (!isset($condition[2])) ? null : $condition[2]; //hay opciones de configuracion que pueden no definir valores
    /**
     * No usar empty, puede definirse el valor false
     */
    $mode = (empty($condition[3])) ? "AND" : $condition[3];  //el modo indica la concatenacion con la opcion precedente, se usa en un mismo conjunto (array) de opciones

    $condicion = $this->conditionFieldCheckValue($condition[0], $option, $value);
    /**
     * El campo de identificacion del array posicion 0 no debe repetirse en las condiciones no estructuradas y las condiciones estructuras
     * Se recomienda utilizar un sufijo por ejemplo "_" para distinguirlas mas facilmente
     */
    return ["condition" => $condicion, "mode" => $mode];
  }
  
  
   /**
   * metodo de iteracion para definir condiciones
   */
  protected function conditionIterable(array $conditionIterable) { 
    $conditionModes = array();

    for($i = 0; $i < count($conditionIterable); $i++){
      $conditionMode = $this->conditionRecursive($conditionIterable[$i]);
      array_push($conditionModes, $conditionMode);
    }

    $modeReturn = $conditionModes[0]["mode"];
    $condition = "";

    foreach($conditionModes as $cm){
      $mode = $cm["mode"];
      if(!empty($condition)) $condition .= "
" . $mode . " ";
      $condition.= $cm["condition"];
    }

    return ["condition"=>"(
".$condition."
)", "mode"=>$modeReturn];
  }


  /**
   * Combinar parametros y definir SQL con la opcion
   */
  protected function conditionFieldCheckValue($field, $option, $value){

    if(!is_array($value)) {
      $condition = $this->conditionField($field, $option, $value);
      if(!$condition) throw new Exception("No pudo definirse el SQL de la condicion del campo: {$this->entity_name}.{$field}");
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

      $condition_ = $this->conditionFieldCheckValue($field, $option, $v);
      $condition .= $condition_;
    }

    return "(
  ".$condition."
)";
  }

  /**
   * Traducir campo y definir SQL con la opcion
   */
  protected function conditionField($field, $option, $value){
    $f = $this->container->explode_field($this->entity_name, $field);

    if(strpos($value, FF) === 0) { //definir condicion entre fields
      $v = $this->container->explode_field($this->entity_name, substr($value, strlen(FF)));
      $fieldSql1 = $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]);
      $fieldSql2 = $this->container->mapping($v["entity_name"], $v["field_id"])->map($v["field_name"]);
      
      switch($option) {
        case "=~": return "(lower(CAST({$fieldSql1} AS CHAR)) LIKE CONCAT('%', lower(CAST({$fieldSql2} AS CHAR)), '%'))";
        case "!=~": return "(lower(CAST({$fieldSql1} AS CHAR)) NOT LIKE CONCAT('%', lower(CAST({$fieldSql2} AS CHAR)), '%'))";
        default: return "({$fieldSql1} {$option} {$fieldSql2}) ";  
      }
    }

    return $this->container->condition($f["entity_name"], $f["field_id"])->_($f["field_name"], $option, $value);
    /**
     * Debido a la complejidad del metodo "condition" se proporciona un ejemplo para entender su comportamiento: 
     * Desde la entidad alumno, Se quiere traducir "persona-numero_documento.max"
     * Se define una instancia de condition con los siguientes atributos: 
     *    entity_name = "persona"
     *    prefix = "persona-"
     * 
     * Desde condition se ejecuta
     * 1) _("numero_documento.max", "=", "something") //verifica si hay metodo local "numeroDocumentoMax" sino invoca a _defineCondition("numero_documento.max")}
     * 2) _defineCondition("numero_documento.max") //traduce la funcion necesaria para armar la condicion, en este caso  se traduce como "_string"
     * 3) _string("numero_documento.max", "=", "something") //define el mapeo del field y el valor
     *    Para el mapeo, utiliza  $field = $this->container->mapping("persona", "persona-")->map("numero_documento.max"); que se traduce a MAX(persona-numero_documento)
     *    Para el valor, utiliza $this->container->value("persona", "persona-")->_set("numero_documento.max","something")... value->_check("numero_documento.max") ...value->_sql("numero_documento.max") que se traduce a "'something'"
     */

  }



  /**
   * Procesar atributo order y definir ordenamiento
   */
  protected function _order(){
    $entity = $this->container->entity($this->entity_name);
    $orderDefault = (!empty($entity->getOrderDefault())) ? $entity->getOrderDefault() : array_fill_keys($entity->main, "asc"); //se retorna ordenamiento por defecto considerando campos principales nf de la entidad principal

    foreach($this->order as $key => $value) {
      if(array_key_exists($key, $orderDefault)) unset($orderDefault[$key]);
    }

    $order = array_merge($this->order, $orderDefault);
    
    $sql = '';
    foreach($order as $key => $value){
      $value = ((strtolower($value) == "asc") || ($value === true)) ? "asc" : "desc";
      $f = $this->container->explode_field($this->entity_name, $key);
      $map_ = $this->container->mapping($f["entity_name"], $f["field_id"])->map($f["field_name"]);
      $sql_ = "{$map_} IS NULL, {$map_} {$value}";
      $sql .= concat($sql_, ', ', ' ORDER BY', $sql);
    }
    return $sql;
  }
}
