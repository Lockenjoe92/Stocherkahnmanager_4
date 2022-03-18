<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 18.11.17
 * Time: 12:34
 */
include_once "./ressourcen/ressourcen.php";
$link = connect_db();

//Get the year from URL in case we want to switch
$URLYear = $_GET['jahr'];
if(isset($URLYear)){
    $ThisYear = $URLYear;
} else {
    $ThisYear = date('Y');
}

$HTML = "<h1>&Uuml;bersicht &Uuml;bergaben ".$ThisYear."</h1>";
$HTML .= "<table>";
$HTML .= "<tr><th>Wart</th><th>Anzahl</th></tr>";

//Look for all active Stocherkahnwarts
$Anfrage1 = "SELECT user FROM user_rollen WHERE recht = 'wart' AND storno_user = '0'";
$Abfrage1 = mysqli_query($link, $Anfrage1);
$Anzahl1 = mysqli_num_rows($Abfrage1);

//Iterate over Warts
for($a=1; $a<=$Anzahl1; $a++){

    //Fetch Result
    $Ergebnis = mysqli_fetch_assoc($Abfrage1);
    $WartID = $Ergebnis['user'];

    //Load User Meta
    $UserMeta = lade_user_meta($WartID);

    //Load Übergaben from this Year
    $BeginYear = "".$ThisYear."-01-01 00:00:01";
    $EndYear = "".$ThisYear."-12-31 23:59:59";
    $Anfrage2 = "SELECT id FROM uebergaben WHERE wart = '".$WartID."' AND beginn > '".$BeginYear."' AND beginn < '".$EndYear."' AND durchfuehrung > '0000-00-00 00:00:00' AND storno_user = '0'";
    $Abfrage2 = mysqli_query($link, $Anfrage2);
    $Anzahl2 = mysqli_num_rows($Abfrage2);

    $HTML .= "<tr><td>".$UserMeta['vorname']."</td><td>".$Anzahl2."</td></tr>";

}

$HTML .= "</table>";
echo $HTML;

?>