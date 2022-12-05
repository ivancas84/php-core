<?php

/**
 * @todo Implementar render en el getall
 */

require_once("model/Db.php");


class Ma extends Db {
  /**
   * Model access (Acceso rapido al modelo)
   * Interfaz opcional entre el modelo y la base de datos para ser utilizada con los métodos de uso general
   * Prefijos y sufijos en el nombre de metodos:
   *   get: Utiliza id como parametro principal de busqueda
   *   all: Se refiere a un conjunto de valores
   *   one: Debe retornar un unico valor
   *   OrNull: Puede retornar valores nulos
   */

  public $container;
    
  public function count($entity, $render = null){
    /**
     * cantidad
     */
    $r = EntityQuery::getInstance($render);
    $r->size(false);
    $r->page(1);
    $r->order([]);

    if(!in_array("_count", $r->getFields())) $r->setFields(["_count"]);
    
    $sql = $this->container->persist($entity)->select($r);
    $result = $this->query($sql);
    $row = $result->fetch_assoc();
    $result->free();
    return intval($row["_count"]);
  }


  public function select($entity, EntityQuery $render){
    /**
     * consulta avanzada
     * Reduce la cantidad de campos a consultar
     * No se debe utilizar storage
     */
    $sql = $this->container->persist($entity)->select($render);
    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;    
  }

  public function unique($entityName, array $params){
    /**
     * Busqueda por campos unicos
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    if(empty($params)) return null;
    $render = $this->container->query($entityName);
    $c = $render->setConditionUniqueFields($params);

    $render->addFields($this->container->tools($entityName)->fieldNames());
    $sql = $this->container->persist($entityName)->select($render);
    if(empty($sql)) return null;

    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    if(count($rows) > 1) throw new Exception("La busqueda por campos unicos de {$entityName} retorno mas de un resultado");
    if(count($rows) == 1) return $rows[0];
    return null;
  }

  public function ids($entityName, $render = null){   
    $render->setFields(["id"]);
    $sql = $this->container->persist($entityName)->select($render);
    $result = $this->query($sql);
    $ids = $this->fetch_all_columns($result, 0);
    $result->free();
    
    require_once("function/to_string.php");
    array_walk($ids, "to_string"); 
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    return $ids;
  }

  public function id($entity, $render = null) {
    /**
     * id
     */
    $ids = $this->ids($entity, $render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    else throw new Exception("La consulta no arrojó resultados");
  }

  public function idOrNull($entity, $render = null) {
    /**
     * id o null
     */
    $ids = $this->ids($entity, $render);
    if(count($ids) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($ids) == 1) return (string)$ids[0];
    /**
     * los ids son tratados siempre como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    else return null;
  }

  public function all(string $entityName, $render = null){
    /**
     * todos los valores
     */
    if(!$render) $render = $this->container->query($entityName);
    $render->addFields($this->container->tools($entityName)->fieldNames());
    $sql = $this->container->persist($entityName)->select($render);
    $result = $this->query($sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rows;
  }

  public function get(string $entity, $id, $render = null) { //busqueda por id
    if(!$id) throw new Exception("No se encuentra definido el id");
    $rows = $this->getAll($entity, [$id], $render);
    if (!count($rows)) throw new Exception("La búsqueda por id no arrojó ningun resultado");
    return $rows[0];
  }

  public function labelGet(string $entityName, $id, $render = null) { //busqueda por id
    if(!$id) throw new Exception("No se encuentra definido el id");
    $render = $this->container->query($entityName);
    $render->setFields(["id","label"]);
    $render->setCondition(["id","=",$id]);
    $rows = $this->select($entityName, $render);
    if (count($rows) != 1) throw new Exception("Error al definir label");
    return $rows[0];
  }

  public function labelGetAll(string $entityName, $ids, $render = null) { //busqueda por id
    if(!$ids) throw new Exception("No se encuentra definido el id");
    $render = $this->container->query($entityName);
    $render->setFields(["id","label"]);
    $render->setCondition(["id","=",$ids]);
    $rows = $this->select($entityName, $render);
    return $rows;
  }



  public function getOrNull($entity, $id, $render = null){ //busqueda por id o null
    if(empty($id)) return null;
    $rows = $this->getAll($entity, [$id], $render);
    return (!count($rows)) ? null : $rows[0];
  }

  public function getAll($entityName, $ids, $render = null){ //busqueda por ids
    if(empty($ids)) return [];
    if(!is_array($ids)) $ids = [$ids];
    if(!$render) $render = new EntityQuery();
    $render->size(false);
    $render->addCondition(["id","=",$ids]);
    $render->setFields($this->container->tools($entityName)->fieldNames());

    return $this->all($entityName, $render);
  }

  public function one($entity, $render = null) { //un solo valor
    $rows = $this->all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else throw new Exception("La consulta no arrojó resultados");
  }

  public function first($entity, $render = null) { //un solo valor
    $rows = $this->all($entity, $render);
    if (empty($rows)) throw new Exception("La consulta no arrojó resultados");
    return $rows[0];
  }

  public function firstOrNull($entity, $render = null) { //un solo valor
    $rows = $this->all($entity, $render);
    return empty($rows) ? null : $rows[0];
  }

  public function oneOrNull($entity, $render = null) { //un solo valor o null
    $rows = $this->all($entity, $render);
    if(count($rows) > 1 ) throw new Exception("La consulta retorno mas de un resultado");
    elseif(count($rows) == 1) return $rows[0];
    else return null;
  }


  public function persist($entity, array $row){
    /**
     * Persistencia directa (no realiza chequeo de valores ni log)
     */
    $row_ = $this->unique($entity, $row); 
    
    if (!empty($row_)){ 
      $row["id"] = $row_["id"];
      return $this->update($entity, $row);
    }

    return $this->insert($entity, $row);
  }

  public function persistId($entity, array $row){
    /**
     * Persistencia directa (no realiza chequeo de valores ni log)
     */
    if (!empty($row["id"])) return $this->update($entity, $row);
    return $this->insert($entity, $row);
  }

  public function insert($entity, array $row){ 
    /**
     * Insercion directa (no realiza chequeo de valores)
     */
    $insert = $this->container->persist($entity)->insert($row);
    $result = $this->query($insert["sql"]);
    return array("id" => $insert["id"], "detail"=>$insert["detail"]);
  }

  public function update($entity, array $row){
    /**
     * Actualizacion directa (no realiza chequeo de valores)
     */ 
    $update = $this->container->getSql($entity)->update($row);
    $result = $this->query($update["sql"]);
    return array("id" => $update["id"], "detail"=>$update["detail"]);
  }

  public function delete($entityName, array $ids){ 
    /**
     */
    $sql = $this->container->persist($entityName)->delete($ids);
    $result = $this->query($sql);
    return array("ids" => $ids, "detail"=>preg_filter('/^/', $entityName, $ids));
  }


}