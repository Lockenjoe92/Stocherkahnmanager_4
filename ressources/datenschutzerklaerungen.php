<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Datenschutz - " . lade_db_einstellung('site_name');
$ParserAnlegen = ds_anlegen_parser();

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Datenschutzerklärungen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Datenschutz</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= section_builder(ds_anlegen_formular($ParserAnlegen));

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);
