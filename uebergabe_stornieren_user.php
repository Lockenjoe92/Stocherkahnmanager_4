<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bergabe absagen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bergabe absagen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">&Uuml;bergabe absagen</h1>';
$HTML = section_builder($PageTitle);

$UebergabeID = $_GET['id'];
$Parser = parser_uebergabe_absagen_user($UebergabeID);

if ($Parser == NULL){
    $PromptText = "M&ouml;chtest du wirklich die Schl&uuml;ssel&uuml;bergabe f&uuml;r deine Reservierung l&ouml;schen?";
    $HTML .= prompt_karte_generieren('uebergabe_absagen', 'Absagen', 'uebergabe_infos_user.php?id='.$UebergabeID.'', 'Zur&uuml;ck', $PromptText, TRUE, 'kommentar_absage');
} else if ($Parser == TRUE){
    $HTML .= zurueck_karte_generieren(TRUE, '', 'my_reservations.php');
} else if ($Parser == FALSE){
    $HTML .= zurueck_karte_generieren(FALSE, '', 'my_reservations.php');
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);




function parser_uebergabe_absagen_user($UebergabeID){

    if (isset($_POST['uebergabe_absagen'])){

        $Uebergabe = lade_uebergabe($UebergabeID);
        $Reservierung = lade_reservierung($Uebergabe['res']);

        if (lade_user_id() != $Reservierung['user']){
            return false;
        } else {
            $Ergebnis = uebergabe_stornieren($UebergabeID, $_POST['kommentar_absage']);
            return $Ergebnis['success'];
        }
    }
}

?>