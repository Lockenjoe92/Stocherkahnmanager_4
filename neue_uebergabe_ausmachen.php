<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Neue Schl&uuml;ssel&uuml;bergabe ausmachen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Neue Schl&uuml;ssel&uuml;bergabe ausmachen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Neue &Uuml;bergabe ausmachen</h1>';
$HTML = section_builder($PageTitle);

$ReservierungID = $_GET['res'];
$Parser = parser_uebergabe_hinzufuegen_ueser($ReservierungID, 'change');
$HTML .= card_resinfos_generieren($ReservierungID);

if (($Parser == NULL) OR ($Parser == FALSE)){
    $Mode = 'change';
    $HTML .= schluesseluebergabe_ausmachen_moeglichkeiten_anzeigen($ReservierungID, $Mode);
} else if ($Parser == TRUE){
    $HTML .= uebergabe_erfolgreich_eingetragen_user();
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

?>