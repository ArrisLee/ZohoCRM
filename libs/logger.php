<?php
/**
 * Simple logger for CF7 Zoho Leads plugin
 */

class SimpleLogger {
  const ERROR = 1;
  const WARN = 2;
  const INFO = 3;
  const DEBUG = 4;

  public function __construct($level) {
    $this->level = $level;
  }

  private function log($msg, $args) {
    error_log(addslashes(vsprintf($msg, $args)));
  }

  public function error($msg) {
    if( $this->level >= self::ERROR )
      $this->log($msg, array_slice(func_get_args(), 1));
  }

  public function warn($msg) {
    if( $this->level >= self::WARN )
      $this->log($msg, array_slice(func_get_args(), 1));
  }

  public function info($msg) {
    if( $this->level >= self::INFO )
      $this->log($msg, array_slice(func_get_args(), 1));
  }

  public function debug($msg) {
    if( $this->level >= self::DEBUG )
      $this->log($msg, array_slice(func_get_args(), 1));
  }
}
