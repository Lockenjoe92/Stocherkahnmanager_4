<?php

//Gets called every 5 minutes
include_once "./ressources/ressourcen.php";
echo "<h2>Auswertung Spendenaktion</h2>";
$link = connect_db();

$Jahr = $_GET['jahr'];
$Monat = $_GET['monat'];
$Tag = $_GET['end_day'];


$AnfangJahr = "".$Jahr."-".$Monat."-01 00:00:01";
$EndeJahr = "".$Jahr."-".$Monat."-".$Tag." 23:59:59";

$Anfrage = "SELECT id, betrag FROM finanz_einnahmen WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = 0";
$Abfrage = mysqli_query($link, $Anfrage);
$Anzahl = mysqli_num_rows($Abfrage);
$Einnahmen = 0;
for ($a = 1; $a <= $Anzahl; $a++){
    $Einnahme = mysqli_fetch_assoc($Abfrage);
    $Einnahmen = $Einnahmen + $Einnahme['betrag'];
}
echo "<h3>".$Einnahmen."&euro;</h3>";
?>