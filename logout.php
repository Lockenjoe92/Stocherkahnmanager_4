<?php
session_start();
session_destroy();
include_once "./ressources/ressourcen.php";

$Destination = lade_xml_einstellung('destination_url_after_logout');
if($Destination==''){
    $Destination = "./index.php";
}

header("Location: ".$Destination);
die();
?>