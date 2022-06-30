<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.06.18
 * Time: 15:04
 */

#include_once "./ressourcen.php";

function lade_xml_einstellung($NameEinstellung, $mode='global'){

    if($mode == 'global'){
        $xml = @simplexml_load_file("./ressources/settings.xml");
    } elseif ($mode == 'db'){
        $xml = simplexml_load_file("./ressources/local_db_settings.xml");
    }

    if (false === $xml) {
        // throw new Exception("Cannot load xml source.\n");
        $StrValue = false;

    } else {

        $Value = $xml->$NameEinstellung;
        $StrValue = (string) $Value;
    }

    return $StrValue;

}

function update_xml_einstellung($NameEinstellung, $WertEinstellung, $mode='global'){

    $WertEinstellung = utf8_encode($WertEinstellung);

    #Catch Error when trying to save empty field
    if(strlen($WertEinstellung)==0){
        $WertEinstellung = utf8_encode(' ');
    }

    if($mode == 'global'){
        $xml = simplexml_load_file("./ressources/settings.xml");
        $xml->$NameEinstellung = $WertEinstellung;
        $xml->asXML("./ressources/settings.xml");
    } elseif ($mode == 'db'){
        $xml = simplexml_load_file("./ressources/local_db_settings.xml");
        $xml->$NameEinstellung = $WertEinstellung;
        $xml->asXML("./ressources/local_db_settings.xml");
    } elseif ($mode == 'cdata'){
        $xml = simplexml_load_file("./ressources/settings.xml");
        $Einstellung = $xml->$NameEinstellung;
        $xmlDoc = new DOMDocument();
        $xmlDoc->load("./ressources/settings.xml");
        $y=$xmlDoc->getElementsByTagName($NameEinstellung)[0];
        $cdata = $y->firstChild;
        $cdata->replaceData(0,strlen($Einstellung),utf8_decode($WertEinstellung));
        $xmlDoc->save("./ressources/settings.xml");
    }

}

