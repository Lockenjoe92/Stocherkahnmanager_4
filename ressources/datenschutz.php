<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 22.11.18
 * Time: 13:00
 */

function ds_anlegen_formular($Parser, $Content){

    $HTML = "<p class='center-align'>DANGER-ZONE!!! Gibst du eine neue DSE ein, wird die andere archiviert und ALLE USER müssen sie neu akzeptieren!<br>Das System überprüft nur, dass die Felder nicht leer sind, für den Inhalt der DSE bist du verantwortlich.<br>Vor dem tatsächlichen Eintragen generiert das System eine Vorschau deines Inputs. So kannst du dein HTML erstmal austesten:)</p>";

    if(isset($Parser['meldung'])) {
        $HTML .= "<h3 class='center-align'>" . $Parser['meldung'] . "</h3>";
    }

    if(!isset($_POST['add_dse_action'])){
        $Table = table_form_string_item('Version', 'version', $_POST['version'], false);
        $Table .= table_form_string_item('Erklärung', 'erklaerung', $_POST['erklaerung'], false);
        $Table .= table_form_html_area_item('Inhalt', 'text', "<pre><code>".$Content."</code></pre>", false);
        $Table .= table_row_builder(table_header_builder(form_button_builder('add_dse_action', 'Überprüfen', 'action', 'send', '')).table_data_builder(''));
        $HTML .= section_builder(form_builder(table_builder($Table), '#', 'post'));
    } elseif(isset($_POST['add_dse_action'])) {
        if($Parser['success'] == TRUE){
            $Table = table_form_string_item('Version', 'version', $_POST['version'], false);
            $Table .= table_form_string_item('Erklärung', 'erklaerung', $_POST['erklaerung'], false);
            $Table .= table_form_html_area_item('Inhalt', 'text', $_POST['text'], false);
            $Table .= table_row_builder(table_header_builder(form_button_builder('add_dse_action_do_it', 'Eintragen', 'action', 'send', '')."&nbsp;".form_button_builder('add_dse_action', 'Überprüfen', 'action', 'send', '')).table_data_builder(''));
            $HTML .= section_builder(form_builder(table_builder($Table), '#', 'post'));
        } elseif ($Parser['success'] == FALSE){
            $Table = table_form_string_item('Version', 'version', $_POST['version'], false);
            $Table .= table_form_string_item('Erklärung', 'erklaerung', $_POST['erklaerung'], false);
            $Table .= table_form_html_area_item('Inhalt', 'text', $_POST['text'], false);
            $Table .= table_row_builder(table_header_builder(form_button_builder('add_dse_action', 'Überprüfen', 'action', 'send', '')).table_data_builder(''));
            $HTML .= section_builder(form_builder(table_builder($Table), '#', 'post'));
        }
    }

    if(isset($_POST['add_dse_action_do_it'])){
        if($Parser['success'] == TRUE){
            $HTML .= zurueck_karte_generieren(true, 'Datenschutzerklärung erfolgreich angelegt.', 'datenschutzerklaerungen.php');
        } else {
            $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'datenschutzerklaerungen.php');
        }
    }

    return $HTML;

}

