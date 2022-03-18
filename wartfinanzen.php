<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */
include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Wartfinanzen - " . lade_db_einstellung('site_name');
$UserID = lade_user_id();
$HTML = section_builder("<h1 class='center-align'>Wartfinanzen</h1>");

#ParserStuff
$Parser = wartfinanzen_parser($UserID);
if(isset($Parser['meldung'])){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

$HTML .= form_builder(section_wartkasse($UserID), '#', 'post');
$HTML .= form_builder(section_vergangene_transaktionen($UserID), '#', 'post');

$HTML .= "<h4 class='center-align'>Forderungen</h4>";
$HTML .= section_offene_forderungen();
$HTML .= section_forderung_an_user_anlegen($UserID);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function wartfinanzen_parser($UserID){

    $Antwort['success'] = null;

    for($a=1;$a<=100000;$a++){
        if(isset($_POST['delete_einnahme_'.$a.''])){
            #var_dump($_POST['delete_einnahme_'.$a.'']);
            $Antwort = einnahme_loeschen($a);
        }
        if(isset($_POST['delete_ausgabe_'.$a.''])){
            #var_dump($_POST['delete_ausgabe_'.$a.'']);
            $Antwort = ausgabe_loeschen($a);
        }
        if(isset($_POST['einnahme_forderung_'.$a.'_festhalten'])){
            if(is_numeric($_POST['einnahme_forderung_'.$a.''])){
                $Forderung = lade_forderung($a);
                $Konto = lade_konto_user(lade_user_id());
                $Antwort = einnahme_festhalten($a, $Konto['id'], $_POST['einnahme_forderung_'.$a.''], $Forderung['steuersatz']);
            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = 'Bitte gib einen validen Betrag ein!';
            }
        }
    }

    if(isset($_POST['action_add_forderung'])){
        if(is_numeric($_POST['betrag'])){
            $Till = $_POST['datum'].' 00:00:01';
            $Antwort = forderung_generieren($_POST['betrag'], $_POST['steuer'], $_POST['user'], '', lade_zielkonto_einnahmen_forderungen_id(), '', $_POST['reason'], $Till, lade_user_id());
        } else {
            $Antwort['success'] = false;
            $Antwort['meldung'] = 'Bitte gib einen validen Betrag ein!';
        }
    }

    return $Antwort;
}

function section_wartkasse($UserID){

    $Konto = lade_konto_user($UserID);
    $HTML = '<h5 class="center-align">Dein aktueller Wartkontostand: '.$Konto['wert_aktuell'].'&euro;</h5>';

    return section_builder($HTML);
}

function section_vergangene_transaktionen($UserID){

    $link = connect_db();
    $Wartkonto = lade_konto_user($UserID);
    $Grenze = date('Y-m-d G:i:s', strtotime('- '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' weeks'));
    $HTML = '';
    zeitformat();

    $AnfrageEinnahmen = "SELECT * FROM finanz_einnahmen WHERE konto_id = ".$Wartkonto['id']." AND  timestamp >= '".$Grenze."' AND storno_user = 0 ORDER BY timestamp DESC";
    $AbfrageEinnahmen = mysqli_query($link, $AnfrageEinnahmen);
    $AnzahlEinnahmen = mysqli_num_rows($AbfrageEinnahmen);
    $EinnahmenItems = '';
    for($a=1;$a<=$AnzahlEinnahmen;$a++){
        $ErgebnisEinnahmen = mysqli_fetch_assoc($AbfrageEinnahmen);
        if($ErgebnisEinnahmen['betrag']!=0){
            $Forderung = lade_forderung($ErgebnisEinnahmen['forderung_id']);
            $ForderungUser = lade_user_meta($Forderung['von_user']);
            if(intval($Forderung['referenz_res'])>0){
                $ForString = 'Res. #'.$Forderung['referenz_res'];
            }else{
                $ForString = $Forderung['referenz'];
            }
            $Title = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($ErgebnisEinnahmen['timestamp'])).' - '.$ErgebnisEinnahmen['betrag'].'&euro; von '.$ForderungUser['vorname'].' '.$ForderungUser['nachname'].' für '.$ForString.'<br>';
            if(intval($Forderung['referenz_res'])>0){
                //Buttons link to delete übergabe so we can take care of stuff there
                $Anfrage = "SELECT id FROM uebergaben WHERE res = ".$Forderung['referenz_res']." AND durchfuehrung != '0000-00-00 00:00:00'";
                $Abfrage = mysqli_query($link, $Anfrage);
                $Ergebnis = mysqli_fetch_assoc($Abfrage);
                $Content = button_link_creator('löschen', "undo_uebergabe.php?uebergabe=".$Ergebnis['id']."", 'delete_forever', '');
            } else {
                //Just delete it
                $Content = form_button_builder('delete_einnahme_'.$ErgebnisEinnahmen['id'].'', 'löschen', 'action', 'delete_forever');

            }
            $EinnahmenItems .= collapsible_item_builder($Title, $Content, '');
        }
    }

    $HTML .= '<h4 class="center-align">Einnahmen der letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen</h4>';
    if($AnzahlEinnahmen>0){
        $HTML .= collapsible_builder($EinnahmenItems);
    } else {
        $EinnahmenItems = collapsible_item_builder('Keine Einnahmen in den letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen!','','');
        $HTML .= collapsible_builder($EinnahmenItems);
    }

    $HTML .= divider_builder();

    $AnfrageAusgaben = "SELECT * FROM finanz_ausgaben WHERE konto_id = ".$Wartkonto['id']." AND storno_user = 0 AND timestamp >= '".$Grenze."' ORDER BY timestamp DESC";
    $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
    $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);
    $AusgabenItems = '';
    for($b=1;$b<=$AnzahlAusgaben;$b++){
        $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
        if($ErgebnisAusgaben['betrag']!=0){
            $Ausgleich = lade_ausgleich($ErgebnisAusgaben['ausgleich_id']);
            $ForderungUser = lade_user_meta($Ausgleich['von_user']);
            if(intval($Ausgleich['referenz_res'])>0){
                $ForString = 'Res. #'.$Ausgleich['referenz_res'];
            }else{
                $ForString = $Ausgleich['referenz'];
            }
            $Title = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($ErgebnisAusgaben['timestamp'])).' - '.$ErgebnisAusgaben['betrag'].'&euro; von '.$ForderungUser['vorname'].' '.$ForderungUser['nachname'].' für '.$ForString.'<br>';
            //Just delete it
            $Content = form_button_builder('delete_ausgabe_'.$ErgebnisAusgaben['id'].'', 'löschen', 'action', 'delete_forever');
            $AusgabenItems .= collapsible_item_builder($Title, $Content, '');
        }
    }

    $HTML .= '<h4 class="center-align">Ausgaben der letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen</h4>';
    if($AnzahlAusgaben>0){
        $HTML .= collapsible_builder($AusgabenItems);
    } else {
        $AusgabenItems = collapsible_item_builder('Keine Ausgaben in den letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen!', '', '');
        $HTML .= collapsible_builder($AusgabenItems);
    }

    $HTML .= divider_builder();

    $AnfrageTransfers = "SELECT * FROM finanz_transfer WHERE ((von = ".$Wartkonto['id'].") OR (nach = ".$Wartkonto['id'].")) AND storno_user = 0 AND timestamp >= '".$Grenze."' ORDER BY timestamp DESC";
    $AbfrageTransfers = mysqli_query($link, $AnfrageTransfers);
    $AnzahlTransfers = mysqli_num_rows($AbfrageTransfers);
    $TransferItems='';
    for($c=1;$c<=$AnzahlTransfers;$c++){
        $ErgebnisTransfer = mysqli_fetch_assoc($AbfrageTransfers);
        if($ErgebnisTransfer['betrag']!=0){
            if(intval($ErgebnisTransfer['von'])==$Wartkonto){
                $AnderesKonto = lade_konto_via_id($ErgebnisTransfer['nach']);
                if(intval($AnderesKonto['name'])>0){
                        $AnderesKontoUser = lade_user_meta($AnderesKonto['nach']);
                        $ForString = $AnderesKontoUser['vorname'].'&nbsp;'.$AnderesKontoUser['nachname'];
                } else {
                        $ForString = $AnderesKonto['name'];
                }
                $Title = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($ErgebnisTransfer['timestamp'])).' - '.$ErgebnisTransfer['betrag'].'&euro; von <b>dir</b> zu '.$ForString.'<br>';
            }elseif (intval($ErgebnisTransfer['nach'])==$Wartkonto){
                $AnderesKonto = lade_konto_via_id($ErgebnisTransfer['von']);
                if(intval($AnderesKonto['name'])>0){
                    $AnderesKontoUser = lade_user_meta($AnderesKonto['nach']);
                    $ForString = $AnderesKontoUser['vorname'].'&nbsp;'.$AnderesKontoUser['nachname'];
                } else {
                    $ForString = $AnderesKonto['name'];
                }
                $Title = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($ErgebnisTransfer['timestamp'])).' - '.$ErgebnisTransfer['betrag'].'&euro; von '.$ForString.' zu <b>dir</b><br>';
            }
            $Content = form_button_builder('delete_transfer_'.$ErgebnisAusgaben['id'].'', 'löschen', 'action', 'delete_forever');
            $TransferItems .= collapsible_item_builder($Title, '', '');
        }
    }

    $HTML .= '<h4 class="center-align">Umbuchungen der letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen</h4>';
    if($AnzahlTransfers>0){
        $HTML .= collapsible_builder($TransferItems);
    } else {
        $TransferItems = collapsible_item_builder('Keine Umbuchungen in den letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen!', '', 'swap_horiz');
        $HTML .= collapsible_builder($TransferItems);
    }

    $HTML .= divider_builder();

    return $HTML;
}

