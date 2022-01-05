<?php

require_once("class/api/Base.php");
require_once("function/get_entity_tree.php");

class TreeApi extends BaseApi {

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    $render = $this->container->getRender($this->entityName);
    return get_entity_tree($render->entityName);    
  }
}



