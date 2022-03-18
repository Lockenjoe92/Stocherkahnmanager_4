<?php

include_once "./ressources/ressourcen.php";
$Mode = $_GET['mode'];
$Reason = $_GET['reason'];
$Res = $_GET['res'];
if($Mode=='wart'){
    session_manager('ist_wart');
} elseif ($Mode=='user'){
    if($Reason=='rueckgabe'){
        if(intval($Res)>0){
            $ResInfos = lade_reservierung($Res);
            $UserID = lade_user_id();
            if($ResInfos['user']==$UserID){
                session_manager();
            } else {
                header('Location: my_reservations.php');
                die();
            }
        } else {
            header('Location: my_reservations.php');
            die();
        }
    } else {
        header('Location: my_reservations.php');
        die();
    }
} else {
    header('Location: my_reservations.php');
    die();
}

$Header = "Termin ausmachen - " . lade_db_einstellung('site_name');
$HTML = section_builder("<h1 class='center-align'>Termin ausmachen</h1>");

#ParserStuff
$Parser = add_termin_parser($Mode);

if($Parser['success']===null){
    if($Mode=='wart'){
        $HTML .= add_termin_form2($Mode);
    }elseif (($Mode=='user')AND(($Reason=='rueckgabe'))){
        $HTML .= add_geldrueckgabetermin_user_form($ResInfos, $UserID, $Parser);
    }
}elseif($Parser['success']===true){
    if($Mode=='wart'){
        $HTML .= zurueck_karte_generieren(true, $Parser['meldung'], 'termine.php');
    } elseif ($Mode=='user'){
        $HTML .= zurueck_karte_generieren(true, $Parser['meldung'], 'my_reservations.php');
    }
}elseif($Parser['success']===false){
    if($Mode=='wart'){
        $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'termine.php');
    } elseif ($Mode=='user'){
        $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'my_reservations.php');
    }
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function add_termin_parser($Mode){
    #var_dump($_POST);

    $DAUcounter = 0;
    $DAUmessage = '';
    //Checking Wartmode

    //Checking entries from first form:
    if(!isset($_POST['second_form_button'])){
        if($Mode=='wart'){
            if($_POST['type_termin']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle einen Termintyp aus!<br>';
            }
            if(($_POST['type_termin']=='andere') AND ($_POST['type_termin_eigen']=='')){
                $DAUcounter++;
                $DAUmessage .= 'Wenn du einen eigenen Termintyp anlegen willst, musst du diesem einen Namen geben!<br>';
            }
            if(($_POST['type_termin']!='andere') AND ($_POST['type_termin_eigen']!='')){
                $DAUcounter++;
                $DAUmessage .= 'Du musst hier keinen eigenen Terminnamen eintragen!<br>';
            }
            if(!isset($_POST['user_termin'])){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle einen Nutzer aus, mit dem du dich treffen willst!<br>';
            }
            if($_POST['terminangebot_add_termin']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle eines deiner Terminangebote aus, an dem du dich treffen willst!<br>';
            }
        }
    } else {
        //Check second form stuff
        if($_POST['type_termin']==''){
            $DAUcounter++;
            $DAUmessage .= 'Bitte wähle einen Termintyp aus!<br>';
        }
        if(($_POST['type_termin']=='andere') AND ($_POST['type_termin_eigen']=='')){
            $DAUcounter++;
            $DAUmessage .= 'Wenn du einen eigenen Termintyp anlegen willst, musst du diesem einen Namen geben!<br>';
        }
        if(($_POST['type_termin']!='andere') AND ($_POST['type_termin_eigen']!='')){
            $DAUcounter++;
            $DAUmessage .= 'Du musst hier keinen eigenen Terminnamen eintragen!<br>';
        }
        if(!isset($_POST['user_termin'])){
            $DAUcounter++;
            $DAUmessage .= 'Bitte wähle einen Nutzer aus, mit dem du dich treffen willst!<br>';
        }
        if($_POST['terminangebot_add_termin']==''){
            $DAUcounter++;
            $DAUmessage .= 'Bitte wähle eines deiner Terminangebote aus, an dem du dich treffen willst!<br>';
        }
        if($_POST['termin_uhrzeit_select']==''){
            $DAUcounter++;
            $DAUmessage .= 'Bitte wähle ein Zeitfenster des Terminangebots aus, an dem du dich treffen willst!<br>';
        }
        if($_POST['kommentar_termin']==''){
            $Kommentar = ' ';
        } else {
            $Kommentar = $_POST['kommentar_termin'];
        }
    }

    if($Mode=='user'){
        if(isset($_POST['user_add_rueckzahlung_first'])){
            if($_POST['terminangebot_add_termin']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle eines der Terminangebote aus, an dem du dich treffen willst!<br>';
            }
            if($_POST['res_termin_rueckzahlung']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle eine deiner Reservierungen aus, bei deinen du eine Geldrückgabe ausmachen willst!<br>';
            }
        }
        if(isset($_POST['user_add_rueckzahlung_final'])){
            if($_POST['terminangebot_add_termin']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle eines der Terminangebote aus, an dem du dich treffen willst!<br>';
            }
            if($_POST['res_termin_rueckzahlung']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle eine deiner Reservierungen aus, bei deinen du eine Geldrückgabe ausmachen willst!<br>';
            }
            if($_POST['termin_uhrzeit_select']==''){
                $DAUcounter++;
                $DAUmessage .= 'Bitte wähle ein Zeitfenster des Terminangebots aus, an dem du dich treffen willst!<br>';
            }
            if($_POST['kommentar_termin']==''){
                $Kommentar = ' ';
            } else {
                $Kommentar = $_POST['kommentar_termin'];
            }
        }
    }
    if($DAUcounter>0){
        if(isset($_POST['user_add_rueckzahlung_first'])){
            $Antwort['success'] = null;
            $Antwort['meldung'] = $DAUmessage;
        } else {
            $Antwort['success'] = false;
            $Antwort['meldung'] = $DAUmessage;
        }
    } else {
        if(!isset($_POST['second_form_button'])){
            $Antwort['success'] = null;
        } else {
            if($Mode=='wart') {
                //add andere terminangebote
                if($_POST['type_termin']=='andere'){
                    $Antwort = termin_anlegen($_POST['user_termin'], lade_user_id(), $_POST['terminangebot_add_termin'], $_POST['termin_uhrzeit_select'], $_POST['type_termin_eigen'], '', $Kommentar);
                } elseif ($_POST['type_termin']=='Geldrückgabe'){
                    $Ausgleich = lade_offene_ausgleiche_res($_POST['res_termin_rueckzahlung']);
                    $Antwort = termin_anlegen($_POST['user_termin'], lade_user_id(), $_POST['terminangebot_add_termin'], $_POST['termin_uhrzeit_select'], 'ausgleich', $Ausgleich['id'], $Kommentar);
                }
            }
        }
        if ($Mode=='user'){
            if(isset($_POST['user_add_rueckzahlung_first'])){
                $Antwort['success'] = null;
                $Antwort['meldung'] = 'Prima! Wähle jetzt bitte eine konrete Zeit zum Treffen aus:)';
            }
            if(isset($_POST['user_add_rueckzahlung_final'])){
                $Reservierung = lade_reservierung($_POST['res_termin_rueckzahlung']);
                $Terminangebot = lade_terminangebot($_POST['terminangebot_add_termin']);
                $Ausgleich = lade_offene_ausgleiche_res($Reservierung);
                $Antwort = termin_anlegen($Reservierung['user'], $Terminangebot['wart'], $_POST['terminangebot_add_termin'], $_POST['termin_uhrzeit_select'], 'ausgleich', $Ausgleich['id'], $Kommentar);
            }
        }
    }
    return $Antwort;
}

