<?php

function httperror($err) {
  if (!is_string($err)) {
    $err = print_r($err, true);
  }
  header("HTTP/1.0 500 Error");
  header("Status: 500 Error");
  $e = new Exception;
  $backtrace = $e->getTraceAsString();
  echo "<pre>\n";
  echo "Error: $err\n";
  echo "Backtrace:\n $backtrace\n";
  echo "</pre>\n";
  die($err);
}
