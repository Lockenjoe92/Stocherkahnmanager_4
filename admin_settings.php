<?php

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Admin Einstellungen - " . lade_db_einstellung('site_name');
$DBSettings = ['site_name', 'site_footer_name', 'earliest_begin', 'latest_begin', 'site_menue_color',
    'site_footer_color', 'site_buttons_color', 'site_error_buttons_color', 'display_big_footer',
    'big_footer_left_column_html', 'big_footer_right_column_html', 'max_size_file_upload', 'farbe-button-kalender-buchbar', 'homepagetext_rueckgabeautomatik_bad_reception',
    'farbe-button-kalender-nicht-buchbar', 'homepagetext_wasserstand_fuer_erfahrene_geeignet','homepagetext_rueckgabeautomatik_needs_charge','homepagetext_rueckgabeautomatik_24hours', 'homepagetext_rueckgabeautomatik_fehlerfrei','homepagetext_wasserstand_sperrung_bald', 'homepagetext_wasserstand_fuer_anfaenger_geeignet', 'farbe-button-kalender-reserviert','site_background_color','wasserstand_sperrungsautomatik_text'];
admin_db_settings_parser($DBSettings);

$XMLsettings = ['extremepasswordmode', 'site_url', 'absender_mail', 'absender_name', 'reply_mail', 'sms-active', 'user-sms', 'key-sms',
    'absender-sms', 'max-kosten-einer-reservierung', 'max-dauer-einer-reservierung', 'max-stunden-vor-abfahrt-buchbar',
    'max-tage-vor-abfahrt-uebergabe', 'max-minuten-vor-abfahrt-uebergabe', 'zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage',
    'tage-spontanuebergabe-reservierungen-zukunft-dropdown', 'kritischer-abstand-storno-vor-beginn', 'uebernahmefunktion-global-aktiv', 'erinnerung-uebergabe-ausmachen-1',
    'erinnerung-uebergabe-ausmachen-2', 'erinnerung-schluessel-zurueckgeben-intervall-beginn', 'erinnerung-schluessel-zurueckgeben-intervall-groesse', 'stunden-bis-uebergabe-eingetragen-sein-soll',
    'zeit-tage-nach-res-ende-zahlen', 'card_panel_hintergrund', 'delete-inactive-users-after-x-years', 'site_menue_text_color', 'rechnungsfunktion_global', 'rechnungsfunktion_wart', 'paypal-aktiv', 'future_daily_status_mail',
    'soll_uhrzeit_weekly_status_mail', 'soll_uhrzeit_daily_status_mail', 'erinnerung-nachzahlung-intervall-groesse', 'erinnerung-nachzahlung-intervall-beginn', 'rssi_db_untergrenze',
    'schluesselrueckgabe_automat_aktiv', 'wasserstand_mail_time', 'wasserstand_mail_wart_mode', 'wasserstand_akkordeon_title', 'wasserstand_sperrungsautomatik_stunden', 'wasserstand_sperrungsautomatik_on_off',
    'pretix_widget_global', 'wasserstand_generelle_sperrung_auto', 'wasserstand_vorwarnung_beginner', 'wasserstand_mail_mode', 'grenze_trendberechnung_wasserstand', 'wasserstand_global_on_off', 'anzahl_messungen_trendberechnung_wasserstand',
    'wasserstand_vorwarnung_erfahrene', 'wasserstand_generelle_sperrung', 'tage-spontanuebergabe-reservierungen-vergangenheit-dropdown','warnung_lora_unterspannung_aktiv', 'batterie_spannung_untergrenze', 'warnung_lora_totmann_aktiv',
    'hinweis_login_formular', 'moegliche_schluesselorte', 'search_URL_pegelstaende', 'destination_url_after_logout'];
admin_xml_settings_parser($XMLsettings);

