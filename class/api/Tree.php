<?php

require_once("class/api/Base.php");

class TreeApi extends BaseApi {

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    $render = $this->container->getRender($this->entityName);
    return $this->container->getEntityTree($render->entityName);    
  }
}



