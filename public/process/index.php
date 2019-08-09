<?php

require_once("class/view/Admin.php");

$viewList = new EntityViewList($entity);
$viewList->setCondition($params);
$viewList->display();