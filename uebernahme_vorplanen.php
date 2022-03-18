<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bernahme vorplanen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bernahme vorplanen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">&Uuml;bernahme vorplanen</h1>';
$HTML = section_builder($PageTitle);

$ReservierungID = $_GET['res'];
$Parser = uebernahme_vorplanen_parser($ReservierungID);

if($Parser['success']===null){
    $HTML .= card_resinfos_generieren($ReservierungID);
    $HTML .= form_builder(seiteninhalt_uebernahme_vorplanen_generieren($ReservierungID),'#', 'post');
} elseif($Parser['success']===true) {
    $HTML .= zurueck_karte_generieren(true, $Parser['meldung'], 'wartwesen.php');
} elseif($Parser['success']===false) {
    $HTML .= zurueck_karte_generieren(false, $Parser['meldung'], 'wartwesen.php');
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


?>