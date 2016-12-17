<?php
$link = mysqli_connect("localhost","fvs","dkURw8yL5xx9f2na")  or die("failed to connect to server !!");
mysqli_select_db($link,"finanzen_intern");
if(isset($_REQUEST['submit']))
{
$errorMessage = "";
$projekt_titel=$_POST['projekt-titel'];
$projekt_institution = $_POST['von-pick']
$projekt_verantwortlich = $_POST['projekt-verantwortlich'];
$projekt_beschluss=$_POST['projekt-beschluss']
$date_von=$_POST['date-von'];
$date_bis=$_POST['date-bis'];
$beschreibung=$_POST['comment'];

// Validation will be added here

if ($errorMessage != "" ) {
echo "<p class='message'>" .$errorMessage. "</p>" ;
}
else{
//Inserting record in table using INSERT query
$insqDbtb="INSERT INTO `finanzen_intern`.`antraege`
(`titel`, `orga`, `mail`, `link`, `begin`,
`ende`, `beschreibung`) VALUES ('$projekt_titel', '$projekt_institution',
'$projekt_verantwortlich', '$projekt_beschluss', '$date_von', '$date_bis', '$beschreibung')";
mysqli_query($link,$insqDbtb) or die(mysqli_error($link));
}
}
?>
