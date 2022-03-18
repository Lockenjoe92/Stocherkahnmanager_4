<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Reservierung bearbeiten - " . lade_db_einstellung('site_name');
$ResID = $_GET['id'];
$Mode = mode_feststellen_res_bearbeiten($ResID);
$Parser = parse_res_bearbeiten($ResID);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Reservierung bearbeiten</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Reservierung bearbeiten</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= reservierung_bearbeiten_seiteninhalt($Mode, $ResID, $Parser);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function reservierung_bearbeiten_seiteninhalt($Mode, $ResID, $Parser){

    if ($Parser['success'] === NULL){

        //Reservierungsinfos
        $HTML = karte_reservierungsinfos_generieren($Mode, $ResID);

        //Wartfunktionen
        if ($Mode == "wart") {
            $HTML .= karte_wartfunktionen($ResID);
        }

        //Zeiten ändern
        $HTML .= karte_zeiten_aendern($ResID, $Mode);

        $HTML = form_builder($HTML, './reservierung_bearbeiten.php?id='.$ResID.'', 'post', 'preiskram');

    } else {

        if ($Parser['success'] == TRUE){

            if ($Mode == "wart"){
                $ResMeta = lade_reservierung($ResID);
                if($ResMeta['user'] == lade_user_id()){
                    $HTML = zurueck_karte_generieren(TRUE, $Parser['meldung'], 'my_reservations.php');
                } else {
                    $HTML = zurueck_karte_generieren(TRUE, $Parser['meldung'], 'reservierungsmanagement.php');
                }
            } else if ($Mode == "eigen"){
                $HTML = zurueck_karte_generieren(TRUE, $Parser['meldung'], 'my_reservations.php');
            }

        } else if ($Parser['success'] == FALSE){

            if ($Mode == "wart"){
                $ResMeta = lade_reservierung($ResID);
                if($ResMeta['user'] == lade_user_id()){
                    $HTML = zurueck_karte_generieren(TRUE, $Parser['meldung'], 'my_reservations.php');
                } else {
                    $HTML = zurueck_karte_generieren(TRUE, $Parser['meldung'], 'reservierungsmanagement.php');
                }
            } else if ($Mode == "eigen"){
                $HTML = zurueck_karte_generieren(FALSE, $Parser['meldung'], 'my_reservations.php');
            }

        }

    }

    return $HTML;
}

function parse_res_bearbeiten($Res){

    $Antwort = array();
    $Antwort['success'] = null;

    if (isset($_POST['zeiten_veraendern'])){

        $AngabeBeginn = $_POST['beginn_verschieben'];
        $AngabeEnde = $_POST['ende_verschieben'];
        $Verguenstigung = $_POST['verguenstigung'];

        if (isset($_POST['gratis_fahrt'])){
            $Gratisfahrt = true;
        } else {
            $Gratisfahrt = false;
        }

        $ResBearbeiten = reservierung_bearbeiten($Res, $AngabeBeginn, $AngabeEnde, $Verguenstigung, $Gratisfahrt);

        $Antwort['success'] = $ResBearbeiten['success'];
        $Antwort['meldung'] = $ResBearbeiten['meldung'];

    } else if (isset($_POST['wart_veraendern'])){
        $Antwort['success'] = null;
    }

    return $Antwort;
}

function mode_feststellen_res_bearbeiten($ResID){

    $Reservierung = lade_reservierung($ResID);
    $UserID = lade_user_id();

    if ($UserID == intval($Reservierung['user'])) {
        //Jemand versucht seine eigene Res zu bearbeiten
        return "eigen";
    } else {
        //jemand versucht eine fremde Res zu bearbeiten - kontrolle ob Wart oder nicht
        $Benutzerrollen = lade_user_meta($UserID);
        if ($Benutzerrollen['ist_wart'] == 'true') {
            //Ok wir machen weiter
            return "wart";
        } else {
            header("Location: ./my_reservations.php");
            die();
        }
    }
}

function karte_reservierungsinfos_generieren($Mode, $ResID){

    zeitformat();
    $Reservierung = lade_reservierung($ResID);
    $UserRes = lade_user_meta($Reservierung['user']);

    $HTML = table_row_builder(table_header_builder('Reservierungsnummer').table_data_builder($Reservierung['id']));
    $HTML .= table_row_builder(table_header_builder('Datum').table_data_builder(strftime("%A, %d %b %G", strtotime($Reservierung['beginn']))));
    $HTML .= table_row_builder(table_header_builder('Zeitfenster').table_data_builder("".date("G", strtotime($Reservierung['beginn']))." - ".date("G", strtotime($Reservierung['ende']))." Uhr"));
    $HTML .= table_row_builder(table_header_builder('Kosten').table_data_builder("".kosten_reservierung($Reservierung['id'])."&euro;"));
    if ($Mode == "wart"){
        $HTML .= table_row_builder(table_header_builder('User').table_data_builder("".$UserRes['vorname']." ".$UserRes['nachname'].""));
    }
    $HTML = table_builder($HTML);

    return $HTML;
}

