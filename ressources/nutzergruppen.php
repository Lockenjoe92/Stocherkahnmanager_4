<?php

function active_nutzergruppen_form(){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT * FROM nutzergruppen WHERE delete_user = 0 ORDER BY name ASC"))) {
        $Antwort['erfolg'] = false;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
    } else {
        $res = $stmt->get_result();
        $num = mysqli_num_rows($res);
        if($num>0){
            $CollapsibleItems = "";
            for($x=1;$x<=$num;$x++){
                $NutzergruppeInfo = mysqli_fetch_assoc($res);

                $NutzergruppeInfoTableRows = table_row_builder(table_header_builder('Erklärtext für User').table_data_builder($NutzergruppeInfo['erklaertext']));
                $NutzergruppeInfoTableRows .= table_row_builder(table_header_builder('Verifikationsregel').table_data_builder($NutzergruppeInfo['req_verify']));

                if($NutzergruppeInfo['visible_for_user'] == 'true'){
                    $NutzergruppeInfoTableRows .= table_row_builder(table_header_builder('Sichtbarkeit für User').table_data_builder('Ja'));
                }
                if($NutzergruppeInfo['alle_res_gratis'] == 'true'){
                    $NutzergruppeInfoTableRows .= table_row_builder(table_header_builder('Alle User fahren gratis').table_data_builder('Ja'));
                }
                if(intval($NutzergruppeInfo['hat_freifahrten_pro_jahr']) > 0){
                    $NutzergruppeInfoTableRows .= table_row_builder(table_header_builder('Anzahl Freifahrten im Jahr').table_data_builder($NutzergruppeInfo['hat_freifahrten_pro_jahr']));
                }
                if($NutzergruppeInfo['darf_last_minute_res'] == 'true'){
                    $NutzergruppeInfoTableRows .= table_row_builder(table_header_builder('Nutzergruppe darf last Minute reservieren').table_data_builder('Ja'));
                }

                $NutzergruppeInfoInhalt = table_builder($NutzergruppeInfoTableRows);
                $NutzergruppeInfoInhalt .= divider_builder();

                //Tabelle mit aktiven Nutzern der Gruppe
                $UserStatsNutzergruppe = load_nutzergruppe_current_user_stats($NutzergruppeInfo['id']);
                $TableRows = table_row_builder(table_header_builder('Gesamtzahl User:').table_data_builder($UserStatsNutzergruppe['total']));
                if($NutzergruppeInfo['req_verify']!='false'){
                    $TableRows .= table_row_builder(table_header_builder('Davon aktuell verifiziert:').table_data_builder($UserStatsNutzergruppe['verified']));
                }

                $NutzergruppeInfoInhalt .= "<h5>Nutzerstatistik</h5>";
                $NutzergruppeInfoInhalt .= table_builder($TableRows);

                $NutzergruppeInfoInhalt .= divider_builder();

                //Tabelle mit Knöpfen
                $NutzergruppeInfoInhalt .= table_builder(table_row_builder(table_data_builder(button_link_creator('Bearbeiten', './admin_nutzergruppen.php?mode=edit_nutzergruppe&nutzergruppe='.$NutzergruppeInfo['id'].'', 'edit', '')."&nbsp;".button_link_creator('Löschen', './admin_nutzergruppen.php?mode=delete_nutzergruppe&nutzergruppe='.$NutzergruppeInfo['id'].'', 'delete_forever', ''))));

                $CollapsibleItems .= collapsible_item_builder($NutzergruppeInfo['name'], $NutzergruppeInfoInhalt, 'group');
            }

            $HTML = "<h3>Liste aktiver Nutzergruppen</h3>";
            $HTML .= collapsible_builder($CollapsibleItems);
        }else{
            $HTML = "<h3>Bislang keine Nutzergruppen angelegt!</h3>";
        }
    }

    return $HTML;
}

