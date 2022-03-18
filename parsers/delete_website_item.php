<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 10:02
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Baustein = $_GET['item'];

if(intval($Baustein)>0){
    startseiteninhalt_loeschen($Baustein);
    header("Location: ./admin_edit_startpage.php");
    die();
} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}