<?php

require_once("class/model/Sqlo.php");
require_once("class/model/Ma.php");

require_once("function/array_combine_key.php");
require_once("function/error_handler.php");

abstract class Import {
    /**
     * Importacion de elementos
     */
    
    public $id; //identificacion de los datos a procear
    public $source; //fuente de los datos a procesar
    public $pathSummary; //directorio donde se almacena el resumen del procesamiento
    public $headers; //opcional encabezados
    public $mode = "csv";  //modo de procesamiento
        /**
         * post: post tab
         * post_comma: post comma
         */
    
    public $ids = []; //array asociativo con identificadores
    public $dbs = []; //array asociativo con el resultado de las consultas a la base de datos
    public $elements = []; //array de elementos a importar
    
    public function main(){
        echo date("Y-m-d H:i:s") . " BEGIN " . $this->id . "<br>";
        $this->define();
        $this->identify();
        $this->query();
        $this->process();
        $this->persist();
        $this->summary();
        echo date("Y-m-d H:i:s") . " END " . $this->id."<br>";
    }

    abstract public function element($i, $data);
    /**
     * En funcion de los datos de entrada, se definen un elemento
     * Un elemento posee todos los datos que posteriormente seran insertados y los posibles errores que puede haber
     * Existe una clase abstracta Element que posee un conjunto de metodos de uso habitual
     * 
     * Ejemplo:
     * {
     *   $element = new ImportPersonaElement($i, $data); 
     *   array_push($this->elements, $element);
     * }
     */
        
    abstract public function identify();
    /**
     * Se completa el atributo ids definiendo por cada entidad un identificador
     * Cobra particular importancia el uso del atributo identifier de cada entidad
     * 
     * Ejemplo:
     * {
     *   ***** PERSONA (utiliza campo unico de identificacion) *****
     *   $this->ids["persona"] = [];
     *   foreach($this->elements as &$element){
     *     $dni = $element->entities["persona"]->numeroDocumento();
     *     if(Validation::is_empty($dni)){
     *       $element->process = false;                
     *       $element->logs->addLog("persona", "error", "El número de documento no se encuentra definido");
     *       continue;
     *     }
     *   }
     *   
     *   //OPCIONAL por si no se quiere definir dos veces la misma persona
     *   if(in_array($dni, $this->ids["persona"])) $element->logs->addLog("persona","error","El número de documento ya existe");
     *   
     *   array_push($this->ids["persona"], $element->entities["persona"]->numeroDocumento());
     * 
     *   ***** LUGAR (utiliza "identifier")*****
     *   $this->ids["lugar"] = [];
     *   $element->entities["lugar"]->_setIdentifier(
     *     $element->entities["lugar"]->distrito().UNDEFINED.
     *     $element->entities["lugar"]->provincia()
     *   );
     *
     *   if(!in_array($element->entities["lugar"]->_identifier(), $this->ids["lugar"])) array_push($this->ids["lugar"], $element->entities["lugar"]->_identifier());
     * }
     */

    abstract public function query();
    /**
     * Consulta de existencia de datos en la base de datos para evitar volver a ejecutar o actualizar datos existentes
     * Los datos consultados se cargan en el atributo dbs
     * 
     * Ejemplo: Pueden utilizarse los metodos predefinidos queryEntityField, queryEntityIdentifier
     * {
     *   $this->queryEntityField_("persona","numero_documento");
     * }
     **/

    abstract public function process();
    /**
     * Procesamiento de datos: Define el SQL de actualizacion o insercion de datos
     * Es en esta etapa que se realizan las relaciones
     * 
     * Ejemplo: Pueden utilizarse metodos predefinidos processElement, insertElement, updateElement
     * 
     * {
     *   ***** PERSONA *****
     *   foreach($this->elements as &$element) {
     *     if(!$element->process) continue;
     *
     *     if(key_exists($element->entities["persona"]->numeroDocumento(), $this->dbs["persona"])){
     *       $personaExistente = EntityValues::getInstanceRequire("persona");
     *       $dni = $element->entities["persona"]->numeroDocumento();
     *       $personaExistente->_fromArray($this->dbs["persona"][$dni]);
     *       if(!$element->entities["persona"]->checkNombresParecidos($personaExistente)){                    
     *         $element->logs->addLog("persona", "error", "En la base existe una persona cuyos datos no coinciden");
     *         continue;
     *       }
     *     }
     *     $element->sql .= $this->processElement("persona", $element->entities, $element->entities["persona"]->numeroDocumento());
     * 
     *   ***** INSCRIPCION (ejemplo de relacion) *****
     *   foreach($this->elements as &$element) {
     *     if(!$element->process) continue;
     *     $element->entities["inscripcion"]->setAlumno($element->entities["persona"]->id());
     *     $this->processElement("inscripcion", $element->entities, $element->entities["inscripcion"]->_identifier());
     *   }
     * }
     * 
     * 
     */

