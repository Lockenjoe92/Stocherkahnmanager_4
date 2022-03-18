<?php

function aktuellen_mietvertrag_id_laden(){

    $link = connect_db();

    $Anfrage = "SELECT id FROM ausleihvertraege WHERE archivar = '0' ORDER BY create_time DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis['id'];
}

function mietvertrag_unterschreiben($User, $DSid){

    $link = connect_db();
    $Meta = lade_user_meta($User);
    $UserString = $Meta['vorname'].' '.$Meta['nachname'].' - '.$Meta['strasse'].''.$Meta['hausnummer'].' '.$Meta['stadt'].' '.$Meta['plz'];
    $Timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO ausleihvertrag_unterzeichnungen (vertrag, user_id, user_string, timestamp) VALUES (?,?,?,?)"))) {
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

function lade_mietvertrag($ID){

    $link = connect_db();

    if (!($stmt = $link->prepare("SELECT * FROM ausleihvertraege WHERE archivar = '0' AND id = ? ORDER BY create_time DESC"))) {
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

function user_needs_mv(){

    $link = connect_db();
    $UserID = lade_user_id();
    $AktDSEid = aktuellen_mietvertrag_id_laden();

    $Anfrage = "SELECT id FROM ausleihvertrag_unterzeichnungen WHERE vertrag = ".$AktDSEid." AND user_id = ".$UserID."";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl == 1){
        return false;
    } else {
        return true;
    }
}

function mv_anlegen_formular($Parser, $Content){

    $HTML = "<h2 class='center-align'>Neuen Mietvertrag anlegen</h2>";
    $HTML .= "<p class='center-align'>DANGER-ZONE!!! Gibst du einen neuen Mietvertrag ein, wird der andere archiviert und ALLE USER müssen ihn neu akzeptieren!<br>Das System überprüft nur, dass die Felder nicht leer sind, für den Inhalt des Mietvertrags bist du verantwortlich.<br>Vor dem tatsächlichen Eintragen generiert das System eine Vorschau deines Inputs. So kannst du dein HTML erstmal austesten:)</p>";

    if(isset($Parser['meldung'])) {
        $HTML .= "<h3 class='center-align'>" . $Parser['meldung'] . "</h3>";
    }

    if(!isset($_POST['add_mv_action'])){
        $Table = table_form_string_item('Version', 'version', $_POST['version'], false);
        $Table .= table_form_string_item('Erklärung', 'erklaerung', $_POST['erklaerung'], false);
        $Table .= table_form_html_area_item('Inhalt', 'text', $Content, false);
        $Table .= table_row_builder(table_header_builder(form_button_builder('add_mv_action', 'Überprüfen', 'action', 'send', '')).table_data_builder(''));
        $HTML .= section_builder(form_builder(table_builder($Table), '#', 'post'));
    } elseif(isset($_POST['add_mv_action'])) {
        if($Parser['success'] == TRUE){
            $Table = table_form_string_item('Version', 'version', $_POST['version'], false);
            $Table .= table_form_string_item('Erklärung', 'erklaerung', $_POST['erklaerung'], false);
            $Table .= table_form_html_area_item('Inhalt', 'text', $_POST['text'], false);
            $Table .= table_row_builder(table_header_builder(form_button_builder('add_mv_action_do_it', 'Eintragen', 'action', 'send', '')).table_data_builder(''));
            $HTML .= section_builder(form_builder(table_builder($Table), '#', 'post'));
        } elseif ($Parser['success'] == FALSE){
            $Table = table_form_string_item('Version', 'version', $_POST['version'], false);
            $Table .= table_form_string_item('Erklärung', 'erklaerung', $_POST['erklaerung'], false);
            $Table .= table_form_html_area_item('Inhalt', 'text', $_POST['text'], false);
            $Table .= table_row_builder(table_header_builder(form_button_builder('add_mv_action', 'Überprüfen', 'action', 'send', '')).table_data_builder(''));
            $HTML .= section_builder(form_builder(table_builder($Table), '#', 'post'));
        }
    }

    if(isset($_POST['add_mv_action_do_it'])){
        if($Parser['success'] == TRUE){
            $HTML .= zurueck_karte_generieren(true, 'Mietvertrag erfolgreich angelegt.', 'ausleihvertraege_admin.php');
        } else {
            $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'ausleihvertraege_admin.php');
        }
    }

    return $HTML;

}

function mv_anlegen_parser(){

    if(isset($_POST['add_mv_action'])){

        $DAUcounter = 0;
        $DAUerr = "";

        if($_POST['erklaerung'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte eine Erklärung zum Update des Mietvertrags an!<br>";
        }

        if($_POST['version'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte dem Mietvertrag eine Versionsangabe!<br>";
        }

        if($_POST['text'] == ''){
            $DAUcounter++;
            $DAUerr .= "Der Inhalt des Mietvertrags darf nicht leer sein!<br>";
        }

        if($DAUcounter == 0){
            $Antwort['success'] = true;
            $Antwort['meldung'] = 'Deine Eingaben können eingetragen werden! Überprüfe das Ergebnis in der Vorschau, bevor du den neuen Mietvertrag endgültig abspeicherst.<br>';
        } elseif ($DAUcounter > 0){
            $Antwort['success'] = false;
            $Antwort['meldung'] = $DAUerr;
        }

        return $Antwort;
    }

    if(isset($_POST['add_mv_action_do_it'])){

        $DAUcounter = 0;
        $DAUerr = "";

        if($_POST['erklaerung'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte eine Erklärung zum Update des Mietvertrages an!<br>";
        }

        if($_POST['version'] == ''){
            $DAUcounter++;
            $DAUerr .= "Gib bitte dem Mietvertrag eine Versionsangabe!<br>";
        }

        if($_POST['text'] == ''){
            $DAUcounter++;
            $DAUerr .= "Der Inhalt des Mietvertrages darf nicht leer sein!<br>";
        }

        if($DAUcounter == 0){

            $link = connect_db();
            $Anfrage = "UPDATE ausleihvertraege SET archivar = ".lade_user_id().", archiv_time = ".timestamp()." WHERE id = ".aktuellen_mietvertrag_id_laden()."";
            $Abfrage = mysqli_query($link, $Anfrage);
            $CleanedText = str_replace( '<pre><code>', '', $_POST['text']);
            $CleanedText = str_replace( '</code></pre>', '', $CleanedText);
            $Antwort = mv_anlegen($_POST['erklaerung'], $_POST['version'], $CleanedText, lade_user_id());

        } elseif ($DAUcounter > 0){
            $Antwort['success'] = false;
            $Antwort['meldung'] = $DAUerr;
        }

        return $Antwort;

    }
}

function mv_anlegen($Erklaerung, $Version, $Inhalt, $User){

    $link = connect_db();
    $timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO ausleihvertraege (version, erklaerung, inhalt, ersteller_id, create_time) VALUES (?,?,?,?,?)"))) {
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