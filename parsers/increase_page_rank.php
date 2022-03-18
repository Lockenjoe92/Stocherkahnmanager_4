<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 13:49
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$link = connect_db();
$Rang = $_GET['rang'];
$Name = $_GET['name'];

if((intval($Rang)>0) and (!empty($Name))){

    #Calculate new Rang
    $NewRang = $Rang - 1;

    #Load the other item
    $Anfrage = "SELECT * FROM homepage_sites WHERE menue_rang = ".$NewRang." AND delete_user = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    # Update selected Item
    update_website_page_item($Name, 'menue_rang', $NewRang);

    # Update corresponding Item
    update_website_page_item($Ergebnis['name'], 'menue_rang', $Rang);

    header("Location: ./admin_edit_startpage.php");
    die();
} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}