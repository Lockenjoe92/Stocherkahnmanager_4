<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Schl&uuml;sselverwaltung - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;sselverwaltung</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Schl&uuml;ssel verwalten</h1>';
$HTML = section_builder($PageTitle);

#ParserStuff
$Parser = parser_schluesselmanagement();
if(isset($Parser['meldung'])){
    $HTML .= "<h5>".$Parser['meldung']."</h5>";
}

# Content
$HTML .= spalte_anstehende_rueckgaben();
$HTML .= spalte_verfuegbare_schluessel();
$HTML .= spalte_dir_zugeteilte_schluessel();
$HTML .= spalte_schluessel_verwalten();

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function spalte_verfuegbare_schluessel(){

    $link = connect_db();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header center-align'>Verf&uuml;gbare Schl&uuml;ssel</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id, farbe, farbe_materialize, RFID FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    if ($AnzahlLadeVerfuegbareSchluessel == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons icon-red'>info</i>Derzeit keine Schl&uuml;ssel im R&uuml;ckgabekasten!</div>";
        $HTML .= "</li>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons icon-red'>info</i>R&uuml;ckgabekasten leer!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeVerfuegbareSchluessel > 0){

        //Lade letzte zwei Logs
        $minTimestampToday = date('Y-m-d')." 00:00:01";
        $anfrage = "SELECT * FROM lora_logs WHERE timestamp > '".$minTimestampToday."' ORDER BY timestamp DESC";
        $abfrage = mysqli_query($link, $anfrage);
        $anzahl = mysqli_num_rows($abfrage);

        if($anzahl>0) {

            $lastLog = array();
            $secondLastLog = array();

            for ($a = 0; $a < 2; $a++) {
                if ($a == 0) {
                    $lastLog = mysqli_fetch_assoc($abfrage);
                } elseif ($a == 1) {
                    $secondLastLog = mysqli_fetch_assoc($abfrage);
                }
            }

            $KeystatusLastLog = explode(',', $lastLog['schluessel']);
            $KeystatusSecondLastLog = explode(',', $secondLastLog['schluessel']);
        } else {
            $lastLog = '';
            $secondLastLog = '';
        }

        for ($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
            $Content = '';

            if($lastLog == ''){
                if($Schluessel['RFID']!=''){
                    $TitleString = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']." <i class='tiny material-icons'>bluetooth_audio</i>";
                } else {
                    $TitleString = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."";
                }
            } else {
                //Checken, ob RFID Tag 2x in Folge gefunden wurde
                $LastStatusKey = $KeystatusLastLog[$Schluessel['id']];
                $SecondLastStatusKey = $KeystatusSecondLastLog[$Schluessel['id']];
                $Sum = $LastStatusKey+$SecondLastStatusKey;
                if(($Sum/2)==1){
                    #Schlüssel ist da
                    $ColorCommand = '';
                } elseif (($Sum/2)==0.5){
                    #Wackelige Verbindung/vor 15min entfernt
                    $ColorCommand = 'yellow';
                    $Content .= "<p class='center-align'>Schlüssel seit mehr als 15 Minuten nicht mehr im Kasten! Evtl. schon herausgenommen, aber noch nicht umgebucht.</p>";
                } elseif ($Sum==0){
                    #Schlüssel seit 30min weg
                    $ColorCommand = 'red';
                    $Content .= "<p class='center-align'>Schlüssel seit mehr als 30 Minuten nicht mehr im Kasten! Evtl. schon herausgenommen, aber noch nicht umgebucht.</p>";
                }

                if($Schluessel['RFID']!=''){
                    $TitleString = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."&nbsp;<i class='tiny material-icons ".$ColorCommand."'>bluetooth_audio</i>";
                } else {
                    $TitleString = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."";
                }
            }

            $Content .= form_builder(table_builder(table_header_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_herausnehmen', 'Herausnehmen', 'action', 'send'))), '#', 'post', '','');
            $HTML .= collapsible_item_builder($TitleString, $Content, 'vpn_key', $Schluessel['farbe_materialize']);

        }

    }

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_dir_zugeteilte_schluessel(){

    $link = connect_db();

    $VerfuegbareSchluessel = wart_verfuegbare_schluessel(lade_user_id());

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header center-align'>Dir zugeteilte Schl&uuml;ssel</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeZugeteilteSchluessel = "SELECT id, farbe, farbe_materialize, RFID FROM schluessel WHERE akt_user = '".lade_user_id()."' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeZugeteilteSchluessel = mysqli_query($link, $AnfrageLadeZugeteilteSchluessel);
    $AnzahlLadeZugeteilteSchluessel = mysqli_num_rows($AbfrageLadeZugeteilteSchluessel);

    if ($AnzahlLadeZugeteilteSchluessel == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons icon-red'>info</i>Derzeit sind dir keine Schl&uuml;ssel zugeteilt!</div>";
        $HTML .= "</li>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons icon-red'>info</i>Du bist Schl&uuml;ssellos!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeZugeteilteSchluessel > 0){

        for ($a = 1; $a <= $AnzahlLadeZugeteilteSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeZugeteilteSchluessel);

            if($Schluessel['RFID']==''){
                $Titel = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."";
            } else {
                $Titel = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']." <i class='tiny material-icons'>bluetooth_audio</i>";
            }

            if ($VerfuegbareSchluessel > 0) {
                $Content = form_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('action_schluessel_' . $Schluessel['id'] . '_zuruecklegen', 'Zurücklegen', 'action', 'send', '')))), '#', 'post', '', '');
            } else {
                $Content = "Schlüssel bereits verplant!";
            }
            $HTML .= collapsible_item_builder($Titel, $Content, 'vpn_key', $Schluessel['farbe_materialize']);

        }

    }

    if($AnzahlLadeZugeteilteSchluessel > wart_verfuegbare_schluessel(lade_user_id())){

        $Differenz = $AnzahlLadeZugeteilteSchluessel - wart_verfuegbare_schluessel(lade_user_id());
        if ($Differenz == 1){
            $Grammatik = "ist bereits einer ";
        } else if ($Differenz > 1){
            $Grammatik = "sind bereits ".$Differenz." ";
        }


        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Achtung!</div>";
        $HTML .= "<div class='collapsible-body'>";

            if ($AnzahlLadeZugeteilteSchluessel == 1){
                $HTML .= "<p>Der dir zugeteilte Schl&uuml;ssel ist bereits f&uuml;r eine &Uuml;bergabe eingeplant!</p>";
            } else if ($AnzahlLadeZugeteilteSchluessel > 1){
                $HTML .= "<p>Von den ".$AnzahlLadeZugeteilteSchluessel." dir zugeteilten Schl&uuml;sseln, ".$Grammatik."f&uuml;r &Uuml;bergaben eingeplant!</p>";
            }

        $HTML .= "</div>";
        $HTML .= "</li>";

    }


    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_schluessel_verwalten(){

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='center-align'>Schl&uuml;ssel verwalten</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $HTML .= schluessel_aktueller_status_listenelement_generieren();
    $HTML .= schluessel_umbuchen_listenelement_generieren();
    $HTML .= schluessel_bearbeiten_listenelement_generieren();
    $HTML .= schluessel_hinzufuegen_listenelement_generieren();

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}

