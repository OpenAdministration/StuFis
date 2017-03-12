<form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
  <input type="hidden" name="action" value="hibiscus.sct">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>


<table class="table table-striped">
 <thead><tr><th class="col-xs-2">Betrag</th><th>Empfänger</th><th>IBAN</th><th>ID</th><th>Verwendungszweck</th></tr></thead>
 <tbody>
<?php
$sum = 0.00;

foreach ($antraege as $a) {
  $value = $a["_value"];
  $fvalue = convertDBValueToUserValue($value, "money");
  $sum += $value;

  echo "<tr>";
  echo "<td class=\"text-right\">";
  echo htmlspecialchars($fvalue." €");
  echo "<input type=\"hidden\" name=\"ueberweisung[".$a["id"]."][betrag]\" value=\"".htmlspecialchars($value)."\">";
  echo "</td>";
  echo "<td valign=\"top\">";
  echo htmlspecialchars($a["_empfname"]);
  echo "<input type=\"hidden\" name=\"ueberweisung[".$a["id"]."][empfname]\" value=\"".htmlspecialchars($a["_empfname"])."\">";
  echo "</td>";
  echo "<td valign=\"top\">";
  echo htmlspecialchars($a["_iban"]);
  echo "<input type=\"hidden\" name=\"ueberweisung[".$a["id"]."][empfiban]\" value=\"".htmlspecialchars($a["_iban"])."\">";
  echo "</td>";
  echo "<td valign=\"top\">";
  echo $a["id"];
  echo "<input type=\"hidden\" name=\"ueberweisung[".$a["id"]."][eref]\" value=\"StuRa-".htmlspecialchars($a["id"])."\">";
  echo "</td>";
  $revConfig = $a["_form"]["config"];
  $classConfig = $a["_form"]["_class"];
  $caption = getAntragDisplayTitle($a, $revConfig);
  $caption = trim(strip_tags(implode(" ", $caption)));
  $classTitle = "{$a["type"]}";
  if (isset($classConfig["title"]))
    $classTitle = "{$classConfig["title"]}";
  if (isset($classConfig["shortTitle"]))
    $classTitle = "{$classConfig["shortTitle"]}";
  $vzw = "StuRa-{$a["id"]} {$classTitle}\n$caption";
  $url = str_replace("//","/", $URIBASE."/".$a["token"]);
  echo "<td><a href=\"".htmlspecialchars($url)."\">".str_replace("\n","<br>\n", htmlspecialchars($vzw))."</a>";
  foreach (explode("\n", $vzw) as $j => $vzwline) {
    echo "<input type=\"hidden\" name=\"ueberweisung[".$a["id"]."][vzw][{$j}]\" value=\"".htmlspecialchars($vzwline)."\">";
  }
  echo "</td>";
  echo "</tr>";

}

?>

 </tbody>
 <tfoot>
 <tr><th class="text-right">
<?php
  $fsum = convertDBValueToUserValue($sum, "money");
  echo htmlspecialchars("Σ ".$fsum." €");
?>
 </th><th colspan="4">
<?php
echo count($antraege)." Überweisungen";
?>
 </th></tr>
 </tfoot>
 </table>

<input type="submit" name="absenden" value="Buchungen zuordnen" class="btn btn-primary pull-right">

</form>

<?php
# vim: set syntax=php:

