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
     * 
     * Ejemplo: Pueden utilizarse metodos predefinidos processSource, insertSource, updateSource
     * 
     * {
     *   foreach($this->elements as &$element) {
     *   if($element->logs->isError()) continue;
     *
     *   if(key_exists($element->entities["persona"]->numeroDocumento(), $this->dbs["persona"])){
     *     $personaExistente = EntityValues::getInstanceRequire("persona");
     *     $dni = $element->entities["persona"]->numeroDocumento();
     *     $personaExistente->_fromArray($this->dbs["persona"][$dni]);
     *     if(!$element->entities["persona"]->checkNombresParecidos($personaExistente)){                    
     *       $element->logs->addLog("persona", "error", "En la base existe una persona cuyos datos no coinciden");
     *       continue;
     *     }
     *   }
     *   $element->sql .= $this->processSource_("persona", $element->entities, $element->entities["persona"]->numeroDocumento());
     * }
     */

    public function summary() {
        $informe = "<h3>Resultado " . $this->id . "</h3>";
        $informe .= "<p>Cantidad de filas procesadas: " . count($this->elements) . "</p>
";      
    
        $i = 0;
        foreach($this->elements as $element) {
            $i++;
            $errores = [];
            $advertencias = [];
            if(!empty($element->logs->getLogs())) $errores = array_merge($errores, $element->logs->getLogs());
            if(!empty($element->logsEntities())) $advertencias = array_merge($advertencias, $element->logsEntities());
            
            if(count($errores) || count($advertencias)){
                $informe .= "
    <div class=\"card\">
    <ul class=\"list-group list-group-flush\">
        <li class=\"list-group-item active\">FILA " . $i . "</li>
";                
                if($element->logs->isError()) $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">LA FILA NO FUE PROCESADA</li>
";
                foreach($errores as $key => $logs) {
                    foreach($logs as $log)  $informe .= "        <li class=\"list-group-item list-group-item-warning\">" . $key . ": " .$log["data"]."</li>
";
                }
                foreach($advertencias as $key => $logs) {   
                    foreach($logs as $log) $informe .= "        <li class=\"list-group-item list-group-item-secondary\">" . $key . ": " .$log["data"]. "</li>
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
            // echo "<pre>";
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
            if($element->logs->isError()) continue;
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
    

    protected function processSource($name, &$source, $value, $id = null){
        if(empty($id)) $id = $name;
        
        if(key_exists($value, $this->dbs[$id])){
          $existente = EntityValues::getInstanceRequire($name);
          $existente->_fromArray($this->dbs[$id][$value]);
          $sql = $this->updateSource($source, $name, $existente);
        } else {        
            $sql = $this->insertSource($source, $name);
        }
        $this->dbs[$id][$value] = $source[$name]->_toArray();
        return $sql;
    }

    protected function insertSource(&$source, $name){

        if(Validation::is_empty($source[$name]->id())) $source[$name]->setId(uniqid()); 
        $persist = EntitySqlo::getInstanceRequire($name)->insert($source[$name]->_toArray());
        $source[$name]->setId($persist["id"]);
        return $persist["sql"];
    }
      
    protected function updateSource(&$source, $name, $existente){
        $source[$name]->setId($existente->id());
        if(!$source[$name]->_equalTo($existente)) {
          $persist = EntitySqlo::getInstanceRequire($name)->update($source[$name]->_toArray());
          return $persist["sql"];
        }
    
        return "";
    }

}