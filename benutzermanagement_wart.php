<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Usermanagement - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Usermanagement</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Usermanagement</h1>';
$HTML = section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= seiteninhalt_liste_user('nachname');
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function seiteninhalt_liste_user($Sortierung){

    $HTML = "";

    //Liste generieren
    $UserIDchosen = $_GET['user'];
    if ($UserIDchosen != ""){
        $UserMetaToast = lade_user_meta($UserIDchosen);
        $HTML .= "<h5 class='center-align'>Um die Kontaktdaten von ".$UserMetaToast['vorname']." ".$UserMetaToast['nachname']." zu sehen, musst du einfach runter scrollen:)</h5>";
    }

    $Nutzergruppen = lade_alle_nutzgruppen();
    $AktuellerUser = lade_user_meta(lade_user_id());
	$link = connect_db();
	$AnfrageParser = 'SELECT id, mail FROM users';
	$AbfrageParser = mysqli_query($link, $AnfrageParser);
	$AnzahlParser = mysqli_num_rows($AbfrageParser);
	$ParserArray = array();
	for($Pcount=1;$Pcount<=$AnzahlParser;$Pcount++){
		$ErgebnisParser = mysqli_fetch_assoc($AbfrageParser);
		#var_dump($ErgebnisParser);
array_push($ParserArray, $ErgebnisParser);

	}

		#var_dump($AnzahlParser);
    #$AllUsers = get_sorted_user_array_with_user_meta_fields($Sortierung);
    benutzermanagement_parser($ParserArray);

    //Update Changes
    $AllUsers = get_sorted_user_array_with_user_meta_fields($Sortierung);
    $ListHTML = "";
    $Counter = 0;
    $GesperrtListHTML = "";
    $GesperrtCounter = "";
    foreach ($AllUsers as $User){
        if($User['mail']!=''){
            if($User['ist_gesperrt']!='true'){
                $Counter++;
                #if($Counter<4){
                $ListHTML .= listenobjekt_user_generieren($User, $UserIDchosen, $Nutzergruppen, $AktuellerUser);
                #}
            } elseif ($User['ist_gesperrt']=='true') {
                $GesperrtCounter++;
                $GesperrtListHTML .= listenobjekt_user_generieren($User, $UserIDchosen, $Nutzergruppen, $AktuellerUser);
            }
        }
    }

    $HTML .= "<h5 class='header hide-on-med-and-down center-align'>".$Counter." Aktive Nutzeraccounts</h5>";
    $HTML .= "<h5 class='header hide-on-large-only center-align'>".$Counter." Nutzeraccounts</h5>";
    $HTML .= collapsible_builder($ListHTML);

    if($GesperrtCounter>0){
        $HTML .= "<h5 class='header hide-on-med-and-down center-align'>".$GesperrtCounter." gesperrte Nutzeraccounts</h5>";
        $HTML .= "<h5 class='header hide-on-large-only center-align'>".$GesperrtCounter." gesperrte Nutzeraccounts</h5>";
        $HTML .= collapsible_builder($GesperrtListHTML);
    }

    return $HTML;
}

