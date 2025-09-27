<?php
if (!defined('ABSPATH')) exit;

class Resales_Settings {
  private static $instance = null;
  private $p1;
  private $p2;
  private $api_id;
  private $lang;

  private function __construct() {
    // Mantén los mismos option_names que ya usa el plugin
    $this->p1     = get_option('resales_api_p1');
    $this->p2     = get_option('resales_api_p2');
    $this->api_id = get_option('resales_api_apid'); // usamos P_ApiId, NO P_Agency_FilterId
    $this->lang   = get_option('resales_api_lang', 1);

    // Validaciones mínimas
    if (!$this->p1 || !$this->p2 || !$this->api_id) {
      resales_log('WARN', 'API settings incompletos', ['p1'=>(bool)$this->p1,'p2'=> (bool)$this->p2,'api_id'=>(bool)$this->api_id]);
    }
  }

  public static function instance() {
    if (self::$instance === null) self::$instance = new self();
    return self::$instance;
  }

  public function get_p1()     { return $this->p1; }
  public function get_p2()     { return $this->p2; }
  public function get_api_id() { return $this->api_id; }
  public function get_lang()   { return $this->lang; }
}