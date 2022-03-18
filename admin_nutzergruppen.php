<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$parser = add_nutzergruppe_form_parser();

$HTML = "<h1 class='center-align'>Nutzergruppen verwalten</h1>";

//Section add nutzergruppe
if($_GET['mode']=='delete_nutzergruppe'){
    $Nutzergruppe = lade_nutzergruppe_infos($_GET['nutzergruppe']);
    $ParseDeleteNutzergruppe = delete_nutzergruppe_parser($_GET['nutzergruppe']);
    if($ParseDeleteNutzergruppe==null){
        $HTML .= prompt_karte_generieren('delete_nutzergruppe_'.$_GET['nutzergruppe'].'', 'Löschen', 'admin_nutzergruppen.php', 'Abbrechen', '<h5 class="center-align">Willst du die Nutzergruppe <b>'.$Nutzergruppe['name'].'</b> wirklich löschen?</h5>', false, '');
    } elseif ($ParseDeleteNutzergruppe==true){
        $HTML .= zurueck_karte_generieren(true,'Nutzergruppe erfolgreich gelöscht!', './admin_nutzergruppen.php');
    } elseif ($ParseDeleteNutzergruppe==false){
        $HTML .= zurueck_karte_generieren(false,'Fehler beim Löschen der Nutzergruppe!', './admin_nutzergruppen.php');
    }
} elseif ($_GET['mode']=='edit_nutzergruppe'){
    $Nutzergruppe = lade_nutzergruppe_infos($_GET['nutzergruppe']);
    $ParseEditNutzergruppe = edit_nutzergruppe_parser($Nutzergruppe);
    if($ParseEditNutzergruppe['success']===null){
        //Convert Switch visibility
        if($Nutzergruppe['visible_for_user']=='true'){$SwitchPresetSichtbarkeit = 'on';}else{$SwitchPresetSichtbarkeit = 'off';}
        if($Nutzergruppe['alle_res_gratis']=='true'){$SwitchPresetGratis = 'on';}else{$SwitchPresetGratis = 'off';}
        if($Nutzergruppe['darf_last_minute_res']=='true'){$SwitchPresetLastMinute = 'on';}else{$SwitchPresetLastMinute = 'off';}
        if($Nutzergruppe['multiselect_possible']=='true'){$SwitchPresetMulti = 'on';}else{$SwitchPresetMulti = 'off';}

        $TableHTML = table_form_string_item('Name der Nutzergruppe', 'name_nutzergruppe_edit', $Nutzergruppe['name'], true);
        $TableHTML .= table_form_html_area_item('Erkl&auml;render Text zur Nutzergruppe', 'erklaerung_nutzergruppe_edit', $Nutzergruppe['erklaertext'], false);
        $TableHTML .= table_form_nutzergruppe_verification_mode_select('Verifizierung der Zugeh&ouml;rigkeit', 'verification_mode_edit', $Nutzergruppe['req_verify'], $Disabled=false, $SpecialMode='');
        $TableHTML .= table_form_swich_item('Sichtbar f&uuml;r User', 'user_visibility_edit', 'Nein', 'Ja', $SwitchPresetSichtbarkeit, false);
        $TableHTML .= table_form_swich_item('Nutzergruppe f&auml;hrt stets gratis', 'alle_res_gratis_edit', 'Nein', 'Ja', $SwitchPresetGratis, false);
        $TableHTML .= table_form_select_item('Nutzergruppe hat Freifahrten pro Jahr', 'hat_freifahrten_pro_jahr_edit', 0, 12, $Nutzergruppe['anz_freifahrten'], '', '', '');
        $TableHTML .= table_form_swich_item('Nutzergruppe kann last Minute buchen', 'darf_last_minute_res_edit', 'Nein', 'Ja', $SwitchPresetLastMinute, false);
        $TableHTML .= table_form_swich_item('Nutzergruppe macht neben anderen bei einem Nutzer Sinn', 'multiselect_possible_edit', 'Nein', 'Ja', $SwitchPresetMulti, false);
        $FormHTML = section_builder(table_builder($TableHTML));

        $FormHTML .= divider_builder();

        //Kostenstaffelung
        $TableKostenstaffelungRowsHTML = "";
        $MaxKostenEinerReservierung = lade_xml_einstellung('max-kosten-einer-reservierung');
        $MaxStundenReservierungMoeglich = lade_xml_einstellung('max-dauer-einer-reservierung');;
        $FormHTML .= "<h3 class='center-align'>Kostenstaffelung eingeben</h3><p class='center-align'>Nicht notwendig, wenn Nutzergruppe stets gratis fährt!</p><p class='center-align'>Aktuell dürfen Reservierungen nur maximal ".$MaxStundenReservierungMoeglich." Stunden am Stück betragen. Dies kannst du im Bereich der Reservierungseinstellungen ändern!</p>";
        for($a=1;$a<=intval($MaxStundenReservierungMoeglich);$a++){
            $Operator = 'kosten_'.$a.'_h';
            $Kosten = intval(lade_nutzergruppe_meta($Nutzergruppe['id'], $Operator));
            if($a==1){
                $TableKostenstaffelungRowsHTML .= table_form_select_item('Kosten für eine Stunde', 'kosten_'.$a.'_h_edit', 0, $MaxKostenEinerReservierung, $Kosten, '&euro;', '', '');
            } else {
                $TableKostenstaffelungRowsHTML .= table_form_select_item('Kosten für '.$a.' Stunden', 'kosten_'.$a.'_h_edit', 0, $MaxKostenEinerReservierung, $Kosten, '&euro;', '', '');
            }
        }
        $FormHTML .= table_builder($TableKostenstaffelungRowsHTML);

        $FormHTML .= section_builder(table_builder(table_row_builder(table_data_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', '')).table_data_builder(form_button_builder('action_edit_nutzergruppe', 'Bearbeiten', 'action', 'send')))));
        $FormHTML = form_builder($FormHTML, './admin_nutzergruppen.php?mode=edit_nutzergruppe&nutzergruppe='.$_GET['nutzergruppe'], 'post', 'edit_nutzergruppe_form', '');
        $HTML .= $FormHTML;

        #$HTML .= prompt_karte_generieren('delete_nutzergruppe_'.$_GET['nutzergruppe'].'', 'Löschen', 'admin_nutzergruppen.php', 'Abbrechen', '<h5 class="center-align">Willst du die Nutzergruppe <b>'.$Nutzergruppe['name'].'</b> wirklich löschen?</h5>', false, '');
    } elseif ($ParseEditNutzergruppe['success']===true){
        $HTML .= zurueck_karte_generieren(true,'Nutzergruppe erfolgreich bearbeitet!', './admin_nutzergruppen.php');
    } elseif ($ParseEditNutzergruppe['success']===false){
        $HTML .= zurueck_karte_generieren(false,$ParseEditNutzergruppe['meldung'], './admin_nutzergruppen.php?mode=edit_nutzergruppe&nutzergruppe='.$_GET['nutzergruppe']);
    }

} else {
    $HTML .= active_nutzergruppen_form();
}

$HTML .= "<h3>Weitere Funktionen</h3>";
$HTML .= add_nutzergruppe_form($parser);

# Output site
echo site_header($Header);
echo site_body(container_builder($HTML));


function edit_nutzergruppe_parser($Nutzergruppe){

    $Antwort['success']=null;

    if(isset($_POST['action_edit_nutzergruppe'])){

        ## DAU CHECKS ##
        $DAUcounter = 0;
        $DAUerror = "";

        if (empty($_POST['erklaerung_nutzergruppe_edit'])) {
            $DAUcounter++;
            $DAUerror .= "Gib bitte einen Erklärungstext an!<br>";
        }

        if (empty($_POST['verification_mode_edit'])) {
            $DAUcounter++;
            $DAUerror .= "Bitte wähle einen Verifizierungsmodus aus!<br>";
        }

        ## DAU AUSWERTEN ##
        if ($DAUcounter > 0) {
            $Antwort['erfolg'] = false;
            $Antwort['meldung'] = $DAUerror;
            return $Antwort;
        } else {

            //Parse switch items
            if(isset($_POST['user_visibility_edit'])){$SwitchPresetSichtbarkeit = 'true';}else{$SwitchPresetSichtbarkeit = 'false';}
            if(isset($_POST['alle_res_gratis_edit'])){$SwitchPresetGratis = 'true';}else{$SwitchPresetGratis = 'false';}
            if(isset($_POST['darf_last_minute_res_edit'])){$SwitchPresetLastMinute = 'true';}else{$SwitchPresetLastMinute = 'false';}
            if(isset($_POST['multiselect_possible_edit'])){$SwitchPresetMulti = 'true';}else{$SwitchPresetMulti = 'false';}

            //Kostenstaffelung
            if(!isset($_POST['alle_res_gratis_edit'])){

                $MaxStundenRes = lade_xml_einstellung('max-dauer-einer-reservierung');
                $array_kosten_pro_stunde = array();

                for($a=1;$a<=$MaxStundenRes;$a++){
                    $Operator = 'kosten_'.$a.'_h';
                    $KostenGewaehlteStunde = $_POST[$Operator.'_edit'];
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

            return edit_nutzergruppe($Nutzergruppe, $_POST['erklaerung_nutzergruppe_edit'], $_POST['verification_mode_edit'], $SwitchPresetSichtbarkeit, $SwitchPresetGratis, $_POST['hat_freifahrten_pro_jahr_edit'], $SwitchPresetLastMinute, $SwitchPresetMulti, $array_kosten_pro_stunde);

        }


    }

    return $Antwort;
}