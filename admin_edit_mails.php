<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Admin Mailvorlagen editieren - " . lade_db_einstellung('site_name');

$MailArray = array();
array_push($MailArray, array('name'=>'registrierung_user','erklaerung'=>'<b>Benutzeraccounts:</b>&nbsp; Mail an User*in wenn neu registriert','icon'=>'info'));
array_push($MailArray, array('name'=>'passwort-zurueckgesetzt-selbst','erklaerung'=>'<b>Benutzeraccounts:</b>&nbsp; Mail an User*in wenn Passwort selbst zurückgesetzt','icon'=>'undo'));
array_push($MailArray, array('name'=>'passwort-zurueckgesetzt-wart','erklaerung'=>'<b>Benutzeraccounts:</b>&nbsp; Mail an User*in wenn Passwort durch Wart*in zurückgesetzt','icon'=>'undo'));
array_push($MailArray, array('name'=>'benutzerkonto-deaktiviert-user','erklaerung'=>'<b>Benutzeraccounts:</b>&nbsp; Mail an User*in wenn Nutzerkonto selbst deaktiviert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'benutzerkonto-deaktiviert-wart','erklaerung'=>'<b>Benutzeraccounts:</b>&nbsp; Mail an User*in wenn Nutzerkonto durch Wart*in deaktiviert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'mail_erinnerung_schluesselrueckgabe_intervall','erklaerung'=>'<b>Erinnerungsmails:</b>&nbsp; Erinnerung Schlüsselrückgabe','icon'=>'info'));
array_push($MailArray, array('name'=>'mail_erinnerung_nachzahlung_intervall','erklaerung'=>'<b>Erinnerungsmails:</b>&nbsp; Erinnerung Nachzahlung','icon'=>'info'));
array_push($MailArray, array('name'=>'mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt','erklaerung'=>'<b>Erklärmails:</b>&nbsp; Erklärmail an User direkt nach der Fahrt','icon'=>'info'));
array_push($MailArray, array('name'=>'mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt_mit_uebernahme','erklaerung'=>'<b>Erklärmails:</b>&nbsp; Erklärmail an User direkt nach der Fahrt wenn Gruppe danach Übernahme gebucht hat','icon'=>'info'));
array_push($MailArray, array('name'=>'warnung-wart-uebernahme-kurzfristig-abgesagt','erklaerung'=>'<b>Infomails:</b>&nbsp; Infomail an Warte*innen falls Übernahme sehr kurzfristig geplatzt ist','icon'=>'info'));
array_push($MailArray, array('name'=>'erinnerung-wart-schluesseluebergabe-eintragen','erklaerung'=>'<b>Infomails:</b>&nbsp; Infomail an Warte*innen falls er/sie vergessen hat ne Übergabe durchzuklicken','icon'=>'info'));
array_push($MailArray, array('name'=>'storno-reservierung','erklaerung'=>'<b>Reservierungen:</b>&nbsp; Storno einer Reservierung','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'reservierung-angelegt','erklaerung'=>'<b>Reservierungen:</b>&nbsp; Anlegen einer Reservierung','icon'=>'add_new'));
array_push($MailArray, array('name'=>'termin-bekommen-wart','erklaerung'=>'<b>Termine:</b>&nbsp; Info neuen Termin bekommen für Wart*in','icon'=>'info'));
array_push($MailArray, array('name'=>'termin-storniert-user','erklaerung'=>'<b>Termine:</b>&nbsp; Mail an User*in wenn Termin selbst storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'termin-storniert-wart','erklaerung'=>'<b>Termine:</b>&nbsp; Mail an Wart*in wenn Termin durch User selbst storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'uebergabe-angelegt-selbst','erklaerung'=>'<b>Übergaben:</b>&nbsp; Anlegen einer Übergabe durch eine*n User*in','icon'=>'add_new'));
array_push($MailArray, array('name'=>'uebergabe-angelegt-wart','erklaerung'=>'<b>Übergaben:</b>&nbsp; Anlegen einer Übergabe durch eine*n Wart*in','icon'=>'add_new'));
array_push($MailArray, array('name'=>'uebergabe-bekommen-wart','erklaerung'=>'<b>Übergaben:</b>&nbsp; Info neue Übergabe bekommen für Wart*in','icon'=>'info'));
array_push($MailArray, array('name'=>'uebergabe-storniert-user','erklaerung'=>'<b>Übergaben:</b>&nbsp; Mail an User*in wenn Übergabe selbst storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'uebergabe-storniert-anderer-wart','erklaerung'=>'<b>Übergaben:</b>&nbsp; Mail an Wart*in wenn Übergabe durch andere*n Wart*in storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'uebergabe-storniert-wart','erklaerung'=>'<b>Übergaben:</b>&nbsp; Mail an Wart*in wenn Übergabe durch User selbst storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'uebergabe-storniert-wart','erklaerung'=>'<b>Übergaben:</b>&nbsp; Mail an Wart*in wenn Übergabe durch User selbst storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'erinnerung-uebergabe-ausmachen-intervall-eins','erklaerung'=>'<b>Übergaben:</b>&nbsp; Erste Erinnerung an User*in Übergabe ausmachen','icon'=>'info'));
array_push($MailArray, array('name'=>'erinnerung-uebergabe-ausmachen-intervall-zwei','erklaerung'=>'<b>Übergaben:</b>&nbsp; Zweite Erinnerung an User*in Übergabe ausmachen','icon'=>'info'));
array_push($MailArray, array('name'=>'uebernahme-angelegt-vorgruppe','erklaerung'=>'<b>Übernahmen:</b>&nbsp; Mail an User*in davor wenn Übernahme durch folgende*n User*in angelegt','icon'=>'info'));
array_push($MailArray, array('name'=>'uebernahme-angelegt-nachgruppe','erklaerung'=>'<b>Übernahmen:</b>&nbsp; Mail an User*in wenn Übernahme angelegt','icon'=>'info'));
array_push($MailArray, array('name'=>'uebernahme-storniert-user','erklaerung'=>'<b>Übernahmen:</b>&nbsp; Mail an User*in wenn Übernahme selbst storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'uebernahme-storniert-user-davor','erklaerung'=>'<b>Übernahmen:</b>&nbsp; Mail an User*in davor wenn Übernahme durch folgende*n User*in storniert','icon'=>'delete_forever'));
array_push($MailArray, array('name'=>'lora_batt_unterspannung','erklaerung'=>'<b>R&uuml;ckgabeautomatik:</b>&nbsp; Mail an Marc wenn Akku zur Neige geht','icon'=>'network_check'));
array_push($MailArray, array('name'=>'lora_missing','erklaerung'=>'<b>R&uuml;ckgabeautomatik:</b>&nbsp; Mail an Marc wenn sich R&uuml;ckgabeautomatik seit 12 Stunden nicht gemeldet hat','icon'=>'network_check'));
array_push($MailArray, array('name'=>'wasserstandsmail-res','erklaerung'=>'<b>Wasserstandsautomatik:</b>&nbsp; Mail an User x Stunden vor Fahrtantritt mit aktuellen Wasserstandsinfos u. Empfehlung ob für Anfänger geeignet','icon'=>'show_chart'));

$Parser = edit_mails_parser_admin($MailArray);

$PageTitle = '<h1 class="hide-on-med-and-down">Mailvorlagen editieren</h1>';
$PageTitle .= '<h1 class="hide-on-large-only">Mailvorlagen</h1>';
$HTML .= section_builder($PageTitle);
$HTML .= section_builder(edit_mails_view_admin($MailArray));

#Put it all in a container
$HTML = container_builder($HTML, 'admin__mail_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);


function edit_mails_parser_admin($MailArray){

    if(isset($_POST['change_mails_settings'])) {

        $ChangeCounter = 0;
        $ErrCounter = 0;
        $ErrMessage = "";

        foreach ($MailArray as $Mail){
            $Mail_Einteilung_geloescht = lade_mailvorlage($Mail['name']);

            if ($Mail_Einteilung_geloescht['betreff'] != $_POST[$Mail['name'].'-betreff']) {
                #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
                if ($_POST[$Mail['name'].'-betreff'] == '') {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Der Betreff der Mail <b>".$Mail['name']."</b> darf nicht leer sein!<br>";
                } else {
                    if (edit_mailvorlage($Mail['name'], 'betreff', utf8_decode($_POST[$Mail['name'].'-betreff']))) {
                        $ChangeCounter++;
                    } else {
                        $ChangeCounter++;
                        $ErrCounter++;
                        $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>".$Mail['name']."</b>!<br>";
                    }
                }
            }
            if ($Mail_Einteilung_geloescht['text'] != $_POST[$Mail['name'].'-text']) {
                #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
                if ($_POST[$Mail['name'].'-text'] == '') {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Der Inhalt der Mail <b>".$Mail['name']."</b> darf nicht leer sein!<br>";
                } else {
                    if (edit_mailvorlage($Mail['name'], 'text', utf8_decode($_POST[$Mail['name'].'-text']))) {
                        $ChangeCounter++;
                    } else {
                        $ChangeCounter++;
                        $ErrCounter++;
                        $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>".$Mail['name']."</b>!<br>";
                    }
                }
            }
        }

        ##### DONT TOUCH ########

        #Wurde was verändert?
        if ($ChangeCounter > 0) {
            #Gab es einen Fehler?
            if ($ErrCounter == 0) {
                return 'erfolg';
            } elseif ($ErrCounter > 0) {
                return $ErrMessage;
            }
        } else {
            return 'null';
        }
    } else {
        return 'null';
    }
}
function edit_mails_view_admin($MailArray){

    $HTML_emails_collapsible = '';

    foreach ($MailArray as $Mail){
        if($Mail['name']!=''){
            $Vorlage_automatische_registrierung = lade_mailvorlage($Mail['name']);

            if(isset($_POST[$Mail['name'].'-betreff'])){
                $Value_betreff_automatische_registrierung = $_POST[$Mail['name'].'-betreff'];
            } else {
                $Value_betreff_automatische_registrierung = $Vorlage_automatische_registrierung['betreff'];
            }

            if(isset($_POST[$Mail['name'].'-text'])){
                $Value_text_automatische_registrierung = $_POST[$Mail['name'].'-text'];
            } else {
                $Value_text_automatische_registrierung = $Vorlage_automatische_registrierung['text'];
            }

            $HTML_automatische_registrierung = table_row_builder(table_header_builder('Betreff:').table_data_builder(form_string_item($Mail['name'].'-betreff', $Value_betreff_automatische_registrierung, '')));
            $HTML_automatische_registrierung .= table_row_builder(table_header_builder('Inhalt:').table_data_builder(form_html_area_item($Mail['name'].'-text', $Value_text_automatische_registrierung, '')));
            $HTML_emails_collapsible .= collapsible_item_builder($Mail['erklaerung'], table_builder($HTML_automatische_registrierung), $Mail['icon']);
        }
    }


    $HTML = section_builder(collapsible_builder($HTML_emails_collapsible));
    $HTML .= section_builder(table_builder(table_row_builder(table_data_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', '')).table_data_builder(form_button_builder('change_mails_settings', 'Speichern', 'action', 'send')))));
    $HTML = form_builder($HTML, '#', 'post', 'edit_mails_form', '');

    return $HTML;
}