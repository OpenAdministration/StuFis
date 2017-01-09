<!DOCTYPE html>
<html lang="de">
	<head>
		<!-- Das neueste kompilierte und minimierte CSS -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">


		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">


		<meta name="description" content="">
		<meta name="author" content="">


		<title>FVS - Neuer Interner Antrag</title>

		<link href="main.css" rel="stylesheet">
		<!--https://datatables.net/download/index -->
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/pdfmake-0.1.18/dt-1.10.13/b-colvis-1.2.4/fh-3.1.2/datatables.min.css"/>

		<script type="text/javascript" src="https://cdn.datatables.net/v/bs/pdfmake-0.1.18/dt-1.10.13/b-colvis-1.2.4/fh-3.1.2/datatables.min.js"></script>



	</head>
	<body>

		<nav class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">FVS - Finanz Verwaltungs System Interne Anträge</a>
				</div>
				<div id="navbar" class="navbar-collapse collapse">
					<form class="navbar-form navbar-right">
						<div class="form-group">
							<input type="text" placeholder="Email" class="form-control">
						</div>
						<div class="form-group">
							<input type="password" placeholder="Password" class="form-control">
						</div>
						<button type="submit" class="btn btn-success">Login</button>
					</form>
				</div><!--/.navbar-collapse -->
			</div>
		</nav>

		<div class="container">
			<div class="btn-group" data-toggle="buttons">
				<label class="btn">
					<input type="radio" name="options" id="option1" autocomplete="off"> Alle
				</label>
				<label class="btn btn-danger">
					<input type="radio" name="options" id="option1" autocomplete="off"> Neu
				</label>
				<label class="btn btn-warning">
					<input type="radio" name="options" id="option2" autocomplete="off"> WIP
				</label>
				<label class="btn btn-success">
					<input type="radio" name="options" id="option3" autocomplete="off"> DONE
				</label>
			</div>



			<table class="table">
				<thead>
					<th>Nr</th>
					<th>von</th>
					<th>Beginn</th>
					<th>Ende</th>
					<th>Status</th>
				</thead>
				<tbody>
					<?php
					error_reporting(E_ALL);
					ini_set('display_errors', 1);

					try {
						$dbh = new PDO("mysql:dbname=finanzen_intern;host=localhost","fvs","dkURw8yL5xx9f2na");
						$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					} catch (PDOException $e) {
						die('Connection failed: ' . $e->getMessage());
					}
					$insStmt = $dbh->prepare("SELECT * FROM `antraege`");//may filter here
					$insStmt->execute();
					foreach ($insStmt->fetchAll(PDO::FETCH_ASSOC) as $row){
						$id = $row['id'];
						$titel = $row['titel'];
						$org = $row['orga'];
						$mail = $row['mail'];
						$link = $row['link'];
						$begin = $row['begin'];
						$ende = $row['ende'];
						$beschreibung = $row['beschreibung'];
						$eingang = $row['eingang'];
						$status = $row['status'];
						$antragStm = $dbh->prepare("SELECT * FROM `posten` WHERE `proj-id`=?");
						$antragStm->execute();
						foreach ($antragStm->fetchAll(PDO::FETCH_ASSOC) as $posten){
							$nr = $posten['nr'];
							$postenbeschreibung = $posten['beschreibung'];
							$einnahme = $posten['einnahme'];
							$ausgabe = $posten['ausgabe'];
						}
						echo "<tr>"; // neue Zeile
						//Neue Zellen
						echo "<td>$id</td>";
						echo "<td>$org</td>";
						echo "<td>$begin</td>";
						echo "<td>$ende</td>";
						echo "<td>$status</td>";

						echo "</tr>";
					}
					?>
				</tbody>
			</table>
		</div>



		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<!-- http://bootsnipp.com/snippets/featured/easy-table-filter-->
		<script>
			$(document).ready(function () {

				$('.btn-filter').on('click', function () {
					var $target = $(this).data('target');
					if ($target != 'all') {
						$('.table tr').css('display', 'none');
						$('.table tr[data-status="' + $target + '"]').fadeIn('slow');
					} else {
						$('.table tr').css('display', 'none').fadeIn('slow');
					}
				});
			});
		</script>
	</body>





</html>
