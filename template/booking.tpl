<div class="alert alert-info">
Bitte wähle nur eine Zahlung oder nur einen Grund und dann beliebig viele zugehörige Gründe bzw. Zahlungen aus.
</div>

<form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
  <input type="hidden" name="action" value="booking">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>

<table>
<thead><tr><th class="text-center col-xs-6">Zahlungen</th><th class="text-center col-xs-6">Zahlungsgründe</th></tr></thead>
<tbody>
<tr><td valign="top">

 <div>Ausgewählte Summe: <span class="checkbox-summing-output"></span> €</div>

 <table class="table table-striped checkbox-summing">
 <thead><tr><th></th><th class="col-xs-2">Betrag</th><th>ID</th><th>Verwendungszweck</th></tr></thead>
 <tbody>
<?php

foreach ($alZahlung as $a) {
  $value = $a["_value"];
  $fvalue = convertDBValueToUserValue($value, "money");

  echo "<tr>";
  echo "<td>";
  echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"".$fvalue."\" name=\"zahlungId[]\" value=\"".htmlspecialchars($a["id"])."\">";
  echo "</td>";
  echo "<td class=\"text-right\">";
  echo htmlspecialchars($fvalue." €");
  echo "<input type=\"hidden\" name=\"zahlungValue[".$a["id"]."]\" value=\"$value\">";
  echo "</td>";
  echo "<td valign=\"top\">";
  echo $a["id"];
  echo "</td>";
  $revConfig = $a["_form"]["config"];
  $caption = getAntragDisplayTitle($a, $revConfig);
  $caption = trim(implode(" ", $caption));
  $url = str_replace("//","/", $URIBASE."/".$a["token"]);
  echo "<td><a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
  echo "</tr>";

}

?>

 </tbody>
 </table>


</td><td valign="top">

 <div>Ausgewählte Summe: <span class="checkbox-summing-output"></span> €</div>
 
 <table class="table table-striped checkbox-summing">
 <thead><tr><th></th><th class="col-xs-2">Betrag</th><th>ID</th><th>Verwendungszweck</th></tr></thead>
 <tbody>
<?php

foreach ($alGrund as $a) {
  $value = $a["_value"];
  $fvalue = convertDBValueToUserValue($value, "money");

  echo "<tr>";
  echo "<td>";
  echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"".$fvalue."\" name=\"grundId[]\" value=\"".htmlspecialchars($a["id"])."\">";
  echo "</td>";
  echo "<td class=\"text-right\">";
  echo htmlspecialchars($fvalue." €");
  echo "<input type=\"hidden\" name=\"grundValue[".$a["id"]."]\" value=\"$value\">";
  echo "</td>";
  echo "<td valign=\"top\">";
  echo $a["id"];
  echo "</td>";
  $revConfig = $a["_form"]["config"];
  $caption = getAntragDisplayTitle($a, $revConfig);
  $caption = trim(implode(" ", $caption));
  $url = str_replace("//","/", $URIBASE."/".$a["token"]);
  echo "<td><a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
  echo "</tr>";

}

?>

 </tbody>
 </table>

</td></tr>
</tbody>
</table>

<input type="submit" name="absenden" value="Buchungen zuordnen" class="btn btn-primary pull-right">

</form>

<?php
# vim: set syntax=php:
