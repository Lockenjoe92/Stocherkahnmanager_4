<?php
function forderung_generieren($Betrag, $Steuersatz, $VonUser, $VonKonto, $Zielkonto, $ReferenzReservierung, $Referenz, $ZahlbarBis, $Buchender, $ChosenBookingDate=''){

    $DAUcounter = 0;
    $DAUerror = "";
    if($ChosenBookingDate==''){
        $Timestamp = timestamp();
    } else {
        $Timestamp = $ChosenBookingDate;
    }
    $link = connect_db();

    //DAU Block

    //Betrag
    if(!isset($Betrag)){
        $DAUcounter++;
        $DAUerror .= "Es wurde kein zu buchender Betrag eingegeben!<br>";
    }

    if ($Betrag < 0){
        $DAUcounter++;
        $DAUerror .= "Der Forderungsbetrag darf nicht negativ sein!<br>";
    }

    if(!intval($Zielkonto)>0){
        $DAUcounter++;
        $DAUerror .= "Wähle bitte ein Zielkonto aus!<br>";
    }

    //Steuersatz
    if (!isset($Steuersatz)){
        $DAUcounter++;
        $DAUerror .= "Es muss eine Angabe zum Steuersatz gemacht werden!<br>";
    }

    if (($Steuersatz < 1) AND ($Steuersatz >= 1)){
        $DAUcounter++;
        $DAUerror .= "Der Steuersatz muss in ganzen Zahlen angegeben werden!<br>";
    }

    //Forderung von
    if (($VonUser=='') AND ($VonKonto=='')){
        $DAUcounter++;
        $DAUerror .= "Es muss angegeben sein an wen die Forderung gerichtet ist!<br>";
    }

    //Referenz
    if (($Referenz=='') AND ($ReferenzReservierung=='')){
        $DAUcounter++;
        $DAUerror .= "Es muss eine Referenz angegeben sein!<br>";
    }

    //Zahlungsziel
    if(!isset($ZahlbarBis)){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Zahlungsziel angegeben sein!<br>";
    }

    //DAU auswertung

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        if ($VonUser==''){
            $VonUser = 0;
        }

        if ($VonKonto==''){
            $VonKonto = 0;
        }

        //Forderung eintragen
        if($ReferenzReservierung==''){
            $ReferenzReservierung=0;
        }

        $AnfrageForderungEintragen = "INSERT INTO finanz_forderungen (betrag, steuersatz, von_user, von_konto, zielkonto, referenz_res, referenz, zahlbar_bis, timestamp, bucher) VALUES ('$Betrag', '$Steuersatz', '$VonUser', '$VonKonto', '$Zielkonto', '$ReferenzReservierung', '$Referenz', '$ZahlbarBis', '$Timestamp', '$Buchender')";
        $AbfrageForderungEintragen = mysqli_query($link, $AnfrageForderungEintragen);

        if ($AbfrageForderungEintragen){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Forderung erfolgreich eingetragen!";
        } else {
            $Antwort['success'] = FALSE;
            #$Antwort['meldung'] = "Fehler beim Zugriff auf die Datenbank!<br>";
            $Antwort['meldung'] = "Fehler beim Zugriff auf die Datenbank!<br>".$AnfrageForderungEintragen;
        }
    }
    return $Antwort;
}
function zahlungsgrenze_forderung_laden($EndeReservierung){

    $GrenzeXML = lade_xml_einstellung('zeit-tage-nach-res-ende-zahlen');
    $Befehl = "+ ".$GrenzeXML." days";
    $Grenze = date("Y-m-d G:i:s", strtotime($Befehl, strtotime($EndeReservierung)));

    return $Grenze;
}
function forderung_stornieren($ForderungID){

    $link = connect_db();

    $AnfrageForederungStornieren = "UPDATE finanz_forderungen SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = '".$ForderungID."'";
    if(mysqli_query($link, $AnfrageForederungStornieren)){
        return true;
    } else {
        return false;
    }
}

function undo_forderung_stornieren($ForderungID){

    $link = connect_db();

    $AnfrageForederungStornieren = "UPDATE finanz_forderungen SET storno_user = '0', storno_time = NULL WHERE id = '".$ForderungID."'";
    if(mysqli_query($link, $AnfrageForederungStornieren)){
        return true;
    } else {
        return false;
    }
}

function lade_zielkonto_einnahmen_forderungen_id(){

    $Link = connect_db();

    $Anfrage = "SELECT id FROM finanz_konten WHERE verstecker = 0 AND name = 'Einnahmen aus Forderungen'";
    $Abfrage = mysqli_query($Link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    return $Ergebnis['id'];
}

function lade_ausgleiche_fuer_res_zielkonto(){
    $Link = connect_db();

    $Anfrage = "SELECT id FROM finanz_konten WHERE verstecker = 0 AND name = 'Auszahlungen zu Reservierungen'";
    $Abfrage = mysqli_query($Link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    return $Ergebnis['id'];
}

function ausgleich_loeschen($Ausgleich){
    $Link = connect_db();
    $Anfrage = "UPDATE finanz_ausgleiche SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = ".$Ausgleich."";
    return mysqli_query($Link, $Anfrage);
}

function undo_ausgleich_loeschen($Ausgleich){
    $Link = connect_db();
    $Anfrage = "UPDATE finanz_ausgleiche SET storno_user = '0', storno_time = NULL WHERE id = ".$Ausgleich;
    return mysqli_query($Link, $Anfrage);
}

function forderung_bearbeiten($NeuerBetrag, $ForderungID, $Mode='res'){

    $link = connect_db();
    $Forderung = lade_forderung($ForderungID);
    $Zahlungen = lade_einnahmen_forderung($ForderungID);

    if($Zahlungen>$NeuerBetrag){
        $AusgleichBetrag = $Zahlungen-$NeuerBetrag;
        //Erzeuge Ausgleich
        if($Mode=='res'){
            ausgleich_hinzufuegen_res($Forderung['referenz_res'], $AusgleichBetrag, 19);
        }
    } elseif ($Zahlungen<$NeuerBetrag){
        //lösche etwaige Ausgleiche
        $Ausgleiche = lade_offene_ausgleiche_res($Forderung['referenz_res']);
        foreach ($Ausgleiche as $Ausgleich) {
            ausgleich_loeschen($Ausgleich['id']);
        }
    }

    $Anfrage = "UPDATE finanz_forderungen SET betrag = '$NeuerBetrag' WHERE id = '$ForderungID'";
    return mysqli_query($link, $Anfrage);

}
function lade_kontostand($Empfangskonto){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE id = '$Empfangskonto'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return floatval($Ergebnis['wert_aktuell']);
}

function lade_forderung_res($ResID, $IgnoreStorno=false){

    $link = connect_db();

    if($IgnoreStorno){
        $Anfrage = "SELECT * FROM finanz_forderungen WHERE referenz_res = '$ResID'";
    } else {
        $Anfrage = "SELECT * FROM finanz_forderungen WHERE referenz_res = '$ResID' AND storno_user = 0";
    }
    $Abfrage = mysqli_query($link, $Anfrage);
    $Forderung = mysqli_fetch_assoc($Abfrage);

    return $Forderung;
}

function lade_offene_forderungen_user($UserID){
    $link = connect_db();
    $ReturnArray = array();
    $Anfrage = "SELECT * FROM finanz_forderungen WHERE von_user = '$UserID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link,$Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    for($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $Einnahmen = lade_einnahmen_forderung($Ergebnis['id']);
        if($Einnahmen<$Ergebnis['betrag']){
            array_push($ReturnArray, $Ergebnis);
        }
    }
    return $ReturnArray;
}

function lade_konto_user($User){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE name = '$User' AND verstecker = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Konto = mysqli_fetch_assoc($Abfrage);

    return $Konto;
}

function lade_forderung($ID){
    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_forderungen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Konto = mysqli_fetch_assoc($Abfrage);

    return $Konto;
}

function lade_konto_via_id($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Konto = mysqli_fetch_assoc($Abfrage);

    return $Konto;
}

function lade_einnahme($IDeinnahme){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_einnahmen WHERE id = '$IDeinnahme'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Einnahme = mysqli_fetch_assoc($Abfrage);

    return $Einnahme;
}

function lade_ausgabe($IDeinnahme){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_ausgaben WHERE id = '$IDeinnahme'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Einnahme = mysqli_fetch_assoc($Abfrage);

    return $Einnahme;
}

function einnahme_loeschen($ID){
    $link = connect_db();
    $Einnahme = lade_einnahme($ID);
    $Konto = lade_konto_via_id($Einnahme['konto_id']);
    $Anfrage = "UPDATE finanz_einnahmen SET storno = '".timestamp()."', storno_user = ".lade_user_id()." WHERE id = '$ID'";
    if(mysqli_query($link, $Anfrage)){
        $NeuerKontostand = $Konto['wert_aktuell']-$Einnahme['betrag'];
        return update_kontostand($Einnahme['konto_id'], $NeuerKontostand);
    } else{
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler beim Löschen';
        return $Antwort;
    }
}

function undo_einnahme_loeschen($ID){
    $link = connect_db();
    $Einnahme = lade_einnahme($ID);
    $Konto = lade_konto_via_id($Einnahme['konto_id']);
    $Anfrage = "UPDATE finanz_einnahmen SET storno = NULL, storno_user = 0 WHERE id = '$ID'";
    if(mysqli_query($link, $Anfrage)){
        $NeuerKontostand = $Konto['wert_aktuell']+$Einnahme['betrag'];
        return update_kontostand($Einnahme['konto_id'], $NeuerKontostand);
    } else{
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler beim Löschen';
        return $Antwort;
    }
}

function ausgabe_loeschen($ID){
    $link = connect_db();
    $Ausgabe = lade_ausgabe($ID);
    $Konto = lade_konto_via_id($Ausgabe['konto_id']);
    $Anfrage = "UPDATE finanz_ausgaben SET storno = '".timestamp()."', storno_user = ".lade_user_id()." WHERE id = '$ID'";
    if(mysqli_query($link, $Anfrage)){
        $NeuerKontostand = $Konto['wert_aktuell']+$Ausgabe['betrag'];
        return update_kontostand($Ausgabe['konto_id'], $NeuerKontostand);
    } else{
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler beim Löschen';
        return $Antwort;
    }
}

function undo_ausgabe_loeschen($ID){
    $link = connect_db();
    $Ausgabe = lade_ausgabe($ID);
    $Konto = lade_konto_via_id($Ausgabe['konto_id']);
    $Anfrage = "UPDATE finanz_ausgaben SET storno = NULL, storno_user = 0 WHERE id = '$ID'";
    if(mysqli_query($link, $Anfrage)){
        $NeuerKontostand = $Konto['wert_aktuell']-$Ausgabe['betrag'];
        return update_kontostand($Ausgabe['konto_id'], $NeuerKontostand);
    } else{
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler beim Löschen';
        return $Antwort;
    }
}

function gesamteinnahmen_jahr($Jahr){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_einnahmen WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Einnahmen = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Einnahme = mysqli_fetch_assoc($Abfrage);
        $Einnahmen = $Einnahmen + $Einnahme['betrag'];
    }

    return $Einnahmen;
}

