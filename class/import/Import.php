<?php

require_once("class/model/EntityQuery.php");
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
 * $import->main();
 */
abstract class Import {
    /**
     * Importacion de elementos
     */

    public $id; //identificacion de la importacion
    public $source; //fuente de los datos a procesar
    public $start = 0; //fila de comienzo del source
    public $headers; //encabezados
    
    public $ids = []; //array asociativo con identificadores
    public $dbs = []; //array asociativo con el resultado de las consultas a la base de datos
    public $elements = []; //array de elementos a importar
    public $entityNames = []; //array asociativo que indica la entidad correspondiente a un nombre
    public $container;
    
    public function main(){
      $this->config(); //utiliza element
      $this->define(); //utiliza element
      $this->identify();
      $this->query();
      $this->process();

    }

    /**
     * Metodo principal para definir elementos.
     * 
     * Un elemento es una estructura que posee un conjunto de datos para im-
     * portar entidades.
     * Habitualmente un elemento define un unico juego de entidades relaciona-
     * das.
     * Si los parametros poseen mas de un juego de entidades, se recomienda 
     * sobrescribir element para definir varios elementos (uno por cada juego)
     * Existe una clase abstracta Element que posee un conjunto de metodos de 
     * uso habitual para los elementos
     * 
     * @param $i Identificacion del elemento
     * @param $data Juego de datos del elemento
     */
    public function element($i, $data){
      $element = $this->container->getImportElement($this->id, $this);
      $element->index = $i;
      try{
        if(empty($data)) throw new Exception("Datos vacios para el elemento ". $i);
        $element->setEntities($data);
      } catch (Exception $exception){
        $element->process = false;
        $element->logs->addLog("element", "error", $exception->getMessage() . "(". $element->index .")");
      }
      array_push($this->elements, $element);
    }

    /** 
     * Configuracion inicial
     * 
     * Se definen entre otras cosas los identificadores de la entidad
     * 
     * @example Chequeo de existencia de valor
     * public function config(){
     *   if(Validation::is_empty($this->idComision)) throw new Exception("El id no se encuentra definido");
     * }
     * 
     * @example Definir identificadores
     * 
     */
    abstract public function config();
    


    /**
     * Identificar entidades
     * 
     * Recorre los elementos definidos y completa el atributo ids definiendo 
     * por cada entidad un identificador previamente configurado.
     * 
     * Puede utilizar metodos de Element para facilitar:
     *   identifyCheck: Definicion de id y chequeo de que ya no exista. 
     * valor
     *   idEntity: Definicion de id sin chequeo.
     * 
     * @example 1: Persona se identifica con el dni y no debe existir en el 
     * conjunto de datos, dos veces la misma persona
     *   
     *   foreach($this->elements as &$element){
     *     if(!$element->process) continue;
     *     $element->identifyCheck("persona");
     *   }
     */
    abstract public function identify();
    

    abstract public function query();
    /**
     * Consulta de existencia de datos en la base de datos para evitar volver 
     * a ejecutar o actualizar datos existentes.
     * Los datos consultados se cargan en el atributo dbs
     * 
     * @example Puede utilizarse metodos predefinido queryEntity
     * {
     *   $this->queryEntity("persona","numero_documento");
     *   $this->queryEntity("alumno","identifier");
     * }
     **/

