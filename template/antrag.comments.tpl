<?php

if (count($antrag["_comments"]) == 0) return;

?>
<div class="clearfix"> </div>

<div class="panel panel-default">
<div class="panel-heading">Kommentare</div>
<div class="panel-body">

<table class="table table-striped">
 <thead>
  <tr>
   <th>ID</th>
   <th>Wann</th>
   <th>Wer</th>
   <th>Nachricht</th>
  </tr>
 </thead>
 <tbody>
<?php

foreach ($antrag["_comments"] as $c) {
?>
  <tr>
   <td><?php echo htmlspecialchars($c["id"]); ?></td>
   <td><?php echo htmlspecialchars($c["timestamp"]); ?></td>
   <td><?php
       if (($c["creator"] == $c["creatorFullName"]) || empty($c["creatorFullName"])) {
         echo htmlspecialchars($c["creator"]);
       } else {
         echo "<span title=\"";
         echo htmlspecialchars($c["creator"]);
         echo "\">";
         echo htmlspecialchars($c["creatorFullName"]);
         echo "</span>";
       }?>
   </td>
   <td><?php echo htmlspecialchars($c["text"]); ?></td>
  </tr>
<?php
}

?>
 </tbody>

</table>

</div>
</div>

<?php
# vim:syntax=php
