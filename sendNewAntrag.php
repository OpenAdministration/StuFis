<?php
echo "Hallo Welt0";

try {
    $dbh = new PDO("mysql:dbname=finanzen_intern;host=localhost","fvs","dkURw8yL5xx9f2na");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

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


    if ($errorMessage !== false ) {
      die("<p class='message'>" .$errorMessage. "</p>" );
    } else{
      //Inserting record in table using INSERT query
      print("Hallo Welt1");
      try {
          $insStmt = $dbh->prepare("INSERT INTO `antraege` (`titel`, `orga`, `mail`, `link`, `begin`, `ende`, `beschreibung`) VALUES ( ?, ?, ?, ?, ?, ?, ?)");
          $insStmt->execute($projekt_titel, $projekt_institution, $projekt_verantwortlich, $projekt_beschluss, $date_von, $date_bis, $beschreibung);
      } catch (PDOException $e) {
          die('Query failed: ' . $e->getMessage());
      }
   }
   print("Hallo Welt2");
}
print("Hallo Welt3");
