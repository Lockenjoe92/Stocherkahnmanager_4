<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.06.18
 * Time: 16:21
 */

include_once 'ressources/ressourcen.php';

#Post einlesen
$Post = $_POST;

#Parser
$ErgebnisParser = website_baustein_eintragen_parser($Post);
if($ErgebnisParser['erfolg'] == true){
    echo "passt";
} else {
    echo $ErgebnisParser['meldung'];
}

echo "<h2>Baustein hinzufuegen</h2>";
echo "<form action='#' method='POST'>";

echo "Ort: <input name='ort' type='text' value='".$Post['ort']."'>";
echo "Typ: <input name='typ' type='text' value='".$Post['typ']."'>";
echo "Name: <input name='name' type='text' value='".$Post['name']."'>";
echo "<input name='action_baustein' type='submit'>";

echo "</form><br>";

echo "<h2>Inhalt hinzufuegen</h2>";
echo "<form action='#' method='POST'>";

echo "Baustein: <input name='id_baustein' type='text' value='".$Post['id_baustein']."'>";
echo "Titel: <input name='titel' type='text' value='".$Post['titel']."'>";
echo "Titel_2: <input name='titel2' type='text' value='".$Post['titel2']."'>";
echo "Inhalt: <textarea name='html'>".$Post['html']."</textarea>";
echo "URI-Bild: <input name='uri_bild' type='text' value='".$Post['uri_bild']."'>";
echo "Icon: <input name='icon' type='text' value='".$Post['icon']."'>";
echo "<input name='action_inhalt' type='submit'>";

echo "</form>";

function website_baustein_eintragen_parser($Post){
    if(isset($Post['action_baustein'])){
        $Action = startseitenelement_anlegen($Post['ort'], $Post['typ'], $Post['name']);
    } elseif(isset($Post['action_inhalt'])) {
        echo "Inhalt";
        $Action = startseiteninhalt_einfuegen($Post['id_baustein'], $Post['titel'], $Post['titel2'], $Post['html'], $Post['uri_bild'], $Post['icon']);
    } else {
        $Action['erfolg'] = null;
    }
    return $Action;
}

?>

