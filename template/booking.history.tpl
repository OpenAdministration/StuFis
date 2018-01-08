<?php
//var_dump($content);

?>
<div class="container">
    <table class="table" align="right">

        <?php
        //for each title

        ?>

        <thead>
            <tr>
                <th>Lfd. Nr</th>
                <th>Datum</th>
                <th>Beleg ID</th>
                <th>Betrag (EUR)</th>
                <th>Titel</th>
                <th>Zahlungs ID</th>
                <th>Kommentar</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($content as $lfdNr => $res){
                echo "<tr>";
                echo "<td>";
                echo $lfdNr +1;
                echo "</td>";
                echo "<td>";
                echo $res['datum'];
                echo "</td>";
                echo "<td>";
                echo $res['belegId'];
                echo "</td>";
                echo "<td align='right'>";
                echo number_format(abs($res['ausgaben']-$res['einnahmen']),2). "€";
                echo "</td>";
                echo "<td>";
                echo $res['titel'];
                echo "</td>";
                echo "<td>";
                echo $res['zahlungId'];
                echo "</td>";
                echo "<td>";
                //echo $res['zahlungId'];
                echo "</td>";

                echo "</tr>";
            }

            ?>
        </tbody>

    </table>