    abstract public function process();
    /**
     * Procesamiento de datos: Define el SQL de persistencia de datos.
     * 
     * Es en esta etapa que se realizan las relaciones entre las distintas en-
     * tidades del elemento, ya que se van definiendo los ids de la base de 
     * datos.
     * 
     * Pueden utilizarse metodos predefinidos:
     *   processElement: Insercion o actualizacion.
     *   insertElement: Insercion.
     *   updateElement: Actualizacion.
     *   existElement: Chequeo de existencia
     * 
     * Para saber si un elemento puede ser procesado, se utiliza el atributo
     * booleano $element->process. Si se detecta algun error en el elemento,
     * en algun momento de la ejecucion de la importacion $element->process
     * se carga en false.
     * 
     * @example: Procesamiento de persona
     *   foreach($this->elements as &$element) {
     *     if(!$element->process) continue;
     *
     *     if(key_exists($element->entities["persona"]->numeroDocumento(), $this->dbs["persona"])){
     *       $personaExistente = $this->container->values("persona");
     *       $dni = $element->entities["persona"]->numeroDocumento();
     *       $personaExistente->_fromArray($this->dbs["persona"][$dni]);
     *       if(!$element->entities["persona"]->checkNombresParecidos($personaExistente)){                    
     *         $element->logs->addLog("persona", "error", "En la base existe una persona cuyos datos no coinciden");
     *         continue;
     *       }
     *     }
     *    $this->processElement($element, "persona", $dni)
     * 
     * @example 2: Procesamiento de inscripcion
     *   foreach($this->elements as &$element) {
     *     if(!$element->process) continue;
     *     $element->entities["inscripcion"]->setAlumno($element->entities["persona"]->id());
     *     $this->processElement("inscripcion", $element->entities, $element->entities["inscripcion"]->_identifier());
     *   }
     * 
     * @example 3: Chequeo de existencia
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
         
    
        echo $informe;
    }


  public function define(){
    /**
     * Datos definidos utilizando "tab"
     */
    if(empty($this->source)) throw new Exception("Source vacio");
    $source = explode("\n", $this->source);

    if(empty($this->headers)) {
        $this->headers = [];
        foreach( preg_split('/\t+/', $source[0]) as $h) array_push($this->headers, trim($h));
        if($this->start == 0) $this->start = 1;
    } 

    for($i = $this->start; $i < count($source); $i++){
      if(empty($source[$i])) break;
      $datos = [];
                  
      foreach( explode("\t", $source[$i]) as $d) array_push($datos, trim($d));
      //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
      $e = @array_combine($this->headers, $datos);
      $this->element($i + $this->start, $e);                  
      //if($i==100) break;           
    }
  }

  public function defineCsv(){
    if (($gestor = fopen("../../tmp/" . $this->source . ".csv", "r")) !== FALSE) {
      $encabezados = fgetcsv($gestor, 1000, ",");

      $i = 0 + $this->start;
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

  
  protected function defineDb(){
    /**
     * Datos definidos desde la base datos
     */
    
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
    

  public function persist(){
    $sql = "";
    foreach($this->elements as $element) {
      if($element->process) {
        $element->persist();
        $sql .= $element->sql;
      }
    }
  }

  public function sql(){
    $sql = "";
    foreach($this->elements as $element) {
      if($element->process) {
        $sql .= $element->sql;
      }
    }
    return $sql;
  }
 
  public function getEntityName($name){
    return (in_array($name, $this->entityNames)) ? $this->entityNames[$name] : $name;
  }

  protected function queryEntity($name){
    /**
     * Consulta a la base de datos de la entidad $entityName.
     * 
     * Utilizando el campo $field (supuestamente unico) y el valor almacenado 
     * de field desde el atributo "ids".
     * Todos los resultados los carga en el atributo "dbs" que indica los va-
     * lores que fueron extraidos de la base de datos.
     */
    $this->dbs[$name] = [];
    if(empty($this->ids[$name])) throw new Exception("query error: No se encuentran definidos los identificadores de " . $name);

    $entityName = $this->getEntityName($name);
    $render = $this->container->query($entityName);
    $render->setFields(["identifier"]);
    $render->setSize(false);
    $render->addCondition(["identifier","=",$this->ids[$name]]);
    $rows = $this->container->getDb()->all($entityName, $render);

    //si se devuelven varias instancias del mismo identificador (no deberia pasar) solo se considerara una
    $this->dbs[$name] = array_combine_key(
      $rows,
      "identifier"
    );
  }




  


}