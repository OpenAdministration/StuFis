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

		<!--https://datatables.net/download/index
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/pdfmake-0.1.18/dt-1.10.13/b-colvis-1.2.4/fh-3.1.2/datatables.min.css"/>

<script type="text/javascript" src="https://cdn.datatables.net/v/bs/pdfmake-0.1.18/dt-1.10.13/b-colvis-1.2.4/fh-3.1.2/datatables.min.js"></script>-->



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
			<div class="pull-right">
				<div class="btn-group">
					<button type="button" class="btn btn-default btn-filter" data-target="all">ALL</button>
					<button type="button" class="btn btn-danger btn-filter" data-target="0">NEW</button>
					<button type="button" class="btn btn-warning btn-filter" data-target="1">WIP</button>
					<button type="button" class="btn btn-success btn-filter" data-target="2">DONE</button>

				</div>
			</div>

			<table class="table">
				<thead>
					<tr class="header">
						<th>Nr</th>
						<th>Titel</th>
						<th>von</th>
						<th>Eingegangen</th>
					</tr>
				</thead>
				<tbody>
					<?php
					error_reporting(E_ALL);
					ini_set('display_errors', 1);
					//Get Data from DB
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
						$antragStm->execute([$id]);

						$nr = array();
						$postenbeschreibung = array();
						$einnahme = array();
						$ausgabe = array();

						foreach ($antragStm->fetchAll(PDO::FETCH_ASSOC) as $posten){
							$nr[] = $posten['nr'];
							$postenbeschreibung[] = $posten['beschreibung'];
							$einnahme[] = $posten['einnahme'];
							$ausgabe[] = $posten['ausgabe'];
						}

						$name = explode("@",str_replace(".", " ", $mail))[0];

						echo "<tr class='head-row' data-status='$status'>"; // neue Zeile
						//Neue Zellen
						echo "<td>$id</td>";
						echo "<td>$titel</td>";
						echo "<td>$org</td>";
						echo "<td>$eingang</td>";
						//Zeilenende
						echo "</tr>";
						// Ausgeklappter Content
						echo "<tr>";
						echo "<td class='content' colspan=42>"; //>> max. Anzahl
						// Inhalt des Ausklappbaren
						echo "<div class='containter'";
						echo "<ul class='list-group row'>
							<li class='list-group-item col-md-4'>
								<div class='row'>
									<select class='col-md-4 selectpicker form-control' title='Beschlossen durch' class='beschluss-pick' id ='beschluss-pick-$id'>
										<option>Haushaltsverantwortlicher</option>
										<option>StuRa Beschluss vom</option>
									</select>
      						<input type='text' class=' col-md-8 form-control'>
    						</div>
							</li>
							<li class='list-group-item col-md-4'><b>HHP-Titel</b> </li>
							<li class='list-group-item col-md-4'><b>Genehmigt KV</b> </li>
    					<li class='list-group-item col-md-4'><b>Projektverantwortlich</b> <a href='mailto:$mail'>$name</a></li>
							<li class='list-group-item col-md-4'><b>Beschluss</b> <a href='$link' target='_blank'>$link</a></li>
							<li class='list-group-item col-md-4'><b>Projektdauer</b> von $begin bis $ende </li>
							<li class='list-group-item col-md-12'><b>Projektbeschreibung</b> $beschreibung</li>

						</ul>";
						for($i = 0; $i < count($nr); $i++){
							echo $nr[$i];
							echo $postenbeschreibung[$i];
							echo $einnahme[$i];
							echo $ausgabe[$i];
						}

						echo "</div>";

						echo "</td>";//End Zelle
						echo "</tr>"; //End Zeile
					}
					?>
				</tbody>
			</table>
		</div>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/js/bootstrap-select.min.js"></script>
		<!-- http://bootsnipp.com/snippets/featured/easy-table-filter-->
		<script>
			$(document).ready(function () {

				$('.btn-filter').on('click', function () {
					var $target = $(this).data('target');
					$('.content').hide();
					if ($target != 'all') {
						$('.head-row').hide();
						$('.head-row[data-status="' + $target + '"]').fadeIn('slow');
					} else {
						$('.head-row').hide().fadeIn('slow');
					}
				});


				$("table .head-row").click(function(event) {

					// No bubbling up
					event.stopPropagation();

					var $target = $(event.target);

					// Open and close the appropriate thing
					if ( $target.closest("td").attr("colspan") > 1 ) {
						//$target.closest("td").slideUp('slow');
					} else {
						$target.closest("tr").next().find("td").slideToggle('slow');
					}

				});

				// Initially hide toggleable content
				$('.content').hide();

			});
		</script>
	</body>

</html>
