<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
$Header = "Impressum - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align">Impressum</h1>';
$HTML .= section_builder($PageTitle);
$HTML .= section_builder(lade_xml_einstellung('impressum_html'));


$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

?>