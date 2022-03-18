<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Übergabe durchführen - " . lade_db_einstellung('site_name');
$UebergabeID = $_GET['id'];
$HTML = section_builder("<h1 class='center-align'>Übergabe durchführen</h1>");

#ParserStuff
$Parser = do_uebergabe_parser($UebergabeID);

if ($Parser['success'] == NULL){
    $HTML .= formular_do_uebergabe($UebergabeID);
} else if (($Parser['success'] == TRUE)){
    $HTML .= zurueck_karte_generieren(TRUE, '&Uuml;bergabe erfolgreich durchgef&uuml;hrt!', 'termine.php');
} else if (($Parser['success'] == FALSE)){
    $HTML .= zurueck_karte_generieren(TRUE, 'Fehler bei der Durchf&uuml;hrung der &Uuml;bergabe!:<br>'.$Parser['error'].'', 'termine.php');
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);




function do_uebergabe_parser($UebergabeID){

    // Catch URL BS
    if(intval($UebergabeID > 0)){
        if(isset($_POST['uebergabe_durchfuehren'])){

            if (isset($_POST['gratis'])){
                $Gratisfahrt = TRUE;
            } else {
                $Gratisfahrt = FALSE;
            }

            if (isset($_POST['status_verified'])){
                $Status = TRUE;
            } else {
                $Status = FALSE;
            }

            if ($_POST['alternativer_preis'] != ""){
                $AndererPreis = $_POST['alternativer_preis'];
            }

            $Antwort = uebergabe_durchfuehren($UebergabeID, $_POST['schluessel'], $_POST['gezahlter_betrag'], $AndererPreis, $Gratisfahrt, $Status);
            return $Antwort;
        } else {
            return $Antwort['success']=null;
        }

    } else {
        header("Location: ./wartwesen.php");
        die();
    }
}
function formular_do_uebergabe($UebergabeID){

    //LoadStuff
    $Uebergabe = lade_uebergabe($UebergabeID);
    $ResDerUebergabe = lade_reservierung($Uebergabe['res']);
    $UserID = $ResDerUebergabe['user'];
    $UserMeta = lade_user_meta($UserID);
    $NutzergruppeMeta = lade_nutzergruppe_infos($UserMeta['ist_nutzergruppe'], 'name');
    $VerifizierungErklaerung = $NutzergruppeMeta['req_verify'];

    if($VerifizierungErklaerung!='false'){
        $NutzergruppeVerification = load_last_nutzergruppe_verification_user($NutzergruppeMeta['id'], $UserID);
            if($NutzergruppeVerification['erfolg'] == 'false'){
                $VerifizierungAusgabe = "<b>Letzte Verifizierung ABGELEHNT!</b><br>Nutzergruppe <b>".$NutzergruppeMeta['name']."</b> trotzdem verifizieren";
                $NGswitchPreload = 'off';
            } elseif ($NutzergruppeVerification['erfolg'] == 'true'){
                if($NutzergruppeVerification['timestamp'] < "".date('Y')."-01-01 00:00:01"){
                    $VerifizierungAusgabe = "<b>Verifizierung abgelaufen!</b><br>Nutzergruppe <b>".$NutzergruppeMeta['name']."</b> verifizieren";
                    $NGswitchPreload = 'off';
                } elseif ($NutzergruppeVerification['timestamp'] >= "".date('Y')."-01-01 00:00:01"){
                    $VerifizierungAusgabe = "<b>Verifizierung dieses Jahr erfolgt!:)</b>";
                    $NGswitchPreload = 'on';
                }
            } elseif (empty($NutzergruppeVerification)){
                $VerifizierungAusgabe = "<b>Verifizierung bislang NIE erfolgt!</b><br>Nutzergruppe <b>".$NutzergruppeMeta['name']."</b> verifizieren";
                $NGswitchPreload = 'off';
            }
    }

    if(isset($_POST['uebergabe_durchfuehren'])){
        $NGswitch = $_POST['status_verified'];
    } else {
        $NGswitch = $NGswitchPreload;
    }

    $HTML = "<h3 class='center-align'>Informationen</h3>";
    $CollapsibleItems = reservierung_listenelement_generieren($Uebergabe['res'], true);
    $CollapsibleItems .= uebergabe_listenelement_generieren($UebergabeID, FALSE);
    $CollapsibleItems .= dokumente_listenelement_generieren();
    $CollapsibleItems .= faq_user_hauptansicht_generieren();
    $HTML .= section_builder(collapsible_builder($CollapsibleItems));

    $DurchfuehrungTableHTML = table_row_builder(table_header_builder('Schlüssel auswählen').table_data_builder(dropdown_verfuegbare_schluessel_wart('schluessel', $Uebergabe['wart'], true)));
    $DurchfuehrungTableHTML .= table_form_swich_item('Als Gratisfahrt eintragen', 'gratis', 'Nein', 'Ja', $_POST['gratis'], false);
    $DurchfuehrungTableHTML .= table_form_select_item('Alternativer Preis', 'alternativer_preis', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $_POST['alternativer_preis'], '&euro;', 'Alternativer Preis', '');
    $DurchfuehrungTableHTML .= table_form_select_item('Gezahlter Betrag', 'gezahlter_betrag', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $_POST['gezahlter_betrag'], '&euro;', 'Gezahlter Betrag', '');
    $DurchfuehrungTableHTML .= table_form_swich_item($VerifizierungAusgabe, 'status_verified', 'Nein', 'Ja', $NGswitch, false);
    $DurchfuehrungTableHTML .= table_row_builder(table_header_builder(button_link_creator('Abbrechen', './wartwesen.php', 'arrow_back', '')).table_data_builder(form_button_builder('uebergabe_durchfuehren', 'Durchführen', 'action', 'send', '')));
    $DurchfuehrungTable = table_builder($DurchfuehrungTableHTML);
    $DurchfuehrungTableHTML = form_builder($DurchfuehrungTable, '#', 'post', 'do_uebergabe_'.$UebergabeID);

    $DurchfuehrungHTML = "<h3 class='center-align'>Durchführung</h3>";
    $DurchfuehrungHTML .= section_builder($DurchfuehrungTableHTML);
    $HTML .= $DurchfuehrungHTML;

    return $HTML;
}

?>