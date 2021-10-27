<?php

require_once("class/model/Render.php");
require_once("function/array_combine_key.php");
require_once("function/error_handler.php");

/**
 * Ejemplo ejecucion
 * require_once("../config/config.php");
 * require_once("class/Container.php");
 * set_time_limit ( 0 );
 * $container = new Container();
 * $import = $container->getImport("alumno");
 * $import->defineSource();
 * $import->pathSummary = $_SERVER["DOCUMENT_ROOT"] ."/".PATH_ROOT . "/info/import/" . $import->id;
 * $import->main();
 */
abstract class Import {
    /**
     * Importacion de elementos
     */

    public $id; //identificacion de la importacion
    public $start = 0; //comienzo
    public $source; //fuente de los datos a procesar
    public $pathSummary; //directorio donde se almacena el resumen del procesamiento
      //ej. $_SERVER["DOCUMENT_ROOT"] ."/".PATH_ROOT . "/info/import/" . $import->id;
      //utilizando el mismo path se definen dos archivos, uno html con el resumen y otro sql con la sentencia ejecutada
    
    public $headers; //encabezados
    /**
     *  si no se incluyen se procesara la primer fila como encabezados
     */ 

    public $mode = "csv";  //modo de procesamiento (csv, db, tab)
    public $updateNull = false; //flag para indicar si se deben actualizar valores nulos del source
    
    public $import = null; //referencia a la clase de importacion para acceder a atributos y datos adicionales
    public $ids = []; //array asociativo con identificadores
    public $dbs = []; //array asociativo con el resultado de las consultas a la base de datos
    public $elements = []; //array de elementos a importar
    public $db;
    public $container;
    
    public function main(){
      $this->define();
      $this->identify();
      $this->query();
      $this->process();
      $this->persist();
    }

