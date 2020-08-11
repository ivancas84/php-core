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
        
    abstract public function identify();

    abstract public function query();

    abstract public function process();

    public function summary() {
        $informe = "<h3>Resultado " . $this->id . "</h3>";
        $informe .= "<p>Cantidad de filas procesadas: " . count($this->elements) . "</p>
";      
    
        $i = 0;
        foreach($this->elements as $element) {
            $i++;
            $errores = [];
            $advertencias = [];
            if(!empty($element->logs()->getLogs())) $errores = array_merge($errores, $element->logs()->getLogs());
            if(!empty($element->logsEntities())) $advertencias = array_merge($advertencias, $element->logsEntities());
            
            if(count($errores) || count($advertencias)){
                $informe .= "
    <div class=\"card\">
    <ul class=\"list-group list-group-flush\">
        <li class=\"list-group-item active\">FILA " . $i . "</li>
";                
                if($element->logs()->isError()) $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">LA FILA NO FUE PROCESADA</li>
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
            // print_r($this->headers);
            // print_r($datos);
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

    public function identifyValue_($id, $value){
        if(!isset($this->ids[$id])) $this->ids[$id] = [];
        if(!in_array($value, $this->ids[$id])) array_push($this->ids[$id], $value); 
    }

    public function queryEntityField_($name, $field, $id = null){
      /**
       * Consulta a la base de datos de la entidad $name
       * Utilizando el campo field y el valor almacenado (deberia ser unico)
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

    public function queryEntityIdentifier_($name){        
        if(!empty($this->ids[$name])) $this->dbs[$name] = array_combine_concat(
            Ma::open()->identifier($name, $this->ids[$name]),
            Entity::getInstanceRequire($name)->identifier
        );;
    }
    

    public function processSource_($name, &$source, $value, $id = null){
        if(empty($id)) $id = $name;
        
        if(key_exists($value, $this->dbs[$id])){
          $existente = EntityValues::getInstanceRequire($name);
          $existente->_fromArray($this->dbs[$id][$value]);
          $sql = $this->updateSource_($source, $name, $existente);
        } else {        
            $sql = $this->insertSource_($source, $name);
        }
        $this->dbs[$id][$value] = $source[$name]->_toArray();
        return $sql;
    }

    public function insertSource_(&$source, $name){

        if(Validation::is_empty($source[$name]->id())) $source[$name]->setId(uniqid()); 
        $persist = EntitySqlo::getInstanceRequire($name)->insert($source[$name]->_toArray());
        $source[$name]->setId($persist["id"]);
        return $persist["sql"];
    }
      
    public function updateSource_(&$source, $name, $existente){
        $source[$name]->setId($existente->id());
        if(!$source[$name]->_equalTo($existente)) {
          $persist = EntitySqlo::getInstanceRequire($name)->update($source[$name]->_toArray());
          return $persist["sql"];
        }
    
        return "";
    }

}