function add_db_einstellung($NameEinstellung, $ValueEinstellung){

    $link = connect_db();

    if (!($stmt = $link->prepare("INSERT INTO settings (name,value) VALUES (?,?)"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("ss", $NameEinstellung, $ValueEinstellung)) {
        $Antwort['erfolg'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $Message = 'Loaded Setting '.$NameEinstellung.' from settings.xml';
        add_protocol_entry(0, $Message, 'settings');
    }

}

function lade_db_einstellung($NameEinstellung){

    $link = connect_db();

    #Try to load Setting
    $Anfrage = "SELECT * FROM settings WHERE name = '".$NameEinstellung."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        #Try to load the setting from the xml_file
        $XML = lade_xml_einstellung($NameEinstellung, $mode='global');

        if ($XML == false){
            $value = 'ERROR loading '.$NameEinstellung.'';
        } else {
            $value = $XML;
            add_db_einstellung($NameEinstellung, $value);
        }

    } elseif ($Anzahl == 1) {

        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $value = $Ergebnis['value'];

    }

    return $value;

}

function update_db_setting($Setting, $SettingValue){

    $link = connect_db();
    $CurrentSettingValue = lade_db_einstellung($Setting);

    if ($CurrentSettingValue != $SettingValue){

        if (!($stmt = $link->prepare("UPDATE settings SET value = ? WHERE name = ?"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        $SettingValue = strval($SettingValue);

        if (!$stmt->bind_param("ss", $SettingValue, $Setting)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $Message = 'Updated Setting '.$Setting.' to '.$SettingValue.'';
            add_protocol_entry(lade_user_id(), $Message, 'settings');
        }

    }

}

function slider_setting_interpreter($SettingValue){

    if ($SettingValue == ''){
        return true;
    } elseif ($SettingValue == 'on'){
        return false;
    } else{
        return true;
    }

}

### Parser Logic
function admin_db_settings_parser($SettingsArray){

    if (isset($_POST['admin_settings_action'])){

        for($x=0;$x<sizeof($SettingsArray);$x++){

            $Setting = $SettingsArray[$x];
            $SettingValue = $_POST[$Setting];

            #Remove certain HTML Tags from HTML-Textarea-Input
            $SettingValue = str_replace('<pre>','',$SettingValue);
            $SettingValue = str_replace('<code>','',$SettingValue);
            $SettingValue = str_replace('</code>','',$SettingValue);
            $SettingValue = str_replace('</pre>','',$SettingValue);

            update_db_setting($Setting, $SettingValue);

        }

        #return toast('Einstellungen erfolgreich gespeichert.');
    }

}

### Parser Logic
function admin_xml_settings_parser($SettingsArray){

    if (isset($_POST['admin_settings_action'])){

        for($x=0;$x<sizeof($SettingsArray);$x++){

            $Setting = $SettingsArray[$x];
            $SettingValue = $_POST[$Setting];

            #Remove certain HTML Tags from HTML-Textarea-Input
            $SettingValue = str_replace('<pre>','',$SettingValue);
            $SettingValue = str_replace('<code>','',$SettingValue);
            $SettingValue = str_replace('</code>','',$SettingValue);
            $SettingValue = str_replace('</pre>','',$SettingValue);

            update_xml_einstellung($Setting, $SettingValue);

        }

        #return toast('Einstellungen erfolgreich gespeichert.');
    }

}

function admin_xml_cdata_settings_parser($SettingsArray){

    if (isset($_POST['admin_settings_action'])){

        for($x=0;$x<sizeof($SettingsArray);$x++){

            $Setting = $SettingsArray[$x];
            $SettingValue = $_POST[$Setting];

            #Remove certain HTML Tags from HTML-Textarea-Input
            $SettingValue = str_replace('<pre>','',$SettingValue);
            $SettingValue = str_replace('<code>','',$SettingValue);
            $SettingValue = str_replace('</code>','',$SettingValue);
            $SettingValue = str_replace('</pre>','',$SettingValue);

            update_xml_einstellung($Setting, $SettingValue, 'cdata');

        }

        #return toast('Einstellungen erfolgreich gespeichert.');
    }

}

function user_settings_parser($SettingsArray){

    $link = connect_db();
    $UserID = lade_user_id();

    //Passwort ändern
    if (isset($_POST['action_password'])){

        //DAU
        $DAUcounter = 0;
        $DAUerror = "";

        if($_POST['password'] == ""){
            $DAUcounter++;
            $DAUerror .= "Du hast keine Eingabe im ersten Feld gemacht!<br>";
        }

        if($_POST['password_repeat'] == ""){
            $DAUcounter++;
            $DAUerror .= "Bitte wiederhole die Eingabe deines Passwortes!<br>";
        }

        if($_POST['password_repeat'] != $_POST['password']){
            $DAUcounter++;
            $DAUerror .= "Die Wiederholung des Passwortes war nicht identisch!<br>";
        }

        if(strlen($_POST['password']) < 6){
            $DAUcounter++;
            $DAUerror .= "Dein Passwort muss mindestens 6 Zeichen haben!<br>";
        }

        if ($DAUcounter > 0){
            $Antwort['success']=false;
            $Antwort['meldung']=$DAUerror;
        } else if ($DAUcounter == 0){
            $Antwort = change_pswd_user($UserID, $_POST['password'], $_POST['password_repeat']);
        }
    }

    //Konto löschen
    if (isset($_POST['action_konto'])){

        //DAU
        $DAUcounter = 0;
        $DAUerror = "";

        if (!isset($_POST['konto_validate'])){
            $DAUcounter++;
            $DAUerror = "Bitte best&auml;tige den Vorgang indem du das H&auml;kchen setzt!<br>";
        }

        if ($DAUcounter > 0){
            $Antwort['success']=false;
            $Antwort['meldung']=$DAUerror;
        } else if ($DAUcounter == 0){
            $Antwort = null;
            #$Antwort = userkonto_deaktivieren($UserID, 0, '');
        }

    }

    //User settings
    if (isset($_POST['user_settings_action'])){

        for($x=0;$x<sizeof($SettingsArray);$x++){

            $Setting = $SettingsArray[$x];

            if($Setting=='nutzergruppe'){
                if($_POST[$Setting] != ''){
                    $NutzergruppeMeta = lade_nutzergruppe_infos($_POST[$Setting]);
                    $Setting = 'ist_nutzergruppe';
                    $SettingValue = $NutzergruppeMeta['name'];
                    update_user_meta(lade_user_id(), $Setting, $SettingValue);
                    nutzergruppen_verifications_user_loeschen(lade_user_id(), $_POST[$Setting]);
                }
            } else {
                $SettingValue = $_POST[$Setting];
                update_user_meta(lade_user_id(), $Setting, $SettingValue);
            }
        }
    }

    //Wartfunktionen
    //Vorlage anlegen
    if(isset($_POST['action_wart_textkonserve_hinzufuegen'])){

        $DAUcounter = 0;
        $DAUerror = "";

        if($_POST['ortsangabe_neu'] == ""){
            $DAUcounter++;
            $DAUerror .= "Du hast keine Eingabe gemacht!<br>";
        }

        $Anfrage = "SELECT id FROM vorlagen_ortsangaben WHERE angabe = '".$_POST['ortsangabe_neu']."' AND wart = '".lade_user_id()."' AND delete_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl > 0){
            $DAUcounter++;
            $DAUerror .= "Du hast bereits eine Vorlage mit diesem Text angelegt!<br>";
        }

        if ($DAUcounter > 0){
        } else if ($DAUcounter == 0){
            ortsvorlage_anlegen(lade_user_id(), $_POST['ortsangabe_neu']);
        }
    }

    //Vorlage löschen
    if (isset($_POST['action_wart_textkonserve_loeschen'])){

        $DAUcounter = 0;
        $DAUerror = "";

        if ($_POST['ortskonserven'] == ""){
            $DAUcounter++;
            $DAUerror .= "Du hast keine zu l&ouml;schende Vorlage gewa&auml;hlt!<br>";
        }

        if ($DAUcounter > 0){
            #toast_ausgeben($DAUerror);
        } else if ($DAUcounter == 0){
            ortsvorlage_loeschen($_POST['ortskonserven']);
        }
    }

    //Persönliche Daten
    if (isset($_POST['action_wart_persoenliche_daten_aendern'])){

        $Benutzereinstellungen = lade_user_meta($UserID);

        if (isset($_POST['mail_kontaktseiten'])){

            if ($Benutzereinstellungen['mail-kontaktseite'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-kontaktseite', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail-kontaktseite'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_userinteraktion'])){

            if ($Benutzereinstellungen['mail-userinfo'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-userinfo', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail-userinfo'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['tel_kontaktseiten'])){

            if ($Benutzereinstellungen['tel-kontaktseite'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'tel-kontaktseite', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'tel-kontaktseite'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['tel_userinteraktion'])){

            if ($Benutzereinstellungen['tel-userinfo'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'tel-userinfo', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'tel-userinfo'";
            mysqli_query($link, $Anfrage);
        }
    }

    //Übergabeeinstellungen
    if (isset($_POST['action_wart_uebergaben_aendern'])){

        $Anfrage = "SELECT * FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'max_num_uebergaben_at_once'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);
        if($Anzahl==0){
            $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'max_num_uebergaben_at_once', ".$_POST['max_num_uebergaben_at_once'].")";
            mysqli_query($link, $AnfrageEinfuegen);
        } elseif ($Anzahl>0){
            $Anfrage = "UPDATE user_meta SET wert = ".$_POST['max_num_uebergaben_at_once']." WHERE user = '".lade_user_id()."' AND schluessel = 'max_num_uebergaben_at_once'";
            mysqli_query($link, $Anfrage);
        }
    }



    //Benachrichtigungen
    if (isset($_POST['action_wart_benachrichtigungen_aendern'])){

        if (isset($_POST['mail_uebergabe_erhalten'])){

            if ($Benutzereinstellungen['mail-wart-neue-uebergabe'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-wart-neue-uebergabe', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail-wart-neue-uebergabe'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_uebergabe_storno'])){

            if ($Benutzereinstellungen['mail-wart-storno-uebergabe'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-wart-storno-uebergabe', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail-wart-storno-uebergabe'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['sms_uebergabe_erhalten'])){

            if ($Benutzereinstellungen['sms-wart-neue-uebergabe'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'sms-wart-neue-uebergabe', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'sms-wart-neue-uebergabe'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['sms_uebergabe_storno'])){

            if ($Benutzereinstellungen['sms-wart-storno-uebergabe'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'sms-wart-storno-uebergabe', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'sms-wart-storno-uebergabe'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_uebernahme_kurzfristig'])){

            if ($Benutzereinstellungen['mail-kurzfristig-uebernahme-abgesagt'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-kurzfristig-uebernahme-abgesagt', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND setting = 'mail-kurzfristig-uebernahme-abgesagt'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_uebernahme_erinnerung'])){

            if ($Benutzereinstellungen['erinnerung-wart-schluesseluebergabe-eintragen'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'erinnerung-wart-schluesseluebergabe-eintragen', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'erinnerung-wart-schluesseluebergabe-eintragen'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_status_dailly'])){

            if ($Benutzereinstellungen['mail-wart-daily-update'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-wart-daily-update', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail-wart-daily-update'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_status_weekly'])){

            if ($Benutzereinstellungen['mail-wart-weekly-update'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail-wart-weekly-update', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail-wart-weekly-update'";
            mysqli_query($link, $Anfrage);
        }

        if (isset($_POST['mail_status_only_important'])){

            if ($Benutzereinstellungen['mail_status_only_important'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'mail_status_only_important', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'mail_status_only_important'";
            mysqli_query($link, $Anfrage);
        }
    }

    //Wartkalender
    if(isset($_POST['action_wart_kalenderabo_aendern'])){

        if (isset($_POST['kalenderabo_checkbox'])){

            if ($Benutzereinstellungen['kalenderabo'] != "true"){
                $AnfrageEinfuegen = "INSERT INTO user_meta (user, schluessel, wert) VALUES ('$UserID', 'kalenderabo', 'true')";
                mysqli_query($link, $AnfrageEinfuegen);
            }

        } else {
            $Anfrage = "DELETE FROM user_meta WHERE user = '".lade_user_id()."' AND schluessel = 'kalenderabo'";
            mysqli_query($link, $Anfrage);
        }
    }
	
	return $Antwort;
}

?>