<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Kahnausfall bearbeiten - " . lade_db_einstellung('site_name');

//DAU Seitenmodus
if (isset($_GET['typ'])){
    $Typ = $_GET['typ'];
} else {
    $Typ = $_POST['typ'];
}
$IDausfall = $_GET['id'];
$link = connect_db();
if ($Typ === "pause"){
    $Modename = "Betriebspause";

    //Daten des Ausfalls laden
    $Anfrage = "SELECT * FROM pausen WHERE id = '$IDausfall'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Daten = mysqli_fetch_assoc($Abfrage);
} else if ($Typ === "sperrung"){
    $Modename = "Sperrung";

    //Daten des Ausfalls laden
    $Anfrage = "SELECT * FROM sperrungen WHERE id = '$IDausfall'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Daten = mysqli_fetch_assoc($Abfrage);
} else {
    //Fuck them
    header("Location: ./wartwesen.php");
    die();
}

//Formular Anzeigen
if(isset($_POST['bearbeiten'])){
    $Von = "".$_POST['datum_von']." ".$_POST['zeit_von'].":00";
    $Bis = "".$_POST['datum_bis']." ".$_POST['zeit_bis'].":00";
    $Erklaerung = $_POST['erklaerung'];
    $Titel = $_POST['titel'];
    $Type = $_POST['typus'];
} else {
    $Von = $Daten['beginn'];
    $Bis = $Daten['ende'];
    $Erklaerung = $Daten['erklaerung'];
    $Titel = $Daten['titel'];
    $Type = $Daten['typ'];
}

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Kahnausfall bearbeiten</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Kahnausfall bearbeiten</h1>';
$HTML = section_builder($PageTitle);

//Parser
$Buttonmode = parser_ausfall_bearbeiten_formular($Typ, $Modename, $IDausfall);

$HTML .= ausfall_bearbeiten_formular($Type, $Titel, $Erklaerung, $Von, $Bis, $Modename, $Buttonmode, $Typ);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function ausfall_bearbeiten_formular($Typ, $Titel, $Erklaerung, $Von, $Bis, $Modename, $Buttonmode, $TypURL){

    $HTML = "<h3 class='center-align'>".$Modename." bearbeiten</h3>";

    if(isset($Buttonmode['meldung'])){
            $HTML .= $Buttonmode['meldung'];
    }

    $TableHTML = table_form_string_item("Titel der " . $Modename . "", 'titel', $Titel, false);
    $TableHTML .= table_form_string_item("Typ der " . $Modename . "", 'typus', $Typ, false);
    $TableHTML .= table_form_datepicker_reservation_item('Datum Beginn', 'datum_von', date('Y-m-d', strtotime($Von)), false, true, '');
    $TableHTML .= table_form_timepicker_item('Zeit Beginn', 'zeit_von', date('G:i', strtotime($Von)), false, true, '');
    $TableHTML .= table_form_datepicker_reservation_item('Datum Ende', 'datum_bis', date('Y-m-d', strtotime($Bis)), false, true, '');
    $TableHTML .= table_form_timepicker_item('Zeit Ende', 'zeit_bis', date('G:i', strtotime($Bis)), false, true, '');
    $TableHTML .= table_form_string_item("Erklärung", 'erklaerung', $Erklaerung, false);
    $TableHTML .= table_row_builder(table_header_builder(button_link_creator('Zurück', 'ausfaelle.php', 'arrow_back', '')." ".form_button_builder('bearbeiten', 'Bearbeiten', 'action', 'edit')).table_row_builder(''));
    $TableHTML = table_builder($TableHTML);

    $TableHTML .= "<input name='typ' type='hidden' value='$TypURL'>";
    $HTML .= section_builder(form_builder($TableHTML, '#', 'post', '', ''));

    return $HTML;
}
function parser_ausfall_bearbeiten_formular($Modus, $Modename, $IDausfall){

    $Eintrag = NULL;

    if (isset($_POST['bearbeiten'])){

        //DAU Check
        $Titel = $_POST['titel'];
        $Typ = $_POST['typus'];
        $Erklaerung = $_POST['erklaerung'];
        $Von = "".$_POST['datum_von']." ".$_POST['zeit_von'].":00";
        $Bis = "".$_POST['datum_bis']." ".$_POST['zeit_bis'].":00";

        if ($Modus == "pause"){
            $Eintrag = pause_bearbeiten($IDausfall, $Von, $Bis, $Typ, $Titel, $Erklaerung, FALSE);
        } else if ($Modus == "sperrung"){
            $Eintrag = sperrung_bearbeiten($IDausfall, $Von, $Bis, $Typ, $Titel, $Erklaerung, FALSE);
        }

        if ($Eintrag['erfolg'] == FALSE){

            if ($Eintrag['reservierungen_betroffen'] > 0){

                //Text generieren
                if ($Eintrag['reservierungen_betroffen'] == 1){
                    $ReservierungText = "ist eine Reservierung";
                } else if ($Eintrag['reservierungen_betroffen'] > 1){
                    $ReservierungText = "sind ".$Eintrag['reservierungen_betroffen']." Reservierungen";
                }

                $Eintrag['override'] = true;
                $Eintrag['meldung'] =  "<h5 class='center-align'>Achtung!</h5><br><p class='center-align'>Von dieser ".$Modename." ".$ReservierungText." betroffen!<br>Bitte l&ouml;se den Vorgang erneut aus um die Pause trotzdem einzutragen - betroffene Nutzer werden dann per Mail oder SMS benachrichtigt.</p>";
            } else {
                $Eintrag['meldung'] = "<h5 class='center-align'>Fehler!</h5><br><p class='center-align'>".$Eintrag['meldung']."</p>";
            }
        } else if ($Eintrag['erfolg'] == TRUE){
            $Eintrag['meldung'] = "<h5 class='center-align'>Erfolg!</h5><br><p class='center-align'>".$Eintrag['meldung']."</p>";
        }
    }

    if (isset($_POST['override'])){

        //DAU Check
        $Titel = $_POST['titel'];
        $Typ = $_POST['typus'];
        $Erklaerung = $_POST['erklaerung'];
        $Von = "".$_POST['jahr_von']."-".$_POST['monat_von']."-".$_POST['tag_von']." ".$_POST['stunde_von'].":00:00";
        $Bis = "".$_POST['jahr_bis']."-".$_POST['monat_bis']."-".$_POST['tag_bis']." ".$_POST['stunde_bis'].":00:00";

        if ($Modus == "pause"){
            $Eintrag = pause_bearbeiten($IDausfall, $Von, $Bis, $Typ, $Titel, $Erklaerung, TRUE);
        } else if ($Modus == "sperrung"){
            $Eintrag = sperrung_bearbeiten($IDausfall, $Von, $Bis, $Typ, $Titel, $Erklaerung, TRUE);
        }

        if ($Eintrag['success'] == FALSE){
            $Eintrag['meldung'] = "<h5>Fehler!</h5><br>".$Eintrag['meldung']."";
        } else if ($Eintrag['erfolg'] == TRUE){
            $Eintrag['meldung'] = "<h5>Erfolg!</h5><br>".$Eintrag['meldung']."";
        }
    }

    return $Eintrag;
}