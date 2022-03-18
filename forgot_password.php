<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.10.18
 * Time: 18:04
 */

# Include all ressources
include_once "./ressources/ressourcen.php";

#Generate Content
$Header = "Passwort zurücksetzen - " . lade_db_einstellung('site_name');
$Parser = pswd_reset_parser();
$HTML = pswd_reset_formular($Parser);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);