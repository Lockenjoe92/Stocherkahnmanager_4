<?php

function zeitformat(){
    setlocale(LC_ALL, "de_DE.utf8");
}

function tage_differenz_berechnen($TimestampAnfang, $TimestamoEnde){
    $datetime1 = date_create($TimestampAnfang);
    $datetime2 = date_create($TimestamoEnde);
    $interval = date_diff($datetime1, $datetime2);
    $DifferenzTage = $interval->format('%a');
    return $DifferenzTage;
}

function stunden_differenz_berechnen($TimestampAnfang, $TimestamoEnde){
    $datetime1 = date_create($TimestampAnfang);
    $datetime2 = date_create($TimestamoEnde);
    $interval = date_diff($datetime1, $datetime2);
    $DifferenzTage = $interval->format('%h');
    return $DifferenzTage;
}