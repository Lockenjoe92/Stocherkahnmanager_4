<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Kahnausfall l&ouml;schen - " . lade_db_einstellung('site_name');

//DAU Typ & ID abfangen
if (isset($_POST['typ'])){
    $Typ = $_POST['typ'];
} else {
    $Typ = $_GET['typ'];
}
if (isset($_POST['id'])){
    $ID = $_POST['id'];
} else {
    $ID = $_GET['id'];
}
$link = connect_db();
if ($Typ === "pause"){
    $Modename = "Betriebspause";

    //Daten des Ausfalls laden
    $Anfrage = "SELECT * FROM pausen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Daten = mysqli_fetch_assoc($Abfrage);
} else if ($Typ === "sperrung"){
    $Modename = "Sperrung";

    //Daten des Ausfalls laden
    $Anfrage = "SELECT * FROM sperrungen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Daten = mysqli_fetch_assoc($Abfrage);
} else {
    //Fuck them
    header("Location: ./wartwesen.php");
    die();
}

//PARSER
$Parser = parser($Typ, $ID);

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">'.$Modename.' l&ouml;schen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">'.$Modename.' l&ouml;schen</h1>';
$HTML = section_builder($PageTitle);
$HTML .= seiteninhalt($Parser, $Modename, $Daten, $ID, $Typ);
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function seiteninhalt($Parser, $Modename, $Daten, $ID, $Typ){
    if ($Parser['success'] === TRUE){
        $HTML = "<h3 class='center-align'>Erfolg!</h3>";
        $HTML .= section_builder("<p>Die ".$Modename." wurde erfolgreich gel&ouml;scht!</p>", '', 'center-align');
        $HTML .= section_builder(button_link_creator('Zurück', 'ausfaelle.php', 'arrow_back', ''), '', 'center-align');
    } else if ($Parser['success'] === FALSE){
        $HTML = "<h3 class='center-align'>Fehler!</h3>";
        $HTML .= section_builder("Fehler beim L&ouml;schen der ".$Modename."!<br>".$Parser['meldung']."", '', 'center-align');
        $HTML .= section_builder(button_link_creator('Zurück', 'ausfaelle.php', 'arrow_back', ''), '', 'center-align');
    } else if ($Parser['success'] === NULL){
        $HTML = "<h3 class='center-align'>Achtung!</h3>";
        $HTML .= section_builder("<p>M&ouml;chtest du die ".$Modename." '".$Daten['titel']."' wirklich l&ouml;schen?</p>", '', 'center-align');
        $FormHTML = "<input type='hidden' name='id' value='$ID'>";
        $FormHTML .= "<input type='hidden' name='typ' value='$Typ'>";
        $FormHTML .= form_button_builder('action', 'Löschen', 'action', 'delete', '')." ".button_link_creator('Abbruch', 'ausfaelle.php', 'arrow_back', '');
        $HTML .= form_builder(section_builder($FormHTML, '', 'center-align'), '#', 'post', '', '');
    }

    return $HTML;
}

function parser($Typ, $ID){

    $User = lade_user_id();
    $Ergebnis = NULL;

    if (isset($_POST['action'])){
        if ($Typ === "pause"){
            $Ergebnis = pause_stornieren($ID, $User);
        } else if ($Typ === "sperrung"){
            $Ergebnis = sperrung_stornieren($ID, $User);
        }
    }

    return $Ergebnis;
}
?>