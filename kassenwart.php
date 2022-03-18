<?php
include_once "./ressources/ressourcen.php";

session_manager('ist_kasse');
$Header = "Vereinskasse - " . lade_db_einstellung('site_name');
$HTML = section_builder("<h1 class='center-align'>Vereinskasse</h1>");
if($_POST['year_global']!=''){
   $YearGlobal = $_POST['year_global'];
} else {
    $YearGlobal = date('Y');
}

#ParserStuff
$Parser = vereinskasse_parser($YearGlobal);
if(isset($Parser['meldung'])){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

if($Parser['ansicht']==null){
    #var_dump($_POST);
    $HTML .= uebersicht_section_vereinskasse($YearGlobal);
    $HTML .= kontos_section_vereinskasse($YearGlobal, $Parser);
    $HTML .= choose_views_vereinskasse();
} elseif ($Parser['ansicht']=='guv'){
    $HTML .= guv_rechnung_jahr($YearGlobal);
} elseif ($Parser['ansicht']=='konto_details'){
    $HTML .= konto_details($YearGlobal, $Parser['konto_id']);
} elseif ($Parser['ansicht']=='list_all_forderungen'){
    $HTML .= forderungen_section_vereinskasse($YearGlobal);
} elseif ($Parser['ansicht']=='list_all_ausgaben'){
    $HTML .= ausgaben_section_vereinskasse($YearGlobal);
} elseif ($Parser['ansicht']=='transactions'){
    $HTML .= add_transaktions_vereinskasse($YearGlobal);
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function vereinskasse_parser($YearGlobal){

    $Antwort = array();
    $Antwort['ansicht']=null;

    for($a=1;$a<=10000;$a++){
        if(isset($_POST['konto_details_'.$a.''])){
            $Antwort['konto_id']=$a;
            $Antwort['ansicht']='konto_details';
        }
        if(isset($_POST['einnahme_stornieren_'.$a])){
            $Result = einnahme_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['storno_einnahme_konto_details_'.$a])){
            $Result = einnahme_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='konto_details';
            $Antwort['konto_id']=$_POST['konto_id'];
        }
        if(isset($_POST['einnahme_storno_aufheben_'.$a])){
            $Result = undo_einnahme_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['storno_aufheben_einnahme_konto_details_'.$a])){
            $Result = undo_einnahme_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='konto_details';
            $Antwort['konto_id']=$_POST['konto_id'];
        }
        if(isset($_POST['delete_forderung_'.$a])){
            $Result = forderung_stornieren($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['undo_storno_forderung_'.$a])){
            $Result = undo_forderung_stornieren($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['delete_ausgleich_'.$a])){
            $Result = ausgleich_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
        if(isset($_POST['undo_storno_ausgleich_'.$a])){
            $Result = undo_ausgleich_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
        }

        if(isset($_POST['storno_transfer_konto_details_'.$a])){
            $Result = transfer_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='konto_details';
            $Antwort['konto_id']=$_POST['konto_id'];
        }
        if(isset($_POST['storno_aufheben_transfer_konto_details_'.$a])){
            $Result = undo_transfer_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='konto_details';
            $Antwort['konto_id']=$_POST['konto_id'];
        }

        if(isset($_POST['ausgabe_stornieren_'.$a])){
            $Result = ausgabe_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
        if(isset($_POST['storno_ausgabe_konto_details_'.$a])){
            $Result = ausgabe_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
            $Antwort['ansicht']='konto_details';
            $Antwort['konto_id']=$_POST['konto_id'];
        }
        if(isset($_POST['ausgabe_storno_aufheben_'.$a])){
            $Result = undo_ausgabe_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
        if(isset($_POST['storno_aufheben_ausgabe_konto_details_'.$a])){
            $Result = undo_ausgabe_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='konto_details';
            $Antwort['konto_id']=$_POST['konto_id'];
        }
        if(isset($_POST['einnahme_forderung_'.$a.'_festhalten'])){
            if(is_numeric($_POST['einnahme_forderung_'.$a.''])){
                if(($_POST['wartkonto_einnahme_forderung_'.$a.''] != '') AND ($_POST['neutralkonto_einnahme_forderung_'.$a.''] != '')){
                    $Antwort['success']=false;
                    $Antwort['meldung']='Du darfst nicht ein Wart- und ein Neutralkonto in einer Buchung gleichzeitig verwenden!';
                    $Antwort['ansicht']='transactions';
                } else {
                    if($_POST['wartkonto_einnahme_forderung_'.$a.''] != ''){
                        $KontoID = lade_konto_user($_POST['wartkonto_einnahme_forderung_'.$a.'']);
                        $KontoID = $KontoID['id'];
                    }
                    if($_POST['neutralkonto_einnahme_forderung_'.$a.''] != ''){
                        $KontoID = $_POST['neutralkonto_einnahme_forderung_'.$a.''];
                    }
                    $Forderung = lade_forderung($a);
                    $Result = einnahme_festhalten($a, $KontoID, $_POST['einnahme_forderung_' . $a . ''], $Forderung['steuersatz'], $_POST['einnahme_eintragen_chosen_date_'.$Forderung['id']]);
                    $Antwort['success']=$Result['success'];
                    $Antwort['meldung']=$Result['meldung'];
                    $Antwort['ansicht']='transactions';
                }
            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = 'Bitte gib einen validen Betrag ein!';
                $Antwort['ansicht']='transactions';
            }
        }
    }

    if(isset($_POST['action_add_konto'])){
        $Antwort = konto_anlegen($_POST['new_konto_name'], $_POST['new_konto_typ'], $_POST['new_konto_initial']);
        $Antwort['ansicht']=null;
    }

    if(isset($_POST['activate_guv'])){
        $Antwort['ansicht']='guv';
    }

    if(isset($_POST['activate_list_all_forderungen'])){
        $Antwort['ansicht']='list_all_forderungen';
    }

    if(isset($_POST['activate_transactions_view'])){
        $Antwort['ansicht']='transactions';
    }

    if(isset($_POST['activate_list_all_ausgaben'])){
        $Antwort['ansicht']='list_all_ausgaben';
    }
    if(isset($_POST['ausgleich_anlegen'])){
        $Result = ausgleich_hinzufuegen($_POST['neu_ausgleich_konto'], $_POST['neu_ausgleich_referenz'], $_POST['neu_ausgleich_betrag'], $_POST['neu_ausgleich_steuersatz'], $_POST['new_ausgleich_chosen_date']);
        $Antwort['success']=$Result;
        $Antwort['ansicht']='transactions';
    }
    if(isset($_POST['action_ausgabe_durchfuehren'])){

        if(($_POST['ausgabe_eintragen_wart'] != '') AND ($_POST['ausgabe_eintragen_neutralkonto'] != '')){
            $Antwort['success']=false;
            $Antwort['meldung']='Du darfst nicht ein Wart- und ein Neutralkonto in einer Buchung gleichzeitig verwenden!';
            $Antwort['ansicht']='transactions';
        } else {
            if($_POST['ausgabe_eintragen_wart'] != ''){
                $Konto = lade_konto_user($_POST['ausgabe_eintragen_wart']);
                $KontoID = $Konto['id'];
            }
            if($_POST['ausgabe_eintragen_neutralkonto'] != ''){
                $KontoID = $_POST['ausgabe_eintragen_neutralkonto'];
            }
            $Result = ausgabe_hinzufuegen($_POST['ausgabe_eintragen_betrag'], $_POST['ausgabe_eintragen_steuersatz'], $_POST['ausgabe_eintragen_ausgleich'], $KontoID, $_POST['ausgabe_eintragen_chosen_date']);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='transactions';
        }

    }

    if(isset($_POST['action_add_forderung'])){
        if(is_numeric($_POST['betrag_add_forderung'])){
            $Till = $_POST['datum_add_forderung'].' 00:00:01';
            $Result = forderung_generieren($_POST['betrag_add_forderung'], $_POST['steuer_add_forderung'], $_POST['user_add_forderung'], '', lade_zielkonto_einnahmen_forderungen_id(), '', $_POST['reason_add_forderung'], $Till, lade_user_id(), $_POST['add_forderung_chosen_date']);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='transactions';
        } else {
            $Antwort['success'] = false;
            $Antwort['meldung'] = 'Bitte gib einen validen Betrag ein!';
            $Antwort['ansicht']='transactions';
        }
    }

    if(isset($_POST['action_transfer'])){
        $Result = add_transfer($_POST['von_konto_transfer'], $_POST['nach_konto_transfer'], $_POST['betrag_transfer'], $_POST['transfer_chosen_date']);
        $Antwort['success']=$Result['success'];
        $Antwort['meldung']=$Result['meldung'];
        $Antwort['ansicht']='transactions';
    }

    if(isset($_POST['reset_view'])){
        $Antwort['ansicht']=null;
    }

    return $Antwort;
}
function guv_rechnung_jahr($YearGlobal){

    $link = connect_db();

    //Einnahmenkonten
    $Anfrage5 = "SELECT * FROM finanz_konten WHERE typ = 'einnahmenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage5 = mysqli_query($link, $Anfrage5);
    $Anzahl5 = mysqli_num_rows($Abfrage5);

    $Anfrage6 = "SELECT * FROM finanz_konten WHERE typ = 'ausgabenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage6 = mysqli_query($link, $Anfrage6);
    $Anzahl6 = mysqli_num_rows($Abfrage6);

    //Berechne Zahl nötiger Zeilen
    if($Anzahl5>$Anzahl6){
        $Runs = $Anzahl5;
    }elseif ($Anzahl5<$Anzahl6){
        $Runs = $Anzahl6;
    }else{
        $Runs = $Anzahl5;
    }

    $kontoItems = table_row_builder(table_header_builder('Ausgabenkonten').table_header_builder('Ausgaben').table_header_builder('Einnahmenkonten').table_header_builder('Einnahmen'));
    //Iterate over konten
    $AusgabenSumme=0.0;
    $EinnahmenSumme=0.0;
    for($a=1;$a<=$Runs;$a++){
        //Ausgabenkonto
        if($a<=$Anzahl6){
            $Ergebnis6 = mysqli_fetch_assoc($Abfrage6);
            $Ausgleiche = ausgleiche_konto($Ergebnis6['id'], $YearGlobal);
            $AusgabenKonto=0.0;
            foreach ($Ausgleiche as $Ausgleich){
                $AusgabenKonto = $AusgabenKonto + lade_gezahlte_betraege_ausgleich($Ausgleich['id']);
            }
            $AusgabenSumme = $AusgabenSumme + $AusgabenKonto;
            $ItemsAusgabenkonten = table_data_builder($Ergebnis6['name']).table_data_builder($AusgabenKonto.'&euro;');
        }else{
            $ItemsAusgabenkonten = table_data_builder('').table_data_builder('');
        }
        //Einnahmenkonto
        if($a<=$Anzahl5){
            $Ergebnis5 = mysqli_fetch_assoc($Abfrage5);
            $Forderungen = forderungen_konto($Ergebnis5['id'], $YearGlobal);
            $EinnahmenKonto=0.0;
            foreach ($Forderungen as $Forderung){
                $EinnahmenKonto = $EinnahmenKonto + lade_einnahmen_forderung($Forderung['id']);
            }
            $EinnahmenSumme = $EinnahmenSumme + $EinnahmenKonto;
            $ItemsEinnahmenkonten = table_data_builder($Ergebnis5['name']).table_data_builder($EinnahmenKonto.'&euro;');
        }else{
            $ItemsEinnahmenkonten = table_data_builder('').table_data_builder('');
        }
        $kontoItems .= table_row_builder($ItemsAusgabenkonten.$ItemsEinnahmenkonten);
    }
    $kontoItems .= table_row_builder(table_data_builder('').table_data_builder('Summe: '.$AusgabenSumme.'&euro;').table_data_builder('').table_data_builder('Summe: '.$EinnahmenSumme.'&euro;'));
    $Differenz = $EinnahmenSumme-$AusgabenSumme;

    $contentHTML = section_builder(table_builder($kontoItems));
    $contentHTML .= section_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back')).table_header_builder('Gewinn/Verlust: '.$Differenz.'&euro;'))));
    $contentHTML .= "<input type='hidden' name='year_global' value='".$_POST['year_global']."'>";

    $HTML = "<h3 class='center-align'>GUV-Rechnung ".$YearGlobal."</h3>";
    $HTML .= form_builder($contentHTML, '#', 'post');
    return $HTML;
}
function uebersicht_section_vereinskasse($YearGlobal){

    $Gesamteinnahmen = gesamteinnahmen_jahr($YearGlobal);
    $Gesamtausgaben = gesamtausgaben_jahr($YearGlobal);
    $Differenz = $Gesamteinnahmen - $Gesamtausgaben;
    if (floatval($Differenz) >= 0){
        $StyleGUV = "class=\"green lighten-2\"";
    } else {
        $StyleGUV = "class=\"red lighten-1\"";
    }

    $HTML = "<h3 class='center-align'>Jahresstatistik ".$YearGlobal."</h3>";
    $Table = table_row_builder(table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss').table_header_builder(form_select_item('year_global', 2017, date('Y'), $_POST['year_global'], '', 'Betrachtungsjahr', '')));
    $Table .= table_row_builder(table_data_builder($Gesamteinnahmen."&euro;").table_data_builder($Gesamtausgaben."&euro;").table_data_builder("<p ".$StyleGUV.">".$Differenz."&euro;</p>").table_data_builder(form_button_builder('change_betrachtungsjahr', 'wechseln', 'action', 'send')));
    $HTML .= form_builder(table_builder($Table), '#', 'post', 'jahresstats');

    return section_builder($HTML);
}
function kontos_section_vereinskasse($YearGlobal, $Parser){

    $BigItems = '';
    $link = connect_db();

    //Einnahmenkonten
    $Anfrage5 = "SELECT * FROM finanz_konten WHERE typ = 'einnahmenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage5 = mysqli_query($link, $Anfrage5);
    $Anzahl5 = mysqli_num_rows($Abfrage5);
    $EinnahmenkontoCounter = 0;
    $EinnahmenkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Forderungen').table_header_builder('Einnahmen').table_header_builder('Differenz').table_header_builder('Aktionen'));
    for ($e = 1; $e <= $Anzahl5;$e++) {
        $Ergebnis5 = mysqli_fetch_assoc($Abfrage5);
        $Forderungen = forderungen_konto($Ergebnis5['id'], $YearGlobal, true);
        $ForderungenSumme = 0;
        $EinnahmenSumme = 0;
        foreach ($Forderungen as $Forderung){
            if($Forderung['storno_user']==0){
                $ForderungenSumme = $ForderungenSumme + $Forderung['betrag'];
            }
            $Einnahme = lade_einnahmen_forderung($Forderung['id']);
            $EinnahmenSumme = $EinnahmenSumme + $Einnahme;
        }
        $Differenz = $EinnahmenSumme - $ForderungenSumme;
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }
        $Buttons = form_button_builder('konto_details_'.$Ergebnis5['id'].'', 'Details', 'action', 'search');
        $EinnahmenkontoItems .= table_row_builder(table_data_builder($Ergebnis5['name']).table_data_builder($ForderungenSumme.'&euro;').table_data_builder($EinnahmenSumme.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
        $EinnahmenkontoCounter++;
    }
    if ($EinnahmenkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Einnahmenkonten', table_builder($EinnahmenkontoItems), 'attach_money');
    } else{
        $BigItems .= collapsible_item_builder('Einnahmenkonten', 'Bislang keine Einnahmenkonten angelegt!', 'attach_money');
    }

    //Ausgabenkonten
    $Anfrage6 = "SELECT * FROM finanz_konten WHERE typ = 'ausgabenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage6 = mysqli_query($link, $Anfrage6);
    $Anzahl6 = mysqli_num_rows($Abfrage6);
    $AusgabenkontoCounter = 0;
    $AusgabenkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Geplant').table_header_builder('Ausgegeben').table_header_builder('Differenz').table_header_builder('Aktionen'));
    for ($f = 1; $f <= $Anzahl6;$f++) {
        $Ergebnis6 = mysqli_fetch_assoc($Abfrage6);
        $Ausgleiche = ausgleiche_konto($Ergebnis6['id'], $YearGlobal);
        $AUSgleichSumme = 0.0;
        $AusgabeSumme = 0.0;
        foreach ($Ausgleiche as $Ausgleich){
            $AUSgleichSumme = $AUSgleichSumme + $Ausgleich['betrag'];
            $Ausgabe = lade_ausgaben_ausgleich($Ausgleich['id']);
            $AusgabeSumme = $AusgabeSumme + $Ausgabe;
        }
        $Differenz = $AUSgleichSumme - $AusgabeSumme;
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }
        $Buttons = form_button_builder('konto_details_'.$Ergebnis6['id'].'', 'Details', 'action', 'search');
        $AusgabenkontoItems .= table_row_builder(table_data_builder($Ergebnis6['name']).table_data_builder($AUSgleichSumme.'&euro;').table_data_builder($AusgabeSumme.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
        $AusgabenkontoCounter++;
    }
    if ($AusgabenkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Ausgabenkonten', table_builder($AusgabenkontoItems), 'money_off');
    } else{
        $BigItems .= collapsible_item_builder('Ausgabenkonten', 'Bislang keine Ausgabenkonten angelegt!', 'money_off');
    }

    //Neutralkonten
    $Anfrage7 = "SELECT * FROM finanz_konten WHERE typ = 'neutralkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage7 = mysqli_query($link, $Anfrage7);
    $Anzahl7 = mysqli_num_rows($Abfrage7);
    $NeutralkontoCounter = 0;
    $NeutralkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Aktueller Kontostand').table_header_builder('Aktionen'));
    for ($g = 1; $g <= $Anzahl7;$g++) {
        $Ergebnis7 = mysqli_fetch_assoc($Abfrage7);
        $Buttons = form_button_builder('konto_details_'.$Ergebnis7['id'].'', 'Details', 'action', 'search');
        $NeutralkontoItems .= table_row_builder(table_data_builder($Ergebnis7['name']).table_data_builder(round($Ergebnis7['wert_aktuell'],2).'&euro;').table_data_builder($Buttons));
        $NeutralkontoCounter++;
    }
    if ($NeutralkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Neutralkonten', table_builder($NeutralkontoItems), 'iso');
    } else{
        $BigItems .= collapsible_item_builder('Neutralkonten', 'Bislang keine Neutralkonten angelegt!', 'iso');
    }

    //Wartkonten
    $Users = get_sorted_user_array_with_user_meta_fields('nachname');
    $WartkontoCounter = 0;
    $WartkontoItems = table_row_builder(table_header_builder('Wart*in').table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss').table_header_builder('Aktionen'));
    foreach ($Users as $User){
        if ($User['ist_wart'] == 'true') {
            $Konto = lade_konto_user($User['id']);
            $Einnahmen = gesamteinnahmen_jahr_konto($YearGlobal,$Konto['id']);
            $Ausgaben = gesamtausgaben_jahr_konto($YearGlobal,$Konto['id']);
            $Differenz = $Einnahmen-$Ausgaben;
            if (floatval($Differenz) >= 0){
                $StyleGUV = "class=\"green lighten-2\"";
            } else {
                $StyleGUV = "class=\"red lighten-1\"";
            }
            if($Parser['highlight_user']==$User['id']){
                $Highlight = 'class="blue lighten-2"';
            } else {
                $Highlight = '';
            }
            $Buttons = form_button_builder('konto_details_'.$Konto['id'].'', 'Details', 'action', 'search');
            #$AktionLinks = form_button_builder('highlight_user_actions_'.$User['id'].'', 'hervorheben', 'action', 'highlight');
            $WartkontoItems .= table_row_builder(table_data_builder('<p '.$Highlight.'>'.$User['vorname'].'&nbsp;'.$User['nachname'].'</p>').table_data_builder($Einnahmen.'&euro;').table_data_builder($Ausgaben.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
            $WartkontoCounter++;
        }
    }
    if ($WartkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Wartkonten', table_builder($WartkontoItems), 'android');
    } else{
        $BigItems .= collapsible_item_builder('Wartkonten', 'Bislang keine Wartkonten angelegt!', 'android');
    }

    $BigItems .= konto_anlegen_formular();
    $BigItems .= "<input type='hidden' name='year_global' value='".$_POST['year_global']."'>";

    $HTML = '<h3 class="center-align">Konten</h3>';
    $HTML .= form_builder(collapsible_builder($BigItems), '#', 'post', 'konten_form');

    return section_builder($HTML);
}
function forderungen_section_vereinskasse($YearGlobal){

    $Forderungen = lade_alle_forderungen_jahr($YearGlobal);

    $TableResForderungen = table_row_builder(table_header_builder('#').table_header_builder('Res.-Infos').table_header_builder('User').table_header_builder('Betrag').table_header_builder('Einnahme').table_header_builder('Betrag').table_header_builder('Empfänger*in').table_header_builder('Differenz').table_header_builder('Aktionen'));
    $TableAndereForderungen = table_row_builder(table_header_builder('#').table_header_builder('Referenz').table_header_builder('User').table_header_builder('Betrag').table_header_builder('Aktionen').table_header_builder('Einnahme').table_header_builder('Betrag').table_header_builder('Empfänger*in').table_header_builder('Differenz').table_header_builder('Aktionen'));

    foreach ($Forderungen as $Forderung){
        $UserMeta = lade_user_meta($Forderung['von_user']);

        //Parse Einnahmen
        $Einnahmen = lade_einnahmen_forderung($Forderung['id'],true);
        $EinnahmeDatum = '';
        $EinnahmeBetrag = '';
        $EinnahmeWart = '';
        $EinnahmeSumme = 0.0;
        $EinnahmeAktions = '';
        foreach ($Einnahmen as $Einnahme){
            if($Einnahme['storno_user']>0){
                $sBegin="<s>";
                $sEnd="</s>";
                $EinnahmeAktions .= form_button_builder('einnahme_storno_aufheben_'.$Einnahme['id'].'', 'Aufheben', 'action', '')."<br>";
            }else{
                $sBegin="";
                $sEnd="";
                $EinnahmeSumme = $EinnahmeSumme + $Einnahme['betrag'];
                $EinnahmeAktions .= form_button_builder('einnahme_stornieren_'.$Einnahme['id'].'', 'Storno', 'action', '')."<br>";
            }
            $KontoEinnahme = lade_konto_via_id($Einnahme['konto_id']);
            if($KontoEinnahme['typ']=='wartkonto'){
                $WartMeta = lade_user_meta($KontoEinnahme['name']);
                $EinnahmeWart .= $sBegin.$WartMeta['vorname'].'&nbsp;'.$WartMeta['nachname'].$sEnd."<br>";
            }elseif ($KontoEinnahme['typ']=='neutralkonto'){
                $EinnahmeWart .= $sBegin.$KontoEinnahme['name'].$sEnd."<br>";
            }
            $EinnahmeDatum .= $sBegin.date("d.m.Y", strtotime($Einnahme['timestamp'])).$sEnd."<br>";
            $EinnahmeBetrag .= $sBegin.$Einnahme['betrag'].'&euro;'.$sEnd."<br>";
        }
        if(sizeof($Einnahmen)==0){
            $EinnahmeDatum = '-';
            $EinnahmeBetrag = '-';
            $EinnahmeWart = '-';
            $EinnahmeAktions = '-';
        }
        if(sizeof($Einnahmen)>1){
            $EinnahmeBetrag .= "----<br>".$EinnahmeSumme.'&euro;';
            $EinnahmeDatum .= "<br><br>";
            $EinnahmeWart .= "<br><br>";
        }

        if($Forderung['storno_user']==0){
            $Differenz = $EinnahmeSumme - $Forderung['betrag'];
            if (floatval($Differenz) >= 0){
                $StyleGUV = "class=\"green lighten-2\"";
            } else {
                $StyleGUV = "class=\"red lighten-1\"";
            }
        } else {
            $Differenz = 0;
            $StyleGUV = '';
        }


        if($Forderung['referenz_res']>0){       //Forderung betrifft ne reservierung
            if($Forderung['storno_user']>0) {
                $TableResForderungen .= table_row_builder(table_data_builder('<s>'.$Forderung['id'].'</s>') . table_data_builder('<s>'.$Forderung['referenz_res'].'</s>') . table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>') . table_data_builder('<s>'.$Forderung['betrag'].'&euro;</s>') . table_data_builder($EinnahmeDatum) . table_data_builder($EinnahmeBetrag) . table_data_builder($EinnahmeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($EinnahmeAktions));
            }else{
                if($Forderung['betrag']>0){
                    if(lade_xml_einstellung('rechnungsfunktion_global')=='on'){
                        $EinnahmeAktions .= "<br>".button_link_creator('Rechnung', './rechnung_anzeigen.php?forderung_id='.$Forderung['id'], 'payment', '');
                    }
                }
                $TableResForderungen .= table_row_builder(table_data_builder($Forderung['id']) . table_data_builder($Forderung['referenz_res']) . table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']) . table_data_builder($Forderung['betrag'].'&euro;') . table_data_builder($EinnahmeDatum) . table_data_builder($EinnahmeBetrag) . table_data_builder($EinnahmeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($EinnahmeAktions));
            }
        } else {                                //Forderung betrifft was anderes
            if($Forderung['storno_user']>0) {
                $AktionButtonForderung = form_button_builder('undo_storno_forderung_'.$Forderung['id'].'', 'Reaktivieren', 'action', '');
                $TableAndereForderungen .= table_row_builder(table_data_builder('<s>'.$Forderung['id'].'</s>').table_data_builder('<s>'.$Forderung['referenz'].'</s>').table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>').table_data_builder('<s>'.$Forderung['betrag'].'&euro;</s>').table_data_builder($AktionButtonForderung).table_data_builder($EinnahmeDatum).table_data_builder($EinnahmeBetrag).table_data_builder($EinnahmeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($EinnahmeAktions));
            } else {
                $AktionButtonForderung = form_button_builder('delete_forderung_'.$Forderung['id'].'', 'Stornieren', 'action', '')."<br>".button_link_creator('Rechnung', './rechnung_anzeigen.php?forderung_id='.$Forderung['id'], 'payment', '');
                $TableAndereForderungen .= table_row_builder(table_data_builder($Forderung['id']).table_data_builder($Forderung['referenz']).table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']).table_data_builder($Forderung['betrag'].'&euro;').table_data_builder($AktionButtonForderung).table_data_builder($EinnahmeDatum).table_data_builder($EinnahmeBetrag).table_data_builder($EinnahmeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($EinnahmeAktions));
            }
        }
    }



    $ResFordContent = table_builder($TableResForderungen);
    $AndFordContent = table_builder($TableAndereForderungen);

    $Items = collapsible_item_builder('Forderungen aus Reservierungen', $ResFordContent, 'today');
    $Items .= collapsible_item_builder('Andere Forderungen', $AndFordContent, 'toll');

    $HTML = '<h3 class="center-align">Alle Forderungen '.$YearGlobal.'</h3>';
    $HTML .= section_builder("<input type='hidden' name='year_global' value='".$YearGlobal."'>");
    $HTML .= section_builder(collapsible_builder($Items));
    $HTML .= section_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back'));

    return form_builder($HTML, '#', 'post', 'forderungen_section_form');
}
function ausgaben_section_vereinskasse($YearGlobal){
    $Ausgleiche = lade_alle_ausgleiche_jahr($YearGlobal);

    $TableResAusgleiche = table_row_builder(table_header_builder('#').table_header_builder('Res.-Infos').table_header_builder('User').table_header_builder('Betrag').table_header_builder('Ausgabe').table_header_builder('Betrag').table_header_builder('Zahler*in').table_header_builder('Differenz').table_header_builder('Aktionen'));
    $TableAndereAusgleiche = table_row_builder(table_header_builder('#').table_header_builder('Konto').table_header_builder('Referenz').table_header_builder('Betrag').table_header_builder('Aktionen').table_header_builder('Ausgabe').table_header_builder('Betrag').table_header_builder('Zahler*in').table_header_builder('Differenz').table_header_builder('Aktionen'));

    foreach ($Ausgleiche as $Ausgleich){
        $UserMeta = lade_user_meta($Ausgleich['fuer_user']);

        //Parse Einnahmen
        $Ausgaben = lade_ausgaben_ausgleich($Ausgleich['id'],true);
        $AusgabeDatum = '';
        $AusgabeBetrag = '';
        $AusgabeWart = '';
        $AusgabeSumme = 0.0;
        $AusgabeAktions = '';
        foreach ($Ausgaben as $Ausgabe){
            if($Ausgabe['storno_user']>0){
                $sBegin="<s>";
                $sEnd="</s>";
                $AusgabeAktions .= form_button_builder('ausgabe_storno_aufheben_'.$Ausgabe['id'].'', 'Aufheben', 'action', '')."<br>";
            }else{
                $sBegin="";
                $sEnd="";
                $AusgabeSumme = $AusgabeSumme + $Ausgabe['betrag'];
                $AusgabeAktions .= form_button_builder('ausgabe_stornieren_'.$Ausgabe['id'].'', 'Storno', 'action', '')."<br>";
            }
            $KontoAusgabe = lade_konto_via_id($Ausgabe['konto_id']);
            if($KontoAusgabe['typ']=='wartkonto'){
                $WartMeta = lade_user_meta($KontoAusgabe['name']);
                $AusgabeWart .= $sBegin.$WartMeta['vorname'].'&nbsp;'.$WartMeta['nachname'].$sEnd."<br>";
            }elseif ($KontoAusgabe['typ']=='neutralkonto'){
                $AusgabeWart .= $sBegin.$KontoAusgabe['name'].$sEnd."<br>";
            }
            $AusgabeDatum .= $sBegin.date("d.m.Y", strtotime($Ausgabe['timestamp'])).$sEnd."<br>";
            $AusgabeBetrag .= $sBegin.$Ausgabe['betrag'].'&euro;'.$sEnd."<br>";
        }
        if(sizeof($Ausgaben)==0){
            $AusgabeDatum = '-';
            $AusgabeBetrag = '-';
            $AusgabeWart = '-';
            $AusgabeAktions = '-';
        }
        if(sizeof($Ausgaben)>1){
            $AusgabeBetrag .= "----<br>".$AusgabeSumme.'&euro;';
            $AusgabeDatum .= "<br><br>";
            $AusgabeWart .= "<br><br>";
        }

        if($Ausgleich['storno_user']>0){
            $Ausgleichbetrag = 0;
        } else {
            $Ausgleichbetrag = $Ausgleich['betrag'];
        }

        if($Ausgabe['storno_user']==0){
            $Differenz = $AusgabeSumme - $Ausgleichbetrag;
            if (floatval($Differenz) == 0){
                $StyleGUV = "class=\"green lighten-2\"";
            } else {
                $StyleGUV = "class=\"red lighten-1\"";
            }
        } else {
            $Differenz = 0;
            $StyleGUV = '';
        }

        if($Ausgleich['referenz_res']>0){       //Ausgleich betrifft ne reservierung
            if($Ausgleich['storno_user']>0) {
                $TableResAusgleiche .= table_row_builder(table_data_builder('<s>'.$Ausgleich['id'].'</s>') . table_data_builder('<s>'.$Ausgleich['referenz_res'].'</s>') . table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>') . table_data_builder('<s>'.$Ausgleich['betrag'].'&euro;</s>') . table_data_builder($AusgabeDatum) . table_data_builder($AusgabeBetrag) . table_data_builder($AusgabeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($AusgabeAktions));
            }else{
                $TableResAusgleiche .= table_row_builder(table_data_builder($Ausgleich['id']) . table_data_builder($Ausgleich['referenz_res']) . table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']) . table_data_builder($Ausgleich['betrag'].'&euro;') . table_data_builder($AusgabeDatum) . table_data_builder($AusgabeBetrag) . table_data_builder($AusgabeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($AusgabeAktions));
            }
        } else {                                //Ausgleich betrifft was anderes
            $KontoAusgleich = lade_konto_via_id($Ausgleich['von_konto']);
            if($Ausgleich['storno_user']>0) {
                $AktionButtonForderung = form_button_builder('undo_storno_ausgleich_'.$Ausgleich['id'].'', 'Reaktivieren', 'action', '');
                $TableAndereAusgleiche .= table_row_builder(table_data_builder('<s>'.$Ausgleich['id'].'</s>').table_data_builder('<s>'.$KontoAusgleich['name'].'</s>').table_data_builder('<s>'.$Ausgleich['referenz'].'</s>').table_data_builder('<s>'.$Ausgleich['betrag'].'&euro;</s>').table_data_builder($AktionButtonForderung).table_data_builder($AusgabeDatum).table_data_builder($AusgabeBetrag).table_data_builder($AusgabeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($AusgabeAktions));
            } else {
                $AktionButtonForderung = form_button_builder('delete_ausgleich_'.$Ausgleich['id'].'', 'Storno', 'action', '');
                $TableAndereAusgleiche .= table_row_builder(table_data_builder($Ausgleich['id']).table_data_builder($KontoAusgleich['name']).table_data_builder($Ausgleich['referenz']).table_data_builder($Ausgleich['betrag'].'&euro;').table_data_builder($AktionButtonForderung).table_data_builder($AusgabeDatum).table_data_builder($AusgabeBetrag).table_data_builder($AusgabeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($AusgabeAktions));
            }
        }
    }



    $ResFordContent = table_builder($TableResAusgleiche);
    $AndFordContent = table_builder($TableAndereAusgleiche);

    $Items = collapsible_item_builder('Auszahlungen zu Reservierungen', $ResFordContent, 'today');
    $Items .= collapsible_item_builder('Andere Ausgaben', $AndFordContent, 'toll');

    $HTML = '<h3 class="center-align">Alle Ausgaben '.$YearGlobal.'</h3>';
    $HTML .= section_builder("<input type='hidden' name='year_global' value='".$YearGlobal."'>");
    $HTML .= section_builder(collapsible_builder($Items));
    $HTML .= section_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back'));

    return form_builder($HTML, '#', 'post', 'ausgleiche_section_form');
}
function konto_details($YearGlobal, $Konto){

    $AnfangJahr = "".$YearGlobal."-01-01 00:00:01";
    $EndeJahr = "".$YearGlobal."-12-31 23:59:59";
    $KontoInfos = lade_konto_via_id($Konto);
    $link = connect_db();

    if(intval($KontoInfos['name'])>0){
        $UserKonto = lade_user_meta($KontoInfos['name']);
        $Kontoangabe = 'Wartkonto '.$UserKonto['vorname'].'&nbsp;'.$UserKonto['nachname'];
    } else {
        $Kontoangabe = $KontoInfos['name'];
    }
    $FormHTML = "<h3 class='center-align'>Kontodetails ".$Kontoangabe." ".$YearGlobal."</h3>";

    if(($KontoInfos['typ']=='wartkonto')OR($KontoInfos['typ']=='neutralkonto')){
        $AnfrageAusgaben = "SELECT * FROM finanz_ausgaben WHERE konto_id = ".$Konto." AND timestamp >= '".$AnfangJahr."' AND timestamp <= '".$EndeJahr."'";
        $AnfrageEinnahmen = "SELECT * FROM finanz_einnahmen WHERE konto_id = ".$Konto." AND timestamp >= '".$AnfangJahr."' AND timestamp <= '".$EndeJahr."'";
        $AnfrageTransfers = "SELECT * FROM finanz_transfer WHERE ((von = ".$Konto.") OR (nach = ".$Konto.")) AND timestamp >= '".$AnfangJahr."' AND timestamp <= '".$EndeJahr."'";

        $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
        $AbfrageEinnahmen = mysqli_query($link, $AnfrageEinnahmen);
        $AbfrageTransfers = mysqli_query($link, $AnfrageTransfers);

        $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);
        $AnzahlEinnahmen = mysqli_num_rows($AbfrageEinnahmen);
        $AnzahlTransfers = mysqli_num_rows($AbfrageTransfers);

        $MinusItems = array();
        $PlusItems = array();

        for($a=1;$a<=$AnzahlAusgaben;$a++){
            $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
            $NeuerEintrag = array('id'=>$ErgebnisAusgaben['id'], 'timestamp'=>$ErgebnisAusgaben['timestamp'], 'ausgleich_id'=>$ErgebnisAusgaben['ausgleich_id'], 'betrag'=>$ErgebnisAusgaben['betrag'], 'storno_user'=>$ErgebnisAusgaben['storno_user'], 'typ'=>'ausgabe');
            array_push($MinusItems, $NeuerEintrag);
        }
        for($b=1;$b<=$AnzahlEinnahmen;$b++){
            $ErgebnisEinnahmen = mysqli_fetch_assoc($AbfrageEinnahmen);
            $NeuerEintrag = array('id'=>$ErgebnisEinnahmen['id'], 'timestamp'=>$ErgebnisEinnahmen['timestamp'], 'forderung_id'=>$ErgebnisEinnahmen['forderung_id'], 'betrag'=>$ErgebnisEinnahmen['betrag'], 'storno_user'=>$ErgebnisEinnahmen['storno_user'], 'typ'=>'einnahme');
            array_push($PlusItems, $NeuerEintrag);
        }
        for($c=1;$c<=$AnzahlTransfers;$c++){
            $ErgebnisTransfers = mysqli_fetch_assoc($AbfrageTransfers);
            if($ErgebnisTransfers['von']==$Konto){
                $NeuerEintrag = array('id'=>$ErgebnisTransfers['id'], 'timestamp'=>$ErgebnisTransfers['timestamp'], 'other_konto'=>$ErgebnisTransfers['nach'], 'betrag'=>$ErgebnisTransfers['betrag'], 'storno_user'=>$ErgebnisTransfers['storno_user'], 'typ'=>'transfer');
                array_push($MinusItems, $NeuerEintrag);
            } else {
                $NeuerEintrag = array('id'=>$ErgebnisTransfers['id'], 'timestamp'=>$ErgebnisTransfers['timestamp'], 'other_konto'=>$ErgebnisTransfers['von'], 'betrag'=>$ErgebnisTransfers['betrag'], 'storno_user'=>$ErgebnisTransfers['storno_user'], 'typ'=>'transfer');
                array_push($PlusItems, $NeuerEintrag);
            }
        }

        $MinusEintraege = sizeof($MinusItems);
        $PlusEintraege = sizeof($PlusItems);

        asort($MinusItems);
        asort($PlusItems);

        if($MinusEintraege>$PlusEintraege){
            $MaxRuns=$MinusEintraege;
        }else{
            $MaxRuns=$PlusEintraege;
        }

        $TableRows = table_row_builder('<th class="center-align" colspan="5">Ausgaben</th><th class="center-align" colspan="5">Einnahmen</th>');
        $TableRows .= table_row_builder(table_header_builder('Datum').table_header_builder('Ausgabe').table_header_builder('Für').table_header_builder('Betrag').table_header_builder('Aktionen').table_header_builder('Datum').table_header_builder('Forderung').table_header_builder('Von').table_header_builder('Betrag').table_header_builder('Aktionen'));

        for($a=0;$a<$MaxRuns;$a++){
            if(($a+1)>$MinusEintraege){
                $Ausgaben = table_data_builder('').table_data_builder('').table_data_builder('').table_data_builder('').table_data_builder('');
            } else {
                $Ausgabe = $MinusItems[$a];

                if($Ausgabe['typ']=='ausgabe'){
                    $Ausgleich = lade_ausgleich($Ausgabe['ausgleich_id']);
                    if($Ausgleich['referenz_res']>0){
                        $ForderungVonUser = lade_user_meta($Ausgleich['fuer_user']);
                        $FuerText = $ForderungVonUser['vorname'].' '.$ForderungVonUser['nachname'];
                        $ForderungText = 'Res #'.$Ausgleich['referenz_res'];
                    } else {
                        $AusgabeKonto = lade_konto_via_id($Ausgleich['von_konto']);
                        $FuerText = $AusgabeKonto['name'];
                        $ForderungText = $Ausgleich['referenz'];
                    }
                    if($Ausgabe['storno_user']>0){
                        $HTMLStornoCommandBegin = "<s>";
                        $HTMLStornoCommandEnd = "</s>";
                        $Button = form_button_builder('storno_aufheben_ausgabe_konto_details_'.$Ausgabe['id'],'', 'action', 'undo');
                    } else {
                        $HTMLStornoCommandBegin = "";
                        $HTMLStornoCommandEnd = "";
                        $Button = form_button_builder('storno_ausgabe_konto_details_'.$Ausgabe['id'],'', 'action', 'delete_forever');
                    }
                    $Ausgaben = table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Ausgabe['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$FuerText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Ausgabe['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($Button);
                }elseif ($Ausgabe['typ']=='transfer'){
                    $VonKonto = lade_konto_via_id($Ausgabe['other_konto']);
                    if(intval($VonKonto['name'])>0){
                        $TransferVonUser = lade_user_meta($VonKonto['name']);
                        $VonInfos = $TransferVonUser['vorname'].' '.$TransferVonUser['nachname'];
                    } else {
                        $VonInfos = $VonKonto['name'];
                    }
                    if($Ausgabe['storno_user']>0){
                        $HTMLStornoCommandBegin = "<s>";
                        $HTMLStornoCommandEnd = "</s>";
                        $Button = form_button_builder('storno_aufheben_transfer_konto_details_'.$Ausgabe['id'],'', 'action', 'undo');
                    } else {
                        $HTMLStornoCommandBegin = "";
                        $HTMLStornoCommandEnd = "";
                        $Button = form_button_builder('storno_transfer_konto_details_'.$Ausgabe['id'],'', 'action', 'delete_forever');
                    }
                    $Ausgaben = table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Ausgabe['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.'Umbuchung'.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$VonInfos.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Ausgabe['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($Button);
                }
            }

            if(($a+1)>$PlusEintraege){
                $Einnahmen = table_data_builder('').table_data_builder('').table_data_builder('').table_data_builder('').table_data_builder('');
            } else {
                $Einnahme = $PlusItems[$a];

                if($Einnahme['typ']=='einnahme'){
                    $Forderung = lade_forderung($Einnahme['forderung_id']);
                    $ForderungVonUser = lade_user_meta($Forderung['von_user']);
                    if($Forderung['referenz_res']>0){
                        $ForderungText = 'Res #'.$Forderung['referenz_res'];
                    } else {
                        $ForderungText = $Forderung['referenz'];
                    }
                    if($Einnahme['storno_user']>0){
                        $HTMLStornoCommandBegin = "<s>";
                        $HTMLStornoCommandEnd = "</s>";
                        $Button = form_button_builder('storno_aufheben_einnahme_konto_details_'.$Einnahme['id'],'', 'action', 'undo');
                    } else {
                        $HTMLStornoCommandBegin = "";
                        $HTMLStornoCommandEnd = "";
                        $Button = form_button_builder('storno_einnahme_konto_details_'.$Einnahme['id'],'', 'action', 'delete_forever');
                    }
                    $Einnahmen = table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Einnahme['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungVonUser['vorname'].' '.$ForderungVonUser['nachname'].$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Einnahme['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($Button);
                }elseif ($Einnahme['typ']=='transfer'){
                    $VonKonto = lade_konto_via_id($Einnahme['other_konto']);
                    if(intval($VonKonto['name'])>0){
                        $TransferVonUser = lade_user_meta($VonKonto['name']);
                        $VonInfos = $TransferVonUser['vorname'].' '.$TransferVonUser['nachname'];
                    } else {
                        $VonInfos = $VonKonto['name'];
                    }
                    if($Einnahme['storno_user']>0){
                        $HTMLStornoCommandBegin = "<s>";
                        $HTMLStornoCommandEnd = "</s>";
                        $Button = form_button_builder('storno_aufheben_transfer_konto_details_'.$Einnahme['id'],'', 'action', 'undo');
                    } else {
                        $HTMLStornoCommandBegin = "";
                        $HTMLStornoCommandEnd = "";
                        $Button = form_button_builder('storno_transfer_konto_details_'.$Einnahme['id'],'', 'action', 'delete_forever');
                    }
                    $Einnahmen = table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Einnahme['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.'Umbuchung'.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$VonInfos.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Einnahme['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($Button);
                }
            }

            $TableRows .= table_row_builder($Ausgaben.$Einnahmen);
        }
        $FormHTML .= "<h5 class='center-align'>Aktueller Kontostand: ".$KontoInfos['wert_aktuell']."&euro;</h5>";
    } elseif ($KontoInfos['typ']=='einnahmenkonto'){
        $AnfrageAusgaben = "SELECT * FROM finanz_forderungen WHERE zielkonto = ".$Konto." AND timestamp >= '".$AnfangJahr."' AND timestamp <= '".$EndeJahr."'";
        $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
        $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);
        $MinusItems = array();
        for($a=1;$a<=$AnzahlAusgaben;$a++){
            $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
            $NeuerEintrag = array('timestamp'=>$ErgebnisAusgaben['timestamp'], 'id'=>$ErgebnisAusgaben['id'], 'betrag'=>$ErgebnisAusgaben['betrag'], 'storno_user'=>$ErgebnisAusgaben['storno_user'], 'referenz'=>$ErgebnisAusgaben['referenz'], 'referenz_res'=>$ErgebnisAusgaben['referenz_res']);
            array_push($MinusItems, $NeuerEintrag);
        }
        asort($MinusItems);

        $TableRows = table_row_builder('<th class="center-align" colspan="3">Forderungen</th><th class="center-align" colspan="2">Einnahmen</th>');
        $TableRows .= table_row_builder(table_header_builder('Datum').table_header_builder('Referenz').table_header_builder('Betrag').table_header_builder('Erfolgte Einnahmen').table_header_builder('Differenz zu Forderung')); #.table_header_builder('Aktionen')

        foreach ($MinusItems as $Ausgleich){

            if($Ausgleich['referenz_res']>0){
                $ForderungVonUser = lade_user_meta($Ausgleich['fuer_user']);
                $FuerText = $ForderungVonUser['vorname'].' '.$ForderungVonUser['nachname'];
                $ForderungText = 'Res #'.$Ausgleich['referenz_res'];
            } else {
                $AusgabeKonto = lade_konto_via_id($Ausgleich['fuer_konto']);
                $FuerText = $AusgabeKonto['name'];
                $ForderungText = $Ausgleich['referenz'];
            }

            $AnfrageAusgaben = "SELECT * FROM finanz_einnahmen WHERE forderung_id = ".$Ausgleich['id'];
            $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
            $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);

            $AuszahlungenText = "";
            $EinnahmenCount = 0;
            for($z=1;$z<=$AnzahlAusgaben;$z++){
                $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
                $AusgabeKonto = lade_konto_via_id($ErgebnisAusgaben['konto_id']);
                if(intval($AusgabeKonto['name'])>0){
                    $ForderungVonUser = lade_user_meta($AusgabeKonto['name']);
                    $FuerText = $ForderungVonUser['vorname'].' '.$ForderungVonUser['nachname'];
                } else {
                    $FuerText = $AusgabeKonto['name'];
                }

                if($ErgebnisAusgaben['storno_user']>0) {
                    $HTMLStornoCommandBegin = "<s>";
                    $HTMLStornoCommandEnd = "</s>";
                } else {
                    $HTMLStornoCommandBegin = "";
                    $HTMLStornoCommandEnd = "";
                    $EinnahmenCount = $EinnahmenCount + $ErgebnisAusgaben['betrag'];
                }
                $AuszahlungenText .= $HTMLStornoCommandBegin.date('d.m.y', strtotime($ErgebnisAusgaben['timestamp'])).": ".$FuerText.": ".$ErgebnisAusgaben['betrag']."&euro;".$HTMLStornoCommandEnd."<br>";
            }

            if($Ausgleich['storno_user']>0){
                $HTMLStornoCommandBegin = "<s>";
                $HTMLStornoCommandEnd = "</s>";
                $Button = form_button_builder('storno_aufheben_ausgabe_konto_details_'.$Ausgleich['id'],'', 'action', 'undo');
                if($EinnahmenCount>0){
                    $TableRows .= table_row_builder(table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Ausgleich['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Ausgleich['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$AuszahlungenText.$HTMLStornoCommandEnd).table_data_builder($EinnahmenCount-$Ausgleich['betrag'].'&euro;'));#.table_data_builder($Button)
                }
            } else {
                $HTMLStornoCommandBegin = "";
                $HTMLStornoCommandEnd = "";
                $Button = form_button_builder('storno_ausgabe_konto_details_'.$Ausgleich['id'],'', 'action', 'delete_forever');
                $TableRows .= table_row_builder(table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Ausgleich['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Ausgleich['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$AuszahlungenText.$HTMLStornoCommandEnd).table_data_builder($EinnahmenCount-$Ausgleich['betrag'].'&euro;'));#.table_data_builder($Button)
            }
        }
    } elseif ($KontoInfos['typ']=='ausgabenkonto'){
        $AnfrageAusgaben = "SELECT * FROM finanz_ausgleiche WHERE von_konto = ".$Konto." AND timestamp >= '".$AnfangJahr."' AND timestamp <= '".$EndeJahr."'";
        $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
        $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);
        $MinusItems = array();
        for($a=1;$a<=$AnzahlAusgaben;$a++){
            $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
            $NeuerEintrag = array('timestamp'=>$ErgebnisAusgaben['timestamp'], 'id'=>$ErgebnisAusgaben['id'], 'fuer_konto'=>$ErgebnisAusgaben['fuer_konto'], 'betrag'=>$ErgebnisAusgaben['betrag'], 'storno_user'=>$ErgebnisAusgaben['storno_user'], 'referenz'=>$ErgebnisAusgaben['referenz'], 'anleger'=>$ErgebnisAusgaben['anleger']);
            array_push($MinusItems, $NeuerEintrag);
        }
        asort($MinusItems);

        $TableRows = table_row_builder('<th class="center-align" colspan="3">Geplant</th><th class="center-align" colspan="2">Erfolgt</th>');
        $TableRows .= table_row_builder(table_header_builder('Datum').table_header_builder('Referenz').table_header_builder('Betrag').table_header_builder('Erfolgte Ausgaben').table_header_builder('Differenz'));#.table_header_builder('Aktionen')

        foreach ($MinusItems as $Ausgleich){

            if($Ausgleich['referenz_res']>0){
                $ForderungVonUser = lade_user_meta($Ausgleich['fuer_user']);
                $FuerText = $ForderungVonUser['vorname'].' '.$ForderungVonUser['nachname'];
                $ForderungText = 'Res #'.$Ausgleich['referenz_res'];
            } else {
                $AusgabeKonto = lade_konto_via_id($Ausgleich['fuer_konto']);
                $FuerText = $AusgabeKonto['name'];
                $ForderungText = $Ausgleich['referenz'];
            }

            $AnfrageAusgaben = "SELECT * FROM finanz_ausgaben WHERE ausgleich_id = ".$Ausgleich['id'];
            $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
            $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);

            $AuszahlungenText = "";
            $AusgabebCount = 0;
            for($z=1;$z<=$AnzahlAusgaben;$z++){
                $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
                $AusgabeKonto = lade_konto_via_id($ErgebnisAusgaben['konto_id']);
                if(intval($AusgabeKonto['name'])>0){
                    $ForderungVonUser = lade_user_meta($AusgabeKonto['name']);
                    $FuerText = $ForderungVonUser['vorname'].' '.$ForderungVonUser['nachname'];
                } else {
                    $FuerText = $AusgabeKonto['name'];
                }
                if($ErgebnisAusgaben['storno_user']>0) {
                    $HTMLStornoCommandBegin = "<s>";
                    $HTMLStornoCommandEnd = "</s>";
                } else {
                    $HTMLStornoCommandBegin = "";
                    $HTMLStornoCommandEnd = "";
                    $AusgabebCount = $AusgabebCount + $ErgebnisAusgaben['betrag'];
                }
                $AuszahlungenText .= $HTMLStornoCommandBegin.date('d.m.y', strtotime($ErgebnisAusgaben['timestamp'])).": ".$FuerText.": ".$ErgebnisAusgaben['betrag']."&euro;".$HTMLStornoCommandEnd."<br>";
            }

            if($Ausgleich['storno_user']>0){
                $HTMLStornoCommandBegin = "<s>";
                $HTMLStornoCommandEnd = "</s>";
                $Button = form_button_builder('storno_aufheben_ausgabe_konto_details_'.$Ausgleich['id'],'', 'action', 'undo');
                if($AusgabebCount>0){
                    $TableRows .= table_row_builder(table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Ausgleich['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Ausgleich['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$AuszahlungenText.$HTMLStornoCommandEnd).table_data_builder($Ausgleich['betrag']-$AusgabebCount.'&euro;'));#.table_data_builder($Button)
                }
            } else {
                $HTMLStornoCommandBegin = "";
                $HTMLStornoCommandEnd = "";
                $Button = form_button_builder('storno_ausgabe_konto_details_'.$Ausgleich['id'],'', 'action', 'delete_forever');
                $TableRows .= table_row_builder(table_data_builder($HTMLStornoCommandBegin.date("d.m.Y", strtotime($Ausgleich['timestamp'])).$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$ForderungText.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$Ausgleich['betrag'].'&euro;'.$HTMLStornoCommandEnd).table_data_builder($HTMLStornoCommandBegin.$AuszahlungenText.$HTMLStornoCommandEnd).table_data_builder($Ausgleich['betrag']-$AusgabebCount.'&euro;'));#.table_data_builder($Button)
            }
        }

    }

    $FormHTML .= section_builder(table_builder($TableRows));
    $FormHTML .= section_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back'));
    $FormHTML .= "<input type='hidden' name='konto_id' value='".$Konto."'>";
    $FormHTML .= "<input type='hidden' name='year_global' value='".$YearGlobal."'>";

    $HTML = form_builder($FormHTML, '#', 'post', 'konto_details');
    return $HTML;
}
function konto_anlegen_formular(){

    $Table = table_form_string_item('Kontoname', 'new_konto_name', $_POST['new_konto_name']);
    $Table .= table_row_builder(table_header_builder('Kontotyp').table_data_builder(dropdown_kontotyp_waehlen('new_konto_typ', $_POST['new_konto_typ'])));
    $Table .= table_form_string_item('Anfangswert', 'new_konto_initial', $_POST['new_konto_initial']);
    $Table .= table_row_builder(table_header_builder(form_button_builder('action_add_konto', 'Anlegen', 'action', 'send')).table_data_builder(''));
    $Table = table_builder($Table);
    return collapsible_item_builder('Konto anlegen', $Table, 'add_new');
}
function add_transaktions_vereinskasse($YearGlobal){
    $BigItems = ausgleich_anlegen_formular();
    $BigItems .= ausgabe_eintragen_formular($YearGlobal);
    $BigItems .= forderung_anlegen_formular();
    $BigItems .= einnahmen_eintragen_formular($YearGlobal);
    $BigItems .= umbuchen_formular();
    $BigItems .= "<input type='hidden' name='year_global' value='".$_POST['year_global']."'>";
    $BigItems = collapsible_builder($BigItems);
    $BigItems .= form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back');

    $HTML = '<h3 class="center-align">Transaktionen durchführen</h3>';
    $HTML .= section_builder(form_builder($BigItems, '#', 'post', 'kassenwart_transactions_form'));

    return $HTML;
}
function umbuchen_formular(){
    if($_POST['transfer_chosen_date']!=''){
        $ChosenDate = $_POST['transfer_chosen_date'];
    } else {
        $ChosenDate = date('Y-m-d');
    }
    $Table = table_form_dropdown_transferkonten('Von Konto', 'von_konto_transfer', $_POST['von_konto_transfer']);
    $Table .= table_form_dropdown_transferkonten('Nach Konto', 'nach_konto_transfer', $_POST['von_konto_transfer']);
    $Table .= table_form_string_item('Betrag (Format 12.34)', 'betrag_transfer', $_POST['betrag_transfer']);
    $Table .= table_form_datepicker_item('Buchungsdatum', 'transfer_chosen_date', $ChosenDate, false, true);
    $Table .= table_row_builder(table_header_builder(form_button_builder('action_transfer', 'Eintragen', 'action', 'send')).table_data_builder(''));
    $Text = table_builder($Table);

    return collapsible_item_builder('Umbuchung eintragen', $Text, 'swap_horiz');
}
function einnahmen_eintragen_formular($YearGlobal){

    $AnfangJahr = "".$YearGlobal."-01-01 00:00:01";
    $EndeJahr = "".$YearGlobal."-12-31 23:59:59";

    $link = connect_db();
    $Anfrage = "SELECT * FROM finanz_forderungen WHERE storno_user = 0 AND timestamp >= '".$AnfangJahr."' AND timestamp <= '".$EndeJahr."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl>1){
        $ReturnHTML = '';
        $Counter=0;
        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $Summe = lade_gezahlte_summe_forderung($Ergebnis['id']);
            if($Summe<$Ergebnis['betrag']){
                if($Ergebnis['referenz']!=''){
                    $ReturnHTML .= listenelement_offene_forderung_kassenwart_durchfuehren_generieren($Ergebnis, $Summe, 'andere');
                    $Counter++;
                } elseif ($Ergebnis['referenz']==''){
                    $ReturnHTML .= listenelement_offene_forderung_kassenwart_durchfuehren_generieren($Ergebnis, $Summe, 'reservierung');
                    $Counter++;
                }
            }
        }
        if($Counter==0){
            $ReturnHTML = 'Derzeit keine offenen Forderungen!';
        }else{
            $ReturnHTML= collapsible_builder($ReturnHTML);
        }
        $HTML = collapsible_item_builder('Einnahme eintragen', $ReturnHTML, 'toll');
    } elseif ($Anzahl==0) {
        $HTML = collapsible_item_builder('Einnahme eintragen', 'Keine Forderungen angelegt!', 'toll');
    }

    return $HTML;
}
function forderung_anlegen_formular(){

    if($_POST['add_forderung_chosen_date']!=''){
        $ChosenDate = $_POST['add_forderung_chosen_date'];
    } else {
        $ChosenDate = date('Y-m-d');
    }
    if($_POST['steuer_add_forderung']!=''){$Steuersatz = $_POST['steuer_add_forderung'];} else {$Steuersatz = 19;}
    $Table = table_form_string_item('Forderungsgrund', 'reason_add_forderung', $_POST['reason_add_forderung'], false);
    $Table .= table_form_dropdown_menu_user('Von Nutzer', 'user_add_forderung', $_POST['user_add_forderung']);
    $Table .= table_form_string_item('Betrag (Format: 12.34)', 'betrag_add_forderung', $_POST['betrag_add_forderung'], false);
    $Table .= table_form_select_item('Steuersatz', 'steuer_add_forderung', 0, 99, $Steuersatz, '%', '', '');
    $Table .= table_form_datepicker_reservation_item('Zahlbar bis', 'datum_add_forderung', $_POST['datum_add_forderung'], false, false);
    $Table .= table_form_datepicker_item('Buchungsdatum', 'add_forderung_chosen_date', $ChosenDate, false, true);
    $Table .= table_row_builder(table_header_builder(form_button_builder('action_add_forderung', 'Anlegen', 'action', 'send')).table_data_builder(''));
    $Table = table_builder($Table);

    $HTML = collapsible_item_builder('Forderung anlegen', $Table, 'add_new');

    return $HTML;
}
function ausgabe_eintragen_formular($YearGlobal){
    if($_POST['ausgabe_eintragen_chosen_date']!=''){
        $ChosenDate = $_POST['ausgabe_eintragen_chosen_date'];
    } else {
        $ChosenDate = date('Y-m-d');
    }
    if($_POST['ausgabe_eintragen_steuersatz']!=''){$Steuersatz = $_POST['ausgabe_eintragen_steuersatz'];} else {$Steuersatz = 19;}
    $Text = table_form_offene_ausgleiche('Ausgabe wählen', 'ausgabe_eintragen_ausgleich', $_POST['ausgabe_eintragen_ausgleich'], $YearGlobal);
    $Text .= table_form_string_item('Betrag (Format 12.34)', 'ausgabe_eintragen_betrag', $_POST['ausgabe_eintragen_betrag']);
    $Text .= table_form_select_item('Steuersatz', 'ausgabe_eintragen_steuersatz', 0, 99, $Steuersatz, '%', '', '');
    $Text .= table_form_datepicker_item('Buchungsdatum', 'ausgabe_eintragen_chosen_date', $ChosenDate, false, true);
    $Text .= table_row_builder(table_header_builder('Von Wartkonto ausgeben').table_data_builder(dropdown_menu_wart('ausgabe_eintragen_wart', $_POST['ausgabe_eintragen_wart'])));
    $Text .= table_form_neutralkonten_dropdown('Von Neutralkonto ausgeben', 'ausgabe_eintragen_neutralkonto', $_POST['ausgabe_eintragen_neutralkonto']);
    $Text .= table_row_builder(table_header_builder(form_button_builder('action_ausgabe_durchfuehren', 'Eintragen', 'action', 'send')).table_data_builder(''));
    $Text = table_builder($Text);

    return collapsible_item_builder('Geldausgabe eintragen', $Text, 'payment');
}
function ausgleich_anlegen_formular(){

    if($_POST['new_ausgleich_chosen_date']!=''){
        $ChosenDate = $_POST['new_ausgleich_chosen_date'];
    } else {
        $ChosenDate = date('Y-m-d');
    }

    if($_POST['neu_ausgleich_steuersatz']!=''){$Steuersatz = $_POST['neu_ausgleich_steuersatz'];} else {$Steuersatz = 19;}
    $Text = table_form_dropdown_ausgabenkonten('Ausgabenkonto','neu_ausgleich_konto', $_POST['neu_ausgleich_konto']);
    $Text .= table_form_string_item('Referenz', 'neu_ausgleich_referenz', $_POST['neu_ausgleich_referenz']);
    $Text .= table_form_string_item('Betrag (Format: 12.34)', 'neu_ausgleich_betrag', $_POST['neu_ausgleich_betrag']);
    $Text .= table_form_select_item('Steuersatz', 'neu_ausgleich_steuersatz', 0, 99, $Steuersatz, '%', '', '');
    $Text .= table_form_datepicker_item('Buchungsdatum', 'new_ausgleich_chosen_date', $ChosenDate, false, true);
    $Text .= table_row_builder(table_header_builder(form_button_builder('ausgleich_anlegen', 'Anlegen', 'action', 'send')).table_data_builder(''));
    $Text = table_builder($Text);
    return collapsible_item_builder('Ausgabe planen', $Text, 'playlist_add');
}
function choose_views_vereinskasse(){
    $HTML = "<h3 class='center-align'>Weitere Ansichten</h3>";
    $HTML .= form_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('activate_guv', 'GUV-Rechnung', 'action', 'iso', '')).table_header_builder(form_button_builder('activate_transactions_view', 'Transaktionen', 'action', 'swap_horiz', ''))).table_row_builder(table_header_builder(form_button_builder('activate_list_all_ausgaben', 'Alle Ausgaben', 'action', 'money_off', '')).table_header_builder(form_button_builder('activate_list_all_forderungen', 'Alle Forderungen', 'action', 'attach_money', ''))))."<input type='hidden' name='year_global' value='".$_POST['year_global']."'>", '#', 'post', 'view_changer_form');
    return section_builder($HTML);
}