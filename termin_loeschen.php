<?php
include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Termin löschen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Termin löschen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Termin löschen</h1>';
$HTML = section_builder($PageTitle);

$TerminID = $_GET['termin'];
$User = lade_user_meta(lade_user_id());
$Parser = parser_termin_loeschen_ueser($TerminID);

if ($Parser['success'] == NULL){
    if($User['ist_wart']){
        $HTML .= termin_loeschen_formular($TerminID, 'wart');
    } else {
        $HTML .= termin_loeschen_formular($TerminID, 'user');
    }
} elseif ($Parser['success'] == FALSE){
    if($User['ist_wart']) {
        $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'termine.php');
    } else {
        $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'my_reservations.php');
    }
} else if ($Parser['success'] == TRUE){
    if($User['ist_wart']) {
        $HTML .= zurueck_karte_generieren(true, $Parser['meldung'], 'termine.php');
    } else {
        $HTML .= zurueck_karte_generieren(true, $Parser['meldung'], 'my_reservations.php');
    }}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function parser_termin_loeschen_ueser($TerminID){

    $Antwort['success'] = null;

    if(isset($_POST['action_loeschen'])){
        $Antwort = termin_loeschen($TerminID, $_POST['termin_loeschen_kommentar']);
    }

    return $Antwort;
}

function termin_loeschen_formular($TerminID, $Mode){

    $Termin = lade_termin($TerminID);
    zeitformat();
    $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))."</b>";
    $User = lade_user_meta($Termin['user']);

    if($Termin['grund']=='ausgleich'){
        $Class="Geldrückzahlung";
    } else {
        $Class=$Termin['grund'];
    }

    $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> Grund für den Termin: ".$Class."";
    $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
    if($Mode=='wart'){
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> User: ".$User['vorname']." ".$User['nachname']."";
    }
    $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
    $Content = collection_builder($Content);
    $Content = section_builder($Content);
    $Content .= section_builder(table_builder(table_row_builder(table_form_string_item('Kommentar zum Löschen angeben (optional)', 'termin_loeschen_kommentar', '', '')).table_row_builder(table_header_builder(button_link_creator('Zurück', 'termine.php', 'arrow_back', '')."&nbsp;".form_button_builder('action_loeschen', 'Löschen', 'action', 'delete_forever')).table_data_builder(''))));
    $Content = form_builder($Content, '#', 'post');

    return $Content;
}