function gesamteinnahmen_jahr_konto($Jahr, $KontoID){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_einnahmen WHERE konto_id = ".$KontoID." AND timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";

    $Abfrage = mysqli_query($link, $Anfrage);
    if(!$Abfrage){
        #var_dump($Anfrage);
    }

    $Anzahl = mysqli_num_rows($Abfrage);
    $Einnahmen = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Einnahme = mysqli_fetch_assoc($Abfrage);
        $Einnahmen = $Einnahmen + $Einnahme['betrag'];
    }

    return $Einnahmen;
}

function gesamtausgaben_jahr($Jahr){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_ausgaben WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Ausgaben = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Ausgabe = mysqli_fetch_assoc($Abfrage);
        $Ausgaben = $Ausgaben + $Ausgabe['betrag'];
    }

    return $Ausgaben;
}

function gesamtausgaben_jahr_konto($Jahr, $KontoID){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_ausgaben WHERE konto_id = ".$KontoID." AND timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Ausgaben = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Ausgabe = mysqli_fetch_assoc($Abfrage);
        $Ausgaben = $Ausgaben + $Ausgabe['betrag'];
    }

    return $Ausgaben;
}

function forderungen_konto($Konto, $Jahr, $ShowStorno=false){

    $link = connect_db();
    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";
    if($ShowStorno){
        $Anfrage = "SELECT * FROM finanz_forderungen WHERE zielkonto = ".$Konto." AND timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr'";
    } else {
        $Anfrage = "SELECT * FROM finanz_forderungen WHERE zielkonto = ".$Konto." AND timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = 0";
    }
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $ReturnArray = array();
    for($a=1;$a<=$Anzahl;$a++){
        array_push($ReturnArray, mysqli_fetch_assoc($Abfrage));
    }
    return $ReturnArray;
}

