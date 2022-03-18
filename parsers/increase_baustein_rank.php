<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 12:56
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$link = connect_db();
$Baustein = $_GET['baustein'];
$Site = $_GET['site'];

if(intval($Baustein)>0){

    $BausteinMeta = lade_baustein($Baustein);
    $BausteinRang = $BausteinMeta['rang'];

    #Calculate new Rang
    $NewRang = $BausteinRang + 1;

    #Load the other item
    $Anfrage = "SELECT * FROM homepage_bausteine WHERE ort = '".$Site."' AND rang = ".$NewRang." AND storno_user = 0";
    echo $Anfrage;
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    # Update corresponding Item
    update_website_baustein_item($Ergebnis['id'], 'rang', $BausteinRang);

    # Update selected Item
    update_website_baustein_item($Baustein, 'rang', $NewRang);

    header("Location: ./admin_edit_startpage.php");
    die();
} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}