function ds_anlegen_parser(){

    if(isset($_POST['add_dse_action'])){

        $DAUcounter = 0;
        $DAUerr = "";

        if($_POST['erklaerung'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte eine Erklärung zum Update der DSE an!<br>";
        }

        if($_POST['version'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte der DSE eine Versionsangabe!<br>";
        }

        if($_POST['text'] == ''){
            $DAUcounter++;
            $DAUerr .= "Der Inhalt der DSE darf nicht leer sein!<br>";
        }

        if($DAUcounter == 0){
            $Antwort['success'] = true;
            $Antwort['meldung'] = 'Deine Eingaben können eingetragen werden! Überprüfe das Ergebnis in der Vorschau, bevor du die neue DSE endgültig abspeicherst.<br>';
        } elseif ($DAUcounter > 0){
            $Antwort['success'] = false;
            $Antwort['meldung'] = $DAUerr;
        }

        return $Antwort;
    }

    if(isset($_POST['add_dse_action_do_it'])){

        $DAUcounter = 0;
        $DAUerr = "";

        if($_POST['erklaerung'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte eine Erklärung zum Update der DSE an!<br>";
        }

        if($_POST['version'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte der DSE eine Versionsangabe!<br>";
        }

        if($_POST['text'] == ''){
            $DAUcounter++;
            $DAUerr .= "Der Inhalt der DSE darf nicht leer sein!<br>";
        }

        if($DAUcounter == 0){

            $link = connect_db();
            $Anfrage = "UPDATE datenschutzerklaerungen SET archivar = ".lade_user_id().", archiv_time = ".timestamp()." WHERE id = ".aktuelle_ds_id_laden()."";
            $Abfrage = mysqli_query($link, $Anfrage);
            $CleanedText = str_replace( '<pre><code>', '', $_POST['text']);
            $CleanedText = str_replace( '</code></pre>', '', $CleanedText);
            $Antwort = ds_anlegen($_POST['erklaerung'], $_POST['version'], $CleanedText, lade_user_id());

      } elseif ($DAUcounter > 0){
            $Antwort['success'] = false;
            $Antwort['meldung'] = $DAUerr;
        }

        return $Antwort;

    }
}

function ds_anlegen($Erklaerung, $Version, $Inhalt, $User){

    $link = connect_db();
    $timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO datenschutzerklaerungen (version, erklaerung, inhalt, ersteller, create_time) VALUES (?,?,?,?,?)"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        return $Antwort['success'] = false;
    }

    if (!$stmt->bind_param("sssis",$Version, $Erklaerung, $Inhalt, $User, $timestamp)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['success'] = false;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['success'] = false;
    } else {
        $Antwort['meldung'] = 'Anlegen erfolgreich!';
        $Antwort['success'] = true;
        return $Antwort;
    }

}

function aktuelle_ds_id_laden(){

    $link = connect_db();

    $Anfrage = "SELECT id FROM datenschutzerklaerungen WHERE archivar = '0' ORDER BY create_time DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis['id'];
}

function lade_ds($ID){

    $link = connect_db();

    if (!($stmt = $link->prepare("SELECT * FROM datenschutzerklaerungen WHERE archivar = '0' AND id = ? ORDER BY create_time DESC"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        return $Antwort['erfolg'] = false;
    }

    if (!$stmt->bind_param("i",$ID)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['erfolg'] = false;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['erfolg'] = false;
    } else {
        $res = $stmt->get_result();
        $num_results = mysqli_fetch_assoc($res);

        return $num_results;
    }
}

function ds_unterschreiben($User, $DSid){

    $link = connect_db();
    $Meta = lade_user_meta($User);
    $UserString = $Meta['vorname'].' '.$Meta['nachname'];
    $Timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO ds_unterzeichnungen (ds_id, user_id, user_string, timestamp) VALUES (?,?,?,?)"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("iiss",$DSid, $User, $UserString, $Timestamp)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        return true;
    }

}

function ds_unterschreiben_formular_parts(){

    $link = connect_db();
    if(isset($_POST['ds'])){$Checked='checked';}else{$Checked='unchecked';}

    $Anfrage = "SELECT erklaerung, inhalt FROM datenschutzerklaerungen WHERE archivar = '0' ORDER BY create_time DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    $HTML = "<h3>Datenschutzerkl&auml;rung</h3>";
    $HTML .= "<p>Zur Info:<br>".$Ergebnis['erklaerung']."</p>";
    $HTML .= "<p>".$Ergebnis['inhalt']."</p>";
    $HTML .= " <p><label><input type='checkbox' name='ds' id='ds' checked='".$Checked."'><span>Ich stimme den Nutzungsbedingungen, sowie der Speicherung und Verarbeitung gem&auml;&szlig; der Datenschutzerkl&auml;rung zu.</span></label></p>";

    return $HTML;

}

function user_needs_dse(){

    $link = connect_db();
    $UserID = lade_user_id();
    $AktDSEid = aktuelle_ds_id_laden();

    $Anfrage = "SELECT id FROM ds_unterzeichnungen WHERE ds_id = ".$AktDSEid." AND user_id = ".$UserID."";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl == 1){
        return false;
    } else {
        return true;
    }
}

function needs_dse_mv_update(){
    $ID = lade_user_id();
    if(user_needs_dse()){
        header("Location: ./renew_dse_mv.php?mode=dse");
        die();
    }
    if(user_needs_pswd_change($ID)){
        header("Location: ./renew_dse_mv.php?mode=pswd");
        die();
    }
    $UserMeta=lade_user_meta($ID);
    if($UserMeta['strasse']==''){
        header("Location: ./renew_dse_mv.php?mode=addresse");
        die();
    }
    if(user_needs_mv()) {
        header("Location: ./renew_dse_mv.php?mode=mv");
        die();
    }
}