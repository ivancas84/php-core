<?php

class ModelResources {
  /**
   * Recursos para ser utilizados en elementos del modelo
   * Inicialmente se iba a llamar ModelTools pero el nombre estaba siendo utilizado por la aplicacion de implementacion
   * 
   */

  public $container;

  public function transferirEntidad($entityName, $fkName, $fkValue, $fkValueTransfer){
    /**
     * Transferencia simple de una relacion um de una entidad
     * Simple se refiere a que no necesita ninguna comparacion adicional, simplemente debe modificarse la clave foranea
     */
    $render = $this->container->getEntityRender($entityName);
    $render->setCondition([
      [$fkName,"=",$fkValue]
    ]);
    $transferir = $this->container->getDb()->all($entityName,$render);
     
    $sql = "";
    $detail = [];

    if(empty($transferir)) return ["sql"=>$sql,"detail"=>$detail];
    
    foreach($transferir as $detalle){
      $tr = $this->container->getValue($entityName)->_fromArray($detalle,"set");
      $tr->_fastSet($fkName,$fkValueTransfer);
      $tr->_call("reset")->_call("check");
      if($tr->logs->isError()) throw new Exception($tr->logs->toString());
      $sql .= $this->container->getEntitySqlo($entityName)->update($tr->_toArray("sql"));      
    }

    return ["sql"=>$sql,"detail"=>$detail];
  }


  
}