    public function summary() {
        $informe = "<h3>Resultado " . $this->id . "</h3>";
        $informe .= "<p>Cantidad de filas procesadas: " . count($this->elements) . "</p>
";      
    
        $i = 0;
        foreach($this->elements as $element) {
            $i++;
            $logsElement = [];
            $logsEntities = [];
            if(!empty($element->logs->getLogs())) $logsElement = array_merge($logsElement, $element->logs->getLogs());
            if(!empty($element->logsEntities())) $logsEntities = array_merge($logsEntities, $element->logsEntities());
            
            if(count($logsElement) || count($logsEntities)){
                $informe .= "
    <div class=\"card\">
    <ul class=\"list-group list-group-flush\">
        <li class=\"list-group-item active\">FILA " . $i . "</li>
";                
                if(!$element->process) $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">LA FILA NO FUE PROCESADA</li>
";
                foreach($logsElement as $key => $logs) {
                    foreach($logs as $log)  $informe .= "        <li class=\"list-group-item list-group-item-warning\">" . $key . " (". $log["status"]. "): " .$log["data"]."</li>
";
                }
                foreach($logsEntities as $key => $logs) {   
                    foreach($logs as $log) $informe .= "        <li class=\"list-group-item list-group-item-secondary\">" . $key . " (". $log["status"]. "): " .$log["data"]. "</li>
";
                }
                $informe .= "    </ul>
    </div>
    <br><br>";                          
            }
            
        }
        file_put_contents($this->pathSummary . ".html", $informe);
    
        echo $informe;
    }

    public function defineCsv(){
        if (($gestor = fopen("../../tmp/" . $this->source . ".csv", "r")) !== FALSE) {
            $encabezados = fgetcsv($gestor, 1000, ",");

            $i = 0;
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                $i++;
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
                $e = array_combine($encabezados, $datos);
                $this->element($i, $e);                  
                //if($i==100) break;           
            }
            fclose($gestor);
        }
    }

    public function defineTab(){

        $source = explode("\n", $this->source);

        if(empty($this->headers)) {
            $this->headers = [];
            foreach( preg_split('/\t+/', $source[0]) as $h) array_push($this->headers, trim($h));
            $start = 1;
        } else {
            $start = 0;
        }
            
        for($i = $start; $i < count($source); $i++){
            if(empty($source[$i])) break;
            $datos = [];
                        
            foreach( explode("\t", $source[$i]) as $d) array_push($datos, trim($d));
            //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
            //echo "<pre>";
            //print_r($this->headers);
            //print_r($datos);
          
            $e = @array_combine($this->headers, $datos);
            $this->element($i, $e);                  
            //if($i==100) break;           
        }
    }
     
    public function define(){
        switch($this->mode){
            case "tab":
                $this->defineTab();
            break;
            default:
                $this->defineCsv();
        }
    }

    public function persist(){
        $sql = "";
        $db = Db::open();
        foreach($this->elements as $element) {
            if(!$element->process) continue;
            try {
              $sql .= $element->sql;
              $db->multi_query_transaction($element->sql);
            } catch(Exception $exception){
                $element->logs->addLog("persist","error",$exception->getMessage());
            }
        }
        file_put_contents($this->pathSummary . ".sql", $sql);
    }

    protected function identifyValue($id, $value){
        if(!isset($this->ids[$id])) $this->ids[$id] = [];
        if(!in_array($value, $this->ids[$id])) array_push($this->ids[$id], $value); 
    }

    protected function queryEntityField($name, $field, $id = null){
      /**
       * Consulta a la base de datos de la entidad $name
       * Utilizando el campo field (supuestamente unico) y el valor almacenado de field desde el atributo ids
       * Todos los resultados los carga en el atributo dbs que indica los valores que fueron extraidos de la base de datos
       */
        if(!$id) $id = $name;
        $this->dbs[$id] = [];
        if(empty($this->ids[$id])) return;

        $rows = Ma::open()->all($name, [$field,"=",$this->ids[$id]]);
    
        $this->dbs[$id] = array_combine_key(
          $rows,
          $field
        );
    }

    protected function queryEntityIdentifier($name){        
        if(!empty($this->ids[$name])) $this->dbs[$name] = array_combine_concat(
            Ma::open()->identifier($name, $this->ids[$name]),
            Entity::getInstanceRequire($name)->identifier
        );;
    }
    

    protected function processElement($name, &$element, $value, $id = null){
        /**
         * @param $name Nombre de la entidad
         * @param $element Elemento a procesar
         * @param $value Valor de la entidad que la identifica univocamente
         * @param @id Identificador auxiliar de la entidad
         */
        if(empty($id)) $id = $name;
        
        if(key_exists($value, $this->dbs[$id])){
          $existente = EntityValues::getInstanceRequire($name);
          $existente->_fromArray($this->dbs[$id][$value]);
          $this->updateElement($element, $name, $existente);
        } else {        
            $this->insertElement($element, $name);
        }
        $this->dbs[$id][$value] = $element->entities[$name]->_toArray();
    }

    protected function insertElement(&$element, $name){
        if(Validation::is_empty($element->entities[$name]->id())) $element->entities[$name]->setId(uniqid()); 
        $persist = EntitySqlo::getInstanceRequire($name)->insert($element->entities[$name]->_toArray());
        $element->entities[$name]->setId($persist["id"]);
        $element->sql .=  $persist["sql"];
    }
      
    protected function updateElement(&$element, $name, $existente){
        $element->entities[$name]->setId($existente->id());
        if(!$element->entities[$name]->_equalTo($existente)) {
          $element->logs->addLog("persona","warning","El registro sera actualizado");
          $persist = EntitySqlo::getInstanceRequire($name)->update($element->entities[$name]->_toArray());
          $element->sql .= $persist["sql"];
        } else {
          $element->process = false;
          $element->logs->addLog("persona","info","Registros existente, no será actualizado");
        }
    }

}