function lade_alle_forderungen_jahr($Jahr, $InklStorno=true){

    $link = connect_db();
    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";
    if($InklStorno){
        $Anfrage = "SELECT * FROM finanz_forderungen WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' ORDER BY timestamp ASC";
    }else{
        $Anfrage = "SELECT * FROM finanz_forderungen WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0' ORDER BY timestamp ASC";
    }
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $ReturnArray = array();
    for($a=1;$a<=$Anzahl;$a++){
        array_push($ReturnArray, mysqli_fetch_assoc($Abfrage));
    }
    return $ReturnArray;
}

function lade_alle_ausgleiche_jahr($Jahr, $InklStorno=true){

    $link = connect_db();
    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";
    if($InklStorno){
        $Anfrage = "SELECT * FROM finanz_ausgleiche WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' ORDER BY timestamp ASC";
    }else{
        $Anfrage = "SELECT * FROM finanz_ausgleiche WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0' ORDER BY timestamp ASC";
    }
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $ReturnArray = array();
    for($a=1;$a<=$Anzahl;$a++){
        array_push($ReturnArray, mysqli_fetch_assoc($Abfrage));
    }
    return $ReturnArray;
}

function ausgleiche_konto($Konto, $Jahr){

    $link = connect_db();
    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";
    $Anfrage = "SELECT * FROM finanz_ausgleiche WHERE von_konto = ".$Konto." AND timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $ReturnArray = array();
    for($a=1;$a<=$Anzahl;$a++){
        array_push($ReturnArray, mysqli_fetch_assoc($Abfrage));
    }
    return $ReturnArray;
}

function lade_gezahlte_summe_forderung($ForderungID){

    $link = connect_db();

    $AnfrageLadeZahlungen = "SELECT id, betrag FROM finanz_einnahmen WHERE forderung_id = '$ForderungID' AND storno_user = '0'";
    $AbfrageLadeZahlungen = mysqli_query($link, $AnfrageLadeZahlungen);
    $AnzahlLadeZahlungen = mysqli_num_rows($AbfrageLadeZahlungen);

    $Zaehler = 0;

    for ($a = 1; $a <= $AnzahlLadeZahlungen; $a++){

        $Einnahme = mysqli_fetch_assoc($AbfrageLadeZahlungen);
        $Zaehler = $Zaehler + floatval($Einnahme['betrag']);
    }

    return $Zaehler;
}
function einnahme_festhalten($Forderung, $Empfangskonto, $Betrag, $Steuersatz, $ChosenDate=''){

    if($ChosenDate==''){
        $Timestamp = timestamp();
    } else {
        $Timestamp = $ChosenDate;
    }
    if ($Empfangskonto==''){
        return false;
    } else {
        $link = connect_db();
        $Anfrage = "INSERT INTO finanz_einnahmen (betrag, steuersatz, forderung_id, konto_id, timestamp, bucher) VALUES ('$Betrag', '$Steuersatz', '$Forderung', '$Empfangskonto', '$Timestamp', '".lade_user_id()."')";
        #var_dump($Anfrage);
        if (mysqli_query($link, $Anfrage)){

            //Konto aktualisieren
            $KontoAktuell = lade_kontostand($Empfangskonto);
            $KontoNeu = floatval($KontoAktuell) + floatval($Betrag);
            update_kontostand($Empfangskonto, $KontoNeu);

            return true;
        } else {
            return false;
        }
    }

}
function update_kontostand($KontoID, $KontostandNeu){

    $link = connect_db();

    $Anfrage = "UPDATE finanz_konten SET wert_aktuell = '$KontostandNeu' WHERE id = '$KontoID'";

    $Abfrage = mysqli_query($link, $Anfrage);

    return $Abfrage;
}

function einnahme_uebergabe_festhalten($UebergabeID, $GezahlterBetrag, $Empfaenger){

    $Uebergabe = lade_uebergabe($UebergabeID);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $Forderung = lade_forderung_res($Reservierung['id']);
    $Konto = lade_konto_user($Empfaenger);

    if (einnahme_festhalten($Forderung['id'], $Konto['id'], $GezahlterBetrag, 19)){
        return true;
    } else {
        return false;
    }
}
function wartkonto_anlegen($User){

    $link = connect_db();

    $Anfrage = "INSERT INTO finanz_konten (name, wert_start, wert_aktuell, typ, ersteller, erstellt) VALUES ('$User', 0, 0, 'wartkonto', '".lade_user_id()."', '".timestamp()."')";
    $Abfrage = mysqli_query($link, $Anfrage);

    return $Abfrage;
}

