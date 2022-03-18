<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 12:14
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Baustein = $_GET['baustein'];

if(intval($Baustein)>0){
    startseiteninhalt_einfuegen($Baustein, 'Neues Element', '', '', '', 'Hier entsteht ein neues Element', '', 'announcement', 'brown-text');
    header("Location: ./admin_edit_startpage.php");
    die();
} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}