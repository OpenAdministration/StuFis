<table class="table table-striped">

<thead>

<tr><th>ID</th><th>Ersteller</th><th>letztes Update</th><th>Token</th></tr>

</thead>
<tbody>

<?php

foreach ($antraege as $type => $l0) {
  foreach ($l0 as $revision => $l1) {
    echo "<tr><th colspan=\"4\">".htmlspecialchars("{$type} - {$revision}")."</th></tr>\n";
    foreach ($l1 as $antrag) {
      echo "<tr>";
      echo "<td>".htmlspecialchars($antrag["id"])."</td>";
      echo "<td>".htmlspecialchars($antrag["creator"])."</td>";
      echo "<td>".htmlspecialchars($antrag["lastupdated"])."</td>";
      echo "<td><a href=\"{$URIBASE}/".htmlspecialchars($antrag["token"])."\">".htmlspecialchars($antrag["token"])."</a></td>";
      echo "</tr>";
    }
  }
}
?>

</tbody>
</table>

<?php

# vim:syntax=php
