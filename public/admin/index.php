<?php
require_once("class/Filter.php");
require_once("class/view/Admin.php");

$admin = EntityViewAdmin::getInstanceRequire(ENTITY);
$admin->main();