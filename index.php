<?php
//Inhaltstyp und Zeichenkodierung festlegen
header('Content-Type: text/html; charset=utf-8');

//Set language code
$lang = "de_DE";

include_once("./locale/" . $lang . ".php");

function t($str) {
	global $translate;
	return $translate[$str];
}

//Datenbank (SQLite) einbinden
$db = new SQLite3("manager.info");

//Parameter p auf Standard "/" setzen, wenn leer.
if (!isset($_GET['p']) OR trim($_GET['p']) == "") {
	header("Location: ?p=/");
}

function getSetting($name) { //Wert einer Einstellung aus Datenbank lesen
	global $db; //DB-Variable global einbinden
	$settings = $db->query("SELECT VALUE FROM settings WHERE NAME = '" . $name . "';")->fetchArray(); //Datenbankabfrage des Wertes und direktes fetchen als Array
	return $settings["VALUE"]; //Wertrückgabe
}

if ($_GET['p'] == "New.Install") { //Installation per p-Parameter (?p=New.Install)
	//Installation ausführen
	$rowcount = $db->query("SELECT COUNT(*) AS ROWCOUNT FROM settings WHERE ID < 4;")->fetchArray(); //Anzahl Reihen mit ID unter 4 abfragen und als Array fetchen
	if ($rowcount['ROWCOUNT'] == 0) { //Reihen nicht vorhanden
		$db->exec("INSERT INTO settings (ID, NAME, VALUE) VALUES (1, 'getcwdStandard', '" . getcwd() . "'), (2, 'baseDir', '/manager'), (3, 'catproIdA_I', 4);"); //Erstellen der Reihen
		header("Location: ?p=/"); //Installation beenden -> Standard-Verzeichnis
	} elseif ($rowcount['ROWCOUNT'] == 3) { //Reihen vorhanden
		$db->exec("	DELETE FROM settings WHERE ID < 4;
					INSERT INTO settings (ID, NAME, VALUE) VALUES (1, 'getcwdStandard', '" . getcwd() . "'), (2, 'baseDir', '/manager'), (3, 'catproIdA_I', 4);
		"); //UPDATE-Befehl durch Löschen und Neueinfügen der Daten umgehen xP
		header("Location: ?p=/"); //Installation beenden -> Standard-Verzeichnis
	} else { //Ein oder zwei Reihen vorhanden -> Keine Lust auf Fehlerabfang
		echo t("Installationsfehler."); exit; //Fehlerausgabe -> Skriptabbruch
	}
}

//unixUmlauts wandelt Umlaute und sz in Sonderzeichen um und UTF8 dekodiert diese
function unixUmlauts($inputString) {
	return utf8_decode(str_replace(array('Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'), array('%C4', '%D6', '%DC', '%E4', '%F6', '%FC', '%DF'), $inputString));
}

//Pfad aus Parameter p abfragen (wenn nicht "New.Install")
$getPath = $_GET['p'];
$failure = array("", "");

//Optionsformulare verarbeiten
if (isset($_POST['addCP'])) { //POST-Parameter addCP (submit-name) gesendet - Kategorie/Projekt hinzufügen
	$nextFolderID = getSetting("catproIdA_I"); //Nächste Ordner-ID abfragen (aus Settings)
	if (strpos($_POST['CatProName'], "_") > 0 OR strpos($_POST['CatProName'], "/") > 0) { //Ordner erstellen abbrechen, wenn die Zeichen "/" oder "_" enthalten sind
		$failure = array("#000001", t("Das Projekt/Die Kategorie konnte nicht erstellt werden. '/' und '_' sind nicht erlaubt.")); //Fehlermeldung erstellen
	} else {
		$dir = $_POST['CatOrPro'] . "_" . $nextFolderID . "_" . $_POST['CatProName']; //Kategorie/Projekt _ Ordner-ID _ Ordnername (utf8decodiert)
		mkdir(utf8_decode("." . getSetting("baseDir") . $getPath . "/" . $dir)); //Pfad erstellen
		$nextnextFolderID = $nextFolderID + 1; //Ordner-ID erhöhen (für nächsten Ordner)
		$db->exec("UPDATE settings SET VALUE='" . $nextnextFolderID . "' WHERE ID = 3"); //Settings aktualisieren
	}
} elseif (isset($_POST['renameCP'])) { //POST-Parameter renameCP (submit-name) gesendet - Kategorie/Projekt umbenennen
	$folder_renameName = $_POST['CatProReName']; //Neuer Name
	if (strpos($folder_renameName, "_") > 0 OR strpos($folder_renameName, "/") > 0) { //Renaming abbrechen, wenn die Zeichen "/" oder "_" enthalten sind
		$failure = array("#000002", t("Das Projekt/Die Kategorie konnte nicht umbenannt werden. '/' und '_' sind nicht erlaubt.")); //Fehlermeldung erstellen
	} else {
		$c = explode("/", $getPath); //Aufgerufenen Pfad anhand von "/" auseinander nehmen
		$a = explode("_", array_pop($c)); //Letzten Teil des Pfades entfernen -> Rückgabe (entfernter Index) anhand von "_" auseinander nehmen
		$dir_old = utf8_decode("." . getSetting("baseDir") . $getPath); //Alter Pfad
		$folderPath = "." . getSetting("baseDir") . "/"; //Pfadanfang mit Basis-Pfad
		$folderPathURL = "/"; //URL-Pfadanfang mit Basis-Pfad (URL)
		for ($i=count($c)-1; $i>=0; $i--) { //Schleife zur Entfernung leerer Werte
			if ($c[$i] == '') unset ($c[$i]);
		}
		foreach ($c as $b) { //Pfad und URL-Pfad erstellen und erweitern
			$folderPath .= $b . "/";
			$folderPathURL .= $b . "/";
		}
		$folderPathURL = substr($folderPathURL, 0, -1); //Letztes "/" entfernen (URL-Pfad)
		$dir_new = utf8_decode($folderPath . $a[0] . "_" . $a[1] . "_" . $folder_renameName); //Neuer Pfad mit neuem Namen
		rename($dir_old, $dir_new); //Umbennen
		header("Location: ?p=$folderPathURL"); //Neuen (umbenannten) URL-Pfad aufrufen
	}
} elseif (isset($_POST['editP'])) { //POST-Parameter editCP (submit-name) gesendet - -/Projekt bearbeiten (Bild, Priorität, Bewertung)
	$folderPathExplode = explode("/", $getPath);
	$lastFolder = array_pop($folderPathExplode); //Letzten Ordner aus Pfad nehmen
	$folderID = explode("_", $lastFolder); //und anhand von "_" auseinander nehmen
	$folderID = $folderID[1]; //Letzter Ordner-ID
	$numFolderRows = $db->query("SELECT COUNT(*) AS FOLDERS FROM projectorizer WHERE ID='$folderID';")->fetchArray(); //Anzahl Reihen mit Letzter Ordner-ID zählen
	if ($numFolderRows['FOLDERS'] == 0) { //Wenn keine Reihe vorhanden ist
		$db->exec("INSERT INTO projectorizer (ID, PRIORITY, RATING, PICTURE) VALUES ($folderID, '" . $_POST['ProPriority'] . "', '" . $_POST['ProRating'] . "', '" . $_POST['ProImage'] . "');"); //Neue Zeile mit Angaben erstellen
	} else {	/*'" . $_POST[''] . "'*/ //Wenn Reihe bereits vorhanden ist
		$db->exec("UPDATE projectorizer SET PRIORITY='" . $_POST['ProPriority'] . "', RATING='" . $_POST['ProRating'] . "', PICTURE='" . $_POST['ProImage'] . "' WHERE ID = $folderID;"); //Zeile updaten
	}
} elseif (isset($_POST['uploadFilesP'])) { //POST-Parameter uploadFilesP (submit-name) gesendet - -/Projekt Datei hochladen
	$moveToPath = "." . getSetting('baseDir');
	$moveToPath .= ($getPath == "/") ? ($getPath) : ($getPath . "/"); 
	$moveToPath = utf8_decode($moveToPath);//Dateizielpfad erstellen
	for ($i = 0; $i < count($_FILES['uploadFiles']['name']); $i++) { //Solange Dateien in der Warteschleife hängen
		$tmp_name = $_FILES["uploadFiles"]["tmp_name"][$i]; //Temporären Dateinamen in Variable speichern
        $name = utf8_decode(basename($_FILES["uploadFiles"]["name"][$i])); //Dateinamen vom Ursprungsgerät in Variable speichern
        move_uploaded_file($tmp_name, $moveToPath . $name); //Temporären Namen in Ursprungsnamen ändern
	}
} elseif (isset($_POST['editNoticesP'])) { //POST-Parameter editNoticesP (submit-name) gesendet - -/Projekt Notizen bearbeiten
	handleSettingFiles("notes.info", "notices");
} elseif (isset($_POST['editSourceLinksP'])) { //POST-Parameter editSourceLinksP (submit-name) gesendet - -/Projekt Quellen/Links bearbeiten
	handleSettingFiles("sourceLinks.info", "sourceLinks");
} elseif (isset($_POST['editMaterialsP'])) { //POST-Parameter editMaterialsP (submit-name) gesendet - -/Projekt Materialliste bearbeiten
	handleSettingFiles("materials.info", "materials");
} elseif (isset($_POST['editFiles'])) { //POST-Parameter editFiles (submit-name) gesendet - -/Projekt Dateien umbenennen oder löschen
	$file_renameName = $_POST['newName']; //Neuer Name
	$file_nameEx = explode("#####", $_POST['fileOld']); //Alter Name, gesplittet in Name und Dateiendung
	$dir = "." . getSetting("baseDir") . $getPath . "/"; //Pfad zur Datei
	if ($file_renameName == "this.delete") { //Wenn Löschbefehl eingegeben
		unlink(utf8_decode($dir . $file_nameEx[0] . "." . $file_nameEx[1])); //Datei löschen
	} else { //Sonst umbenennen
		rename(utf8_decode($dir . $file_nameEx[0] . "." . $file_nameEx[1]), utf8_decode($dir . $file_renameName . "." . $file_nameEx[1])); //Datei umbenennen
	}
}

function handleSettingFiles($settingsFile, $settingPOST) {
	$file = "." . getSetting('baseDir');
	$file .= ($getPath == "/") ? ($getPath) : ($getPath . "/");
	$file .= $settingsFile;
	$file = utf8_decode($file); //Datei mit Pfad

	$file_handle = fopen($file, 'w'); //Datei öffnen
	ftruncate($file_handle, 0); //Datei leeren
	fwrite($file_handle, $_POST[$settingPOST]); //Textarea-Inhalt in Datei schreiben
	fclose($file_handle); //Datei schließen
}

//Ordnerpfad öffnen
$openDir = opendir(utf8_decode("." . getSetting("baseDir") . $getPath));

$breadcrumbs = "Index"; //Brotkrumen-Navigations-Beginn
$e = explode("/", $getPath); //URL-Pfad auseinandernehmen
for ($i=count($e)-1; $i>=0; $i--) { //Leere Felder entfernen
   if ($e[$i] == '') unset ($e[$i]);
}
foreach ($e as $f) { //Ordner durchlaufen
	$g = explode("_", $f); //Ordner anhand von "_" auseinander nehmen
	$breadcrumbs .= " > " . $g[2]; //Brotkrumen-Navi aneinanderbauen
}
if (isset($f) AND isset($g)) { //Wenn Pfad nicht ?p=/
	$folder = $f; //Aktueller Ordner (nicht auseinander genommen)
	$folderType = $g[0]; //Aktueller Ordner Typ (C/P)
	$folderID = $g[1]; //Aktuelle Ordner ID
	$title = ($g[0] == "C") ? (t("Kategorie") . ": " . $g[2]) : (t("Projekt") . ": " . $g[2]); //Ordnertyp in Sprache umwandeln
} else { //Wenn Pfad gleich ?p=/
	$folder = "/"; //Aktueller Ordner
	$folderType = "C"; //Ordnertyp Kategorie
	$title = t("Index"); //Ordnertyp in Sprache umwandeln (Spezial)
}

//Optionsformulare verarbeiten (2) - openDir und weitere Angaben nötig
if (isset($_POST['deleteCP']) AND $_POST['deletePrompt'] == "J") { //POST-Parameter editCP (submit-name) gesendet und Eingabefeld mit "J" bestätigt - Kategorie/Projekt löschen (Nur möglich wenn C/P leer)
	$folderUpNoURL = str_replace("/" . $folder, "", $getPath); //Mit Basis-Pfad verbinden
	$folderDelFiles = str_replace("/", "", getSetting("baseDir")) . $getPath;
	chdir(utf8_decode($folderDelFiles)); //Zu Verzeichnis wechseln, dass eins höher ist als Aktueller Ordner
	array_map('unlink', glob("*.*"));
	chdir("..");
	rmdir(utf8_decode($folder)); //Aktuellen Ordner löschen
	header("Location: ?p=$folderUpNoURL"); //Höheren Ordner aufrufen
}

?>
<!DOCTYPE HTML>
<html>
	<head>
		<title><?php echo $title . " - " . t("Projektverwaltung"); ?></title>
		<link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all">
		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<!-- Custom Theme files -->
		<link href="css/style.css" rel="stylesheet" type="text/css" media="all"/>
		<!-- Custom Theme files -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
		<!--Google Fonts-->
		<link href='//fonts.googleapis.com/css?family=Roboto:400,500,700' rel='stylesheet' type='text/css'>
		<link href='//fonts.googleapis.com/css?family=Ubuntu+Condensed' rel='stylesheet' type='text/css'>
		<!--google fonts-->
		<script src="js/jquery-1.11.0.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script>
		$(function() { //Beim Öffnen der Website werden alle Felder versteckt.
			$('#abortAction').toggle(false);

			$('#addCategoryorProject').toggle(false);
			$('#renameCategoryorProject').toggle(false);
			$('#editProject').toggle(false);
			$('#deleteCategoryorProject').toggle(false);
			$('#uploadFilesToProject').toggle(false);
			$('#editFiles').toggle(false);
			
			//Bild im Project Edit-Menü laden und ändern.
			formEditPChangeimg(document.getElementById("formEditPSelect"));
		});

		function locationHashChanged() { //Funktion für die Hashänderung, blendet Felder nach Bedarf ein/aus
			function toggleBars() {
				$('.welcome-main').toggle(false);
				$('.actionBar').toggle(false);
				$('#abortAction').toggle(true);
			}

			switch (location.hash) {
				case "#addCatPro":
					toggleBars();
					$('#addCategoryorProject').toggle(true);
					break;
				case "#renameCatPro":
					toggleBars();
					$('#renameCategoryorProject').toggle(true);
					break;
				case "#editProject":
					toggleBars();
					$('#editProject').toggle(true);
					break;
				case "#deleteCatPro":
					toggleBars();
					$('#deleteCategoryorProject').toggle(true);
					break;
				case "#uploadFilesToProject":
					toggleBars();
					$('#uploadFilesToProject').toggle(true);
					break;
				case "#editFiles":
					toggleBars();
					$('#editFiles').toggle(true);
					break;
				default:
					$('.welcome-main').toggle(true);
					$('.actionBar').toggle(true);
					$('#abortAction').toggle(false);

					$('#addCategoryorProject').toggle(false);
					$('#renameCategoryorProject').toggle(false);
					$('#editProject').toggle(false);
					$('#deleteCategoryorProject').toggle(false);
					$('#uploadFilesToProject').toggle(false);
					$('#editFiles').toggle(false);
			}
		}

		//Hash-Change abfangen und Funktion aufrufen
		window.onhashchange = locationHashChanged;

		function formEditPChangeimg(proImage) { //Project Edit-Menü -> Bild laden bzw. de-laden wenn keines gewählt
			if (proImage.value == "")
				document.getElementById("formEditPImgpreview").src = 'images/projects.png';
			else
				document.getElementById("formEditPImgpreview").src = '.' + '<?php echo unixUmlauts(getSetting('baseDir') . $getPath); ?>' + "/" + proImage.value;
		}
		</script>
		<script type="text/javascript">
		(function( $ ) {

			$.fn.overlay = function(overlayerType) {

				var overlay = overlayerType + "overlay";
				var overlaybackground = "overlaybg";

				var ie6 = false;

				function center_overlay_x() {
					var browser_width = document.documentElement.offsetWidth;
					if ((browser_width < $("#" + overlay).outerWidth() + 40) && !ie6) {
						$("#" + overlay).css({
							'position': 'absolute',
							'left': '10px',
							'margin-left': '10px'
						})
					} else {
						$("#" + overlay).css({
							'position': 'absolute',
							'left': '50%',
							'margin-left': - $("#" + overlay).outerWidth() / 2
						})
					}
				}

				function center_overlay_y() {
					var browser_height = document.documentElement.offsetHeight;

					if ((browser_height > $("#" + overlay).height() + 40) && !ie6) {
						$("#" + overlay).css({
							'position': 'absolute',
							'top': "40px"
						})
					} else {
						$("#" + overlay).css({
							'position': 'absolute',
							'top': '20px'
						});
						$(window).scrollTop(0);
					}
				}

				function overlayposition() {
					center_overlay_x();
					center_overlay_y();
					if(ie6) {
						$("#" + overlaybackground).css({
							'position': 'absolute',
							'width': $("body").outerWidth(),
							'height': document.documentElement.offsetHeight
						});
					}
				}

				$(window).resize(function () {
					overlayposition();
				});

				function overlayclose() {
					$("#" + overlay + "," + "#" + overlaybackground).hide();
				}

				$(document).keydown(function (e) {
					if(e.keyCode == 27) {
						overlayclose();
					}
				});

				$("#" + overlaybackground).on("click", function(){
					overlayclose();
				});

				$("#" + overlay + " .closeoverlay").on("click", function(){
					overlayclose();
				});

				$("#" + overlay + "," + "#" + overlaybackground).show();
				overlayposition();

			};

		})( jQuery );
		</script>
	</head>
<body>
<!--header-top start here-->
<div class="top-header">
	<div class="container">
		<div class="top-header-main">
			<div class="col-md-12 header-address">
				<ul>
					<?php 
					if ($getPath != "/") { 
						if ($folderType == "C") {
					?>
					<li class="actionBar"><a href="#addCatPro"><?php echo t("Hinzufügen"); ?></a></li>
					<?php
						}
					?>
					<li class="actionBar"><a href="#renameCatPro"><?php echo t("Umbenennen"); ?></a></li>
					<li class="actionBar"><a href="#deleteCatPro"><?php echo t("Löschen"); ?></a></li>
					<?php
						if ($folderType == "P") {
					?>
					<li class="actionBar"><a href="#editProject"><?php echo t("Bearbeiten"); ?></a></li>
					<li class="actionBar"><a href="#uploadFilesToProject"><?php echo t("Hochladen"); ?></a></li>
					<li class="actionBar"><a href="#editFiles"><?php echo t("Dateien bearbeiten"); ?></a></li>
					<li class="actionBar"><a href="#editNotices" onClick="$.fn.overlay('notices-');"><?php echo t("Notizen"); ?></a></li>
					<?php
						}
						if ($folder != "/") {
					?>
					<li class="actionBar"><a href="#editSourceLinks" onClick="$.fn.overlay('sourceLinks-');"><?php echo t("Quellen/Links"); ?></a></li>
					<?php
						}
						if ($folderType == "P") {
					?>
					<li class="actionBar"><a href="#editMaterials" onClick="$.fn.overlay('materials-');"><?php echo t("Materialliste"); ?></a></li>
					<?php 
						}
					} 
					?>
					<li id="abortAction"><a href="#abortAction"><img src="images/cross.png" width="16" height="16" /> <span><?php echo t("Aktion abbrechen"); ?></span></a></li>
				</ul>
			</div>
		  <div class="clearfix"> </div>
		</div>
	</div>
</div>
<div class="welcome">
	<div class="container">
		<div class="welcome-main">
			 <div class="welcome-top">
			 	<h1><?php echo str_replace(array("@"), array("/"), $title); ?></h1>
					<?php echo t("Navigation") . ": " . str_replace(array("@"), array("/"), $breadcrumbs); ?>
			 </div>
			 <?php if ($failure[0] != "") { //Fehleranzeigen, wenn vorhanden
			 echo "<div class=\"failure\">
			 	<div class=\"failure_message\">
					<strong>$failure[0]</strong><br />
					$failure[1]
				</div>
			 </div>";
			 }
			 ?>
			 <div class="welcome-bottom">
				<?php
				while ($entry = readdir($openDir)) {
					if ($entry != '.' && $entry != "..") {
						if ($folderType == "C") { //Wenn aktueller Ordner "Kategorie" ist (enthält NUR Unterordner)
							$h = explode("_", $entry);
							if ($h[0] == "C") { //Wenn Unterordner "Kategorie" ist (keine Priorität und keine Bewertung) -> Eigenschaften
								$entry_Dir[] = utf8_encode($entry);
								$entry_DirID[] = $h[1];
								$entry_DirType[] = $h[0];
								$entry_DirName[] = utf8_encode($h[2]);
								$entry_ProPriority[] = 0;
								$entry_ProRating[] = 0;
								$entry_ProImage[] = "";
							} elseif ($h[0] == "P") { //Wenn Unterordner "Projekt" ist (mit Bewertung und Priorität) -> Eigenschaften
								$proPriRatImg = $db->query("SELECT PRIORITY as P, RATING as R, PICTURE as I FROM projectorizer WHERE ID = '" . $h[1] . "'")->fetchArray();
								$entry_Dir[] = utf8_encode($entry);
								$entry_DirID[] = $h[1];
								$entry_DirType[] = $h[0];
								$entry_DirName[] = utf8_encode($h[2]);
								$entry_ProPriority[] = ($proPriRatImg["P"] != "") ? ($proPriRatImg["P"]) : (5);
								$entry_ProRating[] = ($proPriRatImg["R"] != "") ? ($proPriRatImg["R"]) : (0);
								$entry_ProImage[] = ($proPriRatImg["I"] != "") ? ($proPriRatImg["I"]) : ("");
							}
						} else { //Wenn aktueller Ordner "Projekt" (oder garnix ist) ist (enthält NUR Dateien)
							$entryInfo = pathinfo($entry);
							$entry_File[] = utf8_encode($entry);
							$entry_FileName[] = utf8_encode($entryInfo["filename"]);
							$entry_FileExtension[] = $entryInfo["extension"];
							//FileExtension in FileType umwandeln und FileType zu Priorität umwandeln (Bilder -> PDF -> Texts -> Dokumente -> Unbekannt)
							if (preg_match("/(png|jpg|jpeg|gif|svg|bmp)/", $entryInfo["extension"])) {
								$entry_FileType[] = "Picture";
								$entry_FilePriority[] = 10;
							} elseif (preg_match("/(pdf)/", $entryInfo["extension"])) {
								$entry_FileType[] = "PDF";
								$entry_FilePriority[] = 8;
							} elseif (preg_match("/(txt|html|htm|css|js|php|xml|xsl|json|csv)/", $entryInfo["extension"])) {
								$entry_FileType[] = "Text";
								$entry_FilePriority[] = 6;
							} elseif (preg_match("/(doc|docx|xls|xlsx|ppt|pptx|rtf|odt|ods|odp)/", $entryInfo["extension"])) {
								$entry_FileType[] = "Documents";
								$entry_FilePriority[] = 4;
							} else {
								$entry_FileType[] = "Unknown";
								$entry_FilePriority[] = 0;
							}
						}
					}
				}

				if ($getPath != trim("/")) {
					$folderUp = str_replace("/" . $folder, "", $getPath);
					($folderUp == trim("")) ? ($folderUp = "/") : ("");
					echo "<a href=\"?p=" . $folderUp . "\"><div class='folders'><div class='iconCon'><img class='icon' src='images/folderUp.png' /></div><div>" . t("Übergeordnetes Element aufrufen") . "</div></div></a>";
				}

				if ($folderType == "C" AND isset($entry_Dir)) {
					array_multisort($entry_DirType, SORT_ASC, SORT_STRING, $entry_ProPriority, SORT_DESC, SORT_NUMERIC, $entry_ProRating, SORT_DESC, SORT_NUMERIC, $entry_DirName, SORT_ASC, SORT_NATURAL, $entry_DirID, $entry_Dir, $entry_ProImage);
					for ($i = 0; $i < count($entry_Dir); $i++) {
						$newPath = ($getPath == "/") ? ("") : ($getPath);
						$newPath = $newPath . "/" . $entry_Dir[$i];
						$imageIcon = ($entry_DirType[$i] == "C") ? ("./images/categories.png") : ( ($entry_ProImage[$i] == "") ? ("./images/projects.png") : (unixUmlauts("." . getSetting('baseDir') . $getPath . "/" . $entry_Dir[$i] . "/" . $entry_ProImage[$i])) );
						echo "<a href=\"?p=" . $newPath . "\"><div class='folders'><div class='iconCon'><img class='icon' src='" . $imageIcon . "' /></div><div>";
						if ($entry_DirType[$i] == "P") {
							echo "<img src='./images/number_" . $entry_ProPriority[$i] . ".png' height='18' width='18' /> ";
							for ($z = 1; $z <= 10; $z++) {
								if ($z <= $entry_ProRating[$i]) {
									echo "<img src='./images/star_full.png' height='10' width='10' />";
								} else {
									echo "<img src='./images/star_empty.png' height='10' width='10' />";
								}
							}
						}
						echo "</div><div>" . str_replace(array("@"), array("/"), $entry_DirName[$i]) . "</div></div></a>";
					}
				} elseif ($folderType == "P" AND isset($entry_File)) {
					array_multisort($entry_FilePriority, SORT_DESC, SORT_NUMERIC, $entry_FileName, SORT_ASC, SORT_NATURAL, $entry_FileExtension, $entry_File, $entry_FileType);
					for ($i = 0; $i < count($entry_File); $i++) {
						if (!preg_match("/(info)/", $entry_FileExtension[$i])) {
							$filePath = "." . getSetting("baseDir") . $getPath . "/" . $entry_File[$i];
							$filePath = utf8_encode(str_replace(array('Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'), array('%C4', '%D6', '%DC', '%E4', '%F6', '%FC', '%DF'), $filePath));
							echo "<a href='" . $filePath . "' target='_blank'><div class='folders'>";
							if ($entry_FileType[$i] == "Picture") {
								echo "<div class='iconCon'><img class='icon' src='" . $filePath . "' /></div><div>" . $entry_FileName[$i] . "</div>";
							} else {
								$imageIcon = strtolower($entry_FileType[$i]);
								echo "<div class='iconCon'><img class='icon' src='images/" . $imageIcon . "_2.png' /></div><div>" . $entry_File[$i] . "</div>";
							}
							echo "</div></a>";
						}
					}
				}

				closedir($openDir);
				?>
			 </div>		
		</div>
		<div class="options-main" id="addCategoryorProject">
			<div class="options-top">
				<h1><?php echo t("Optionen") . " - " . $title; ?></h1>
				<?php echo t("Navigation") . ": " . $breadcrumbs; ?>
			</div>
			<div class="options-bottom">
				<form method="POST" action="#categoryAdded">
					<select name="CatOrPro" size="2" required="required">
						<option value="C"><?php echo t("Kategorie"); ?></option>
						<option value="P"><?php echo t("Projekt"); ?></option>
					</select><br />
					<input type="text" name="CatProName" required="required" />
					<input type="submit" name="addCP" value="<?php echo t("Hinzufügen"); ?>" />
				</form><br />
				<?php echo t("Die Sonderzeichen '/' und '_' sind nicht erlaubt."); ?><br />
				<small><?php echo t("Wird ein '@' eingegeben, wird dieses in der Kategorie-/Projekt-Beschriftung in ein '/' geändert."); ?></small>
			</div>
		</div>
		<div class="options-main" id="renameCategoryorProject">
			<div class="options-top">
				<h1><?php echo t("Optionen") . " - " . $title; ?></h1>
				<?php echo t("Navigation") . ": " . $breadcrumbs; ?>
			</div>
			<div class="options-bottom">
				<form method="POST" action="#categoryRenamed">
					<input type="text" name="CatProReName" required="required" />
					<input type="submit" name="renameCP" value="<?php echo t("Umbenennen"); ?>" />
				</form><br />
				<?php echo t("Die Sonderzeichen '/' und '_' sind nicht erlaubt."); ?><br />
				<small><?php echo t("Wird ein '@' eingegeben, wird dieses in der Kategorie-/Projekt-Beschriftung in ein '/' geändert."); ?></small>
			</div>
		</div>
		<div class="options-main" id="editProject">
			<div class="options-top">
				<h1><?php echo t("Optionen") . " - " . $title; ?></h1>
				<?php echo t("Navigation") . ": " . $breadcrumbs; ?>
			</div>
			<div class="options-bottom">
				<form method="POST" action="#projectEdited">
					<?php
					if (isset($entry_File)) {
						$proPriRatImg = $db->query("SELECT PRIORITY as P, RATING as R, PICTURE as I FROM projectorizer WHERE ID = '" . $folderID . "'")->fetchArray();
						$proPriority = ($proPriRatImg["P"] != "") ? ($proPriRatImg["P"]) : (5);
						$proRating = ($proPriRatImg["R"] != "") ? ($proPriRatImg["R"]) : (0);
						$proImage = ($proPriRatImg["I"] != "") ? ($proPriRatImg["I"]) : ("");
						$proImageOptions = "";
						for ($i = 0; $i < count($entry_File); $i++) {
							if ($entry_FileType[$i] == "Picture") {
								if ($proImage == $entry_File[$i]) {
									$proImageOptions .= "<option value='" . $entry_File[$i] . "' selected='selected'>" . $entry_File[$i] . "</option>\n";
								} else {
									$proImageOptions .= "<option value='" . $entry_File[$i] . "'>" . $entry_File[$i] . "</option>\n";
								}
							}
						}
					}
					?>
					<?php echo t("Priorität"); ?>: <input type="number" name="ProPriority" min="0" max="10" required="required" value="<?php echo $proPriority; ?>" /><br />
					<?php echo t("Bewertung"); ?>: <input type="number" name="ProRating" min="0" max="10" value="<?php echo $proRating; ?>" /><br />
					<?php echo t("Projekt-Bild auswählen"); ?>: 
					<select name="ProImage" onChange="javascript:formEditPChangeimg(this)" id="formEditPSelect">
						<option value=""><?php echo t("Kein Bild"); ?></option>
						<?php echo $proImageOptions; ?>
					</select><br />
					<input type="submit" name="editP" value="<?php echo t('Änderungen speichern'); ?>" />
				</form><br />
				<div>
				<?php echo t("Bild-Vorschau"); ?>: <br />
					<img src="" height="120" id="formEditPImgpreview" />
				</div>
			</div>
		</div>
		<div class="options-main" id="deleteCategoryorProject">
			<div class="options-top">
				<h1><?php echo t("Optionen") . " - " . $title; ?></h1>
				<?php echo t("Navigation") . ": " . $breadcrumbs; ?>
			</div>
			<div class="options-bottom">
				<form method="POST" action="#projectEdited">
				<?php echo t("Eingabe: J für Löschen"); ?> <small>(<?php echo t("Löschbestätigung"); ?>)</small><input type="text" name="deletePrompt" required="required" />
					<input type="submit" name="deleteCP" value="<?php echo t('Änderungen speichern'); ?>" />
				</form><br />
				<?php echo sprintf(t("Es wird nur das Verzeichnis %s gelöscht. Zur Löschung müssen alle Unterverzeichnisse und deren Inhalte vorher gelöscht werden."), $title); ?><br />
				<small><?php echo t("Es können nur Ordner gelöscht werden, die mit dieser Website erstellt wurde, da sonst notwendige Löschrechte für PHP fehlen."); ?></small>
			</div>
		</div>
		<div class="options-main" id="uploadFilesToProject">
			<div class="options-top">
				<h1><?php echo t("Optionen") . " - " . $title; ?></h1>
				<?php echo t("Navigation") . ": " . $breadcrumbs; ?>
			</div>
			<div class="options-bottom">
				<form action="#filesAdded" method="POST" enctype="multipart/form-data">
				<?php echo t("Dateien hochladen"); ?>: <input name="uploadFiles[]" type="file" multiple="multiple" required="required"><br />
					<input type="submit" name="uploadFilesP" value="<?php echo t("Dateien hochladen"); ?>" />
				</form>
			</div>
		</div>
		<div class="options-main" id="editFiles">
			<div class="options-top">
				<h1><?php echo t("Optionen") . " - " . $title; ?></h1>
				<?php echo t("Navigation") . ": " . $breadcrumbs; ?>
			</div>
			<div class="options-bottom">
				<form method="POST" action="#filesEdited">
					<?php
					if (isset($entry_File)) {
						$proFileOptions = "";
						for ($i = 0; $i < count($entry_File); $i++) {
							if (!preg_match("/(info)/", $entry_FileExtension[$i])) {
								$proFileOptions .= "<option value='" . $entry_FileName[$i] . "#####" . $entry_FileExtension[$i] . "'>" . $entry_File[$i] . "</option>\n";
							}
						}
					}
					?>
					<?php echo t("Datei bearbeiten"); ?>:
					<select name="fileOld" required="required">
						<?php echo $proFileOptions; ?>
					</select><br />
					<?php echo t("Neuer Name"); ?>: <input type="text" name="newName" required="required" /><br />
					<input type="submit" name="editFiles" value="<?php echo t("Änderungen speichern"); ?>" /><br />
					<small><?php echo t("Soll eine Datei gelöscht werden, muss das Feld 'this.delete' enthalten (ohne '). - Wenn der Dateiname bereits vorhanden ist, wird die alte Datei überschrieben. - Die Dateiendung muss nicht eingegeben werden."); ?></small>
				</form>
			</div>
		</div>
		<?php
		if ($folderType == "P") {
			$noticesFileContent = "";
			$file = "." . getSetting('baseDir');
			$file .= ($getPath == "/") ? ($getPath) : ($getPath . "/");
			$file .= "notes.info";
			$file = utf8_decode($file);

			if (!file_exists($file)) {
				$file_handle = fopen($file, 'w');
				fclose($file_handle);
			}

			$file_handle = fopen($file, 'r');
			while (!feof($file_handle)) {
				$line = fgets($file_handle);
				$noticesFileContent .= $line;
			}
			fclose($file_handle);

			$materialsFileContent = "";
			$file = "." . getSetting('baseDir');
			$file .= ($getPath == "/") ? ($getPath) : ($getPath . "/");
			$file .= "materials.info";
			$file = utf8_decode($file);

			if (!file_exists($file)) {
				$file_handle = fopen($file, 'w');
				fclose($file_handle);
			}

			$file_handle = fopen($file, 'r');
			while (!feof($file_handle)) {
				$line = fgets($file_handle);
				$materialsFileContent .= $line;
			}
			fclose($file_handle);

		}
		if ($folder != "/") {
			$sourceLinksFileContent = "";
			$sourceLinksFileContentLinks = "";
			$file = "." . getSetting('baseDir');
			$file .= ($getPath == "/") ? ($getPath) : ($getPath . "/");
			$file .= "sourceLinks.info";
			$file = utf8_decode($file);

			if (!file_exists($file)) {
				$file_handle = fopen($file, 'w');
				fclose($file_handle);
			}

			$file_handle = fopen($file, 'r');
			while (!feof($file_handle)) {
				$line = fgets($file_handle);
				$sourceLinksFileContent .= $line;

				$a = explode(" - ", $line);
				if (count($a) > 2) {
					$lineLinks = "<a href='" . $a[0] . "' target='_blank'>" . $a[1] . "</a> - " . $a[2];
				}
				elseif (count($a) == 2) {
					$lineLinks = "<a href='" . $a[0] . "' target='_blank'>" . $a[1] . "</a>";
				}
				else {
					$lineLinks = "<a href='" . $line . "' target='_blank'>" . $line . "</a>";
				}

				$sourceLinksFileContentLinks .= $lineLinks . "\r\n<br />";
			}
			fclose($file_handle);
		}
		?>
		<div id="overlaybg" class="overlayBG" style="display: none;"></div>
		<div id="notices-overlay" class="overlay" style="display: none;">
			<div class="content">
				<h2><?php echo t("Notizen"); ?></h2>
				<div class="overlayContent">
					<form method="POST" action="#noticesEdited">
						<textarea class="formEditTextarea" rows="25" name="notices"><?php echo $noticesFileContent; ?></textarea><br />
						<input type="submit" name="editNoticesP" value="<?php echo t("Änderungen speichern"); ?>" />
					</form>
				</div>
				<div class="closeoverlay" title="<?php echo t("Overlay schließen"); ?>">X</div>
			</div>
		</div>
		<div id="sourceLinks-overlay" class="overlay" style="display: none;">
			<div class="content">
				<h2><?php echo t("Quellen und Links"); ?> <a onClick="$('#showSyntaxSourceLinks').toggle(200);"><img src="./images/info.png" width="24" height="24" /></a></h2>
				<div class="overlayContent">
					<div class="sourceLinksHrefs">
						<div id="showSyntaxSourceLinks" style="display: none;">
							<b>Syntax:</b><br />
							"http://link.com - <?php echo t("Link-Titel"); ?> - <?php echo t("Link-Beschreibung"); ?>" => "http://google.de - Google - <?php echo t("Suchmaschine"); ?>" (<?php echo t("Als Trenner immer ' - ' (ohne '') nutzen"); ?>)<br />
							<?php echo t("oder"); ?><br />
							"http://link.com" => "http://google.de" (<?php echo t("Keine Leerzeichen nach dem Link"); ?>)
						</div>
						<div>
							<b><?php echo t("Links"); ?>:</b><br />
							<?php echo $sourceLinksFileContentLinks; ?>
						</div>
					</div>
					<form method="POST" action="#sourceLinksEdited">
						<textarea class="formEditTextarea" rows="25" name="sourceLinks"><?php echo $sourceLinksFileContent; ?></textarea><br />
						<input type="submit" name="editSourceLinksP" value="<?php echo t("Änderungen speichern"); ?>" />
					</form>
				</div>
				<div class="closeoverlay" title="<?php echo t("Overlay schließen"); ?>">X</div>
			</div>
		</div>
		<div id="materials-overlay" class="overlay" style="display: none;">
			<div class="content">
				<h2><?php echo t("Materialliste"); ?></h2>
				<div class="overlayContent">
					<form method="POST" action="#materialsEdited">
						<textarea class="formEditTextarea" rows="25" name="materials"><?php echo $materialsFileContent; ?></textarea><br />
						<input type="submit" name="editMaterialsP" value="<?php echo t("Änderungen speichern"); ?>" />
					</form>
				</div>
				<div class="closeoverlay" title="<?php echo t("Overlay schließen"); ?>">X</div>
			</div>
		</div>
	</div>
</div>
<div class="copy-rights">
	<div class="container">
		<div class="copy-rights-main">
			 <p>&copy; 2016 Allied. All Rights Reserved | Design by <a href="http://w3layouts.com/" target="_blank">W3layouts</a> </p>
		</div>
	</div>
</div>
</body>
</html>