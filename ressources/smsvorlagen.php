<?php

function lade_smsvorlage($name){

    $xml = simplexml_load_file("./ressources/smsvorlagen.xml");
    $Text = $xml->$name->text;

    $StrText = (string) $Text;
    //$StrText = htmlentities($StrText);

    $Antwort = $StrText;

    return $Antwort;
}
?>