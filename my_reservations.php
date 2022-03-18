<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
needs_dse_mv_update();
$Header = "Reservierungen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="hide-on-med-and-down center-align">Willkommen im Buchungssystem!</h1>';
$PageTitle .= '<h1 class="hide-on-large-only center-align">Willkommen!</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= section_wasserstands_und_rueckgabeautomatikwesen('my_reservations');
$HTML .= seiteninhalt_normalouser_generieren();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);