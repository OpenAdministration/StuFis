<?php

require_once 'HTTP/Request2.php';

class HTTP_Request2_SNI extends HTTP_Request2 {
    public function __construct(
        $url = null, $method = self::METHOD_GET, array $config = array()
    ) {
      parent::__construct($url, $method, $config);
      $this->config['ssl_SNI_enabled'] = true;
      $this->config['ssl_verify_host'] = false;
      $this->config['ssl_verify_peer'] = true;
      //$this->config['ssl_SNI_server_name'] = '';
    }
    public function setConfig($nameOrConfig, $value = null) {
      if ($nameOrConfig == "ssl_verify_host") {
        // used to generate a CN_match, so not set
      } elseif ($nameOrConfig == "ssl_verify_peer") {
        parent::setConfig("ssl_SNI_enabled", true);
        parent::setConfig("ssl_verify_host", false);
        parent::setConfig("ssl_verify_peer", $value);
      } else {
        return parent::setConfig($nameOrConfig, $value);
      }
      return $this;
    }
}