function listenobjekt_user_generieren($UserID, $UserIDchosen, $Nutzergruppen, $AktuellerUser){

    $UserMeta = $UserID;
    $UserID = $UserMeta['id'];
    $HTML = "";
    zeitformat();

    if ($UserID == $UserIDchosen){
        $Active = " active";
    }

    //Fetch Infos
    $AnzahleRes = count(lade_alle_reservierungen_eines_users($UserMeta['id']));
    $Registrierungsdatum = strftime("%A, %d. %B %G", strtotime($UserMeta['registrierung']));
    //Suche nach weiter möglichen Nutzergruppen
    $Nebennutzergruppen = "";
    $VerificationResult = "";
    $ShowVerifyButton = false;
    $NebennutzergruppenCounter = 0;
    foreach ($Nutzergruppen as $Nutzergruppe){
        if($UserMeta[$Nutzergruppe['name']] == 'true'){
            if($Nutzergruppe['visible_for_user'] == "false"){
                $NebennutzergruppenCounter++;
                $Nebennutzergruppen .= "- ".$Nutzergruppe['name']."  <a href='./delete_buchungstool_rolle.php?rolle=".$Nutzergruppe['name']."&user=".$UserID."'><i class=\"tiny material-icons\">delete_forever</i></a><br>";
            }
        }elseif($UserMeta['ist_nutzergruppe']==$Nutzergruppe['name']) {
            $HauptnutzergruppeID = $Nutzergruppe['id'];
            $LetzteVerifizierung = load_last_nutzergruppe_verification_user($HauptnutzergruppeID, $UserID);
            if($Nutzergruppe['req_verify'] != 'false'){
                if($Nutzergruppe['req_verify'] == 'once'){
                    $VerificationResult .= "Einmalig notwendig!<br>";
                    if(date('Y') <= date('Y', strtotime($LetzteVerifizierung['timestamp']))){
                        if($LetzteVerifizierung['erfolg'] == 'true'){
                            $VerificationResult .= "<b>Erfolgreich verifiziert!</b>";
                        } else {
                            $VerificationResult .= "<b>Verfizierung wurde abgelehnt!</b><br>";
                            $ShowVerifyButton = true;
                        }
                    } else {
                        $VerificationResult .= "<b>Noch nicht verifiziert!</b><br>";
                        $ShowVerifyButton = true;
                    }
                } elseif ($Nutzergruppe['req_verify'] == 'yearly'){
                    $VerificationResult .= "Jährlich notwendig!<br>";
                    if(date('Y') == date('Y', strtotime($LetzteVerifizierung['timestamp']))){
                        if($LetzteVerifizierung['erfolg'] == 'true'){
                            $VerificationResult .= "<b>Erfolgreich verifiziert!</b>";
                        } else {
                            $VerificationResult .= "<b>Verfizierung wurde abgelehnt!</b><br>";
                            $ShowVerifyButton = true;
                        }
                    } else {
                        $VerificationResult .= "<b>Noch nicht verifiziert!</b><br>";
                        $ShowVerifyButton = true;
                    }
                }
            } else {
                $VerificationResult .= "Nicht notwendig!";
            }
        }
    }

    if($ShowVerifyButton){
        $VerificationResult .= form_button_builder('verify_nutzergruppe_user_'.$UserID.'', 'Verifizieren', 'action', 'check');
    }

    if($NebennutzergruppenCounter==0){
        $Nebennutzergruppen .= "keine";
    }

    $BuchungstoolRollen = '';
    $BuchungstoolCounter = 0;
    if($UserMeta['ist_wart'] == 'true'){
        $BuchungstoolCounter++;
        $BuchungstoolRollen .= '- Stocherkahnwart:in <a href="./delete_buchungstool_rolle.php?rolle=ist_wart&user='.$UserID.'"><i class="tiny material-icons">delete_forever</i></a><br>';
    }
    if($UserMeta['ist_admin'] == 'true'){
        $BuchungstoolCounter++;
        $BuchungstoolRollen .= '- Admin <a href="./delete_buchungstool_rolle.php?rolle=ist_admin&user='.$UserID.'"><i class="tiny material-icons">delete_forever</i></a><br>';
    }
    if($UserMeta['ist_kasse'] == 'true'){
        $BuchungstoolCounter++;
        $BuchungstoolRollen .= '- Kassenwart*in <a href="./delete_buchungstool_rolle.php?rolle=ist_kasse&user='.$UserID.'"><i class="tiny material-icons">delete_forever</i></a><br>';
    }
    if($BuchungstoolCounter==0){
        $BuchungstoolRollen .= 'keine';
    }

    //Parse User Data
    if(isset($_POST['vorname_user_'.$UserID.''])){
        $Vorname = $_POST['vorname_user_'.$UserID.''];
    } else {
        $Vorname = $UserMeta['vorname'];
    }
    if(isset($_POST['nachname_user_'.$UserID.''])){
        $Nachname = $_POST['nachname_user_'.$UserID.''];
    } else {
        $Nachname = $UserMeta['nachname'];
    }
    if(isset($_POST['mail_user_'.$UserID.''])){
        $Mail = $_POST['mail_user_'.$UserID.''];
    } else {
        $Mail = $UserMeta['mail'];
    }
    if(isset($_POST['telefon_user_'.$UserID.''])){
        $Tel = $_POST['telefon_user_'.$UserID.''];
    } else {
        $Tel = $UserMeta['telefon'];
    }
    if(isset($_POST['strasse_user_'.$UserID.''])){
        $Strasse = $_POST['strasse_user_'.$UserID.''];
    } else {
        $Strasse = $UserMeta['strasse'];
    }
    if(isset($_POST['hausnummer_user_'.$UserID.''])){
        $Hausnummer = $_POST['hausnummer_user_'.$UserID.''];
    } else {
        $Hausnummer = $UserMeta['hausnummer'];
    }
    if(isset($_POST['stadt_user_'.$UserID.''])){
        $Stadt = $_POST['stadt_user_'.$UserID.''];
    } else {
        $Stadt = $UserMeta['stadt'];
    }
    if(isset($_POST['plz_user_'.$UserID.''])){
        $PLZ = $_POST['plz_user_'.$UserID.''];
    } else {
        $PLZ = $UserMeta['plz'];
    }


    $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header".$Active."'><i class='large material-icons'>perm_identity</i>".$UserMeta['vorname']." ".$UserMeta['nachname']."</div>";
        $HTML .= "<div class='collapsible-body'>";
                $HTMLcontainer = "<div class='container'>";
                $HTMLcontainer .= "<h5>Nutzerdaten</h5>";
                $UserTableHTML = table_form_string_item('Vorname', 'vorname_user_'.$UserID.'', $Vorname, false);
                $UserTableHTML .= table_form_string_item('Nachname', 'nachname_user_'.$UserID.'', $Nachname, false);
                $UserTableHTML .= table_row_builder(table_header_builder('Registrierung').table_data_builder($Registrierungsdatum));
                $UserTableHTML .= table_row_builder(table_header_builder('Reservierungen dieses Jahr').table_data_builder($AnzahleRes));
                $UserTableHTML .= table_form_email_item('Mail', 'mail_user_'.$UserID.'', $Mail, false);
                $UserTableHTML .= table_form_string_item('Telefon', 'telefon_user_'.$UserID.'', $Tel, false);
                $UserTableHTML .= table_form_string_item('Straße', 'strasse_user_'.$UserID.'', $Strasse, false);
                $UserTableHTML .= table_form_string_item('Hausnummer', 'hausnummer_user_'.$UserID.'', $Hausnummer, false);
                $UserTableHTML .= table_form_string_item('Stadt', 'stadt_user_'.$UserID.'', $Stadt, false);
                $UserTableHTML .= table_form_string_item('Postleitzahl', 'plz_user_'.$UserID.'', $PLZ, false);
                $HTMLcontainer .= table_builder($UserTableHTML);
                $HTMLcontainer .= divider_builder();
                $HTMLcontainer .= "<h5>Nutzergruppe(n)</h5>";
                $TableHTML = table_row_builder(table_header_builder('Hauptnutzergruppe').table_data_builder($UserMeta['ist_nutzergruppe']));
                $TableHTML .= table_row_builder(table_header_builder('Verifizierung').table_data_builder($VerificationResult));
                $TableHTML .= table_form_dropdown_nutzergruppen_waehlen('Hauptnutzergruppe ändern', 'main_usergroup_'.$UserID.'', $_POST['main_usergroup_'.$UserID.''], $Nutzergruppen,'wart_visibles');
                $TableHTML .= table_row_builder(table_header_builder('Zusätzliche Nutzergruppen').table_data_builder($Nebennutzergruppen));
                $TableHTML .= table_form_dropdown_nutzergruppen_waehlen('Zusätzliche Nutzergruppe hinzufügen', 'additional_usergroup_'.$UserID.'', $_POST['additional_usergroup_'.$UserID.''], $Nutzergruppen,'wart_unvisibles');
                if($AktuellerUser['ist_admin']=='true'){
                    $TableHTML .= table_row_builder(table_header_builder('Buchungstoolrollen').table_data_builder($BuchungstoolRollen));
                    $TableHTML .= table_row_builder(table_header_builder('Buchungstoolrolle hinzufügen').table_data_builder(dropdown_buchungstoolgruppe_waehlen('neue_buchungstoolrolle_'.$UserID.'', $_POST['neue_buchungstoolrolle_'.$UserID.''])));
                }
                if($UserMeta['ist_gesperrt']=='true'){
                    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_edit_user_'.$UserID.'', 'Bearbeiten', 'action', 'edit', '')." ".form_button_builder('action_pswd_rst_user_'.$UserID.'', 'PSWD RST', 'action', 'replay', '')).table_data_builder(form_button_builder('action_unsuspend_user_'.$UserID.'', 'Sperre aufheben', 'action', 'check', '')));
                } else {
                    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_edit_user_'.$UserID.'', 'Bearbeiten', 'action', 'edit', '')." ".form_button_builder('action_pswd_rst_user_'.$UserID.'', 'PSWD RST', 'action', 'replay', '')).table_data_builder(form_button_builder('action_suspend_user_'.$UserID.'', 'Sperren', 'action', 'block', '')));
                }
                $HTMLcontainer .= table_builder($TableHTML);
                $HTMLcontainer .= "</div>";

    $HTML .= form_builder($HTMLcontainer, '#', 'post', 'edit_user_form_'.$UserID.'');

        $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;

}

