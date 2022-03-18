<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Mode = $_GET['mode'];
if($Mode == 'dse'){
    $Erklaerungheader = 'Datenschutzerklärung';
} elseif ($Mode == 'mv'){
    $Erklaerungheader = 'Ausleihvertrag';
} elseif ($Mode == 'pswd'){
    $Erklaerungheader = 'Passwort';
} elseif ($Mode == 'addresse'){
    $Erklaerungheader = 'Addresse';
} else {
    header('Location: ./index.php');
    die();
}

$Parser = renew_dse_mv_parser($Mode);
#var_dump($Parser);
$Header = $Erklaerungheader." erneuern - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
if($Mode == 'dse'){
    $PageTitle = '<h1 class="center-align hide-on-med-and-down">Die '.$Erklaerungheader.' hat sich erneuert</h1>';
    $PageTitle .= '<h1 class="center-align hide-on-large-only">'.$Erklaerungheader.' hat sich erneuert</h1>';
} elseif ($Mode == 'mv'){
    $PageTitle = '<h1 class="center-align hide-on-med-and-down">Der '.$Erklaerungheader.' hat sich erneuert</h1>';
    $PageTitle .= '<h1 class="center-align hide-on-large-only">'.$Erklaerungheader.' hat sich erneuert</h1>';
} elseif ($Mode == 'pswd'){
    $PageTitle = '<h1 class="center-align hide-on-med-and-down">'.$Erklaerungheader.' ändern</h1>';
    $PageTitle .= '<h1 class="center-align hide-on-large-only">'.$Erklaerungheader.' ändern</h1>';
    $Erklaerungheader = "Nachdem dein Passwort zurückgesetzt wurde, musst du nun ein eigenes neues wählen!";
} elseif ($Mode == 'addresse'){
    $PageTitle = '<h1 class="center-align hide-on-med-and-down">'.$Erklaerungheader.' ändern</h1>';
    $PageTitle .= '<h1 class="center-align hide-on-large-only">'.$Erklaerungheader.' ändern</h1>';
    $Erklaerungheader = "Wir benötigen diese Informationen für das Finanzamt - bitte trage diese daher nach:)";
}
$HTML .= section_builder($PageTitle);

if($Mode == 'dse'){
    $Infos = lade_ds(aktuelle_ds_id_laden());
} elseif ($Mode == 'mv'){
    $Infos = lade_mietvertrag(aktuellen_mietvertrag_id_laden());
} elseif ($Mode == 'pswd'){
    $Infos = lade_user_id();
} elseif ($Mode == 'addresse'){
    $Infos = lade_user_id();
}



if(($Parser['success'] == FALSE) OR ($Parser['success'] == NULL)){

	if($Parser['meldung']!=''){
       $Erklaerungheader="<b>".$Parser['meldung']."</b>";
	}
	#$Infos['erklaerung']=$Parser['meldung'];
	
	#var_dump($Erklaerungheader);

    $HTML .= renew_dse_mv_form($Mode, $Erklaerungheader, $Infos);

} elseif ($Parser['success'] == TRUE){
    $HTML .= section_builder(zurueck_karte_generieren(true, 'Dein Eintrag wurde erfolgreich festgehalten!', './my_reservations.php'));
}

	
	#if($Parser['success']==NULL){
#$HTML .= renew_dse_mv_form($Mode, $Erklaerungsheader, $Infos);
	#} elseif ($Parser['success']==TRUE){
#$HTML .= section_builder(zurueck_karte_generieren(true, 'Neues Passwort gespeichert!', './my_reservations.php');
	#}

	#if(($Parser['success']==FALSE) OR ($Parser==NULL)){
		#$Erklaerungsheader.= "<b>".$Parser['meldung']."</b>";
#$HTML .= renew_dse_mv_form($Mode, $Erklaerungsheader, $Infos);
		
	#} elseif {
