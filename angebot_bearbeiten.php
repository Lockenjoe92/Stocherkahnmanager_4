<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Terminangebot bearbeiten - " . lade_db_einstellung('site_name');
$IDangebot = $_GET['id'];
$Parser = parser_angebot_bearbeiten($IDangebot);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Terminangebot bearbeiten</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Terminangebot bearbeiten</h1>';
$HTML .= section_builder($PageTitle);

if ($Parser['success'] === NULL){
    $HTML .= infos_section($IDangebot);
    $HTML .= angebot_bearbeiten_karte($IDangebot);
} else if ($Parser['success'] === FALSE){
    $HTML .= zurueck_karte_generieren(FALSE, $Parser['meldung'], 'termine.php');
} else if ($Parser['success'] === TRUE){
    $HTML .= zurueck_karte_generieren(TRUE, 'Terminangebot wurde erfolgreich ge&auml;ndert!', 'termine.php');
}

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);









function angebot_bearbeiten_karte($IDangebot){

    $Angebot = lade_terminangebot($IDangebot);

    if(isset($_POST['beginn_terminangebot_bearbeiten'])){
        $BeginnZeit = $_POST['beginn_terminangebot_bearbeiten'];
    } else {
        $BeginnZeit = date('G:i', strtotime($Angebot['von']));
    }

    if(isset($_POST['ende_terminangebot_bearbeiten'])){
        $EndeZeit = $_POST['ende_terminangebot_bearbeiten'];
    } else {
        $EndeZeit = date('G:i', strtotime($Angebot['bis']));
    }

    if(isset($_POST['stunden_terminierung_terminangebot_anlegen'])){
        $StundenTermin = $_POST['stunden_terminierung_terminangebot_anlegen'];
    } else {
        if($Angebot['terminierung'] != NULL){
            $StundenTermin = date('G', strtotime($Angebot['von']))-date('G', strtotime($Angebot['terminierung']));
        } else {
            $StundenTermin = "";
        }
    }



    //Checkbox Schalter
    if(isset($_POST['terminierung_terminangebot_anlegen'])){
        $CheckboxTermin = "checked";
    } else {

        if($Angebot['terminierung'] != NULL){
            $CheckboxTermin = "on";
        } else {
            $CheckboxTermin = "off";
        }
    }

    //Bigscreen
    //DATUM UND TERMINIERUNG
    $BigscreenContent = "<h3 class='center-align'>Zeiten und Terminierung</h3>";
    $BigscreenContent .= table_form_timepicker_item('Beginn', 'beginn_terminangebot_bearbeiten', $BeginnZeit, false, true, '');
    $BigscreenContent .= table_form_timepicker_item('Ende', 'ende_terminangebot_bearbeiten', $EndeZeit, false, true, '');
    $BigscreenContent .= table_form_swich_item('Terminierung aktivieren', 'terminierung_terminangebot_bearbeiten', 'Nein', 'Ja', $CheckboxTermin, false);
    $BigscreenContent .= table_form_select_item('Terminierung für', 'stunden_terminierung_terminangebot_bearbeiten', 1, 24, $StundenTermin, 'h', 'Terminierung', '', false);
    $BigscreenContent = table_builder($BigscreenContent);
    $BigscreenContent .= divider_builder();

    //KOMMENTAR
    $BigscreenContent .= "<h3 class='center-align'>Kommentar</h3>";
    $KommentarContent = table_form_string_item('Kommentar (optional)', 'kommentar_terminangebot_bearbeiten', $Angebot['kommentar'], false);
    $BigscreenContent .= table_builder($KommentarContent);
    $BigscreenContent .= divider_builder();

    //KNÖPFE
    $KnoepfeContent = table_row_builder(table_header_builder(form_button_builder('action_terminangebot_bearbeiten', 'Bearbeiten', 'action', 'edit', '')." ".button_link_creator('Zurück', './termine.php', 'arrow_back', '')).table_data_builder(''));
    $BigscreenContent .= table_builder($KnoepfeContent);

    $CollapsibleContent = form_builder($BigscreenContent, '#', 'post', '', '');
    $Collapsible = section_builder($CollapsibleContent);

    return $Collapsible;
}
function parser_angebot_bearbeiten($IDangebot){

    $DAUcounter = 0;
    $DAUerror = "";
    $Antwort = array();
    $Angebot = lade_terminangebot($IDangebot);

    //Terminangebot bereits gelöscht?
    if($Angebot['storno_user'] === "1"){
        $DAUcounter++;
        $DAUerror .= "Das Angebot wurde bereits storniert!<br>";
    }

    //Keine ID in URL
    if($IDangebot === ""){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine zu bearbeitende &Uuml;bergabe ausgew&auml;hlt!<br>";
    }

    if (isset($_POST['action_terminangebot_bearbeiten'])){
        //Zeiten verdreht?
        $EingabeAnfang = "".date("Y-m-d", strtotime($Angebot['von']))." ".$_POST['beginn_terminangebot_bearbeiten'].":00";
        $EingabeEnde = "".date("Y-m-d", strtotime($Angebot['von']))." ".$_POST['ende_terminangebot_bearbeiten'].":00";

        if (strtotime($EingabeAnfang) > strtotime($EingabeEnde)){
            $DAUcounter++;
        $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
        }

        if (strtotime($EingabeAnfang) == strtotime($EingabeEnde)){
            $DAUcounter++;
            $DAUerror .= "Die Zeiten d&uuml;rfen nicht identisch sein!<br>";
        }

        if ((isset($_POST['terminierung_terminangebot_bearbeiten'])) AND ($_POST['stunden_terminierung_terminangebot_bearbeiten'] == "")){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Angabe zur Dauer der Terminierung angeben, wenn du diese einschalten m&oumlchtest!<br>";
        }
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){
        $link = connect_db();
        if(isset($_POST['action_terminangebot_bearbeiten'])){

            if (isset($_POST['terminierung_terminangebot_bearbeiten'])){
                $Befehl = "- ".$_POST['stunden_terminierung_terminangebot_bearbeiten']." hours";
                $Terminierung = "'".date('Y-m-d G:i:s', strtotime($Befehl, strtotime($EingabeAnfang)))."'";
            } else {
                $Terminierung = "NULL";
            }

            $Anfrage = "UPDATE terminangebote SET von = '$EingabeAnfang', bis = '$EingabeEnde', terminierung = ".$Terminierung.", kommentar = '".$_POST['kommentar_terminangebot_bearbeiten']."' WHERE id = '$IDangebot'";
            if(mysqli_query($link, $Anfrage)){
                $Antwort['success'] = TRUE;
                $Antwort['meldung'] = "Terminangebot erfolgreich bearbeitet!";
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Datenbankfehler!".$Anfrage;
            }
        } else if (!isset($_POST['action_terminangebot_bearbeiten'])) {
            $Antwort['success'] = NULL;
        }
    }

    return $Antwort;
}
?>