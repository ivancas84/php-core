<?php
require_once("class/tools/Filter.php");
require_once("class/view/List.php");

$params = Filter::requestAll();
if(!isset($params["entity"])) throw new Exception("La entidad no se encuentra definida");
$entity = $params["entity"];
unset($params["entity"]);

$viewList = new EntityViewList($entity);
$viewList->setCondition($params);
$viewList->display();