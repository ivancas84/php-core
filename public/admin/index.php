<?php
require_once("class/tools/Filter.php");
require_once("class/view/Admin.php");

$admin = EntityViewAdmin::getInstanceRequire(ENTITY);
$admin->main();