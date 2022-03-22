<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "&Uuml;bergabewesen - " . lade_db_einstellung('site_name');
$AngebotHinzufuegenParser = terminangebot_hinzufuegen_listenelement_parser();

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Deine &Uuml;bergaben und Angebote</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">&Uuml;bergaben</h1>';
$HTML .= section_builder($PageTitle);

#Parser output
if(($AngebotHinzufuegenParser['success'] == false) OR ($AngebotHinzufuegenParser['success'] == true)){
    $HTML .= section_builder("<h3>".$AngebotHinzufuegenParser['meldung']."</h3>");
}

# Content
$HTML .= spalte_uebergaben();
$HTML .= spalte_termine();
$HTML .= spalte_uebergabeangebote();
$HTML .= spalte_vergangene_uebergaben();

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function spalte_uebergabeangebote(){

    //Grundsätzliches
    $link = connect_db();
    $Timestamp = timestamp();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Deine Terminangebote</h5>";

    $HTML .= "<div class='section'>";
    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $HTML .= terminangebot_hinzufuegen_listenelement_generieren();

    //Lade aktive Terminangebote
    $AnfrageLadeAktiveUebergabeangebote = "SELECT id FROM terminangebote WHERE bis > '$Timestamp' AND wart = '".lade_user_id()."' AND storno_user = '0' ORDER BY von ASC";
    $AbfrageLadeAktiveUebergabeangebote = mysqli_query($link, $AnfrageLadeAktiveUebergabeangebote);
    $AnzahlLadeAktiveUebergabeangebote = mysqli_num_rows($AbfrageLadeAktiveUebergabeangebote);

    if ($AnzahlLadeAktiveUebergabeangebote == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>error</i>Keine aktiven &Uuml;bergabeangebote!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeAktiveUebergabeangebote > 0){
        for ($a = 1; $a <= $AnzahlLadeAktiveUebergabeangebote; $a ++) {
            $Angebot = mysqli_fetch_assoc($AbfrageLadeAktiveUebergabeangebote);
            $HTML .= terminangebot_listenelement_generieren($Angebot['id']);
        }
    }

    $HTML .= "</ul>";
    $HTML .= "</div>";

    $HTML .= "</div>";

    return $HTML;
}
function terminangebot_hinzufuegen_listenelement_generieren(){


    //Checkbox Schalter

    if(isset($_POST['terminierung_terminangebot_anlegen'])){
        $CheckboxTermin = "checked";
    } else {
        $CheckboxTermin = "unchecked";
    }

    if(isset($_POST['terminangebot_taeglich_wiederholen'])){
        $CheckboxTaeglich = "checked";
    } else {
        $CheckboxTaeglich = "unchecked";
    }

    if(isset($_POST['terminangebot_woechentlich_wiederholen'])){
        $CheckboxWoechentlich = "checked";
    } else {
        $CheckboxWoechentlich = "unchecked";
    }

    //Bigscreen
    //DATUM UND TERMINIERUNG
    $BigscreenContent = "<h3 class='center-align'>Zeiten und Terminierung</h3>";
    $BigscreenContent .= table_form_datepicker_reservation_item('Datum', 'datum_terminangebot_anlegen', $_POST['datum_terminangebot_anlegen'], false, true, '');
    $BigscreenContent .= table_form_timepicker_item('Beginn', 'beginn_terminangebot_anlegen', $_POST['beginn_terminangebot_anlegen'], false, true, '');
    $BigscreenContent .= table_form_timepicker_item('Ende', 'ende_terminangebot_anlegen', $_POST['ende_terminangebot_anlegen'], false, true, '');
    $BigscreenContent .= table_form_swich_item('Terminierung aktivieren', 'terminierung_terminangebot_anlegen', 'Nein', 'Ja', $CheckboxTermin, false);
    $BigscreenContent .= table_form_select_item('Terminierung für', 'stunden_terminierung_terminangebot_anlegen', 1, 24, $_POST['stunden_terminierung_terminangebot_anlegen'], 'h', 'Terminierung', '', false);
    $BigscreenContent = table_builder($BigscreenContent);
    $BigscreenContent .= divider_builder();

    //ORTSANGABE
    $BigscreenContent .= "<h3 class='center-align'>Ortsangabe</h3>";
    $OrtsangabenContent = table_row_builder(table_header_builder('Ortsvorlage verwenden').table_data_builder(dropdown_vorlagen_ortsangaben('ortsangabe_terminangebot_anlegen', lade_user_id(), $_POST['ortsangabe_terminangebot_anlegen'])));
    $OrtsangabenContent .= table_form_string_item('Ortsangabe', 'ortsangabe_schriftlich_terminangebot_anlegen', $_POST['ortsangabe_schriftlich_terminangebot_anlegen'], false);
    $BigscreenContent .= table_builder($OrtsangabenContent);
    $BigscreenContent .= divider_builder();

    //KOMMENTAR
    $BigscreenContent .= "<h3 class='center-align'>Kommentar</h3>";
    $KommentarContent = table_form_string_item('Kommentar (optional)', 'kommentar_terminangebot_anlegen', $_POST['kommentar_terminangebot_anlegen'], false);
    $BigscreenContent .= table_builder($KommentarContent);
    $BigscreenContent .= divider_builder();

    //REPEAT
    $BigscreenContent .= "<h3 class='center-align'>Angebot wiederholen</h3>";
    $RepeatContent = table_form_swich_item('Täglich wiederholen', 'terminangebot_taeglich_wiederholen', 'Nein', 'Ja', $CheckboxTaeglich, false);
    $RepeatContent .= table_form_select_item('Anzahl weitere Tage', 'terminangebot_taeglich_wiederholen_tage', 1, 14, $_POST['terminangebot_taeglich_wiederholen_tage'], 'Tage', 'Terminierung', '', false);
    $RepeatContent .= table_form_swich_item('Wöchentlich wiederholen', 'terminangebot_woechentlich_wiederholen', 'Nein', 'Ja', $CheckboxWoechentlich, false);
    $RepeatContent .= table_form_select_item('Anzahl weitere Wochen', 'terminangebot_woechentlich_wiederholen_wochen', 1, 12, $_POST['terminangebot_woechentlich_wiederholen_wochen'], 'Wochen', 'Terminierung', '', false);
    $BigscreenContent .= table_builder($RepeatContent);
    $BigscreenContent .= divider_builder();

    //KNÖPFE
    $KnoepfeContent = table_row_builder(table_header_builder(form_button_builder('action_terminangebot_anlegen', 'Anlegen', 'action', 'send', '')." ".form_button_builder('reset_terminangebot_anlegen', 'Reset', 'reset', 'clear_all', '')).table_data_builder(''));
    $BigscreenContent .= table_builder($KnoepfeContent);

    $CollapsibleContent = form_builder($BigscreenContent, '#', 'post', '', '');
    $Collapsible = collapsible_item_builder('Terminangebot hinzuf&uuml;gen', $CollapsibleContent, 'note_add');

    return $Collapsible;

}
function terminangebot_hinzufuegen_listenelement_parser(){

    $Antwort['success'] = NULL;
    $Antwort['meldung'] = NULL;

    if(isset($_POST['action_terminangebot_anlegen'])) {

        //DAU
        $DAUcounter = 0;
        $DAUerror = "";

        if(($_POST['datum_terminangebot_anlegen']) == ""){
            $DAUcounter++;
            $DAUerror .= "Du musst ein Datum f&uuml;r das Terminangebot angeben!<br>";
        }

        if(!isset($_POST['beginn_terminangebot_anlegen'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Anfangszeit w&auml;hlen!<br>";
        }

        if(!isset($_POST['ende_terminangebot_anlegen'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine End-Zeit w&auml;hlen!<br>";
        }

        if(isset($_POST['terminierung_terminangebot_anlegen'])){
            if(!isset($_POST['stunden_terminierung_terminangebot_anlegen'])){
                $DAUcounter++;
                $DAUerror .= "Wenn du eine Terminierung w&uuml;nschst, musst du angeben wie viele Stunden vorher das Angebot nicht mehr angezeigt werden soll!<br>";
            }
        }

        if(isset($_POST['terminangebot_taeglich_wiederholen'])){
            if(!isset($_POST['terminangebot_taeglich_wiederholen_tage'])){
                $DAUcounter++;
                $DAUerror .= "Wenn du ein Angebot für mehrere Tage wiederholen willst, musst du angeben für wie viele Tage!<br>";
            }
        }

        if(isset($_POST['terminangebot_woechentlich_wiederholen'])){
            if(!isset($_POST['terminangebot_woechentlich_wiederholen_wochen'])){
                $DAUcounter++;
                $DAUerror .= "Wenn du ein Angebot für mehrere Wochen wiederholen willst, musst du angeben für wie viele Wochen!<br>";
            }
        }

        if (isset($_POST['terminangebot_taeglich_wiederholen']) AND isset($_POST['terminangebot_woechentlich_wiederholen'])){
            $DAUcounter++;
            $DAUerror .= "Du kannst nur entweder tage- oder wochenweises Wiederholen wählen!<br>";
        }

        if(($_POST['ortsangabe_terminangebot_anlegen'] == "") AND ($_POST['ortsangabe_schriftlich_terminangebot_anlegen'] == "")){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Angabe zum Treffpunkt geben!<br>";
        }

        if(($_POST['ortsangabe_terminangebot_anlegen'] != "") AND ($_POST['ortsangabe_schriftlich_terminangebot_anlegen'] != "")){
            $DAUcounter++;
            $DAUerror .= "Du kannst nicht eine Ortsvorlage und eine manuelle Eingabe gleichzeitig machen!<br>";
        }

        $DatumBeginn = "".$_POST['datum_terminangebot_anlegen']." ".$_POST['beginn_terminangebot_anlegen'].":00";
        $DatumEnde = "".$_POST['datum_terminangebot_anlegen']." ".$_POST['ende_terminangebot_anlegen'].":00";

        if (strtotime($DatumEnde) < strtotime($DatumBeginn)){
            $DAUcounter++;
            $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
        }

        if (strtotime($DatumBeginn) === strtotime($DatumEnde)){
            $DAUcounter++;
            $DAUerror .= "Die Zeitpunkte d&uuml;rfen nicht identisch sein!<br>";
        }

        //DAU auswerten
        if ($DAUcounter > 0){
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $DAUerror;
        } else {

            if(isset($_POST['terminangebot_taeglich_wiederholen'])){
                $TotalDays = 1 + intval($_POST['terminangebot_taeglich_wiederholen_tage']);
                $UserID = lade_user_id();
                for($a=0;$a<$TotalDays;$a++){

                    if($a>=1){
                        $TerminierungBefehl = "+ ".$a." days";
                        $DatumBeginnAdapted = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginn)));
                        $DatumEndeAdapted = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumEnde)));
                    } else {
                        $DatumBeginnAdapted = $DatumBeginn;
                        $DatumEndeAdapted = $DatumEnde;
                    }

                    if (isset($_POST['terminierung_terminangebot_anlegen'])){
                        $TerminierungBefehl = "- ".$_POST['stunden_terminierung_terminangebot_anlegen']." hours";
                        $TerminierungTimestamp = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginnAdapted)));
                    } else {
                        $TerminierungTimestamp = NULL;
                    }

                    if ($_POST['ortsangabe_terminangebot_anlegen'] != ""){
                        $Ortsangabe = $_POST['ortsangabe_terminangebot_anlegen'];
                    } else {
                        $Ortsangabe = $_POST['ortsangabe_schriftlich_terminangebot_anlegen'];
                    }

                    $Antwort = terminangebot_hinzufuegen($UserID, $DatumBeginnAdapted, $DatumEndeAdapted, $Ortsangabe, $_POST['kommentar_terminangebot_anlegen'], $TerminierungTimestamp);

                }

            } elseif (isset($_POST['terminangebot_woechentlich_wiederholen'])){
                $TotalWeeks = 1 + intval($_POST['terminangebot_woechentlich_wiederholen_wochen']);
                $UserID = lade_user_id();
                for($a=0;$a<$TotalWeeks;$a++){

                    if($a>=1){
                        $TerminierungBefehl = "+ ".$a." weeks";
                        $DatumBeginnAdapted = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginn)));
                        $DatumEndeAdapted = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumEnde)));
                    } else {
                        $DatumBeginnAdapted = $DatumBeginn;
                        $DatumEndeAdapted = $DatumEnde;
                    }

                    if (isset($_POST['terminierung_terminangebot_anlegen'])){
                        $TerminierungBefehl = "- ".$_POST['stunden_terminierung_terminangebot_anlegen']." hours";
                        $TerminierungTimestamp = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginnAdapted)));
                    } else {
                        $TerminierungTimestamp = NULL;
                    }

                    if ($_POST['ortsangabe_terminangebot_anlegen'] != ""){
                        $Ortsangabe = $_POST['ortsangabe_terminangebot_anlegen'];
                    } else {
                        $Ortsangabe = $_POST['ortsangabe_schriftlich_terminangebot_anlegen'];
                    }

                    $Antwort = terminangebot_hinzufuegen($UserID, $DatumBeginnAdapted, $DatumEndeAdapted, $Ortsangabe, $_POST['kommentar_terminangebot_anlegen'], $TerminierungTimestamp);

                }
            } else {
                if (isset($_POST['terminierung_terminangebot_anlegen'])){
                    $TerminierungBefehl = "- ".$_POST['stunden_terminierung_terminangebot_anlegen']." hours";
                    $TerminierungTimestamp = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginn)));
                } else {
                    $TerminierungTimestamp = NULL;
                }

                if ($_POST['ortsangabe_terminangebot_anlegen'] != ""){
                    $Ortsangabe = $_POST['ortsangabe_terminangebot_anlegen'];
                } else {
                    $Ortsangabe = $_POST['ortsangabe_schriftlich_terminangebot_anlegen'];
                }

                $Antwort = terminangebot_hinzufuegen(lade_user_id(), $DatumBeginn, $DatumEnde, $Ortsangabe, $_POST['kommentar_terminangebot_anlegen'], $TerminierungTimestamp);
            }

        }
    }

    return $Antwort;
}
function spalte_uebergaben(){

    //Grundsätzliches
    $link = connect_db();

    //Lade aktive Übergaben
    $AnfrageLadeAktiveUebergaben = "SELECT id FROM uebergaben WHERE durchfuehrung IS NULL AND wart = '".lade_user_id()."' AND storno_user = '0' ORDER BY beginn ASC";
    $AbfrageLadeAktiveUebergaben = mysqli_query($link, $AnfrageLadeAktiveUebergaben);
    $AnzahlLadeAktiveUebergaben = mysqli_num_rows($AbfrageLadeAktiveUebergaben);

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Deine Schl&uuml;ssel&uuml;bergaben</h5>";

    if ($AnzahlLadeAktiveUebergaben == 0){
        $HTML .= "<p class='caption'>Derzeit hast du keine aktiven Schl&uuml;ssel&uuml;bergaben! <br>";
        $HTML .= "<div class='section'>";
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";
        $HTML .= spontanuebergabe_listenelement_generieren();
        $HTML .= dokumente_listenelement_generieren();
        $HTML .= "</ul>";
        $HTML .= "</div>";

    } else if ($AnzahlLadeAktiveUebergaben > 0){
        $HTML .= "<div class='section'>";
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

        for ($a = 1; $a <= $AnzahlLadeAktiveUebergaben; $a ++){
            $Uebergabe = mysqli_fetch_assoc($AbfrageLadeAktiveUebergaben);
            $HTML .= uebergabe_listenelement_generieren($Uebergabe['id'], TRUE);
        }

        $HTML .= spontanuebergabe_listenelement_generieren();
        $HTML .= uebergabe_planen_listenelement_generieren();
        $HTML .= dokumente_listenelement_generieren();

        $HTML .= "</ul>";
        $HTML .= "</div>";
    }

    $HTML .= "</div>";

    return $HTML;
}
function spalte_termine(){

    //Grundsätzliches
    $link = connect_db();

    //Lade aktive Übergaben
    $AnfrageLadeAktiveTermine = "SELECT id FROM termine WHERE durchfuehrung IS NULL AND wart = '".lade_user_id()."' AND storno_user = '0'";
    $AbfrageLadeAktiveTermine = mysqli_query($link, $AnfrageLadeAktiveTermine);
    $AnzahlLadeAktiveTermine = mysqli_num_rows($AbfrageLadeAktiveTermine);

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Weitere Termine</h5>";

    if ($AnzahlLadeAktiveTermine == 0){
        $HTML .= "<p class='caption'>Derzeit hast du keine anstehenden Termine! <br>";
        $HTML .= collapsible_builder(collapsible_add_termin());
    } else if ($AnzahlLadeAktiveTermine > 0){
        $HTML .= "<div class='section'>";
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

        for ($a = 1; $a <= $AnzahlLadeAktiveTermine; $a ++){
            $Termin = mysqli_fetch_assoc($AbfrageLadeAktiveTermine);
            $HTML .= termin_listenelement_generieren($Termin['id']);
        }

        $HTML .= collapsible_add_termin();
        $HTML .= "</ul>";
        $HTML .= "</div>";
    }

    $HTML .= "</div>";

    return section_builder($HTML);
}
function spalte_vergangene_uebergaben(){

    $link = connect_db();
    $Limit = lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-uebergaben');
    $Grenze = date("Y-m-d G:i:s", strtotime('- '.$Limit.' weeks'));
    $Anfrage = "SELECT * FROM uebergaben WHERE durchfuehrung IS NOT NULL AND wart = ".lade_user_id()." AND storno_user = 0 AND durchfuehrung > '".$Grenze."' ORDER BY durchfuehrung DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    if($Anzahl>0){

        $CollapsibleItems = '';
        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $Schluessel = lade_schluesseldaten($Ergebnis['schluessel']);
            $Reservierung = lade_reservierung($Ergebnis['res']);
            $Forderung = lade_forderung_res($Ergebnis['res']);
            $Empfaenger = lade_user_meta($Reservierung['user']);

            $Titel = strftime("%A, %d. %B %G", strtotime($Ergebnis['durchfuehrung'])).' an '.$Empfaenger['vorname'].' '.$Empfaenger['nachname'].'';
            $BodyTable = table_row_builder(table_header_builder('Schlüssel').table_data_builder('#'.$Schluessel['id'].' '.$Schluessel['farbe'].''));
            $BodyTable .= table_row_builder(table_header_builder('Eingenommene Summe').table_data_builder(lade_gezahlte_summe_forderung($Forderung['id'])."&euro;"));
            $BodyTable .= table_row_builder(table_header_builder(button_link_creator('Storno', './undo_uebergabe.php?uebergabe='.$Ergebnis['id'].'', 'undo', '')).table_data_builder(''));
            $BodyTable = table_builder($BodyTable);

            $CollapsibleItems .= collapsible_item_builder($Titel, $BodyTable, 'forward');
        }

        $HTML = "<h5 class='header'>Vergangene Schl&uuml;ssel&uuml;bergaben der letzten ".$Limit." Wochen</h5>";
        $HTML .= collapsible_builder($CollapsibleItems);
        return section_builder($HTML);

    } else {
        return "";
    }
}
function collapsible_add_termin(){

    $Titel = "Termin hinzufügen";
    $Icon = "add_new";
    $Table = table_form_dropdown_termintyp_waehlen("Termintyp", 'type_termin', $_POST['type_termin']);
    $Table .= table_form_string_item('Eigenen Typ eingeben (optional)', 'type_termin_eigen', $_POST['type_termin_eigen']);
    $Table .= table_form_dropdown_menu_user('Nutzer', 'user_termin', $_POST['user_termin']);
    $Table .= table_form_terminangebote_user('Terminangebot wählen', 'terminangebot_add_termin', $_POST['terminangebot_add_termin']);
    $Table .= table_row_builder(table_header_builder('Den genauen Zeitpunkt wählst du dann im zweiten Schritt;)').table_data_builder(form_button_builder('add_termin', 'Hinzufügen', 'action', 'send')));
    $Content = table_builder($Table);
    $Content = form_builder($Content, './add_termin.php?mode=wart', 'post');

    return collapsible_item_builder($Titel, $Content, $Icon);

}

?>