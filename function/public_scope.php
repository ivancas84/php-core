<?php

function get_public_scope() {
  /**
   * Este metodo debe ser reimplementado para indicar el private scope
   * return [
   *   "read" => ["entity1", "entity2", "..."]
   *   "write" => ["entity1", "entity2", "..."]
   *   "..." => ["..."]
   * ]
   */
  return [];
}
