<?php
require_once("class/Filter.php");
require_once("class/view/List.php");

$entity = Filter::requestRequired("entity");
$search = Filter::request("search");

$viewList = new EntityViewList($entity);
$viewList->search();
print_r($viewList->rows);



//$view = new View();
//$view->content = "public/list/template.html";
//$view->display();