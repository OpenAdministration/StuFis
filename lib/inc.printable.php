<?php

function loadCSS($fileName) {
  global $URIBASE;
  global $inlineCSS;

  $fullFileName = SYSBASE."/www/css/".$fileName;
  if (!$inlineCSS || !file_exists($fullFileName)) {
    return "<link href=\"{$URIBASE}/css/{$fileName}\" rel=\"stylesheet\">\n";
  }

  $out = "<style type=\"text/css\">";
  $css = file_get_contents($fullFileName);

  $css = preg_replace_callback("/url\(([^)]*)\)/", function($treffer) use($fullFileName) {
    $url = parse_url(trim($treffer[1],"'\""));
    $file = dirname($fullFileName)."/".$url["path"];
    if (!file_exists($file)) {
      echo "missing $file<br/>\n";
      return $treffer[0];
    }
    $data = file_get_contents($file);
    $mime = mime_content_type($file);
    return "url(data:{$mime};charset=utf-8;base64,".base64_encode($data).")";
  }, $css);

  $out .= $css;
  $out .= "</style>";

  return $out;
}
