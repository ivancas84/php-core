<?php
require_once("class/import/Import.php");
require_once("class/import/persona/PersonaElement.php");
require_once("class/tools/Validation.php");


class ImportPersona extends Import {
  
  public function element($i, $data){
    /**
     * Definir elemento a procear
     */
    $element = new ImportPersonaElement($i, $data); 
    array_push($this->elements, $element);
  }

  public function identify(){
    $this->ids["persona"] = [];

    foreach($this->elements as &$element){
      $dni = $element->entities["persona"]->numeroDocumento();
      if(Validation::is_empty($dni)){
          $element->process = false;                
          $element->logs->addLog("persona", "error", "El número de documento no se encuentra definido");
          continue;
      }
  
      array_push($this->ids["persona"], $element->entities["persona"]->numeroDocumento());        
    }
  }        
   
  public function query(){
    $this->queryEntityField_("persona","numero_documento");
  }

  public function process(){
    $this->processPersonas();
  }

  public function processPersonas(){
    foreach($this->elements as &$element) {
      if($element->logs->isError()) continue;

      if(key_exists($element->entities["persona"]->numeroDocumento(), $this->dbs["persona"])){
        $personaExistente = EntityValues::getInstanceRequire("persona");
        $dni= $element->entities["persona"]->numeroDocumento();
        $personaExistente->_fromArray($this->dbs["persona"][$dni]);
        if(!$element->entities["persona"]->checkNombresParecidos($personaExistente)){                    
            $element->logs->addLog("persona", "error", "En la base existe una persona cuyos datos no coinciden");
            continue;
        }
      }
      $element->sql .= $this->processSource_("persona", $element->entities, $element->entities["persona"]->numeroDocumento());
    }
  }

}