function schluessel_aktueller_status_listenelement_generieren(){

    $link = connect_db();
    $Anfrage = 'SELECT * FROM schluessel WHERE delete_user = 0 ORDER BY id ASC';
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $HTMLcollapsible = '';
    if($Anzahl > 0){
        for($a=1;$a<=$Anzahl;$a++){
            $Schluessel = mysqli_fetch_assoc($Abfrage);
            if($Schluessel['RFID']==''){
                $SchluesselInfos = '#'.$Schluessel['id'].' '.$Schluessel['farbe'].'';
            } else {
                $SchluesselInfos = '#'.$Schluessel['id'].' '.$Schluessel['farbe'].' <i class="tiny material-icons">bluetooth_audio</i>';
            }
            if(intval($Schluessel['akt_user'])>0){
                $UserMeta = lade_user_meta($Schluessel['akt_user']);
                if($UserMeta['ist_wart']){
                    $Content = '<b>Aktueller User:</b> '.$UserMeta['vorname'].' '.$UserMeta['nachname'].' (Wart:in)';
                } else {
                    $Anfrage2 = 'SELECT * FROM schluesselausgabe WHERE storno_user = 0 AND user = '.$Schluessel['akt_user'].' AND rueckgabe IS NULL';
                    $Abfrage2 = mysqli_query($link, $Anfrage2);
                    $Ausgabe2 = mysqli_fetch_assoc($Abfrage2);
                    $AusgabeWart = lade_user_meta($Ausgabe2['wart']);
                    $Content = '<b>Aktueller User:</b> '.$UserMeta['vorname'].' '.$UserMeta['nachname'].'<br>';
                    $Content .= '<b>Ausgabe:</b> '.strftime("%A, %d. %B %G", strtotime($Ausgabe2['ausgabe'])).' durch '.$AusgabeWart['vorname'].' '.$AusgabeWart['nachname'].'';
                }
            } elseif ($Schluessel['akt_ort'] != '') {
                if($Schluessel['akt_ort'] == 'rueckgabekasten'){
                    $Content = '<b>Aktueller Ort:</b> Rückgabekasten';
                } else {
                    $Content = '<b>Aktueller Ort:</b> '.$Schluessel['akt_ort'];
                }
                $Anfrage3 = "SELECT * FROM schluesselausgabe WHERE schluessel = '".$Schluessel['id']."' AND storno_user = 0 ORDER BY ausgabe DESC";
                $Abfrage3 = mysqli_query($link, $Anfrage3);
                $Anzahl3 = mysqli_num_rows($Abfrage3);
                if($Anzahl3>0){
                    $LetzteAusgabe = mysqli_fetch_assoc($Abfrage3);
                    $UserLetzteAusgabe = lade_user_meta($LetzteAusgabe['user']);
                    $WartLetzteAusgabe = lade_user_meta($LetzteAusgabe['wart']);
                    $Content .= '<br><b>Letzte Ausgabe:</b> '.strftime("%A, %d. %B %G", strtotime($LetzteAusgabe['ausgabe'])).' durch '.$WartLetzteAusgabe['vorname'].' '.$WartLetzteAusgabe['nachname'].' an '.$UserLetzteAusgabe['vorname'].' '.$UserLetzteAusgabe['nachname'].'';
                }
            }

            $HTMLcollapsible .= collapsible_item_builder($SchluesselInfos, $Content, 'vpn_key', $Schluessel['farbe_materialize']);
        }
        $HTMLcollapsible = collapsible_builder($HTMLcollapsible);
    } else {
        $HTMLcollapsible .= 'Aktuell keine Schlüssel angelegt!';
    }
    $HTML = collapsible_item_builder('Schlüsselübersicht', $HTMLcollapsible, 'location_on');
    return $HTML;
}
function schluessel_umbuchen_listenelement_generieren(){

    $FormTable = table_row_builder(table_header_builder('Schlüssel auswählen').table_data_builder(dropdown_aktive_schluessel('id_schluessel_umbuchen')));
    $FormTable .= table_row_builder(table_header_builder('An Wart umbuchen').table_data_builder(dropdown_menu_wart('an_wart_umbuchen', $_POST['an_wart_umbuchen'])));
    $FormTable .= table_row_builder(table_header_builder('An Ort umbuchen').table_data_builder(dropdown_schluesselorte('an_ort_umbuchen', $_POST['an_ort_umbuchen'])));
    $FormTable .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_umbuchen', 'Umbuchen', 'action', 'send', '')).table_data_builder(''));
    $FormTable = table_builder($FormTable);
    $FormTable = form_builder($FormTable, '#', 'post', '', '');
    $HTML = collapsible_item_builder('Schlüssel umbuchen', $FormTable, 'swap_calls');

    return $HTML;
}
function schluessel_bearbeiten_listenelement_generieren(){

    $FormHTML = table_row_builder(table_header_builder('Schlüssel auswählen').table_data_builder(dropdown_aktive_schluessel('id_schluessel_bearbeiten')));
    $FormHTML .= table_form_select_item('Schlüsselnummer ändern', 'schluessel_id', 1, 50, $_POST['schluessel_id'], '', 'Schlüsselnummer', '', false);
    $FormHTML .= table_form_string_item('Farbe', 'farbe_schluessel_bearbeiten', $_POST['farbe_schluessel_bearbeiten'], false);
    $FormHTML .= table_form_string_item('Farbe in materialize.css', 'farbe_schluessel_mat_bearbeiten', $_POST['farbe_schluessel_mat_bearbeiten'], false);
    $FormHTML .= table_form_string_item('RFID Code', 'rfid_code_bearbeiten', $_POST['rfid_code_bearbeiten'], false);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_bearbeiten', 'Eintragen', 'submit', 'send', '')." ".form_button_builder('action_schluessel_loeschen', 'Löschen', 'action', 'delete', '')).table_data_builder(''));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, '#', 'post', '', '');

    $HTML = collapsible_item_builder('Schlüssel bearbeiten', $FormHTML, 'edit');

    return $HTML;
}
function schluessel_hinzufuegen_listenelement_generieren(){

    if(isset($_POST['is_wartschluessel'])){
        $AnAusWart = 'on';
    } else {
        $AnAusWart = 'off';
    }

    $FormHTML = table_form_select_item('Schlüsselnummer', 'schluessel_id', 1, 50, $_POST['schluessel_id'], '', 'Schlüsselnummer', '', false);
    $FormHTML .= table_form_string_item('Farbe', 'farbe_schluessel', $_POST['farbe_schluessel'], false);
    $FormHTML .= table_form_string_item('Farbe in materialize.css', 'farbe_schluessel_mat', $_POST['farbe_schluessel_mat'], false);
    $FormHTML .= table_form_string_item('RFID Code', 'rfid_code', $_POST['rfid_code'], false);
    $FormHTML .= table_form_swich_item('Ist ein Wartschlüssel', 'is_wartschluessel', 'Nein', 'Ja', $AnAusWart, false);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_hinzufuegen', 'Eintragen', 'submit', 'send', '')).table_data_builder(''));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, '#', 'post', '', '');

    $HTML = collapsible_item_builder('Schlüssel anlegen', $FormHTML, 'library_add');

    return $HTML;
}