function konto_anlegen($Name, $Typ, $STartwert){

    $link = connect_db();
    $DAUcount = 0;
    $DAUerr = '';

    if($Name==''){
        $DAUcount++;
        $DAUerr .= 'Bitte gib dem Konto einen Namen!<br>';
    } else{
        if (!($stmt = $link->prepare("SELECT id FROM finanz_konten WHERE name = ? AND verstecker = 0"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        if (!$stmt->bind_param("s",$Name)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        $res = $stmt->get_result();
        $Anzahl = mysqli_num_rows($res);
        if($Anzahl>0){
            $DAUcount++;
            $DAUerr .= 'Ein Konto mit diesem Namen existiert bereits!<br>';
        }
    }
    if($Typ==''){
        $DAUcount++;
        $DAUerr .= 'Bitte wähle einen Kontotyp aus!<br>';
    }
    if($STartwert!=''){
        if(!is_numeric($STartwert)){
            $DAUcount++;
            $DAUerr .= 'Bitte gib eine valide Zahl (Format 12.34) als Startwert an!<br>';
        }
    } else {
        $STartwert = 0.0;
    }

    if($DAUcount>0){
        $Antwort['success']=false;
        $Antwort['meldung']=$DAUerr;
    } else {
        if (!($stmt = $link->prepare("INSERT INTO finanz_konten (name, wert_start, wert_aktuell, typ, ersteller, erstellt) VALUES (?,?,?,?,?,?)"))) {
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler!';
            #echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        if (!$stmt->bind_param("ssssis",$Name, $STartwert, $STartwert, $Typ, lade_user_id(), timestamp())) {
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler!';
            #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler!';
            #echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $Antwort['success']=true;
            $Antwort['meldung']='Konto erfolgreich angelegt!';
        }
    }

    return $Antwort;
}

function lade_gezahlte_betraege_ausgleich($AusgleichID){

    $link = connect_db();

    $Anfrage = "SELECT betrag FROM finanz_ausgaben WHERE ausgleich_id = '$AusgleichID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    $Counter = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Ausgabe = mysqli_fetch_assoc($Abfrage);
        $Counter = $Counter + $Ausgabe['betrag'];
    }

    return $Counter;
}

function nachzahlung_reservierung_festhalten($IDres, $Betrag, $Wart, $PayPal=false){

    $Antwort = array();

    $DAUcounter = 0;
    $DAUerror = "";

    if ($IDres == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Reservierung ausw&auml;hlen!<br>";
    }

    if ($Betrag == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen Betrag angeben!<br>";
    }

    //Forderung schon beglichen
    $Forderung = lade_forderung_res($IDres);
    $BisherigeZahlungen = lade_gezahlte_summe_forderung($Forderung['id']);
    if ($BisherigeZahlungen > floatval($Forderung['betrag'])){
        $DAUcounter++;
        $DAUerror .= "Forderung wurde inzwischen vollst&auml;ndig beglichen!<br>";
    }

    //Zuviel geld
    $Differenz = floatval($Forderung['betrag']) - $BisherigeZahlungen;
    $DifferenzBetrag = $Betrag - $Differenz;
    if ($DifferenzBetrag > 20){
        $DAUcounter++;
        $DAUerror .= "Der eingegebene Betrag &uuml;bersteigt die zul&auml;ssige Trinkgeldgrenze!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        $ForderungRes = lade_forderung_res($IDres);
        if($PayPal){
            $KontoWart = lade_paypal_konto_id();
        } else {
            $KontoWart = lade_konto_user($Wart);
        }

        if (einnahme_festhalten($ForderungRes['id'], $KontoWart['id'], $Betrag, 19)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Einnahme erfolgreich eingetragen!";

            if($PayPal){

                $PayPalKontoID = lade_paypal_konto_id();
                $PayPalAusgabenKonto = lade_paypal_ausgaben_konto_id();
                $PayPalGebuehr = paypal_gebuehr_berechnen($Betrag);
                ausgleich_hinzufuegen($PayPalAusgabenKonto['id'], 'PayPal-Gebühr Res. #'.$IDres, $PayPalGebuehr, 19);

                $link = connect_db();
                $Anfrage = "SELECT id FROM finanz_ausgleiche WHERE referenz = 'PayPal-Gebühr Res. #".$IDres."' AND storno_user = 0";
                $Abfrage = mysqli_query($link, $Anfrage);
                $Ergebnis = mysqli_fetch_assoc($Abfrage);
                ausgabe_hinzufuegen($PayPalGebuehr, 19, $Ergebnis['id'], $PayPalKontoID['id']);
            }

        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Eintragen der Einnahme!";
        }
    }

    return $Antwort;
}

function paypal_gebuehr_berechnen($betrag){

    $genau = $betrag*0.0249;
    $rounded = round($genau, 2);
    return $rounded + 0.35;
}

function lade_paypal_konto_id(){

    $link = connect_db();
    $Anfrage = "SELECT id FROM finanz_konten WHERE name = 'PayPal' AND verstecker = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    return $Ergebnis;
}

function lade_paypal_ausgaben_konto_id(){

    $link = connect_db();
    $Anfrage = "SELECT id FROM finanz_konten WHERE name = 'PayPal Fees' AND verstecker = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    return $Ergebnis;
}

function lade_ausgleich($IDausgleich){
    $link = connect_db();
    $Anfrage = "SELECT * FROM finanz_ausgleiche WHERE id = '$IDausgleich'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Termin = mysqli_fetch_assoc($Abfrage);
    return $Termin;
}

function rueckzahlung_ausgleich_durchfuehren($TerminID, $Summe){

    $Termin = lade_termin($TerminID);
    if($Termin['grund']!='ausgleich'){
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    } else {
        $Konto = lade_konto_user(lade_user_id());
        $Ausgabe = ausgabe_hinzufuegen($Summe, 19, $Termin['id_grund'], $Konto['id']);
        if($Ausgabe['success']){
            $Antwort = termin_durchfuehren($TerminID);
        } else {
            $Antwort = $Ausgabe;
        }
    }

    return $Antwort;
}

function ausgabe_hinzufuegen($Betrag, $Steuersatz, $Ausgleich, $Konto, $ChosenDate=''){

    $link = connect_db();

    $val = str_replace(",",".",$Betrag);
    $val = preg_replace('/\.(?=.*\.)/', '', $val);
    $BetragDB = floatval($val);

    if (!($stmt = $link->prepare("INSERT INTO finanz_ausgaben (betrag, steuersatz, ausgleich_id, konto_id, timestamp, bucher) VALUES (?,?,?,?,?,?)"))) {
        #echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    }

    if($ChosenDate==''){
        if (!$stmt->bind_param("diiisi",$BetragDB, $Steuersatz, $Ausgleich, $Konto, timestamp(), lade_user_id())) {
            #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler';
        }
    } else {
        if (!$stmt->bind_param("diiisi",$BetragDB, $Steuersatz, $Ausgleich, $Konto, $ChosenDate, lade_user_id())) {
            #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler';
        }
    }

    if (!$stmt->execute()) {
        #echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    } else {
        $Kontoinfos = lade_konto_via_id($Konto);
        $NeuerKontostand = $Kontoinfos['wert_aktuell']-$Betrag;
        update_kontostand($Konto, $NeuerKontostand);

        $Antwort['success']=true;
        $Antwort['meldung']='Ausgabe erfolgreich festgehalten!';
    }

    return $Antwort;
}

function lade_ausgaben_ausgleich($Ausgleich, $ReturnArrayMode=false){

    $link = connect_db();
    if($ReturnArrayMode){
        $AnfrageSucheNachZahlungen = "SELECT * FROM finanz_ausgaben WHERE ausgleich_id = '".$Ausgleich."'";
    }else{
        $AnfrageSucheNachZahlungen = "SELECT * FROM finanz_ausgaben WHERE ausgleich_id = '".$Ausgleich."' AND storno_user = '0'";
    }
    $AbfrageSucheNachZahlungen = mysqli_query($link, $AnfrageSucheNachZahlungen);
    $AnzahlSucheNachZahlungen = mysqli_num_rows($AbfrageSucheNachZahlungen);

    $Einnahmenzaehler = 0;
    $Einnahmen = array();
    for ($b = 1; $b <= $AnzahlSucheNachZahlungen; $b++){
        $Zahlung = mysqli_fetch_assoc($AbfrageSucheNachZahlungen);
        array_push($Einnahmen, $Zahlung);
        $Einnahmenzaehler = $Einnahmenzaehler + $Zahlung['betrag'];
    }

    if($ReturnArrayMode){
        return $Einnahmen;
    }elseif (!$ReturnArrayMode){
        return $Einnahmenzaehler;
    }

}

function lade_transfer($ID){
    $link = connect_db();
    $AnfrageTransfers = "SELECT * FROM finanz_transfer WHERE id = ".$ID."";
    $AbfrageTransfers = mysqli_query($link, $AnfrageTransfers);
    return mysqli_fetch_assoc($AbfrageTransfers);
}

function transfer_loeschen($ID){

    $Transfer = lade_transfer($ID);

    $KontoVon = lade_konto_via_id($Transfer['von']);
    $KontoNach = lade_konto_via_id($Transfer['nach']);

    $NeuerKontostandVon = $KontoVon['wert_aktuell']+$Transfer['betrag'];
    $NeuerKontostandNach = $KontoNach['wert_aktuell']-$Transfer['betrag'];

    $ErfolgCount = 0;
    if(update_kontostand($KontoVon['id'], $NeuerKontostandVon)){
        $ErfolgCount++;
    }
    if(update_kontostand($KontoNach['id'], $NeuerKontostandNach)){
        $ErfolgCount++;
    }

    if($ErfolgCount==2){
        $link = connect_db();
        $Anfrage = "UPDATE finanz_transfer SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = ".$ID."";
        return mysqli_query($link, $Anfrage);
    } else {
        return false;
    }
}

function undo_transfer_loeschen($ID){
    $Transfer = lade_transfer($ID);

    $KontoVon = lade_konto_via_id($Transfer['von']);
    $KontoNach = lade_konto_via_id($Transfer['nach']);

    $NeuerKontostandVon = $KontoVon['wert_aktuell']-$Transfer['betrag'];
    $NeuerKontostandNach = $KontoNach['wert_aktuell']+$Transfer['betrag'];

    $ErfolgCount = 0;
    if(update_kontostand($KontoVon['id'], $NeuerKontostandVon)){
        $ErfolgCount++;
    }
    if(update_kontostand($KontoNach['id'], $NeuerKontostandNach)){
        $ErfolgCount++;
    }

    if($ErfolgCount==2){
        $link = connect_db();
        $Anfrage = "UPDATE finanz_transfer SET storno_user = 0, storno_time = NULL WHERE id = ".$ID."";
        return mysqli_query($link, $Anfrage);
    } else {
        return false;
    }
}

function add_transfer($von, $nach, $betrag, $ChosenDate=''){

    $Antwort = array();
    $link = connect_db();

    $DAUcounter = 0;
    $DAUerror = "";

    if ($von == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst ein Ausgangskonto ausw&auml;hlen!<br>";
    }

    if ($nach == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst ein Zielkonto ausw&auml;hlen!<br>";
    }

    if(!is_numeric($betrag)){
        $DAUcounter++;
        $DAUerror .= "Du musst einen validen Umbuchungsbetrag angeben!<br>";
    }

    if($betrag<0){
        $DAUcounter++;
        $DAUerror .= "Der Umbuchungsbetrag darf nicht negativ sein!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){
        $VonKonto = lade_konto_via_id($von);
        $NachKonto = lade_konto_via_id($nach);
        $NeuerKontostandVon = $VonKonto['wert_aktuell']-$betrag;
        $NeuerKontostandNach = $NachKonto['wert_aktuell']+$betrag;

        if (!($stmt = $link->prepare("INSERT INTO finanz_transfer (betrag, von, nach, timestamp, durchfuehrender) VALUES (?,?,?,?,?)"))) {
            #echo "Prepare failed: (" . $link->errno . ") " . $link->error;
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler 1';
        }

        if($ChosenDate==''){
            if (!$stmt->bind_param("siisi", $betrag, $von, $nach, timestamp(), lade_user_id())) {
                #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                $Antwort['success']=false;
                $Antwort['meldung']='Datenbankfehler 2';
            }
        } else {
            if (!$stmt->bind_param("siisi", $betrag, $von, $nach, $ChosenDate, lade_user_id())) {
                #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                $Antwort['success']=false;
                $Antwort['meldung']='Datenbankfehler 3';
            }
        }


        if (!$stmt->execute()) {
            #echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler 4';
        } else {
            update_kontostand($von, $NeuerKontostandVon);
            update_kontostand($nach, $NeuerKontostandNach);
            $Antwort['success']=true;
            $Antwort['meldung']='Umbuchung erfolgreich eingetragen!';
        }
    }

    return $Antwort;
}