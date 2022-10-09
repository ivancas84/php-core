<?php

function get_entity_rel($entityName) {
  $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_SRC . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entity-relations.json");
  $tree = json_decode($string, true);
  return array_key_exists($entityName, $tree) ? $tree[$entityName] : [];
}