function parser_schluesselmanagement(){

    $Parser = spalte_anstehende_rueckgaben_parser();

    if($Parser['success'] == NULL){
        $Parser = spalte_dir_zugeteilte_schluessel_parser();
    }

    spalte_verfuegbare_schluessel_parser();

    if (isset($_POST['action_schluessel_hinzufuegen'])){
        $Parser = schluessel_hinzufuegen($_POST['schluessel_id'], $_POST['farbe_schluessel'], $_POST['farbe_schluessel_mat'], $_POST['rfid_code']);
    }

    if(isset($_POST['action_schluessel_umbuchen'])){
        $Parser = schluessel_umbuchen_listenelement_parser($_POST['id_schluessel_umbuchen'], $_POST['an_wart_umbuchen'], $_POST['an_ort_umbuchen']);
    }

    if (isset($_POST['action_schluessel_bearbeiten'])){
        $Parser = schluessel_bearbeiten($_POST['id_schluessel_bearbeiten'], $_POST['schluessel_id'], $_POST['farbe_schluessel_bearbeiten'], $_POST['farbe_schluessel_mat_bearbeiten'], $_POST['rfid_code_bearbeiten']);
    }

    if (isset($_POST['action_schluessel_loeschen'])){
        $Parser = schluessel_loeschen($_POST['id_schluessel_bearbeiten']);
    }

    return $Parser;
}
function spalte_verfuegbare_schluessel_parser(){

    $link = connect_db();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);
    $UserID = lade_user_id();

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_herausnehmen";

        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'], $UserID, '', $UserID);
            $Event = "Schl&uuml;ssel ".$Schluessel['id']." von ".$UserID." aus R&uuml;ckgabekasten genommen";
            add_protocol_entry($UserID, $Event, 'schluessel');
        }
    }

    return $Antwort;
}
function spalte_dir_zugeteilte_schluessel_parser(){

    $link = connect_db();
    $Antwort = array();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id FROM schluessel WHERE akt_user = '".lade_user_id()."' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_zuruecklegen";
        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'],'', 'rueckgabekasten', lade_user_id());
        }
    }

    return $Antwort;
}







?>