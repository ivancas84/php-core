<?php

class ModelResources {
  /**
   * Recursos para ser utilizados en elementos del modelo
   * Inicialmente se iba a llamar ModelTools pero el nombre estaba siendo utilizado por la aplicacion de implementacion
   * 
   */

  public $container;

  public function transferirEntidad($entity_name, $fkName, $fkValue, $fkValueTransfer){
    /**
     * Transferencia simple de una relacion um de una entidad
     * Simple se refiere a que no necesita ninguna comparacion adicional, simplemente debe modificarse la clave foranea
     */
    $render = $this->container->query($entity_name);
    $render->setCondition([
      [$fkName,"=",$fkValue]
    ]);
    $transferir = $this->container->db()->all($entity_name,$render);
     
    $sql = "";
    $detail = [];

    if(empty($transferir)) return ["sql"=>$sql,"detail"=>$detail];
    
    foreach($transferir as $detalle){
      $tr = $this->container->value($entity_name)->_fromArray($detalle,"set");
      $tr->_fastSet($fkName,$fkValueTransfer);
      $tr->_call("reset")->_call("check");
      if($tr->logs->isError()) throw new Exception($tr->logs->toString());
      $sql .= $this->container->persist($entity_name)->update($tr->_toArray("sql"));      
    }

    return ["sql"=>$sql,"detail"=>$detail];
  }


  
}