#$HTML .= section_builder(zurueck_karte_generieren(true, 'Das neue Passwort wurde erfolgreich eingetragen!', './my

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function renew_dse_mv_parser($Mode){

    $UserID = lade_user_id();
    $UserMeta=lade_user_meta($UserID);

    if($Mode == 'dse'){
        $Continiue = user_needs_dse();
    } elseif($Mode == 'mv'){
        $Continiue = user_needs_mv();
    } elseif($Mode == 'pswd'){
        $Continiue = user_needs_pswd_change($UserID);
    } elseif($Mode == 'addresse'){
        if($UserMeta['strasse']==''){
            $Continiue = true;
        }
    } else {
        $Continiue = false;
    }

    if($Continiue == false){
        header("Location: ./wartwesen.php");
        die();
    } else {
        $Antwort['success'] = null;

        if(isset($_POST['action_dse'])){
            if($_POST['ds']){
                $Antwort['success'] = ds_unterschreiben(lade_user_id(), aktuelle_ds_id_laden());
            }
        }

        if(isset($_POST['action_mv'])){
            if($_POST['vertrag']) {
                $Antwort['success'] = mietvertrag_unterschreiben(lade_user_id(), aktuellen_mietvertrag_id_laden());
            }
        }

        if(isset($_POST['action_pswd'])){
            $Antwort = change_pswd_user($UserID, $_POST['change_pswd'], $_POST['change_pswd_verify']);
			#$Antwort=NULL;
        }

        if(isset($_POST['action_addresse'])){

            $DAUcount = 0;
            if($_POST['strasse']==''){
                $DAUcount++;
            }
            if($_POST['hausnummer']==''){
                $DAUcount++;
            }
            if($_POST['stadt']==''){
                $DAUcount++;
            }
            if($_POST['plz']==''){
                $DAUcount++;
            }
            if($DAUcount>0){
                $Antwort = false;
            } else {
                $UserID = lade_user_id();
                update_user_meta($UserID, 'strasse', $_POST['strasse']);
                update_user_meta($UserID, 'hausnummer', $_POST['hausnummer']);
                update_user_meta($UserID, 'stadt', $_POST['stadt']);
                update_user_meta($UserID, 'plz', $_POST['plz']);
                $Antwort['success']= true;
            }

        }

        return $Antwort;
    }
}
function renew_dse_mv_form($Mode, $Erklaerungheader, $Infos){

    if($Mode == 'dse'){
        $Icon = 'security';
        $TableHTML = table_form_swich_item('Ich stimme den Nutzungsbedingungen, sowie der Speicherung und Verarbeitung gem&auml;&szlig; der Datenschutzerkl&auml;rung zu', 'ds', 'Nein', 'Ja', '', false);
    } elseif($Mode == 'mv') {
        $Icon = 'assignment';
        $TableHTML = table_form_swich_item('Ich stimme allen Vertragsbestandteilen zu', 'vertrag', 'Nein', 'Ja', '', false);
    } elseif($Mode == 'pswd') {
        $Icon = 'vpn_key';
        $TableHTML = table_form_password_item('Neues Passwort wählen', 'change_pswd', 'Passwort', false);
        $TableHTML .= table_form_password_item('Passwort wiederholen', 'change_pswd_verify', 'Passwort', false);
    } elseif($Mode == 'addresse') {
        $Icon = 'home';
        $TableHTML = table_form_string_item('Straße', 'strasse', $_POST['strasse']);
        $TableHTML .= table_form_string_item('Hausnummer', 'hausnummer', $_POST['hausnummer']);
        $TableHTML .= table_form_string_item('Stadt', 'stadt', $_POST['stadt']);
        $TableHTML .= table_form_string_item('Postleitzahl', 'plz', $_POST['plz']);
    }

    $HTML = "";

        $Inhalt = "<h5>".$Infos['erklaerung']."</h5>";
        $Inhalt .= section_builder($Infos['inhalt']);
        $CollapsibleItems = collapsible_item_builder($Erklaerungheader, $Inhalt, $Icon, '');
        $HTML .= collapsible_builder($CollapsibleItems);

    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_'.$Mode.'', 'Absenden', 'action', 'send', '')).table_data_builder(''));
    $HTML .= table_builder($TableHTML);
    $HTML = form_builder($HTML, '#', 'post');

    return $HTML;
}