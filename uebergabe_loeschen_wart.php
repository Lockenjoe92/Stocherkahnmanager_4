<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bergabe stornieren - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bergabe stornieren</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Schl&uuml;ssel&uuml;bergabe stornieren</h1>';
$HTML = section_builder($PageTitle);
$UebergabeID = $_GET['id'];
$Parser = parser_uebergabe_stornieren_wart($UebergabeID);

if ($Parser == NULL){
    $HTML .= section_builder(prompt_karte_generieren('absagen', 'Absagen', 'termine.php', 'Abbrechen', 'M&ouml;chtest du diese &Uuml;bergabe wirklich absagen? Wenn ja, gib bitte einen Kommentar f&uuml;r den User an.', TRUE, 'kommentar'));
} else {
    if($Parser == TRUE){
        $HTML .= section_builder(zurueck_karte_generieren(TRUE, '', 'termine.php'));
    } else if ($Parser == FALSE){
        $HTML .= section_builder(zurueck_karte_generieren(FALSE, '', 'termine.php'));
    }
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);








function parser_uebergabe_stornieren_wart($UebergabeID){

    if (isset($_POST['absagen'])){
        $Ergebnis = uebergabe_stornieren($UebergabeID, $_POST['kommentar']);
        return $Ergebnis;
    } else {
        return null;
    }

}

?>