function add_termin_form2($Mode){

    zeitformat();

    if($_POST['type_termin']=='andere'){
        //Just choose the timeframe
        $Table = table_form_string_item('Termintyp', 'type_termin', 'andere', true);
        $Table .= table_form_string_item('Terminname', 'type_termin_eigen', $_POST['type_termin_eigen'], true);
        $Table .= table_form_dropdown_menu_user('User', 'user_termin', $_POST['user_termin']);
        $Angebpot = lade_terminangebot($_POST['terminangebot_add_termin']);
        $Table .= table_row_builder(table_header_builder('Infos zum Terminangebot').table_data_builder("<b>Wann?</b> ".strftime("%A, %d. %B %G: %H:%M - ", strtotime($Angebpot['von'])).strftime("%H:%M Uhr", strtotime($Angebpot['bis']))."<br><b>Wo?:</b> ".$Angebpot['ort']));
        $Table .= table_form_dropdown_terminzeitfenster_generieren('Terminzeitpunkt auswählen', 'termin_uhrzeit_select', $_POST['terminangebot_add_termin'], $_POST['termin_uhrzeit_select']);
        $Table .= table_form_string_item('Kommentar', 'kommentar_termin', $_POST['kommentar_termin'], '');
        $Table .= table_row_builder(table_header_builder(button_link_creator('Zurück', './termine.php', 'arrow_back', '')."&nbsp;".form_button_builder('second_form_button', 'Anlegen', 'action', 'send')).table_data_builder('<input type="hidden" name="type_termin_eigen" value="'.$_POST['type_termin_eigen'].'"><input type="hidden" name="type_termin" value="'.$_POST['type_termin'].'"><input type="hidden" name="terminangebot_add_termin" value="'.$_POST['terminangebot_add_termin'].'">'));
        $Table = form_builder(table_builder($Table), '#','post');
    } elseif ($_POST['type_termin']=='Geldrückgabe'){
        $Table = table_form_string_item('Termintyp', 'type_termin', 'Geldrückgabe', true);
        $Table .= table_form_dropdown_menu_user('User', 'user_termin', $_POST['user_termin']);
        $Table .= table_form_res_mit_ausgleichen('Mögliche Reservierungen', 'res_termin_rueckzahlung', $_POST['user_termin'], $_POST['res_termin_rueckzahlung']);
        $Angebpot = lade_terminangebot($_POST['terminangebot_add_termin']);
        $Table .= table_row_builder(table_header_builder('Infos zum Terminangebot').table_data_builder("<b>Wann?</b> ".strftime("%A, %d. %B %G: %H:%M - ", strtotime($Angebpot['von'])).strftime("%H:%M Uhr", strtotime($Angebpot['bis']))."<br><b>Wo?:</b> ".$Angebpot['ort']));
        $Table .= table_form_dropdown_terminzeitfenster_generieren('Terminzeitpunkt auswählen', 'termin_uhrzeit_select', $_POST['terminangebot_add_termin'], $_POST['termin_uhrzeit_select']);
        $Table .= table_form_string_item('Kommentar', 'kommentar_termin', $_POST['kommentar_termin'], '');
        $Table .= table_row_builder(table_header_builder(button_link_creator('Zurück', './termine.php', 'arrow_back', '')."&nbsp;".form_button_builder('second_form_button', 'Anlegen', 'action', 'send')).table_data_builder('<input type="hidden" name="type_termin_eigen" value="'.$_POST['type_termin_eigen'].'"><input type="hidden" name="type_termin" value="'.$_POST['type_termin'].'"><input type="hidden" name="terminangebot_add_termin" value="'.$_POST['terminangebot_add_termin'].'">'));
        $Table = form_builder(table_builder($Table), '#','post');
    }

    return $Table;
}

