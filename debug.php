<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.06.18
 * Time: 00:07
 */

include_once "./ressources/ressourcen.php";

echo "debugging";
$link = connect_db();

$Einstallung = form_mediapicker_dropdown('test', '', 'media/pictures', 'test', '');
echo $Einstallung;

?>