function benutzermanagement_parser($AllUsers){

    $link = connect_db();

    foreach ($AllUsers as $User){
        $EditLink = 'action_edit_user_'.$User['id'].'';
        $VerifyLink = 'verify_nutzergruppe_user_'.$User['id'].'';
        $SperrenLink = 'action_suspend_user_'.$User['id'].'';
        $SperreAufhebenLink = 'action_unsuspend_user_'.$User['id'].'';
        $PswdRstLink = 'action_pswd_rst_user_'.$User['id'].'';
        if(isset($_POST[$EditLink])) {
            update_user_meta($User['id'], 'vorname', $_POST['vorname_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'nachname', $_POST['nachname_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'mail', $_POST['mail_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'telefon', $_POST['telefon_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'strasse', $_POST['strasse_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'hausnummer', $_POST['hausnummer_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'stadt', $_POST['stadt_user_' . $User['id'] . '']);
            update_user_meta($User['id'], 'plz', $_POST['plz_user_' . $User['id'] . '']);
            if ($_POST['main_usergroup_' . $User['id'] . '']!='') {
                $NutzergruppeMeta = lade_nutzergruppe_infos($_POST['main_usergroup_' . $User['id'] . ''], 'name');
                $Setting = 'ist_nutzergruppe';
                $SettingValue = $NutzergruppeMeta['name'];
                update_user_meta($User['id'], $Setting, $SettingValue);
                nutzergruppen_verifications_user_loeschen($User['id'], $_POST[$Setting]);
            }
            if($_POST['additional_usergroup_' . $User['id'] . '']!=''){
                $NutzergruppeMeta = lade_nutzergruppe_infos($_POST['additional_usergroup_' . $User['id'] . ''],'name');
                $Setting = $NutzergruppeMeta['name'];
                $SettingValue = 'true';
                $Anfrage = "SELECT id FROM user_meta WHERE schluessel = '".$NutzergruppeMeta['name']."' AND wert = '".$SettingValue."' AND user = ".$User['id']."";
                $Abfrage = mysqli_query($link, $Anfrage);
                $Anzahl = mysqli_num_rows($Abfrage);
                if($Anzahl==0){
                    add_user_meta($User['id'], $Setting, $SettingValue);
                }
            }
            if($_POST['neue_buchungstoolrolle_' . $User['id'] . '']!=''){
                $Setting = $_POST['neue_buchungstoolrolle_' . $User['id'] . ''];
                $SettingValue = 'true';
                $Anfrage = "SELECT id FROM user_meta WHERE schluessel = '".$Setting."' AND user = ".$User['id']."";
                $Abfrage = mysqli_query($link, $Anfrage);
                $Anzahl = mysqli_num_rows($Abfrage);
                if($Anzahl==0){
                    if($Setting=='ist_wart'){
                        wartkonto_anlegen($User['id']);
                        add_user_meta($User['id'], $Setting, $SettingValue);
                    } else {
                        add_user_meta($User['id'], $Setting, $SettingValue);
                    }
                } elseif ($Anzahl==1){
                    update_user_meta($User['id'], $Setting, $SettingValue);
                }
            }
        }
        if(isset($_POST[$SperrenLink])){
            $Setting = 'ist_gesperrt';
            $SettingValue = 'true';

            $Anfrage = "SELECT id FROM user_meta WHERE schluessel = '".$Setting."' AND user = ".$User['id']."";
            $Abfrage = mysqli_query($link, $Anfrage);
            $Anzahl = mysqli_num_rows($Abfrage);
            if($Anzahl==0){
                $AlleResUser = lade_alle_reservierungen_eines_users($User['id']);
                foreach($AlleResUser as $ResUser){
                    reservierung_stornieren($ResUser['id'], lade_user_id(), 'Dein Nutzerkonto wurde gesperrt. Bitte setze dich mit uns in Verbindung um die Sache zu klären!');
                }
                return add_user_meta($User['id'], $Setting, $SettingValue);
            } elseif ($Anzahl==1){
                $AlleResUser = lade_alle_reservierungen_eines_users($User['id']);
                foreach($AlleResUser as $ResUser){
                    reservierung_stornieren($ResUser['id'], lade_user_id(), 'Dein Nutzerkonto wurde gesperrt. Bitte setze dich mit uns in Verbindung um die Sache zu klären!');
                }
                return update_user_meta($User['id'], $Setting, $SettingValue);
            }
        }
        if(isset($_POST[$SperreAufhebenLink])){
            $Setting = 'ist_gesperrt';
            $SettingValue = 'false';
            return update_user_meta($User['id'], $Setting, $SettingValue);
        }
        if(isset($_POST[$PswdRstLink])){
            $Debug = reset_user_pswd($User['mail'], 'wart');
            #var_dump($Debug);
            return $Debug;
        }
        if(isset($_POST[$VerifyLink])){
            return verify_nutzergruppe($User['id'], lade_user_id());
        }
    }
}
?>