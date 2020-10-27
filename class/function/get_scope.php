<?php

function get_scope() {
  /**
   * Este metodo debe ser reimplementado para indicar el scope asignado a cada rol
   * return [
   *   "rol1" => [
   *     "entity1" => ["resource1", "resource2", "..."]
   *     "entity2" => ["resource1", "resource2", "..."]
   *     "..." => ["..."]
   *   ],
   *   "rol2" => ["..."]
   * ]
   */
  return [];
}