function add_nutzergruppe_form($parser){

    $HTML = "";

    if($parser != null){
        if($parser['erfolg'] == true){
            $HTML .= error_button_creator('Nutzergruppe erfolgreich angelegt!', 'done', '');
        } elseif($parser['erfolg'] == false) {
            $HTML .= error_button_creator($parser['meldung'], 'error_outline', '');
        }
    }

    //Convert Switch visibility
    if(isset($_POST['user_visibility'])){$SwitchPresetSichtbarkeit = 'on';}else{$SwitchPresetSichtbarkeit = 'off';}
    if(isset($_POST['alle_res_gratis'])){$SwitchPresetGratis = 'on';}else{$SwitchPresetGratis = 'off';}
    if(isset($_POST['darf_last_minute_res'])){$SwitchPresetLastMinute = 'on';}else{$SwitchPresetLastMinute = 'off';}
    if(isset($_POST['multiselect_possible'])){$SwitchPresetMulti = 'on';}else{$SwitchPresetMulti = 'off';}

    $TableHTML = table_form_string_item('Name der Nutzergruppe', 'name_nutzergruppe', $_POST['name_nutzergruppe'], false);
    $TableHTML .= table_form_string_item('Erkl&auml;render Text zur Nutzergruppe', 'erklaerung_nutzergruppe', $_POST['erklaerung_nutzergruppe'], false);
    $TableHTML .= table_form_nutzergruppe_verification_mode_select('Verifizierung der Zugeh&ouml;rigkeit', 'verification_mode', $_POST['verification_mode'], $Disabled=false, $SpecialMode='');
    $TableHTML .= table_form_swich_item('Sichtbar f&uuml;r User', 'user_visibility', 'Nein', 'Ja', $SwitchPresetSichtbarkeit, false);
    $TableHTML .= table_form_swich_item('Nutzergruppe f&auml;hrt stets gratis', 'alle_res_gratis', 'Nein', 'Ja', $SwitchPresetGratis, false);
    $TableHTML .= table_form_select_item('Nutzergruppe hat Freifahrten pro Jahr', 'hat_freifahrten_pro_jahr', 0, 12, $_POST['hat_freifahrten_pro_jahr'], '', '', '');
    $TableHTML .= table_form_swich_item('Nutzergruppe kann last Minute buchen', 'darf_last_minute_res', 'Nein', 'Ja', $SwitchPresetLastMinute, false);
    $TableHTML .= table_form_swich_item('Nutzergruppe macht neben anderen bei einem Nutzer Sinn', 'multiselect_possible', 'Nein', 'Ja', $SwitchPresetMulti, false);
    $FormHTML = section_builder(table_builder($TableHTML));

    $FormHTML .= divider_builder();

    //Kostenstaffelung
    $TableKostenstaffelungRowsHTML = "";
    $MaxKostenEinerReservierung = lade_xml_einstellung('max-kosten-einer-reservierung');
    $MaxStundenReservierungMoeglich = lade_xml_einstellung('max-dauer-einer-reservierung');;
    $FormHTML .= "<h3>Kostenstaffelung eingeben</h3><p>Nicht notwendig, wenn Nutzergruppe stets gratis fährt!</p><p>Aktuell dürfen Reservierungen nur maximal ".$MaxStundenReservierungMoeglich." Stunden am Stück betragen. Dies kannst du im Bereich der Reservierungseinstellungen ändern!</p>";
    for($a=1;$a<=intval($MaxStundenReservierungMoeglich);$a++){
        if($a==1){
            $TableKostenstaffelungRowsHTML .= table_form_select_item('Kosten für eine Stunde', 'kosten_'.$a.'_h', 0, $MaxKostenEinerReservierung, $_POST['kosten_'.$a.'_h'], '&euro;', '', '');
        } else {
            $TableKostenstaffelungRowsHTML .= table_form_select_item('Kosten für '.$a.' Stunden', 'kosten_'.$a.'_h', 0, $MaxKostenEinerReservierung, $_POST['kosten_'.$a.'_h'], '&euro;', '', '');
        }
    }
    $FormHTML .= table_builder($TableKostenstaffelungRowsHTML);

    $FormHTML .= section_builder(table_builder(table_row_builder(table_data_builder(form_button_builder('action_add_nutzergruppe', 'Anlegen', 'action', 'send')).table_data_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', '')))));
    $FormHTML = form_builder($FormHTML, 'admin_nutzergruppen.php', 'post', 'add_nutzergruppe_form', '');

    $HTML .= collapsible_builder(collapsible_item_builder('Nutzergruppe hinzufügen', $FormHTML, 'add'));
    return $HTML;
}

function add_nutzergruppe_form_parser(){

    if(isset($_POST['action_add_nutzergruppe'])) {

        ## DAU CHECKS ##
        $DAUcounter = 0;
        $DAUerror = "";

        if (empty($_POST['name_nutzergruppe'])) {
            $DAUcounter++;
            $DAUerror .= "Gib der Nutzergruppe biite einen namen!<br>";
        }

        if (empty($_POST['erklaerung_nutzergruppe'])) {
            $DAUcounter++;
            $DAUerror .= "Gib bitte einen Erklärungstext an!<br>";
        }

        if (empty($_POST['verification_mode'])) {
            $DAUcounter++;
            $DAUerror .= "Bitte wähle einen Verifizierungsmodus aus!<br>";
        }

        //Lade ID
        $link = connect_db();
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE name = ? AND delete_user = 0"))) {
            $Antwort['erfolg'] = false;
            $DAUcounter++;
        }

        if (!$stmt->bind_param("s", $_POST['name_nutzergruppe'])) {
            $Antwort['erfolg'] = false;
            $DAUcounter++;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            $DAUcounter++;
        } else {

            $res = $stmt->get_result();
            $num = mysqli_num_rows($res);
            if($num>0){
                $DAUcounter++;
                $DAUerror .= "Eine Nutzergruppe mit diesem Namen existiert bereits!<br>";
            }
        }

        ## DAU AUSWERTEN ##
        if ($DAUcounter > 0) {
            $Antwort['erfolg'] = false;
            $Antwort['meldung'] = $DAUerror;
            return $Antwort;
        } else {

            //Parse switch items
            if(isset($_POST['user_visibility'])){$SwitchPresetSichtbarkeit = 'true';}else{$SwitchPresetSichtbarkeit = 'false';}
            if(isset($_POST['alle_res_gratis'])){$SwitchPresetGratis = 'true';}else{$SwitchPresetGratis = 'false';}
            if(isset($_POST['darf_last_minute_res'])){$SwitchPresetLastMinute = 'true';}else{$SwitchPresetLastMinute = 'false';}
            if(isset($_POST['multiselect_possible'])){$SwitchPresetMulti = 'true';}else{$SwitchPresetMulti = 'false';}

            //Kostenstaffelung
            if(!isset($_POST['alle_res_gratis'])){

                $MaxStundenRes = lade_xml_einstellung('max-dauer-einer-reservierung');
                $array_kosten_pro_stunde = array();

                for($a=1;$a<=$MaxStundenRes;$a++){
                    $Operator = 'kosten_'.$a.'_h';
                    $KostenGewaehlteStunde = $_POST[$Operator];
                    $KostenDetailArray = array($Operator => $KostenGewaehlteStunde);
                    array_push($array_kosten_pro_stunde, $KostenDetailArray);
                }
            } else {

                $MaxStundenRes = lade_xml_einstellung('max-dauer-einer-reservierung');
                $array_kosten_pro_stunde = array();

                for($a=1;$a<=$MaxStundenRes;$a++){
                    $Operator = $a;
                    $KostenGewaehlteStunde = 0;
                    $KostenDetailArray = array($Operator => $KostenGewaehlteStunde);
                    array_push($array_kosten_pro_stunde, $KostenDetailArray);
                }
            }

            if(add_nutzergruppe($_POST['name_nutzergruppe'], $_POST['erklaerung_nutzergruppe'], $_POST['verification_mode'], $SwitchPresetSichtbarkeit, $SwitchPresetGratis, $_POST['hat_freifahrten_pro_jahr'], $SwitchPresetLastMinute, $SwitchPresetMulti, $array_kosten_pro_stunde)){
                $Antwort['erfolg'] = true;
                return $Antwort;
            } else {
                $Antwort['erfolg'] = false;
                $Antwort['meldung'] = 'Fehler beim Anlegen der Nutzergruppe!';
                return $Antwort;
            }
        }
    }
}

function edit_nutzergruppe($Nutzergruppe, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res, $multiselect_possible, $array_kosten_pro_stunde){

    $link = connect_db();
    if (!($stmt = $link->prepare("UPDATE nutzergruppen SET erklaertext = ?, req_verify = ?, visible_for_user = ?, alle_res_gratis = ?, hat_freifahrten_pro_jahr = ?, darf_last_minute_res = ?, multiselect_possible = ? WHERE id = ?"))){
        $Antwort['success'] = false;
        $Antwort['meldung'] = "Prepare failed: (" . $link->errno . ") " . $link->error;
    } else {
        if (!$stmt->bind_param("ssssissi", $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, intval($Anz_gratis_res), $last_minute_res, $multiselect_possible, $Nutzergruppe['id'])) {
            $Antwort['success'] = false;
            $Antwort['meldung'] = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            if (!$stmt->execute()) {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            } else {

                //Kostentabelle in nutzer_meta reinhacken
                if($Alle_res_gratis!='true'){
                    $Counter=1;
                    foreach ($array_kosten_pro_stunde as $Kosten_Stunde_Paar){
                        $Operator = 'kosten_'.$Counter.'_h';
                        $Kosten = $Kosten_Stunde_Paar[$Operator];
                        update_nutzergruppe_meta($Nutzergruppe['id'],$Operator,$Kosten);
                        $Counter++;
                    }
                }

                $Antwort['success'] = true;
            }
        }
    }

    return $Antwort;
}

function add_nutzergruppe($name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res, $multiselect_possible, $array_kosten_pro_stunde){

    $link = connect_db();
    if (!($stmt = $link->prepare("INSERT INTO nutzergruppen (name,erklaertext,req_verify,visible_for_user,alle_res_gratis,hat_freifahrten_pro_jahr,darf_last_minute_res,multiselect_possible,delete_user,delete_timestamp) VALUES (?,?,?,?,?,?,?,?,0,NULL)"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("sssssiss", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, intval($Anz_gratis_res), $last_minute_res, $multiselect_possible)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {

        //Lade ID
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE name = ? AND delete_user = 0"))) {
            $Antwort = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("s", $name)) {
            $Antwort = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            $Ergebnis = mysqli_fetch_assoc($res);

            //Kostentabelle in nutzer_meta reinhacken
            if($Alle_res_gratis!='true'){
                $Counter=1;
                foreach ($array_kosten_pro_stunde as $Kosten_Stunde_Paar){
                    $Operator = 'kosten_'.$Counter.'_h';
                    $Kosten = $Kosten_Stunde_Paar[$Operator];
                    add_nutzergruppe_meta($Ergebnis['id'], $Operator,$Kosten);
                    $Counter++;
                }
            }

            $Antwort = true;
        }
    }

    return $Antwort;
}

function add_nutzergruppe_meta($NutzergruppeID, $Schluessel, $Wert){

    $link = connect_db();
    if (!($stmt = $link->prepare("INSERT INTO nutzergruppe_meta (nutzergruppe, schluessel, wert) VALUES (?,?,?)"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("iss", $NutzergruppeID, $Schluessel, $Wert)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $Antwort = true;
    }

    return $Antwort;
}

function update_nutzergruppe_meta($NutzergruppeID, $Schluessel, $Wert){

    $link = connect_db();
    if (!($stmt = $link->prepare("UPDATE nutzergruppe_meta SET wert = ? WHERE schluessel = ? AND nutzergruppe = ?"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("ssi", $Wert, $Schluessel, $NutzergruppeID)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $Antwort = true;
    }

    return $Antwort;
}

function load_nutzergruppe_current_user_stats($IDNutzergruppe){

    $link = connect_db();
    $Antwort = null;
    $NutzergruppeInfos = lade_nutzergruppe_infos($IDNutzergruppe);

    if($NutzergruppeInfos['visible_for_user']=='true'){
        if (!($stmt = $link->prepare("SELECT user FROM user_meta WHERE schluessel = 'ist_nutzergruppe' AND wert = ?"))) {
            $Antwort = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("s", $NutzergruppeInfos['name'])) {
            $Antwort = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {

            //Lade erstmal alle User, die glauben in einer Nutzergruppe zu sein
            $res = $stmt->get_result();
            $Antwort['total'] = mysqli_num_rows($res);

            //Jetzt noch feststellen, wie viele User eigentlich verifiziert sind
            if (!($stmt = $link->prepare("SELECT id FROM nutzergruppe_verification WHERE nutzergruppe = ? AND erfolg = 'true' AND delete_user = 0"))) {
                $Antwort = false;
                echo "Prepare failed: (" . $link->errno . ") " . $link->error;
            }
            if (!$stmt->bind_param("i", $IDNutzergruppe)) {
                $Antwort = false;
                echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->execute()) {
                $Antwort = false;
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            } else {
                $res = $stmt->get_result();
                $Antwort['verified'] = mysqli_num_rows($res);
            }

            return $Antwort;
        }
    } elseif ($NutzergruppeInfos['visible_for_user']=='false'){
        $TrueString = 'true';
        if (!($stmt = $link->prepare("SELECT user FROM user_meta WHERE schluessel = ? AND wert = ?"))) {
            $Antwort = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("ss", $NutzergruppeInfos['name'], $TrueString)) {
            $Antwort = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {

            //Lade erstmal alle User, die glauben in einer Nutzergruppe zu sein
            $res = $stmt->get_result();
            $Antwort['total'] = mysqli_num_rows($res);

            //Jetzt noch feststellen, wie viele User eigentlich verifiziert sind
            if (!($stmt = $link->prepare("SELECT id FROM nutzergruppe_verification WHERE nutzergruppe = ? AND erfolg = 'true' AND delete_user = 0"))) {
                $Antwort = false;
                echo "Prepare failed: (" . $link->errno . ") " . $link->error;
            }
            if (!$stmt->bind_param("i", $IDNutzergruppe)) {
                $Antwort = false;
                echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->execute()) {
                $Antwort = false;
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            } else {
                $res = $stmt->get_result();
                $Antwort['verified'] = mysqli_num_rows($res);
            }

            return $Antwort;
        }
    }
}

function lade_nutzergruppe_infos($ID, $Mode = 'id'){

    $link = connect_db();
    if($Mode == 'id'){
        if (!($stmt = $link->prepare("SELECT * FROM nutzergruppen WHERE id = ?"))) {
            $Antwort = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("i", $ID)) {
            $Antwort = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    } elseif ($Mode == 'name'){
        if (!($stmt = $link->prepare("SELECT * FROM nutzergruppen WHERE name = ?"))) {
            $Antwort = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("s", $ID)) {
            $Antwort = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    }

    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $res = $stmt->get_result();
        $Antwort = mysqli_fetch_assoc($res);
    }

    return $Antwort;
}

function lade_nutzergruppe_meta($ID, $Key){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT wert FROM nutzergruppe_meta WHERE nutzergruppe = ? AND schluessel = ?"))) {
        $Antwort = false;
        var_dump("Prepare failed: (" . $link->errno . ") " . $link->error);
    }
    if (!$stmt->bind_param("is", $ID, $Key)) {
        $Antwort = false;
        var_dump("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        var_dump("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    } else {
        $res = $stmt->get_result();
        $Antwort = mysqli_fetch_assoc($res);
        $Antwort = $Antwort['wert'];
    }

    return $Antwort;

}

function lade_alle_nutzgruppen(){

    $link = connect_db();
    $Anfrage = "SELECT * FROM nutzergruppen WHERE delete_user = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $ReturnArray = array();
    for ($a=1;$a<=$Anzahl;$a++){
        $Nutzergruppe = mysqli_fetch_assoc($Abfrage);
        array_push($ReturnArray, $Nutzergruppe);
    }
    return $ReturnArray;
}

function form_nutzergruppe_select($ItemName, $StartValue, $Mode='normaluser', $Disabled=false, $SpecialMode=''){

    $link = connect_db();

    if($Mode=='normaluser'){    //Kein Multiselect möglich

        $HTML = "<div class='input-field' ".$SpecialMode.">";
        $HTML .= "<select id='".$ItemName."' name='".$ItemName."'>";

        if ($Disabled == false){
            $DisabledCommand = '';
        } elseif ($Disabled == true){
            $DisabledCommand = 'disabled';
        }

        if($StartValue == ''){
            $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
        } else {
            $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
        }

        //Lade alle Nutzergruppen, die für den User auswählbar sind
        //Lade ID
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE multiselect_possible = 'false' AND delete_user = 0 ORDER BY name ASC"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("sssssis", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            $Anzahl = mysqli_num_rows($res);

            #var_dump($Anzahl);

            for ($x = 1; $x <= $Anzahl; $x++) {

                $Ergebnis = mysqli_fetch_assoc($res);

                if ($StartValue == $Ergebnis['id']) {
                    $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . " selected>" . $Ergebnis['name'] . "</option>";
                } else {
                    $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . ">" . $Ergebnis['name'] . "</option>";
                }
            }

            $HTML .= "</select>";
            $HTML .= "</div>";

        }

    } elseif($Mode=='wart') {    //Multiselect möglich

        $HTML = "<div class='input-field' " . $SpecialMode . ">";
        $HTML .= "<select multiple id='" . $ItemName . "' name='" . $ItemName . "'>";

        if ($Disabled == false) {
            $DisabledCommand = '';
        } elseif ($Disabled == true) {
            $DisabledCommand = 'disabled';
        }

        if ($StartValue == '') {
            $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
        } else {
            $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
        }

        //Lade alle Nutzergruppen, die für den User auswählbar sind
        //Lade ID
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE delete_user = 0 ORDER BY name ASC"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("sssssis", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            $Anzahl = mysqli_num_rows($res);

            for ($x = 1; $x <= $Anzahl; $x++) {

                $Ergebnis = mysqli_fetch_assoc($res);
                $StartValues = explode(', ',$StartValue);

                foreach ($StartValues as $SV){
                    if ($SV == $Ergebnis['id']) {
                        $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . " selected>" . $Ergebnis['name'] . "</option>";
                    } else {
                        $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . ">" . $Ergebnis['name'] . "</option>";
                    }
                }
            }

            $HTML .= "</select>";
            $HTML .= "</div>";

        }

    }
        return $HTML;
}

function form_nutzergruppe_verification_mode_select($ItemName, $StartValue, $Disabled=false, $SpecialMode=''){

    $HTML = "<div class='input-field' ".$SpecialMode.">";
    $HTML .= "<select id='".$ItemName."' name='".$ItemName."'>";

    if ($Disabled == false){
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    if($StartValue == ''){
        $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
    } else {
        $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
    }

    $Optionen = array("false"=>"Keine", "once"=>"Einmalig", "yearly"=>"J&auml;hrlich");

    foreach($Optionen as $Option => $Value){
        if ($StartValue == $Option) {
            $HTML .= "<option value='" . $Option . "' " . $DisabledCommand . " selected>" . $Value . "</option>";
        } else {
            $HTML .= "<option value='" . $Option . "' " . $DisabledCommand . ">" . $Value . "</option>";
        }
    }

    $HTML .= "</select>";
    $HTML .= "</div>";

    return $HTML;
}

function load_last_nutzergruppe_verification_user($NutzergruppeID, $UserID){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT * FROM nutzergruppe_verification WHERE user = ? AND nutzergruppe = ? AND delete_user = 0 ORDER BY timestamp DESC"))) {
        $Antwort = false;
        var_dump("Prepare failed: (" . $link->errno . ") " . $link->error);
    }
    if (!$stmt->bind_param("ii", $UserID, $NutzergruppeID)) {
        $Antwort = false;
        var_dump("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        var_dump("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    } else {
        $res = $stmt->get_result();
        $Antwort = mysqli_fetch_assoc($res);
    }

    return $Antwort;

}

function nutzergruppen_verifications_user_loeschen($UserID, $NutzergruppeID){

    $link = connect_db();
    if (!($stmt = $link->prepare("UPDATE nutzergruppe_verification SET delete_user = ? AND delete_time = ? WHERE user = ? AND nutzergruppe = ?"))) {
        $Antwort = false;
        var_dump("Prepare failed: (" . $link->errno . ") " . $link->error);
    }
    if (!$stmt->bind_param("isii", lade_user_id(), timestamp(), $UserID, $NutzergruppeID)) {
        $Antwort = false;
        var_dump("Binding parameters nutzergruppen_verifications_user_loeschen failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        var_dump("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    } else {
        $Antwort = true;
    }
    return $Antwort;
}

function verify_nutzergruppe($User, $Eintragender, $success='true'){

    $link = connect_db();
    $UserMeta = lade_user_meta($User);
    $NutzergruppeMeta = lade_nutzergruppe_infos($UserMeta['ist_nutzergruppe'], 'name');

    $Anfrage = "INSERT INTO nutzergruppe_verification (nutzergruppe, user, erfolg, kommentar, ueberpruefer, timestamp, delete_user, delete_time) VALUES ('".$NutzergruppeMeta['id']."','".$User."','".$success."','','".$Eintragender."','".timestamp()."',0,NULL)";
    if(mysqli_query($link, $Anfrage)){
        return true;
    } else {
        return false;
    }

}

function adminrolle_loeschen($User){
    $Admins = get_sorted_user_array_with_user_meta_fields('ist_admin');
    if(count($Admins)<=1){
        return false;
    } else {
        return delete_user_meta($User, 'ist_admin', 'true');
    }
}

function wartrolle_loeschen($User){
    $Admins = get_sorted_user_array_with_user_meta_fields('ist_wart');
    if(count($Admins)<=1){
        return false;
    } else {
        return delete_user_meta($User, 'ist_wart', 'true');
    }
}

function delete_nutzergruppe_parser($IDNutzergruppe){

    if(isset($_POST['delete_nutzergruppe_'.$IDNutzergruppe.''])){
        return nutzergruppe_loeschen($IDNutzergruppe);
    } else {
        return false;
    }

}

function nutzergruppe_loeschen($IDNutzergruppe){

    $link = connect_db();
    if (!($stmt = $link->prepare("UPDATE nutzergruppen SET delete_user = ?, delete_timestamp = ? WHERE id = ?"))) {
        return false;
    }

    if (!$stmt->bind_param("isi", lade_user_id(),$IDNutzergruppe, $IDNutzergruppe)) {
        return false;
    }
    if (!$stmt->execute()) {
        return false;
    } else {
        return true;
    }

}