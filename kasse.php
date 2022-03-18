<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_kasse');
$Header = "Kasse - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1>Kasse</h1>';
$HTML .= section_builder($PageTitle);

# Output site
echo site_header($Header);
echo site_body($HTML);

?>