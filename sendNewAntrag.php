<?php
echo "Hallo Welt0";
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $dbh = new PDO("mysql:dbname=finanzen_intern;host=localhost","fvs","dkURw8yL5xx9f2na");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}
echo "2";
if(isset($_REQUEST['submit']))
{
    $errorMessage = false;
    $projekt_titel = $_POST['projekt-titel'];
    $projekt_institution = $_POST['von-pick'];
    $projekt_verantwortlich = $_POST['projekt-verantwortlich'];
    $projekt_beschluss = $_POST['projekt-beschluss'];
    $date_von = $_POST['date-von'];
    $date_bis = $_POST['date-bis'];
    $beschreibung=$_POST['comment'];

    // Validation will be added here
    echo "3";

    if ($errorMessage !== false ) {
      die("<p class='message'>" .$errorMessage. "</p>" );
    } else{
      //Inserting record in table using INSERT query
      try {
	  echo "4";
          $insStmt = $dbh->prepare("INSERT INTO `antraege` (`titel`, `orga`, `mail`, `link`, `begin`, `ende`, `beschreibung`) VALUES ( ?, ?, ?, ?, ?, ?, ?);");
          $insStmt->execute(array($projekt_titel, $projekt_institution, $projekt_verantwortlich, $projekt_beschluss, $date_von, $date_bis, $beschreibung));
          echo $insStmt->errorInfo()[2];
	  // get autoincrement id from dataset
          $proj_id = $dbh->lastInsertId();
          //post rest of the data in other table
          $i = 1;
	  echo "5";
          while(isset($_POST['titel-'. $i]) && isset($_POST['in-'. $i]) && isset($_POST['out-'. $i])){
							$titel = $_POST['titel-'. $i];
							$in = $_POST['in-'. $i];
							$out = $_POST['out-'. $i];
							//more validation here
							$insStmt = $dbh->prepare("INSERT INTO `posten` (`proj-id`, `nr`, `beschreibung`, `einnahme`, `ausgabe`) VALUES (?,?,?,?,?)");
							$insStmt->execute(array($proj_id, $i, $titel, $in, $out));
							$i++;
							echo "LOOP";
					}

      } catch (PDOException $e) {
	  echo "DEAD";
          die('Query failed: ' . $e->getMessage());
      }
   }

}

