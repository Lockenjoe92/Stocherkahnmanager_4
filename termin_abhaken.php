<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Termin abhaken - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Termin abhaken</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Termin abhaken</h1>';
$HTML = section_builder($PageTitle);

$TerminID = $_GET['termin'];
$Parser = parser_termin_abhaken_ueser($TerminID);

if ($Parser['success'] == NULL){
    $HTML .= termin_abhaken_formular($TerminID);
} elseif ($Parser['success'] == FALSE){
    $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'termine.php');
} else if ($Parser['success'] == TRUE){
    $HTML .= zurueck_karte_generieren(true, $Parser['meldung'], 'termine.php');
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function parser_termin_abhaken_ueser($TerminID){

    $Antwort['success'] = null;

    if(isset($_POST['action_andere'])){
        $Antwort = termin_durchfuehren($TerminID);
    } elseif (isset($_POST['rueckzahlung'])){
        $Antwort = rueckzahlung_ausgleich_durchfuehren($TerminID, $_POST['ausgabe_summe']);
    }

    return $Antwort;
}

function termin_abhaken_formular($TerminID){

    $Termin = lade_termin($TerminID);
    zeitformat();
    $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))."</b>";
    $User = lade_user_meta($Termin['user']);

    if($Termin['grund']=='ausgleich'){
        $Ausgleich = lade_ausgleich($Termin['id_grund']);
        $BisherigeAuszahlungen = lade_gezahlte_betraege_ausgleich($Ausgleich['id']);

        $Class="Geldrückzahlung";
        $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> ".$Class."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> User: ".$User['vorname']." ".$User['nachname']."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>info_outline</i> Auszahlbetrag: ".$Ausgleich['betrag']."&euro;";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>info_outline</i> Bisherige Auszahlungen: ".$BisherigeAuszahlungen."&euro;";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
        $Content = section_builder($Content);
        $Table = table_form_select_item('Ausgegebene Summe', 'ausgabe_summe', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $Ausgleich['betrag'], '&euro;', '', '', '');
        $Table .= table_row_builder(table_header_builder(button_link_creator('Zurück', 'termine.php', 'arrow_back', '')."&nbsp;".form_button_builder('rueckzahlung', 'Festhalten', 'action', 'send', '')).table_data_builder(''));
        $Table = table_builder($Table);
        $Content .= section_builder(form_builder($Table, '#', 'post'));
    } else {
        $Class=$Termin['grund'];
        $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> Grund: ".$Class."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> User: ".$User['vorname']." ".$User['nachname']."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
        $Content = collection_builder($Content);
        $Content = section_builder($Content);
        $Content .= section_builder(table_builder(table_row_builder(table_header_builder(button_link_creator('Zurück', 'termine.php', 'arrow_back', '')."&nbsp;".form_button_builder('action_andere', 'Abhacken', 'action', 'check')))));
        $Content = form_builder($Content, '#', 'post');
    }

    return $Content;
}