<?

class FileCache {
  public static function set ($key, $val) {
    $val = var_export($val, true);
    $tmp = "/tmp/$key." . uniqid('', true) . '.tmp';
    file_put_contents($tmp, '<?php $val = ' . $val . ';', LOCK_EX);
    rename($tmp, "/tmp/$key");
  }

  public static function get($key) {
    @include "/tmp/$key";
    return isset($val) ? $val : null;
  }
}