    public function element($i, $data, &$import){
    /**
     * Metodo principal para definir elementos
     * Un elemento es una estructura que posee un conjunto de datos para importar entidades
     * Habitualmente un elemento define un unico juego de entidades relacionadas
     * Si los parametros poseen mas de un juego de entidades, se recomienda sobrescribir element para definir varios elementos (uno por cada juego)
     * Existe una clase abstracta Element que posee un conjunto de metodos de uso habitual para los elementos
     */

      $element = $this->container->getImportElement($this->id);
      $element->import = $import; //referencia a la clase de importacion      
      if($data === false) {
        $element->process = false;
        $element->logs->addLog("data", "error", "Los datos están vacíos o no pudieron combinarse");
      } else {
        $element->setEntities($data);
      }
      array_push($this->elements, $element);
    }
        
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
     *   $element->entities["lugar"]->_set("identifier", 
     *     $element->entities["lugar"]->distrito().UNDEFINED.
     *     $element->entities["lugar"]->provincia()
     *   );
     *
     *   if(!in_array($element->entities["lugar"]->_get("identifier"), $this->ids["lugar"])) array_push($this->ids["lugar"], $element->entities["lugar"]->_get("identifier"));
     * }
     */

    abstract public function query();
    /**
     * Consulta de existencia de datos en la base de datos para evitar volver a ejecutar o actualizar datos existentes
     * Los datos consultados se cargan en el atributo dbs
     * 
     * Ejemplo: Puede utilizarse metodos predefinido queryEntityField
     * {
     *   $this->queryEntityField("persona","numero_documento");
     *   $this->queryEntityField("alumno","identifier");
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
     *       $personaExistente = $this->container->getValues("persona");
     *       $dni = $element->entities["persona"]->numeroDocumento();
     *       $personaExistente->_fromArray($this->dbs["persona"][$dni]);
     *       if(!$element->entities["persona"]->checkNombresParecidos($personaExistente)){                    
     *         $element->logs->addLog("persona", "error", "En la base existe una persona cuyos datos no coinciden");
     *         continue;
     *       }
     *     }
     *    $this->processElement($element, "persona", $dni)
     * 
     *   ***** INSCRIPCION (ejemplo de relacion) *****
     *   foreach($this->elements as &$element) {
     *     if(!$element->process) continue;
     *     $element->entities["inscripcion"]->setAlumno($element->entities["persona"]->id());
     *     $this->processElement("inscripcion", $element->entities, $element->entities["inscripcion"]->_identifier());
     *   }
     * 
     *   ***** EXISTENCIA *****
     *   foreach($this->elements as &$element) {
     *     if(!key_exists($value, $this->dbs[$id])){
     *       $element->process = false
     *       $element->logs->addLog("comision", "error", "No existe la comisión en la base de datos");
     *       continue;
     *     }
     *   }
     * }
     * 
     * 
     */
    
    public function summary() {
        $informe = "<h3>Resultado " . $this->id . "</h3>";
        $informe .= "<p>Cantidad de filas procesadas: " . count($this->elements) . "</p>
";      
    
        foreach($this->elements as $element) {
          if((!$element->process) && (empty($element->logs->getLogs()))) continue;

          
          $informe .= "
    <div class=\"card\">
    <ul class=\"list-group list-group-flush\">
        <li class=\"list-group-item active\">FILA " . ($element->index) . "</li>
";        
           
          $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">" . $element->id() . "</li>
";
          if($element->process && $element->sql) $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">Procesamiento ok: Persistencia realizada</li>
";
          if($element->process && !$element->sql) $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">Procesamiento ok: No se realizara ningun cambio en la base de datos</li>
";
          if(!$element->process) $informe .= "       <li class=\"list-group-item list-group-item-danger font-weight-bold\">Sin procesar</li>
";
          foreach($element->logs->getLogs() as $key => $logs) {
            foreach($logs as $log){
              $class = ($log["status"] == "error") ? "list-group-item-warning" : "list-group-item-secondary" ;
              $informe .= "        <li class=\"list-group-item {$class}\">" . $key . " (". $log["status"]. "): " .$log["data"]."</li>
";
            }
          }
          $informe .= "    </ul>
    </div>
    <br><br>";                          
        }
         
        if(!empty($this->pathSummary)) file_put_contents($this->pathSummary . ".html", $informe);
    
        echo $informe;
    }

  public function defineCsv(){
    if (($gestor = fopen("../../tmp/" . $this->source . ".csv", "r")) !== FALSE) {
      $encabezados = fgetcsv($gestor, 1000, ",");

      $i = 0 + $this->start;
      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
    
          $i++;
          if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
          $e = array_combine($encabezados, $datos);
          $this->element($i, $e, $this);                  
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
      $e = @array_combine($this->headers, $datos);
      $this->element($i + $this->start, $e, $this);                  
      //if($i==100) break;           
    }
  }

  public function defineDb(){
    if(empty($this->source)) throw new Exception("No existen datos para procesar");

    if(empty($this->headers)) {
      $this->headers = [];
      foreach($this->source[0] as $key => $value) array_push($this->headers, $key);
    }

    for($i = $this->start; $i < count($this->source); $i++){
      if(empty($this->source[$i])) break;
      $this->element($i + $this->start, $this->source[$i], $this);                  
    }
  }
    
  public function define(){
      switch($this->mode){
          case "tab":
              $this->defineTab();
          break;
          case "db":
            $this->defineDb();
          break;
          default:
              $this->defineCsv();
      }
  }

  public function persist(){
    $sql = "";
    foreach($this->elements as $element) {
      if($element->process) {
        $element->persist();
        $sql .= $element->sql;
      }
    }
    if(!empty($this->pathSummary)) file_put_contents($this->pathSummary . ".sql", $sql);
  }
 
  protected function queryEntityField($entityName, $field, $id = null){
    /**
     * Consulta a la base de datos de la entidad $entityName
     * Utilizando el campo field (supuestamente unico) y el valor almacenado de field desde el atributo ids
     * Todos los resultados los carga en el atributo dbs que indica los valores que fueron extraidos de la base de datos
     */
    if(!$id) $id = $entityName;
    $this->dbs[$id] = [];
    if(empty($this->ids[$id])) return;

    $render = new Render();
    $render->setFields([$field]);
    $render->setSize(false);
    $render->addCondition([$field,"=",$this->ids[$id]]);
    $rows = $this->container->getDb()->all($entityName, $render);

    //si se devuelven varias instancias del mismo identificador (no deberia pasar) solo se considerara una
    $this->dbs[$id] = array_combine_key(
      $rows,
      $field
    );
  }

  public function existElement(&$element, string $entityName, string $fieldName = "identifier", string $id = null){
    $value = $element->entities[$entityName]->_get($fieldName);

    if(empty($id)) $id = $entityName;

    if(!key_exists($value, $this->dbs[$id])){
      $element->process = false;
      $element->logs->addLog($entityName, "error", "No existe " . $entityName . " en la base de datos");
      return false;
    }
    $existente = $this->container->getValue($entityName);
    $existente->_fromArray($this->dbs[$id][$value], "set");
    $element->entities[$entityName]->_set("id",$existente->_get("id"));
    //$element->logs->addLog($entityName, "info", "Registro existente, no será actualizado");
    return $value;    
  }

  public function processElement(&$element, $entityName, $fieldName = "identifier", $id = null, $updateMode = true){
    /**
     * @param $entityName Nombre de la entidad
     * @param $value Valor de la entidad que la identifica univocamente
     * @param @id Identificador auxiliar de la entidad
     */

    if(empty($id)) $id = $entityName;
    $value = $element->entities[$id]->_get($fieldName);
    if(key_exists($value, $this->dbs[$id])) {
      $existente = $this->container->getValue($entityName);
      $existente->_fromArray($this->dbs[$id][$value], "set");
      $element->entities[$id]->_set("id",$existente->_get("id"));
      $compare = $element->compare($id, $existente);  
      if(!empty($compare)) {
        if(!$element->update($entityName, $existente, $id, $updateMode)) return false;
      }
    } else {        
      if(!$element->insert($entityName, $id)) return false;
    }

    return $value;
  }

  public function insertElement(&$element, $entityName, $fieldName = "identifier", $id = null){
    /**
     * Si no existe lo inserta, nunca actualiza
     * @param $entityName Nombre de la entidad
     * @param $value Valor de la entidad que la identifica univocamente
     * @param @id Identificador auxiliar de la entidad
     */
    if(empty($id)) $id = $entityName;
    $value = $element->entities[$id]->_get($fieldName);

    if(!key_exists($value, $this->dbs[$id])) {
      if(!$element->insert($entityName, $id)) return false;
    } else {
      $existente = $this->container->getValue($entityName);
      $existente->_fromArray($this->dbs[$id][$value], "set");
      $element->entities[$id]->_set("id",$existente->_get("id"));
    }
    
    return $value;
  }

  public function idEntityFieldCheck($id, $identifier, &$element){
    /**
     * Carga de $this->ids[$id] = $identifier correspondiente a $element
     * Si ya existe $identifier, dispara error de Valor duplicado
     */
    if(Validation::is_empty($identifier)) {
      $element->process = false;                
      $element->logs->addLog($id, "error", "El identificador de " . $id . " esta vacio" );
    }
    if(!key_exists($id, $this->ids)) $this->ids[$id] = [];
    if(in_array($identifier, $this->ids[$id])) {
      $element->logs->addLog($id,"error"," Valor duplicado");
      $element->process = false;
      return false;       
    }
    array_push($this->ids[$id], $identifier);
    return true;
  }

  public function idEntityField($entityName, $identifier){
    /**
     * Carga de $this->ids[$id] = $identifier correspondiente a $element
     */
    if(Validation::is_empty($identifier)) throw new Exception ("Identificador vacio");
    if(!key_exists($entityName, $this->ids)) $this->ids[$entityName] = [];
    if(!in_array($identifier, $this->ids[$entityName])) array_push($this->ids[$entityName], $identifier);
  }


}