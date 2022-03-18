<?php

include_once "./ressources/ressourcen.php";

$link = connect_db();

$Anfrage = "SELECT * FROM finanz_ausgleiche";
$Abfrage = mysqli_query($link, $Anfrage);
$Anzahl = mysqli_num_rows($Abfrage);
var_dump($Anzahl);
for($a=1;$a<=$Anzahl;$a++){

    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    if($Ergebnis['referenz']!=''){
        $Anfrage2 = "UPDATE finanz_ausgleiche SET referenz='".utf8_encode($Ergebnis['referenz'])."' WHERE id = ".$Ergebnis['id']."";
        var_dump(mysqli_query($link, $Anfrage2));
        
    }
}


echo site_header($Header);
echo site_body($HTML);