function karte_zeiten_aendern($ResID, $Mode){

    $link = connect_db();
    $Reservierung = lade_reservierung($ResID);
    $DauerRes = stunden_differenz_berechnen($Reservierung['beginn'], $Reservierung['ende']);
    $AnfangTag = "".date("Y-m-d", strtotime($Reservierung['beginn']))." 00:00:01";
    $EndeTag = "".date("Y-m-d", strtotime($Reservierung['beginn']))." 23:59:59";

    //Früherer Beginn - Rservierung vorher, aber am gleichen Tag, die nächsten dran ist benutzen
    $LadeVorgehendeReservierungenAnfrage = "SELECT * FROM reservierungen WHERE storno_user = '0' AND beginn > '$AnfangTag' AND ende < '$EndeTag' AND ende < '".$Reservierung['beginn']."' ORDER BY ende DESC";
    $LadeVorgehendeReservierungenAbfrage = mysqli_query($link, $LadeVorgehendeReservierungenAnfrage);
    $LadeVorgehendeReservierungenAnzahl = mysqli_num_rows($LadeVorgehendeReservierungenAbfrage);

    if ($LadeVorgehendeReservierungenAnzahl == 0){
        //Es gibt keine Reservierung vorher - Grenze ist nur der Früheste erlaubte Buchungsbeginn an diesem Tag
        $WochentagReservierungNumerisch = date("N", strtotime($Reservierung['beginn']));

        if ($WochentagReservierungNumerisch == 1){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-montag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 2){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-dienstag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 3){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-mittwoch'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 4){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-donnerstag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 5){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-freitag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 6){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-samstag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 7){
            $TimestampFruehesterBeginn = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('von-sonntag'), 2, "0", STR_PAD_LEFT).":00:00";
        }

        if (stunden_differenz_berechnen($TimestampFruehesterBeginn, $Reservierung['beginn']) == 0){
            $MoegicheStundenFrueherBeginn = false;
        } else if (stunden_differenz_berechnen($TimestampFruehesterBeginn, $Reservierung['beginn']) > 0){
            $MoegicheStundenFrueherBeginn = stunden_differenz_berechnen($TimestampFruehesterBeginn, $Reservierung['beginn']);
        }


    } else if ($LadeVorgehendeReservierungenAnzahl > 0){

        $VorhergehendeReservierung = mysqli_fetch_assoc($LadeVorgehendeReservierungenAbfrage);

        if (stunden_differenz_berechnen($VorhergehendeReservierung['ende'], $Reservierung['beginn']) == 0){
            $MoegicheStundenFrueherBeginn = false;
        } else if (stunden_differenz_berechnen($VorhergehendeReservierung['ende'], $Reservierung['beginn']) > 0){
            $MoegicheStundenFrueherBeginn = stunden_differenz_berechnen($VorhergehendeReservierung['ende'], $Reservierung['beginn']);
        }

    }

    //Späterer Beginn
    if ($DauerRes == 1){
        $MoegicheStundenSpaeterBeginn = false;
    } else if ($DauerRes > 1){
        $MoegicheStundenSpaeterBeginn = $DauerRes - 1;
    }

    //Früheres Ende - Betrifft also nur Reservierung selbst
    if ($DauerRes == 1){
        $MoegicheStundenFrueherEnde = false;
    } else if ($DauerRes > 1){
        $MoegicheStundenFrueherEnde = $DauerRes - 1;
    }

    //Späteres Ende
    $LadeNachfolgendeReservierungenAnfrage = "SELECT * FROM reservierungen WHERE storno_user = '0' AND beginn > '$AnfangTag' AND ende < '$EndeTag' AND beginn >= '".$Reservierung['ende']."' ORDER BY beginn ASC";
    $LadeNachfolgendeReservierungenAbfrage = mysqli_query($link, $LadeNachfolgendeReservierungenAnfrage);
    $LadeNachfolgendeReservierungenAnzahl = mysqli_num_rows($LadeNachfolgendeReservierungenAbfrage);

    if ($LadeNachfolgendeReservierungenAnzahl == 0){
        //Es gibt keine Reservierung vorher - Grenze ist nur der Früheste erlaubte Buchungsbeginn an diesem Tag
        $WochentagReservierungNumerisch = date("N", strtotime($Reservierung['beginn']));

        if ($WochentagReservierungNumerisch == 1){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-montag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 2){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-dienstag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 3){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-mittwoch'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 4){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-donnerstag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 5){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-freitag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 6){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-samstag'), 2, "0", STR_PAD_LEFT).":00:00";
        } else if ($WochentagReservierungNumerisch == 7){
            $TimestampLetztesEnde = "".date("Y-m-d", strtotime($Reservierung['beginn']))." ".str_pad(lade_xml_einstellung('bis-sonntag'), 2, "0", STR_PAD_LEFT).":00:00";
        }

        if (stunden_differenz_berechnen($TimestampLetztesEnde, $Reservierung['ende']) == 0){
            $MoegicheStundenSpaeterEnde = false;
        } else if (stunden_differenz_berechnen($TimestampLetztesEnde, $Reservierung['ende']) > 0){
            $MoegicheStundenSpaeterEnde = stunden_differenz_berechnen($Reservierung['ende'], $TimestampLetztesEnde);
        }

    } else if ($LadeNachfolgendeReservierungenAnzahl > 0){

        $NachfolgendeReservierung = mysqli_fetch_assoc($LadeNachfolgendeReservierungenAbfrage);

        if (stunden_differenz_berechnen($Reservierung['ende'], $NachfolgendeReservierung['beginn']) == 0){
            $MoegicheStundenSpaeterEnde = false;
        } else if (stunden_differenz_berechnen($Reservierung['ende'], $NachfolgendeReservierung['beginn']) > 0){
            $MoegicheStundenSpaeterEnde = stunden_differenz_berechnen($Reservierung['ende'], $NachfolgendeReservierung['beginn']);
        }

    }

    $HTML = divider_builder();
    $HTML .= '<h3 class="center-align">Zeiten verschieben</h3>';
    $FormTable = table_row_builder(table_header_builder('Beginn der Reservierung verschieben').table_data_builder(dropdown_beginn_reservierung_verschieben('beginn_verschieben', $MoegicheStundenFrueherBeginn, $MoegicheStundenSpaeterBeginn)));
    $FormTable .= table_row_builder(table_header_builder('Ende der Reservierung verschieben').table_data_builder(dropdown_ende_reservierung_verschieben('ende_verschieben', $MoegicheStundenFrueherEnde, $MoegicheStundenSpaeterEnde)));
    if($Mode=='wart'){
        $FormTable .= table_row_builder(table_header_builder(form_button_builder('zeiten_veraendern', 'Bearbeiten', 'action', 'edit', ''). " " .button_link_creator('Zurück', 'reservierungsmanagement.php', 'arrow_back', '')).table_data_builder(''));
    }else{
        $FormTable .= table_row_builder(table_header_builder(form_button_builder('zeiten_veraendern', 'Bearbeiten', 'action', 'edit', ''). " " .button_link_creator('Zurück', 'my_reservations.php', 'arrow_back', '')).table_data_builder(''));
    }
    $FormTable = table_builder($FormTable);
    $HTML .= $FormTable;

    return $HTML;
}

function karte_wartfunktionen($ResID){

        $Res = lade_reservierung($ResID);

        //Checkbox parser
        if (isset($_POST['gratis_fahrt'])) {
            $Checkbox = "checked";
        } else {
            if($Res['gratis_fahrt']==1){
                $Checkbox = "checked";
            } else {
                $Checkbox = "";
            }
        }

        //Vergünstigung Parser
        if($_POST['gratis_fahrt']!=''){
            $Verguenstigung = $_POST['gratis_fahrt'];
        } else {
            $Verguenstigung = $Res['preis_geandert'];
        }

        $Antwort = "<div class='divider'></div>";

        $Antwort .= "<h3 class='center-align'>Kosten bearbeiten</h3>";

        $TableWartHTML = table_form_swich_item('Fahrt gratis', 'gratis_fahrt', 'Nein', 'Ja', $Checkbox, false);
        $TableWartHTML .= table_form_select_item('Verg&uuml;nstigter Tarif', 'verguenstigung', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $Verguenstigung, '&euro;', 'Vergünstigung', '');
        $Antwort .= table_builder($TableWartHTML);

    return $Antwort;
}

?>