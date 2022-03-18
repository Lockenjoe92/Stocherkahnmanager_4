<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 12:43
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$link = connect_db();
$Baustein = $_GET['baustein'];
$Item = $_GET['item'];

if((intval($Baustein)>0) and (intval($Item)>0)){

    $ItemMeta = lade_seiteninhalt($Item);
    $ItemRang = $ItemMeta['rang'];

    #Calculate new Rang
    $NewRang = $ItemRang + 1;

    #Load the other item
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = ".$Baustein." AND rang = ".$NewRang." AND storno_user = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    # Update corresponding Item
    update_website_content_item($Ergebnis['id'], 'rang', $ItemRang);

    # Update selected Item
    update_website_content_item($Item, 'rang', $NewRang);

    header("Location: ./admin_edit_startpage.php");
    die();
} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}