<!DOCTYPE html>
<html lang="de">
	<head>
		<title>FVS - Neuer Interner Antrag</title>
<?php   include("../template/head.tpl"); ?>

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
						<th>Status</th>
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
						echo "<td>" . (($status == 2) ? "<span data-status='2' class='label label-success'>DONE</span>": (($status == 1) ? "<span data-status='1' class='label label-warning'>WIP</span>":"<span data-status='0' class='label label-danger'>TODO</span>")) . "</td>";
						//Zeilenende
						echo "</tr>";
						// Ausgeklappter Content
						echo "<tr>";
						echo "<td class='content' colspan=42>"; //>> max. Anzahl
						// Inhalt des Ausklappbaren
						echo "<div class='containter'>";

						echo "<div class='row'>
										<div class='input-group col-md-6'>
											<label> Inhaltliche Richtigkeit</label>
											<span class='input-group-btn'>
												<select class='selectpicker form-control' title='Beschlossen durch' class='beschluss-pick' id='beschluss-pick-$id'>
													<option>HV</option>
													<option>StuRa Beschluss vom</option>
												</select>
											</span>
											<input type='text' class='form-control'>
										</div>

										<div class='col-md-6'>
											<label from='hhp-pick-$id'>HHP-Titel</label>
											<select class='selectpicker form-control' class='hhp-pick' id='hhp-pick-$id'>
												<option> TITEL </option>
												<option> TITEL </option>
											</select>
										</div>
									</div>

									<div class='row'>
										<div class='input group col-md-4'>
											<label>Projektverantwortlich</label>
											<a href='mailto:$mail'>$name</a>
										</div>
										<div class='input group col-md-4'>
											<label>Beschluss</label>
											<a href='$link' target='_blank'>$link</a>
										</div>
										<div class='input group col-md-4'>
											<label>Projektdauer </label>
											von $begin bis $ende </li>
										</div>
									</div>
									<label> Beschreibung</label>
									$beschreibung

									";
						for($i = 0; $i < count($nr); $i++){
							echo $nr[$i];
							echo $postenbeschreibung[$i];
							echo $einnahme[$i];
							echo $ausgabe[$i];
						}

						echo "</div>"; // end Container

						echo "</td>";//End Zelle
						echo "</tr>"; //End Zeile
					}
					?>
				</tbody>
			</table>
		</div>

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

				//Hide wrong labels
				$(".label [data-status!= ]")

			});
		</script>
	</body>

</html>