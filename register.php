<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 08:26
 */

# Include all ressources
include_once "./ressources/ressourcen.php";

#Generate Content
$Header = "Registrieren - " . lade_db_einstellung('site_name');
$Parser = register_parser();
$HTML = container_builder(register_formular($Parser));

# Output site
echo site_header($Header);
echo site_body($HTML);


?>