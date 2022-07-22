<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Einstellungen - " . lade_db_einstellung('site_name');
$Settings = ['vorname', 'nachname', 'strasse', 'hausnummer', 'plz', 'stadt', 'nutzergruppe', 'telefon', 'mail'];

#Parse input
$Parser = user_settings_parser($Settings);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align">Persönliche Einstellungen</h1>';
$HTML .= section_builder($PageTitle);
$HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";

# Settings Form
$UserID = lade_user_id();
$UserMeta = lade_user_meta($UserID);
$SettingTableItems = table_form_string_item('Vorname', 'vorname', $UserMeta['vorname'], false);
$SettingTableItems .= table_form_string_item('Nachname', 'nachname', $UserMeta['nachname'], false);
$SettingTableItems .= table_form_string_item('Straße', 'strasse', $UserMeta['strasse'], false);
$SettingTableItems .= table_form_string_item('Hausnummer', 'hausnummer', $UserMeta['hausnummer'], false);
$SettingTableItems .= table_form_string_item('Stadt', 'stadt', $UserMeta['stadt'], false);
$SettingTableItems .= table_form_string_item('Postleitzahl', 'plz', $UserMeta['plz'], false);
$SettingTableItems .= table_form_string_item('Mail', 'mail', $UserMeta['mail'], false);
$SettingTableItems .= table_form_string_item('Telefon (optional)', 'telefon', $UserMeta['telefon'], false);
$SettingTable = table_builder($SettingTableItems);
$SettingTable = section_builder($SettingTable);

$NutzergruppeMeta = lade_nutzergruppe_infos($UserMeta['ist_nutzergruppe'], 'name');
$NutzergruppeHTML = "<h3 class='hide-on-med-and-down'>Nutzergruppe</h3>";
$NutzergruppeHTML .= "<h3 class='center-align hide-on-large-only'>Nutzergruppe</h3>";
$NutzergruppeTable = table_row_builder(table_header_builder('Aktuelle Nutzergruppe').table_data_builder($NutzergruppeMeta['name']));
$NutzergruppeTable .= table_row_builder(table_header_builder('Beschreibung').table_data_builder($NutzergruppeMeta['erklaertext']));
$VerifizierungErklaerung = $NutzergruppeMeta['req_verify'];
if($VerifizierungErklaerung!='false'){

    $NutzergruppeVerification = load_last_nutzergruppe_verification_user($NutzergruppeMeta['id'], $UserID);

    if($VerifizierungErklaerung == 'yearly'){
        $VerifizierungErklaerung = "Deine Zugehörigkeit zur Nutzergruppe muss jährlich verifiziert werden!";
        if($NutzergruppeVerification['erfolg'] == 'false'){
            $VerifizierungErklaerung .= "<br><b>Verifizierung abgelehnt!</b><br>Bitte wechsle deine Nutzergruppe oder kontaktiere uns, wenn du meinst, dass ein Fehler vorliegt.";
        } elseif ($NutzergruppeVerification['erfolg'] == 'true'){
            if($NutzergruppeVerification['timestamp'] < "".date('Y')."-01-01 00:00:01"){
                $VerifizierungErklaerung .= "<br><b>Verifizierung abgelaufen!</b><br>Wird bei der nächsten Schlüsselübergabe gemacht:)";
            } elseif ($NutzergruppeVerification['timestamp'] >= "".date('Y')."-01-01 00:00:01"){
                $VerifizierungErklaerung .= "<br><b>Verifizierung dieses Jahr erfolgt!:)</b>";
            }
        } elseif (empty($NutzergruppeVerification)){
            $VerifizierungErklaerung .= "<br><b>Bislang keine Verifizierung erfolgt!</b>";
        }
    } elseif ($VerifizierungErklaerung == 'once'){
        $VerifizierungErklaerung = "Deine Zugehörigkeit zur Nutzergruppe muss einmalig verifiziert werden!";
        if($NutzergruppeVerification['erfolg'] == 'false'){
            $VerifizierungErklaerung .= "<br><b>Verifizierung abgelehnt!</b><br>Bitte wechsle deine Nutzergruppe oder kontaktiere uns, wenn du meinst, dass ein Fehler vorliegt.";
        } elseif ($NutzergruppeVerification['erfolg'] == 'true'){
            $VerifizierungErklaerung .= "<br><b>Verifizierung erfolgt!:)</b>";
        } elseif (empty($NutzergruppeVerification)){
            $VerifizierungErklaerung .= "<br><b>Bislang keine Verifizierung erfolgt!</b>";
        }
    }

    $Nutzergruppen = lade_alle_nutzgruppen();
    $NutzergruppeTable .= table_row_builder(table_header_builder('Verifizierung').table_data_builder($VerifizierungErklaerung));
    $NutzergruppeTable .= table_form_dropdown_nutzergruppen_waehlen('Nutzergruppe wechseln', 'nutzergruppe', $_POST['nutzergruppe'], $Nutzergruppen, 'user');
}
$NutzergruppeTable .= table_row_builder(table_header_builder('').table_data_builder('Bitte beachte: ein Ändern der Nutzergruppe bedeutet, dass diese in jedem Fall bei der nächsten Schlüsselübergabe überprüft werden muss.'));
$NutzergruppeHTML .= table_builder($NutzergruppeTable);
$SettingTable .= section_builder($NutzergruppeHTML);
$SettingTable .= section_builder(form_button_builder('user_settings_action', 'Speichern', 'action', 'send'));

