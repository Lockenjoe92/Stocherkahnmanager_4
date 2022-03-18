<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Kahnausfall hinzuf&uuml;gen - " . lade_db_einstellung('site_name');

//DAU Seitenmodus
if (isset($_GET['typ'])){
    $TypURL = $_GET['typ'];
} else {
    $TypURL = $_POST['typ'];
}

if ($TypURL === "pause"){
    $Modename = "Betriebspause";
} else if ($TypURL === "sperrung"){
    $Modename = "Sperrung";
} else {
    //Fuck them
    header("Location: ./wartwesen.php");
    die();
}

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Ausfall des Kahns hinzufügen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Ausfall hinzufügen</h1>';
$HTML = section_builder($PageTitle);

//Parser
$Buttonmode = parser($TypURL, $Modename);

//Formular Anzeigen
if(isset($_POST['datum_von'])){
    $Von = "".$_POST['datum_von']." ".$_POST['zeit_von'].":00";
    $Bis = "".$_POST['datum_bis']." ".$_POST['zeit_bis'].":00";
} else {
    $Von = timestamp();
    $Bis = date("Y-m-d G:i:s", strtotime('+ 1 hour'));
}


$HTML .= ausfall_hinzufuegen_formular($_POST['typus'], $_POST['titel'], $_POST['erklaerung'], $Von, $Bis, $Modename, $Buttonmode, $TypURL);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function ausfall_hinzufuegen_formular($Typ, $Titel, $Erklaerung, $Von, $Bis, $Modename, $Buttonmode, $TypURL){

    $HTML = "<h3 class='center-align'>".$Modename." hinzuf&uuml;gen</h3>";

    if (isset($Buttonmode['meldung'])){
        $HTML .= section_builder($Buttonmode['meldung'], '', 'center-align');
    }

    if (($Buttonmode['success'] == NULL) OR ($Buttonmode['success'] === FALSE)) {
        $TableHTML = table_form_string_item("Titel der " . $Modename . "", 'titel', $Titel, false);
        $TableHTML .= table_form_string_item("Typ der " . $Modename . "", 'typus', $Typ, false);
        $TableHTML .= table_form_datepicker_reservation_item('Datum Beginn', 'datum_von', date('Y-m-d', strtotime($Von)), false, true, '');
        $TableHTML .= table_form_timepicker_item('Zeit Beginn', 'zeit_von', date('G:i', strtotime($Von)), false, true, '');
        $TableHTML .= table_form_datepicker_reservation_item('Datum Ende', 'datum_bis', date('Y-m-d', strtotime($Bis)), false, true, '');
        $TableHTML .= table_form_timepicker_item('Zeit Ende', 'zeit_bis', date('G:i', strtotime($Bis)), false, true, '');
        $TableHTML .= table_form_string_item("Erklärung", 'erklaerung', $Erklaerung, false);
        $TableHTML .= table_builder(table_row_builder(table_header_builder(button_link_creator('Zurück', 'ausfaelle.php', 'arrow_back', '')." ".form_button_builder('action', 'Eintragen', 'action', 'send')).table_row_builder('')));
        $TableHTML = table_builder($TableHTML);
    } else if ($Buttonmode['override'] === TRUE){
        $TableHTML = table_form_string_item("Titel der " . $Modename . "", 'titel', $Titel, false);
        $TableHTML .= table_form_string_item("Typ der " . $Modename . "", 'typus', $Typ, false);
        $TableHTML .= table_form_datepicker_reservation_item('Datum Beginn', 'datum_von', date('Y-m-d', strtotime($Von)), false, true, '');
        $TableHTML .= table_form_timepicker_item('Zeit Beginn', 'zeit_von', date('G:i', strtotime($Von)), false, true, '');
        $TableHTML .= table_form_datepicker_reservation_item('Datum Ende', 'datum_bis', date('Y-m-d', strtotime($Bis)), false, true, '');
        $TableHTML .= table_form_timepicker_item('Zeit Ende', 'zeit_bis', date('G:i', strtotime($Bis)), false, true, '');
        $TableHTML .= table_form_string_item("Erklärung", 'erklaerung', $Erklaerung, false);
        $TableHTML .= table_builder(table_row_builder(table_header_builder(button_link_creator('Zurück', 'ausfaelle.php', 'arrow_back', '')." ".form_button_builder('override', 'Trotzdem eintragen', 'action', 'send')).table_row_builder('')));
        $TableHTML = table_builder($TableHTML);
    } else if ($Buttonmode['success'] === TRUE){
        $TableHTML = table_builder(table_row_builder(table_header_builder(button_link_creator('Zurück', 'ausfaelle.php', 'arrow_back', '')).table_row_builder('')));
    }

    $TableHTML .= "<input name='typ' type='hidden' value='$TypURL'>";
    $HTML .= section_builder(form_builder($TableHTML, 'ausfall_hinzufuegen.php', 'post', '', ''));

    return $HTML;
}

function parser($Modus, $Modename){

    $Eintrag = NULL;

    if (isset($_POST['action'])){

        //DAU Check
        $Titel = $_POST['titel'];
        $Typ = $_POST['typus'];
        $Erklaerung = $_POST['erklaerung'];
        $Von = "".$_POST['datum_von']." ".$_POST['zeit_von'].":00";
        $Bis = "".$_POST['datum_bis']." ".$_POST['zeit_bis'].":00";

        if ($Modus == "pause"){
            $Eintrag = pause_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), FALSE);
        } else if ($Modus == "sperrung"){
            $Eintrag = sperrung_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), FALSE);
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
                $Eintrag['meldung'] = "<h5>Achtung!</h5><br>Von dieser ".$Modename." ".$ReservierungText." betroffen!<br>Bitte l&ouml;se den Vorgang erneut aus um die Pause trotzdem einzutragen - betroffene Nutzer werden dann per Mail oder SMS benachrichtigt.";
            } else {
                $Eintrag['meldung'] = "<h5>Fehler!</h5><br>".$Eintrag['meldung']."";
            }
        } else if ($Eintrag['erfolg'] == TRUE){
            $Eintrag['meldung'] = "<h5>Erfolg!</h5><br>".$Eintrag['meldung']."";
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
            $Eintrag = pause_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), TRUE);
        } else if ($Modus == "sperrung"){
            $Eintrag = sperrung_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), TRUE);
        }

        if ($Eintrag['success'] == FALSE){
            $Eintrag['meldung'] = "<h5>Fehler!</h5><br>".$Eintrag['meldung']."";
        } else if ($Eintrag['erfolg'] == TRUE){
            $Eintrag['meldung'] = "<h5>Erfolg!</h5><br>".$Eintrag['meldung']."";
        }
    }

    return $Eintrag;
}

?>