function add_geldrueckgabetermin_user_form($ResInfos, $UserID, $Parser){

    zeitformat();

    $Table = table_form_string_item('Termintyp', 'type_termin', 'Geldrückgabe', true);
    #$Table .= table_form_res_mit_ausgleichen('Mögliche Reservierungen für die du Geld zurückbekommen kannst', 'res_termin_rueckzahlung', $UserID, $ResInfos['id']);
    $Table .= table_form_terminangebote_fuer_termine('Termin zur Geldrückgabe wählen', 'terminangebot_add_termin', $_POST['terminangebot_add_termin']);
    if($_POST['terminangebot_add_termin']!=''){
        $Table .= table_form_dropdown_terminzeitfenster_generieren('Terminzeitpunkt auswählen', 'termin_uhrzeit_select', $_POST['terminangebot_add_termin'], $_POST['termin_uhrzeit_select']);
    } else {
        $Table .= table_row_builder(table_header_builder('Genauen Terminzeitpunkt auswählen').table_data_builder('Bitte wähle zuerst ein Terminangebot aus und klicke unten auf Bestätigen:)'));
    }
    $Table .= table_form_string_item('Kommentar', 'kommentar_termin', $_POST['kommentar_termin'], '');
    if($_POST['terminangebot_add_termin']!='') {
        $Table .= table_row_builder(table_header_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', '') . "&nbsp;" . form_button_builder('user_add_rueckzahlung_final', 'Anlegen', 'action', 'send')) . table_data_builder('<input type="hidden" name="res_termin_rueckzahlung" value="'.$ResInfos['id'].'">'));
    } else {
        $Table .= table_row_builder(table_header_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', '') . "&nbsp;" . form_button_builder('user_add_rueckzahlung_first', 'Bestätigen', 'action', 'send')) . table_data_builder('<input type="hidden" name="res_termin_rueckzahlung" value="'.$ResInfos['id'].'">'));
    }
    $HTML = "";
    $HTML .= "<h4 class='center-align'>".$Parser['meldung']."</h4>";
    $HTML .= form_builder(table_builder($Table), '#','post');

    return $HTML;
}