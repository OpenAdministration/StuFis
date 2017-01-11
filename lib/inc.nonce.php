<?php

global $nonce;

if (isset($_COOKIE["Nonce"]) && !empty($_COOKIE["Nonce"])) {
  $nonce = $_COOKIE["Nonce"];
} else {
  $nonce = randomstring();
  setcookie("Nonce", $nonce, 0);
}

/* return a random ascii string */
function randomstring($length = 32) {
  $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
  srand((double)microtime()*1000000);
  $pass = "";
  for ($i = 0; $i < $length; $i++) {
    $num = rand(0, strlen($chars)-1);
    $pass .= substr($chars, $num, 1);
  }
  return $pass;
}