$CDATAxmlSETTINGS = ['rules_normal_password_mode', 'rules_extreme_password_mode','titelinfo-reservierung-hinzufuegen', 'inhalt-dokumente-und-nuetzliches', 'html-faq-user-hauptansicht', 'text-info-uebergabe-dabei-haben', 'text-info-uebergabe-ablauf',
    'text-info-uebergabe-einweisung', 'erklaerung_schluesseluebernahme', 'pretix_widget_css', 'pretix_widget_js', 'erklaerung-forderung-zahlen-user', 'site_name_html', 'rechnungs_header',
    'rechnungs_footer', 'normal-payment-options', 'paypal-text', 'hinweis_login_formular', 'anleitung_kalenderabo_warte'];
admin_xml_cdata_settings_parser($CDATAxmlSETTINGS);


#Generate content
# Page Title
$PageTitle = '<h1>Admineinstellungen</h1>';
$PageTitle = '<h1 class="hide-on-med-and-down">Admineinstellungen</h1>';
$PageTitle .= '<h1 class="hide-on-large-only">Admin Settings</h1>';
$HTML = section_builder($PageTitle);

#Settings Form
$Items="";

#Website Skeleton
$SettingTableItems = table_form_string_item('Website Name', 'site_name', lade_db_einstellung('site_name'), false);
$SettingTableItems .= table_form_html_area_item('HTML für schicken Website-Namen', 'site_name_html', lade_xml_einstellung('site_name_html'), false);
$SettingTableItems .= table_form_string_item('Website Footer Name', 'site_footer_name', lade_db_einstellung('site_footer_name'), false);
$SettingTableItems .= table_form_swich_item('Website Big Footer', 'display_big_footer', 'deaktiviert', 'aktiviert', lade_db_einstellung('display_big_footer'), false);
$SettingTableItems .= table_form_html_area_item('Big Footer Left Column', 'big_footer_left_column_html', lade_db_einstellung('big_footer_left_column_html'), slider_setting_interpreter(lade_db_einstellung('display_big_footer')));
$SettingTableItems .= table_form_html_area_item('Big Footer Right Column', 'big_footer_right_column_html', lade_db_einstellung('big_footer_right_column_html'), slider_setting_interpreter(lade_db_einstellung('display_big_footer')));
$SettingTableItems .= table_form_string_item('Logout Ziel URL', 'destination_url_after_logout', lade_xml_einstellung('destination_url_after_logout'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Website Skeleton', $SettingTable, 'colorize');

# Passwortkram
$SettingTableItems = table_form_swich_item('Passwortmodus', 'extremepasswordmode', 'Normal', 'Extrem', lade_xml_einstellung('extremepasswordmode'), false);
$SettingTableItems .= table_form_html_area_item('HTML für Regeln normale Passwortvorgaben', 'rules_normal_password_mode', lade_xml_einstellung('rules_normal_password_mode'), false);
$SettingTableItems .= table_form_html_area_item('HTML für Regeln schwere Passwortvorgaben', 'rules_extreme_password_mode', lade_xml_einstellung('rules_extreme_password_mode'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Passwortanforderungen für Nutzerkonten', $SettingTable, 'vpn_key');

# Farbenkram
$SettingTableItems = table_form_string_item('Website Men&uuml;farbe', 'site_menue_color', lade_db_einstellung('site_menue_color'), false);
$SettingTableItems .= table_form_string_item('Website Men&uuml; Textfarbe', 'site_menue_text_color', lade_xml_einstellung('site_menue_text_color'), false);
$SettingTableItems .= table_form_string_item('Website Footerfarbe', 'site_footer_color', lade_db_einstellung('site_footer_color'), false);
#$SettingTableItems .= table_form_string_item('Website Hintergrundfarbe', 'site_background_color', lade_db_einstellung('site_background_color'), false);
$SettingTableItems .= table_form_string_item('Farbe Link Buttons', 'site_buttons_color', lade_db_einstellung('site_buttons_color'), false);
$SettingTableItems .= table_form_string_item('Farbe Error Buttons', 'site_error_buttons_color', lade_db_einstellung('site_error_buttons_color'), false);
$SettingTableItems .= table_form_string_item('Farbe Button Kalender: buchbar', 'farbe-button-kalender-buchbar', lade_db_einstellung('farbe-button-kalender-buchbar'), false);
$SettingTableItems .= table_form_string_item('Farbe Button Kalender: nicht buchbar', 'farbe-button-kalender-nicht-buchbar', lade_db_einstellung('farbe-button-kalender-nicht-buchbar'), false);
$SettingTableItems .= table_form_string_item('Farbe Button Kalender: reserviert', 'farbe-button-kalender-reserviert', lade_db_einstellung('farbe-button-kalender-reserviert'), false);
$SettingTableItems .= table_form_string_item('Hintergrundfarbe für Prompt-Karten', 'card_panel_hintergrund', lade_xml_einstellung('card_panel_hintergrund'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Farbschema', $SettingTable, 'color_lens');

#Mailkram
$SettingTableItems = table_form_string_item('Website URL', 'site_url', lade_xml_einstellung('site_url'), false);
$SettingTableItems .= table_form_string_item('Absender: Mail Adresse', 'absender_mail', lade_xml_einstellung('absender_mail'), false);
$SettingTableItems .= table_form_string_item('Absender: Name', 'absender_name', lade_xml_einstellung('absender_name'), false);
$SettingTableItems .= table_form_string_item('Reply to: Mail', 'reply_mail', lade_xml_einstellung('reply_mail'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Mailfunktion', $SettingTable, 'mail');

#SMS kram
$SettingTableItems = table_form_swich_item('SMS-Funktion aktivieren', 'sms-active', 'deaktiviert', 'aktiviert', lade_xml_einstellung('sms-active'), false);
$SettingTableItems .= table_form_string_item('Benutzername sms77', 'user-sms', lade_xml_einstellung('user-sms'), false);
$SettingTableItems .= table_form_string_item('Shared Secret sms77', 'key-sms', lade_xml_einstellung('key-sms'), false);
$SettingTableItems .= table_form_string_item('Angezeigter Absender in SMS', 'absender-sms', lade_xml_einstellung('absender-sms'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('SMS-Funktion', $SettingTable, 'sms');

#Reservierungsgedöns
$SettingTableItems = table_form_select_item('Fr&uuml;hester Verleihbeginn', 'earliest_begin', 0, 23,intval(lade_db_einstellung('earliest_begin')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Sp&auml;tester Verleihbeginn', 'latest_begin', 0, 23,intval(lade_db_einstellung('latest_begin')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Max. Kosten einer Reservierung', 'max-kosten-einer-reservierung', 0, 500,intval(lade_xml_einstellung('max-kosten-einer-reservierung')), '&euro;', '', '');
$SettingTableItems .= table_form_select_item('Max. Dauer einer Reservierung', 'max-dauer-einer-reservierung', 0, 23,intval(lade_xml_einstellung('max-dauer-einer-reservierung')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Min. Anz. Stunden die zwischen Buchung und<br>  Reservierungsbeginn liegen dürfen <br>(gilt nur für User ohne das Recht *darf last_minute*)', 'max-stunden-vor-abfahrt-buchbar', 0, 23,intval(lade_xml_einstellung('max-stunden-vor-abfahrt-buchbar')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Anzahl möglicher inaktiver Jahre<br>bis ein Nutzer automatisch<br><b>gelöscht</b> wird', 'delete-inactive-users-after-x-years', 0, 99,intval(lade_xml_einstellung('delete-inactive-users-after-x-years')), 'a', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Reservierungen', $SettingTable, 'flight');

#Rechnungserstellung
$GlovbalRechnung = lade_xml_einstellung('rechnungsfunktion_global');
$SettingTableItems = table_form_swich_item('Rechnungsanzeige global', 'rechnungsfunktion_global', 'deaktiviert', 'aktiviert', $GlovbalRechnung, false);
if($GlovbalRechnung=='on'){
    $SettingTableItems .= table_form_swich_item('Rechnungsanzeige für Wart*innen bei abgeschlossenen Reservierungen', 'rechnungsfunktion_wart', 'deaktiviert', 'aktiviert', lade_xml_einstellung('rechnungsfunktion_wart'), false);
} else {
    $SettingTableItems .= table_form_swich_item('Rechnungsanzeige für Wart*innen bei abgeschlossenen Reservierungen', 'rechnungsfunktion_wart', 'deaktiviert', 'aktiviert', lade_xml_einstellung('rechnungsfunktion_wart'), true);
}
$SettingTableItems .= table_form_html_area_item('Header für Rechnung', 'rechnungs_header', lade_xml_einstellung('rechnungs_header'), false);
$SettingTableItems .= table_form_html_area_item('Footer für Rechnung', 'rechnungs_footer', lade_xml_einstellung('rechnungs_footer'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Rechnungserstellung', $SettingTable, 'payment');

#PayPal
$GlovbalRechnung = lade_xml_einstellung('paypal-aktiv');
$SettingTableItems = table_form_swich_item('PayPal Funktion aktivieren', 'paypal-aktiv', 'deaktiviert', 'aktiviert', $GlovbalRechnung, false);
$SettingTableItems .= table_form_html_area_item('PayPal Bezahloption-Item für Reservierungen', 'paypal-text', lade_xml_einstellung('paypal-text'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('PayPal', $SettingTable, 'payment');


# Übergabekram
$SettingTableItems = table_form_select_item('Dauer einer Übergabe ca.', 'dauer-uebergabe-minuten', 0, 60,intval(lade_xml_einstellung('dauer-uebergabe-minuten')), 'min', '', '');
$SettingTableItems .= table_form_select_item('Tage die Übergabe frühestens vor Reservierungsbeginn<br>  gebucht werden darf', 'max-tage-vor-abfahrt-uebergabe', 0, 30,intval(lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe')), '', '', '');
$SettingTableItems .= table_form_select_item('Minuten die mindestens zwischen Übergabetermin und <br> Reservierungsbeginn liegen müssen', 'max-minuten-vor-abfahrt-uebergabe', 0, 60,intval(lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')), 'min', '', '');
$SettingTableItems .= table_form_select_item('Minuten die mindestens zwischen Übergabetermin und <br> Reservierungsbeginn liegen müssen', 'max-minuten-vor-abfahrt-uebergabe', 0, 60,intval(lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')), 'min', '', '');
$SettingTableItems .= table_form_select_item('Anzahl Tage ab wann zukünftige Übergaben in die Berechnung <br> der Schlüsselverfügbarkeit eines*er Wart*in einfließt', 'zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage', 0, 30,intval(lade_xml_einstellung('zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage')), '', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Übergaben', $SettingTable, 'arrow_forward');

# Spontanübergabekram
$SettingTableItems = table_form_select_item('Dropdown Reservierungen Sponatnübergabe: <br> Anz. Tage in die Zukunft', 'tage-spontanuebergabe-reservierungen-zukunft-dropdown', 0, 100,intval(lade_xml_einstellung('tage-spontanuebergabe-reservierungen-zukunft-dropdown')), '', '', '');
$SettingTableItems .= table_form_select_item('Dropdown Reservierungen Sponatnübergabe: <br> Anz. Tage in die Vergangenheit', 'tage-spontanuebergabe-reservierungen-vergangenheit-dropdown', 0, 21,intval(lade_xml_einstellung('tage-spontanuebergabe-reservierungen-vergangenheit-dropdown')), '', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Spontanübergaben', $SettingTable, 'flash_on');

# Übernahmekram
$SettingTableItems = table_form_swich_item('Übernahme-Funktion aktivieren', 'uebernahmefunktion-global-aktiv', 'deaktiviert', 'aktiviert', lade_xml_einstellung('uebernahmefunktion-global-aktiv'), false);
$SettingTableItems .= table_form_select_item('Kritischer Abstand bei Stornierung einer Übernahme', 'kritischer-abstand-storno-vor-beginn', 0, 120,intval(lade_xml_einstellung('kritischer-abstand-storno-vor-beginn')), 'min', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Übernahmen', $SettingTable, 'beach_access');

# Terminekram
$SettingTableItems = table_form_string_item('Mögliche Termintypen <br> (getrennt durch Kommata)', 'termin_typen', lade_xml_einstellung('termin_typen'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Termine', $SettingTable, 'date_range');

# Schlüsselkram
$SettingTableItems = table_form_string_item('Mögliche Schlüsselorte <br> (getrennt durch Kommata)', 'moegliche_schluesselorte', lade_xml_einstellung('moegliche_schluesselorte'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Schlüssel', $SettingTable, 'vpn_key');

# Wartinformationen
$SettingTableItems = table_form_select_item('Eigene Übergaben: <br> Anz. Wochen in die Vergangenheit', 'wochen-vergangenheit-durchgefuehrte-uebergaben', 0, 7,intval(lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-uebergaben')), '', '', '');
$SettingTableItems .= table_form_select_item('Eigene Finanztransaktionen: <br> Anz. Wochen in die Vergangenheit', 'wochen-vergangenheit-durchgefuehrte-transaktionen', 0, 7,intval(lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen')), '', '', '');
$SettingTableItems .= table_form_select_item('Erinnerungsmail an Wart eine Schlüsselübergabe nachzutragen<br>nach x Stunden', 'stunden-bis-uebergabe-eingetragen-sein-soll', 0, 23,intval(lade_xml_einstellung('stunden-bis-uebergabe-eingetragen-sein-soll')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Anzahl Tage Zukunft bei täglicher Status-Mail an Warte', 'future_daily_status_mail', 0, 10,intval(lade_xml_einstellung('future_daily_status_mail')), 'd', '', '');
$SettingTableItems .= table_form_timepicker_item('Uhrzeit Versand tägliche Status-Mail an Warte', 'soll_uhrzeit_daily_status_mail', lade_xml_einstellung('soll_uhrzeit_daily_status_mail'), false);
$SettingTableItems .= table_form_timepicker_item('Uhrzeit Versand sonntägliche Status-Mail an Warte für die kommende Woche', 'soll_uhrzeit_weekly_status_mail', lade_xml_einstellung('soll_uhrzeit_weekly_status_mail'), false);
$SettingTableItems .= table_form_html_area_item('Anleitung Kalenderabonnement Warte', 'anleitung_kalenderabo_warte', lade_xml_einstellung('anleitung_kalenderabo_warte'));

$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Wartinformationen', $SettingTable, 'info');

# Erinnerungsmails
$SettingTableItems = table_form_select_item('Erste Erinnerung eine Schlüsselübergabe auszumachen (Tage)', 'erinnerung-uebergabe-ausmachen-1', 0, 7, intval(lade_xml_einstellung('erinnerung-uebergabe-ausmachen-1')), '', '', '');
$SettingTableItems .= table_form_select_item('Zweite Erinnerung eine Schlüsselübergabe auszumachen (Tage)', 'erinnerung-uebergabe-ausmachen-2', 0, 7,intval(lade_xml_einstellung('erinnerung-uebergabe-ausmachen-2')), '', '', '');
$SettingTableItems .= table_form_select_item('Früheste Erinnerung an Schlüsselrückgabe (Tage)', 'erinnerung-schluessel-zurueckgeben-intervall-beginn', 0, 7,intval(lade_xml_einstellung('erinnerung-schluessel-zurueckgeben-intervall-beginn')), '', '', '');
$SettingTableItems .= table_form_select_item('Intervall Erinnerung an Schlüsselrückgabe (Tage)', 'erinnerung-schluessel-zurueckgeben-intervall-groesse', 0, 7,intval(lade_xml_einstellung('erinnerung-schluessel-zurueckgeben-intervall-groesse')), '', '', '');
$SettingTableItems .= table_form_select_item('Früheste Erinnerung an fällige Nachzahlung zu Reservierung (Tage)', 'erinnerung-nachzahlung-intervall-beginn', 0, 7,intval(lade_xml_einstellung('erinnerung-nachzahlung-intervall-beginn')), '', '', '');
$SettingTableItems .= table_form_select_item('Intervall Erinnerung an fällige Nachzahlung zu Reservierung (Tage)', 'erinnerung-nachzahlung-intervall-groesse', 0, 7,intval(lade_xml_einstellung('erinnerung-nachzahlung-intervall-groesse')), '', '', '');
$SettingTableItems .= table_form_select_item('Früheste Erinnerung Fehlende Geldbeträge nachzahlen (Tage)', 'zeit-tage-nach-res-ende-zahlen', 0, 30,intval(lade_xml_einstellung('zeit-tage-nach-res-ende-zahlen')), '', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Erinnerungsmails', $SettingTable, 'mail');

# Media
$SettingTableItems = table_form_string_item('Maximale Dateigröße Uploads', 'max_size_file_upload', lade_db_einstellung('max_size_file_upload'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Medien', $SettingTable, 'perm_media');

# TODOs Infotexte für User als cdata
$SettingTableItems = table_form_html_area_item('Titelinfo beim Anlegen einer neuen Reservierung', 'titelinfo-reservierung-hinzufuegen', lade_xml_einstellung('titelinfo-reservierung-hinzufuegen'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Dokumente und Nützliches*', 'inhalt-dokumente-und-nuetzliches', lade_xml_einstellung('inhalt-dokumente-und-nuetzliches'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *FAQ für User*', 'html-faq-user-hauptansicht', lade_xml_einstellung('html-faq-user-hauptansicht'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Was soll ich <br> bei der Übergabe dabei haben?*', 'text-info-uebergabe-dabei-haben', lade_db_einstellung('text-info-uebergabe-dabei-haben'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Wie läuft eine Übergabe ab?*', 'text-info-uebergabe-ablauf', lade_xml_einstellung('text-info-uebergabe-ablauf'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Einweisung bei einer Übergabe?*', 'text-info-uebergabe-einweisung', lade_xml_einstellung('text-info-uebergabe-einweisung'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Wie funktioniert eine <br> Schlüsselübernahme?*', 'erklaerung_schluesseluebernahme', lade_xml_einstellung('erklaerung_schluesseluebernahme'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Erklärung wie man <br> seine ausstehenden Zahlungen begleicht*', 'erklaerung-forderung-zahlen-user', lade_xml_einstellung('erklaerung-forderung-zahlen-user'));
$SettingTableItems .= table_form_html_area_item('Normale Bezahloptionen eines*r User*in', 'normal-payment-options', lade_xml_einstellung('normal-payment-options'));
$SettingTableItems .= table_form_html_area_item('Infotext im Loginfenster', 'hinweis_login_formular', lade_xml_einstellung('hinweis_login_formular'));

$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Infotexte für User', $SettingTable, 'info_outline');

# LoRa stuff network_check
if(lade_xml_einstellung('site_url')!='https://www.smdkahn.de'){
    $link = connect_db();
    $Anfrage = "SELECT * FROM `lora_logs` ORDER BY `lora_logs`.`id` DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    $LoraTableItems = table_form_swich_item('Rückgabeautomatik', 'schluesselrueckgabe_automat_aktiv', 'Aus', 'An', lade_xml_einstellung('schluesselrueckgabe_automat_aktiv'), false);
    $LoraTableItems .= table_form_swich_item('Mail Unterspannung Lora', 'warnung_lora_unterspannung_aktiv', 'Aus', 'An', lade_xml_einstellung('warnung_lora_unterspannung_aktiv'), false);
    $LoraTableItems .= table_form_string_item('Unterspannungsgrenze in Volt', 'batterie_spannung_untergrenze', lade_xml_einstellung('batterie_spannung_untergrenze'));

    $LoraTableItems .= table_form_string_item('RSSI-Untergrenze in dB', 'rssi_db_untergrenze', lade_xml_einstellung('rssi_db_untergrenze'));
    $LoraTableItems .= table_form_swich_item('Mail Totmann Lora', 'warnung_lora_totmann_aktiv', 'Aus', 'An', lade_xml_einstellung('warnung_lora_totmann_aktiv'), false);

    $LoraTableItems .= table_form_string_item('Erklärungstext Fehlerfreies Rückgabesystem', 'homepagetext_rueckgabeautomatik_fehlerfrei', lade_db_einstellung('homepagetext_rueckgabeautomatik_fehlerfrei'), false);
    $LoraTableItems .= table_form_string_item('Erklärungstext keine Meldung Rückgabesystem 24h', 'homepagetext_rueckgabeautomatik_24hours', lade_db_einstellung('homepagetext_rueckgabeautomatik_24hours'), false);
    $LoraTableItems .= table_form_string_item('Erklärungstext Rückgabesystem niedriger Akkustand', 'homepagetext_rueckgabeautomatik_needs_charge', lade_db_einstellung('homepagetext_rueckgabeautomatik_needs_charge'), false);
    $LoraTableItems .= table_form_string_item('Erklärungstext Rückgabesystem schlechter Empfang', 'homepagetext_rueckgabeautomatik_bad_reception', lade_db_einstellung('homepagetext_rueckgabeautomatik_bad_reception'), false);



    $SettingTable = table_builder($LoraTableItems);
    $SettingTable .= divider_builder();
    $SettingTable .= table_builder(table_row_builder(table_header_builder('Letzte Meldung Rückgabeautomat').table_data_builder($Ergebnis['timestamp'])).table_row_builder(table_header_builder('Letzte RSSI').table_data_builder($Ergebnis['db'].' dB')).table_row_builder(table_header_builder('Letzte Voltage').table_data_builder($Ergebnis['voltage'].' V')));
    $Items.=collapsible_item_builder('Rückgabeautomatik', $SettingTable, 'network_check');
}

# Wasserstandsstuff
$SettingTableItems = table_form_swich_item('Wasserstandswarnung global an/aus', 'wasserstand_global_on_off', 'Aus', 'An', lade_xml_einstellung('wasserstand_global_on_off'));
$SettingTableItems .= table_form_string_item('URL zu JS-Objekt mit den Pegelständen', 'search_URL_pegelstaende', lade_xml_einstellung('search_URL_pegelstaende'), false);
$SettingTableItems .= table_form_string_item('Überschrift Akkordeon-Element Wasserstandswesen', 'wasserstand_akkordeon_title', lade_xml_einstellung('wasserstand_akkordeon_title'), false);
$SettingTableItems .= table_form_swich_item('Automatische Sperrung an/aus', 'wasserstand_sperrungsautomatik_on_off', 'Aus', 'An', lade_xml_einstellung('wasserstand_sperrungsautomatik_on_off'), false);
$SettingTableItems .= table_form_select_item('Zeitraum einer automatischen Sperrung', 'wasserstand_sperrungsautomatik_stunden', 0, 72, lade_xml_einstellung('wasserstand_sperrungsautomatik_stunden'), 'h', 'Wasserstand Warnung Kahnsperrung', '');
$SettingTableItems .= table_form_string_item('Erklärungstext einer automatischen Sperrung', 'wasserstand_sperrungsautomatik_text', lade_db_einstellung('wasserstand_sperrungsautomatik_text'), false);
$SettingTableItems .= table_form_select_item('Wasserstand in cm ab dem eine Vorwarnung für Anfänger angezeigt wird', 'wasserstand_vorwarnung_beginner', 0, 500, lade_xml_einstellung('wasserstand_vorwarnung_beginner'), 'cm', 'Wasserstand Vorwarnung Anfänger', '');
$SettingTableItems .= table_form_select_item('Wasserstand in cm ab dem eine Vorwarnung für Erfahrene angezeigt wird', 'wasserstand_vorwarnung_erfahrene', 0, 500, lade_xml_einstellung('wasserstand_vorwarnung_erfahrene'), 'cm', 'Wasserstand Vorwarnung Erfahrene', '');
$SettingTableItems .= table_form_string_item('Erklärungstext auf Homepage wenn Wasserstand nur für Anfänger geeignet ist', 'homepagetext_wasserstand_fuer_anfaenger_geeignet', lade_db_einstellung('homepagetext_wasserstand_fuer_anfaenger_geeignet'), false);
$SettingTableItems .= table_form_string_item('Erklärungstext auf Homepage wenn Wasserstand nur für Erfahrene geeignet ist', 'homepagetext_wasserstand_fuer_erfahrene_geeignet', lade_db_einstellung('homepagetext_wasserstand_fuer_erfahrene_geeignet'), false);
$SettingTableItems .= table_form_string_item('Erklärungstext auf Homepage wenn Wasserstand für alle gesperrt werden sollte', 'homepagetext_wasserstand_sperrung_bald', lade_db_einstellung('homepagetext_wasserstand_sperrung_bald'), false);

$SettingTableItems .= table_form_swich_item('Warnmail an WartInnen bei drohendem Hochwasser an/aus', 'wasserstand_mail_wart_mode', 'Aus', 'An', lade_xml_einstellung('wasserstand_mail_wart_mode'), true);
$SettingTableItems .= table_form_select_item('Wasserstand in cm ab der Kahn gesperrt werden sollte', 'wasserstand_generelle_sperrung', 0, 500, lade_xml_einstellung('wasserstand_generelle_sperrung'), 'cm', 'Wasserstand Warnung Kahnsperrung', '');
$SettingTableItems .= table_form_select_item('Wasserstand in cm ab der Kahn automatisch gesperrt wird', 'wasserstand_generelle_sperrung_auto', 0, 500, lade_xml_einstellung('wasserstand_generelle_sperrung_auto'), 'cm', 'Wasserstand automatische Kahnsperrung', 'disabled');
$SettingTableItems .= table_form_select_item('Anzahl Messungen für Trendberechnung Wasserstand', 'anzahl_messungen_trendberechnung_wasserstand', 0, 50, lade_xml_einstellung('anzahl_messungen_trendberechnung_wasserstand'), '', 'Wasserstand Warnung Kahnsperrung', '');
$SettingTableItems .= table_form_select_item('Grenze für Relevanz Trendberechnung Wasserstand', 'grenze_trendberechnung_wasserstand', 0, 50, lade_xml_einstellung('grenze_trendberechnung_wasserstand'), 'cm', 'Wasserstand Warnung Kahnsperrung', '');
$SettingTableItems .= table_form_swich_item('Mail Wasserstandslage an User vor Reservierung schicken', 'wasserstand_mail_mode', 'Nur bei höheren Wasserständen', 'Immer', lade_xml_einstellung('wasserstand_mail_mode'));
$SettingTableItems .= table_form_select_item('Zeitpunkt Mail Wasserstandslage vor Abfahrt', 'wasserstand_mail_time', 0, 12, lade_xml_einstellung('wasserstand_mail_time'), 'h', 'Wasserstand Warnung Kahnsperrung', '');

$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Wasserstandswarnung', $SettingTable, 'show_chart');

# Pretix stuff
if(lade_xml_einstellung('site_url')!='https://www.smdkahn.de'){
    $SettingTableItems = table_form_swich_item('Pretix Widget CSS & JS einbinden', 'pretix_widget_global', 'Aus', 'An', lade_xml_einstellung('pretix_widget_global'));
    $SettingTableItems .= table_form_string_item('Pretix Widget CSS', 'pretix_widget_css', lade_xml_einstellung('pretix_widget_css'), false);
    $SettingTableItems .= table_form_string_item('Pretix Widget JS', 'pretix_widget_js', lade_xml_einstellung('pretix_widget_js'), false);

    $SettingTable = table_builder($SettingTableItems);
    $Items.=collapsible_item_builder('Pretix', $SettingTable, 'confirmation_number');
}

#Complete Settings Form
$SettingTable = section_builder(collapsible_builder($Items));
$Buttons = row_builder(form_button_builder('admin_settings_action', 'Speichern', 'action', 'send', ''));
$Buttons .= row_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', ''));
$SettingTable .= section_builder($Buttons);

$SettingForm = form_builder($SettingTable, './admin_settings.php', 'post');
$HTML .= section_builder($SettingForm);

#Put it all in a container
$HTML = container_builder($HTML, 'admin_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);

?>