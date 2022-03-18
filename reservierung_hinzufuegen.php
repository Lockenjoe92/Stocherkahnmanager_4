<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Reservierung hinzuf&uuml;gen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Reservierung hinzuf&uuml;gen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Reservierung hinzuf&uuml;gen</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= seiteninhalt_reservierung_hinzufuegen();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);