function section_offene_forderungen(){

    $link = connect_db();
    $Anfrage = "SELECT * FROM finanz_forderungen WHERE storno_user = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl>1){
        $ReturnHTML = '';
        $counter=0;
        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $Summe = lade_gezahlte_summe_forderung($Ergebnis['id']);
            if($Summe<$Ergebnis['betrag']){
                if($Ergebnis['referenz']!=''){
                    $counter++;
                    $ReturnHTML .= listenelement_offene_forderung_durchfuehren_generieren($Ergebnis, $Summe);
                }
            }
        }
        if($counter>0){
            return form_builder(collapsible_builder($ReturnHTML), '#', 'post', 'open_forderungen');
        }else{
            return collapsible_builder(collapsible_item_builder('Keine offenen Forderungen', '', ''));
        }
    } else {
        return collapsible_builder(collapsible_item_builder('Keine offenen Forderungen', '', ''));
    }
}

function section_forderung_an_user_anlegen($UserID){

    $Table = table_form_string_item('Forderungsgrund', 'reason', $_POST['reason'], false);
    $Table .= table_form_dropdown_menu_user('Von Nutzer', 'user', $_POST['user']);
    $Table .= table_form_string_item('Betrag (Format: 12.34)', 'betrag', $_POST['betrag'], false);
    $Table .= table_form_select_item('Steuersatz', 'steuer', 0, 99, 19, '%', '', '');
    $Table .= table_form_datepicker_reservation_item('Zahlbar bis', 'datum', $_POST['datum'], false, true);
    $Table .= table_row_builder(table_header_builder(form_button_builder('action_add_forderung', 'Anlegen', 'action', 'send')).table_data_builder(''));
    $Table = table_builder($Table);

    $HTML = collapsible_item_builder('Forderung anlegen', $Table, 'add_new');

    return form_builder(collapsible_builder($HTML), '#', 'post', 'add_forderungen');
}