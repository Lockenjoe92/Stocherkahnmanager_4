<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bergabe ausmachen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bergabe ausmachen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Schl&uuml;ssel&uuml;bergabe ausmachen</h1>';
$HTML = section_builder($PageTitle);

$ReservierungID = $_GET['res'];
$Parser = parser_uebergabe_hinzufuegen_ueser($ReservierungID);
$HTML .= card_resinfos_generieren($ReservierungID);
if($Parser['success'] == FALSE){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

if (($Parser['success'] == NULL) OR ($Parser['success'] == FALSE)){
    $HTML .= schluesseluebergabe_ausmachen_moeglichkeiten_anzeigen($ReservierungID, 'fresh');
} else if ($Parser['success'] == TRUE){
    $HTML .= uebergabe_erfolgreich_eingetragen_user();
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

?>