$SettingTable .= "<h3 class='hide-on-med-and-down'>Weitere Funktionen</h3>";
$SettingTable .= "<h3 class='center-align hide-on-large-only'>Weitere Funktionen</h3>";
$WartCollapsibleHTML = '';
if($UserMeta['ist_wart']=='true'){
    $WartCollapsibleHTML = spalte_wartfunktionen($UserMeta);
}
$WartCollapsibleHTML .= spalte_passwort_aendern($Parser);
$WartCollapsibleHTML .= spalte_konto_loeschen();
$WartHTML .= collapsible_builder($WartCollapsibleHTML);
$SettingTable .= section_builder($WartHTML);

$SettingForm = form_builder($SettingTable, './usereinstellungen.php', 'post');
$HTML .= section_builder($SettingForm);

#Put it all in a container
$HTML = container_builder($HTML, 'user_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);



function spalte_wartfunktionen($UserMeta){
$HTML = "<li>";
    $HTML .= "<div class='collapsible-header active'><i class='large material-icons'>android</i>Warteinstellungen</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<div class='container'>";
    $HTML .= "<form method='post'>";

    $HTML .= spalte_Ortsvorlagen();
    $HTML .= "<div class='divider'></div>";
    $HTML .= spalte_persoenliche_daten($UserMeta);
    $HTML .= "<div class='divider'></div>";
    $HTML .= spalte_uebergabeeinstellungen($UserMeta);
    $HTML .= "<div class='divider'></div>";
    $HTML .= spalte_infos_erhalten($UserMeta);
    $HTML .= "<div class='divider'></div>";
    $HTML .= spalte_kalenderabonnement($UserMeta);

    $HTML .= "</form>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}
function spalte_Ortsvorlagen(){

    $HTML = "<h4 class='middle'>Ortsvorlagen</h4>";
    $TableHTML = table_form_string_item('Neue Ortsvorlage', 'ortsangabe_neu', $_POST['ortsangabe_neu'], false);
    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_wart_textkonserve_hinzufuegen', 'Hinzuf&uuml;gen', 'action', 'add')).table_data_builder(''));
    $TableHTML .= table_row_builder(table_header_builder('Ortsvorlage löschen').table_data_builder(dropdown_vorlagen_ortsangaben('ortskonserven', lade_user_id(), $_POST['ortskonserven'])));
    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_wart_textkonserve_loeschen', 'L&ouml;schen', 'action', 'delete_forever')).table_data_builder(''));
    $HTML .= table_builder($TableHTML);

    return section_builder($HTML);
}
function spalte_uebergabeeinstellungen($UserMeta){

    $HTML = "<h4 class='middle'>Übergabeeinstellungen</h4>";
    if(isset($UserMeta['max_num_uebergaben_at_once'])){
        $SettingMaxNumUebergaben = $UserMeta['max_num_uebergaben_at_once'];
    } else {
        $SettingMaxNumUebergaben = 3;
    }
    $TableHTML = table_form_range_item('Max. Anzahl gleichzeitiger Übergaben', 'max_num_uebergaben_at_once', 1, 5, $SettingMaxNumUebergaben);
    $HTML .= section_builder(table_builder($TableHTML));

    $HTML .= divider_builder();
    $HTML .= section_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('action_wart_uebergaben_aendern', '&Auml;ndern', 'action', 'edit', '')).table_data_builder(''))));

    return $HTML;
}
function spalte_persoenliche_daten($UserMeta){

    //Aktuelle Einstellungen laden
    $Benutzereinstellungen = $UserMeta;

    if($Benutzereinstellungen['tel-kontaktseite'] == "true"){
        $TelKontaktseite = "on";
    } else {
        $TelKontaktseite = "off";
    }

    if($Benutzereinstellungen['mail-kontaktseite'] == "true"){
        $MailKontaktseite = "on";
    } else {
        $MailKontaktseite = "off";
    }

    if($Benutzereinstellungen['tel-userinfo'] == "true"){
        $TelUserinfo = "on";
    } else {
        $TelUserinfo = "off";
    }

    if($Benutzereinstellungen['mail-userinfo'] == "true"){
        $MailUserinfo = "on";
    } else {
        $MailUserinfo = "off";
    }

    $HTML = "<h4 class='middle'>Pers&ouml;nliche Daten anzeigen</h4>";

    $HTML .= "<h5 class='middle'>Email</h5>";
    $TableKontaktseite = table_form_swich_item('Email auf Kontaktseiten anzeigen', 'mail_kontaktseiten', 'Nein', 'Ja', $MailKontaktseite, false);
    $TableKontaktseite .= table_form_swich_item('Email als Kontaktinfo bei Mails an User (z.B. bei &Uuml;bergabebest&auml;tigungen) mitsenden', 'mail_userinteraktion', 'Nein', 'Ja', $MailUserinfo, false);
    $HTML .= section_builder(table_builder($TableKontaktseite));

    if($Benutzereinstellungen['telefon']!=''){
        $HTML .= "<h5 class='middle'>Telefon</h5>";
        $Tableseite = table_form_swich_item('Telefonnummer auf Kontaktseiten anzeigen', 'tel_kontaktseiten', 'Nein', 'Ja', $TelKontaktseite, false);
        $Tableseite .= table_form_swich_item('Telefonnummer als Kontaktinfo bei Mails an User mitsenden', 'tel_userinteraktion', 'Nein', 'Ja', $TelUserinfo, false);
        $HTML .= section_builder(table_builder($Tableseite));
    }

    $HTML .= divider_builder();
    $HTML .= section_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('action_wart_persoenliche_daten_aendern', '&Auml;ndern', 'action', 'edit', '')).table_data_builder(''))));

    return section_builder($HTML);
}
function spalte_infos_erhalten($UserMeta){

//Aktuelle Einstellungen laden
    $Benutzereinstellungen = $UserMeta;

    if($Benutzereinstellungen['mail-wart-neue-uebergabe'] == "true"){
        $MailUebergabeErhalten = "on";
    } else {
        $MailUebergabeErhalten = "off";
    }

    if($Benutzereinstellungen['mail-wart-storno-uebergabe'] == "true"){
        $MailUebergabeStorno = "on";
    } else {
        $MailUebergabeStorno = "off";
    }

    if($Benutzereinstellungen['sms-wart-neue-uebergabe'] == "true"){
        $SMSUebergabeErhalten = "on";
    } else {
        $SMSUebergabeErhalten = "off";
    }

    if($Benutzereinstellungen['sms-wart-storno-uebergabe'] == "true"){
        $SMSUebergabeStorno = "on";
    } else {
        $SMSUebergabeStorno = "off";
    }

    if($Benutzereinstellungen['mail-kurzfristig-uebernahme-abgesagt'] == "true"){
        $MailUebernahmeKurzfristigStorniert = "on";
    } else {
        $MailUebernahmeKurzfristigStorniert = "off";
    }

    if($Benutzereinstellungen['erinnerung-wart-schluesseluebergabe-eintragen'] == "true"){
        $MailUebergabeVergessen = "on";
    } else {
        $MailUebergabeVergessen = "off";
    }

    if($Benutzereinstellungen['mail-wart-daily-update'] == "true"){
        $MailWoechentlichStatus = "on";
    } else {
        $MailWoechentlichStatus = "off";
    }

    if($Benutzereinstellungen['mail-wart-weekly-update'] == "true"){
        $MailTaeglichStatus = "on";
    } else {
        $MailTaeglichStatus = "off";
    }

    if($Benutzereinstellungen['mail_status_only_important'] == "true"){
        $MailStatusMode = "on";
    } else {
        $MailStatusMode = "off";
    }

    $HTML = "<h4 class='middle'>Informiert werden</h4>";
    $HTML .= "<h5 class='middle'>Email</h5>";
    $Table1 = table_form_swich_item('Email erhalten bei neuer &Uuml;bergabe', 'mail_uebergabe_erhalten', 'Nein', 'Ja', $MailUebergabeErhalten);
    $Table1 .= table_form_swich_item('Email erhalten wenn &Uuml;bergabe storniert wird', 'mail_uebergabe_storno', 'Nein', 'Ja', $MailUebergabeStorno);
    $Table1 .= table_form_swich_item('Email erhalten wenn du vergessen hast eine &Uuml;bergabe durchzuklicken', 'mail_uebernahme_erinnerung', 'Nein', 'Ja', $MailUebergabeVergessen);
    $Table1 .= table_form_swich_item('Email erhalten wenn eine &Uuml;bernahme kurzfristig storniert wird', 'mail_uebernahme_kurzfristig', 'Nein', 'Ja', $MailUebernahmeKurzfristigStorniert);
    $Table1 .= table_form_swich_item('Tägliche Status-Email für die kommenden '.lade_xml_einstellung('future_daily_status_mail').' Tage erhalten', 'mail_status_dailly', 'Nein', 'Ja', $MailTaeglichStatus);
    $Table1 .= table_form_swich_item('Jeden Sonntag eine Status-Email für die kommende Woche erhalten', 'mail_status_weekly', 'Nein', 'Ja', $MailWoechentlichStatus);
    $Table1 .= table_form_swich_item('Statusmails nur bei offenen Reservierungen erhalten', 'mail_status_only_important', 'Nein', 'Ja', $MailStatusMode);
    $HTML .= section_builder(table_builder($Table1));

    if (lade_xml_einstellung('sms-active') == "on"){
        $HTML .= "<h5 class='middle'>SMS</h5>";
        $Table2 = table_form_swich_item('SMS erhalten bei neuer &Uuml;bergabe', 'sms_uebergabe_erhalten', 'Nein', 'Ja', $SMSUebergabeErhalten);
        $Table2 .= table_form_swich_item('SMS erhalten wenn &Uuml;bergabe storniert wird', 'sms_uebergabe_storno', 'Nein', 'Ja', $SMSUebergabeStorno);
        $HTML .= section_builder(table_builder($Table2));
    }

    $HTML .= divider_builder();
    $HTML .= section_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('action_wart_benachrichtigungen_aendern', '&Auml;ndern', 'action', 'edit', '')).table_data_builder(''))));

    return $HTML;
}
function spalte_kalenderabonnement($UserMeta){

    $Benutzereinstellungen = $UserMeta;

    if($Benutzereinstellungen['kalenderabo'] == "true"){
        $OptionGewaehlt = "on";
    } else {
        $OptionGewaehlt = "off";
    }

    $HTMLinfos = lade_xml_einstellung('anleitung_kalenderabo_warte');
    $Array = array();
    $Array['[id_von_system]'] = lade_user_id();
    $HTMLinfos = str_replace(array_keys($Array), array_values($Array), $HTMLinfos);

    $HTML = section_builder($HTMLinfos);
    $HTMLtable = table_form_swich_item('Wartkalender aktivieren', 'kalenderabo_checkbox', 'Nein', 'Ja', $OptionGewaehlt);
    $HTMLtable .= table_row_builder(table_header_builder(form_button_builder('action_wart_kalenderabo_aendern', '&Auml;ndern', 'action', 'edit', '')).table_data_builder(''));
    $HTML .= section_builder(table_builder($HTMLtable));
    return $HTML;
}
function spalte_passwort_aendern($Parser){
    if(lade_xml_einstellung('extremepasswordmode')=='on'){
        $TableHTML = table_row_builder(table_header_builder('Passwortregeln').table_data_builder(lade_xml_einstellung('rules_extreme_password_mode')));
    } else {
        $TableHTML = table_row_builder(table_header_builder('Passwortregeln').table_data_builder(lade_xml_einstellung('rules_normal_password_mode')));
    }
    $TableHTML .= table_form_password_item('Dein neues Passwort', 'password', '', false);
    $TableHTML .= table_form_password_item('Eingabe wiederholen', 'password_repeat', '', false);
    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_password', '&Auml;ndern', 'action', 'send')).table_data_builder(''));
    return collapsible_item_builder('Passwort &auml;ndern', form_builder("<h5 class='center-align'>".$Parser['meldung']."</h5>".table_builder($TableHTML), '#', 'post','passwort_aendern_form'), 'vpn_key');
}
function spalte_konto_loeschen(){

    $NeinGrundCounter = 0;
    $UserID = lade_user_id();
    $link = connect_db();

    $AnfrageLadeAlleResUser = "SELECT id FROM reservierungen WHERE user = '".$UserID."' AND storno_user = '0'";
    $AbfrageLadeAlleResUser = mysqli_query($link, $AnfrageLadeAlleResUser);
    $AnzahlLadeAlleResUser = mysqli_num_rows($AbfrageLadeAlleResUser);

    for ($a = 1; $a <= $AnzahlLadeAlleResUser; $a++){

        $Reservierung = mysqli_fetch_assoc($AbfrageLadeAlleResUser);

        //Schlüssel zurückgegeben falls Ausgabe erfolgt?
        $SchluesselausgabeAnfrage = "SELECT id FROM schluesselausgabe WHERE user = '".$UserID."' AND storno_user = '0' AND ausgabe IS NOT NULL AND rueckgabe IS NULL";
        $SchluesselausgabeAbfrage = mysqli_query($link, $SchluesselausgabeAnfrage);
        $SchluesselausgabeAnzahl = mysqli_num_rows($SchluesselausgabeAbfrage);

        if ($SchluesselausgabeAnzahl > 0){
            $NeinGrundCounter++;
        }

        //Alle Forderungen gezahlt?
        $ForderungRes = lade_forderung_res($Reservierung['id']);
        $Einnahmen = lade_gezahlte_summe_forderung($ForderungRes['id']);
        if($ForderungRes['betrag'] > $Einnahmen){
            $NeinGrundCounter++;
        }


        //Alle Rückzahlungen erhalten?
        $Rueckzahlungen = lade_offene_ausgleiche_res($Reservierung['id']);
        $Ausgaben = lade_gezahlte_betraege_ausgleich($Rueckzahlungen['id']);
        if ($Rueckzahlungen['betrag'] > $Ausgaben){
            $NeinGrundCounter++;
        }
    }

    if ($NeinGrundCounter == 0){
        $Table = table_form_swich_item('Ich m&ouml;chte mein Benutzerkonto endg&uuml;tig deaktivieren', 'konto_validate', 'Nein', 'Ja', '', false);
        $Table .= table_row_builder(table_header_builder(form_button_builder('action_konto', 'Deaktivieren', 'action', 'delete_forever', '')).table_data_builder(''));
        return collapsible_item_builder('Konto deaktivieren', form_builder(table_builder($Table), '#', 'post', 'konto_loeschen_form'), 'delete');
    } else if ($NeinGrundCounter > 0){
        return collapsible_item_builder('Konto deaktivieren', 'Derzeit sind noch nicht alle Vorg&auml;nge, die dein Konto betreffen abgeschlossen. Bitte &uuml;berpr&uuml;fe ob du alle Fahrten bezahlt, deine Schl&uuml;ssel zur&uuml;ckgegeben oder dir zustehende R&uuml;ckzahlungen abgeholt hast!', 'delete');
    }
}