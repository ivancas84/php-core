<?php
require_once("class/import/Element.php");

class ImportPersonaElement extends ImportElement {
    
  public function setEntities($row) {
    /**
     * Cada entidad definida en los datos se declara y se asigna en función del prefijo
     */
      $this->entities["persona"] = null;

      $this->setEntity($row, "persona");
  }


  

}