<?php

function lade_schluesseldaten($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM schluessel WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergbebnis = mysqli_fetch_assoc($Abfrage);
    return $Ergbebnis;

}
function lade_letze_erinnerung_schluesselrueckgabe($IDres){

    $link = connect_db();
    $Reservierung = lade_reservierung($IDres);
    $Typ = "mail_erinnerung_schluesselrueckgabe_intervall-".$IDres."";
    $TypZwei = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt-".$IDres."";
    $Anfrage = "SELECT * FROM mail_protokoll WHERE empfaenger = '".$Reservierung['user']."' AND typ = '$Typ' AND erfolg = 'true' ORDER BY timestamp DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl == 0){

        $AnfrageZwei = "SELECT * FROM mail_protokoll WHERE empfaenger = '".$Reservierung['user']."' AND typ = '$TypZwei' AND erfolg = 'true' ORDER BY timestamp DESC";
        $AbfrageZwei = mysqli_query($link, $AnfrageZwei);
        $AnzahlZwei = mysqli_num_rows($AbfrageZwei);

        if ($AnzahlZwei == 0){
            return null;
        } else if ($AnzahlZwei > 0){
            $ErgebnisZwei = mysqli_fetch_assoc($AbfrageZwei);
            return $ErgebnisZwei['timestamp'];
        }

    } else if ($Anzahl > 0){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        return $Ergebnis['timestamp'];
    }
}
function schluesselrueckgabe_festhalten($SchluesselID, $Mode='wart'){

    $link = connect_db();

    $AnfrageLadeAlleOffenenAusgaben = "SELECT id FROM schluesselausgabe WHERE schluessel = '$SchluesselID' AND ausgabe IS NOT NULL AND rueckgabe IS NULL AND storno_user = '0'";
    $AbfrageLadeAlleOffenenAusgaben = mysqli_query($link, $AnfrageLadeAlleOffenenAusgaben);
    $AnzahlLadeAlleOffenenAusgaben = mysqli_num_rows($AbfrageLadeAlleOffenenAusgaben);

    $Error = 0;

    for ($a = 1; $a <= $AnzahlLadeAlleOffenenAusgaben; $a++){

        $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleOffenenAusgaben);#

        $AnfrageUpdate = "UPDATE schluessel SET akt_user = '0', akt_ort = 'rueckgabekasten' WHERE id = '$SchluesselID'";
        if (mysqli_query($link, $AnfrageUpdate)){

            $AnfrageRueckgabeFesthalten = "UPDATE schluesselausgabe SET rueckgabe = '".timestamp()."' WHERE id = '".$Ausgabe['id']."'";
            if (!mysqli_query($link, $AnfrageRueckgabeFesthalten)){
                $Error++;
            } else {

                if($Mode=='wart'){
                    add_protocol_entry(lade_user_id(), 'Schluesselr&uuml;ckgabe durch '.lade_user_id().' festgehalten.', 'mail');
                } elseif ($Mode=='auto'){
                    add_protocol_entry(lade_user_id(), 'Schluesselr&uuml;ckgabe durch Automatik festgehalten.', 'mail');
                }

            }

        } else {
            $Error++;
        }
    }

    if ($Error == 0){
        return true;
    } else {
        return false;
    }

}
function schluessel_hinzufuegen($ChosenID, $Farbe, $FarbeMat, $RFID){

    $link = connect_db();
    $Antwort = array();

    //DAU
    $DAUcounter = 0;
    $DAUerror = "";

    if ($ChosenID == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Schl&uuml;sselnummer angeben!<br>";
    }

    if ($Farbe == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Schl&uuml;sselfarbe angeben!<br>";
    }

    if ($FarbeMat == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Materialize-Schl&uuml;sselfarbe angeben! Welche Farben es gibt kannst du <a href='http://materializecss.com/color.html'>hier</a> sehen.<br>";
    }

    $AnfrageDAU = "SELECT * FROM schluessel WHERE id = '$ChosenID' AND delete_user = 0";
    $AbfrageDAU = mysqli_query($link, $AnfrageDAU);
    $AnzahlDAU = mysqli_num_rows($AbfrageDAU);

    if ($AnzahlDAU > 0){
        $DAUcounter++;
        $DAUerror .= "Du hast diesen Schl&uuml;ssel bereits angelegt! Lade ggf. die Seite neu.<br>";
    }

    //DAU auswerten

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        if(isset($_POST['is_wartschluessel'])){
            $AnAusWart = 'on';
        } else {
            $AnAusWart = 'off';
        }

        $Anfrage = "INSERT INTO schluessel (id, farbe, farbe_materialize, RFID, ist_wartschluessel, akt_ort, akt_user, create_user, create_time) VALUES ('$ChosenID','$Farbe', '$FarbeMat', '$RFID', '$AnAusWart', 'rueckgabekasten', '0', '".lade_user_id()."', '".timestamp()."')";
        if (mysqli_query($link, $Anfrage)){

            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Schlüssel erfolgreich angelegt!";

        } else {
            $Antwort['success'] = FALSE;
            #$Antwort['meldung'] = "Fehler beim Datenbankzugriff!".$Anfrage;
            $Antwort['meldung'] = "Fehler beim Datenbankzugriff!";
        }
    }

    return $Antwort;
}
function schluessel_umbuchen($Schluessel, $AnWart, $AnOrt, $Wart){

    $link = connect_db();
    $Antwort = array();

    //DAU block falls notwendig
    $DAUcounter = 0;
    $DAUerror = "";

    //Kein schlüssel
    if($Schluessel == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen zu bewegenden Schl&uuml;ssel angeben!<br>";
    }

    //kein ort oder user angegeben
    if(($AnWart == "") AND ($AnOrt == "")){
        $DAUcounter++;
        $DAUerror .= "Du musst ein Ziel ausw&auml;hlen!<br>";
    }

    //sowohl ort als auch user gegeben
    if(($AnWart != "") AND ($AnOrt != "")){
        $DAUcounter++;
        $DAUerror .= "Du kannst nicht zwei Ziele angeben!<br>";
    }

    if($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        if($AnOrt != ""){
            $Anfrage = "UPDATE schluessel SET akt_ort = '$AnOrt', akt_user = 0 WHERE id = '$Schluessel'";
        } elseif ($AnWart != ""){
            $Anfrage = "UPDATE schluessel SET akt_ort = '', akt_user = '$AnWart' WHERE id = '$Schluessel'";
        }

        if (mysqli_query($link, $Anfrage)){

            $Event = "Umbuchung von ".$Schluessel." nach ".$AnOrt."".$AnWart." durch ".$Wart."";
            add_protocol_entry(lade_user_id(), $Event, 'schluessel');

            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Der Schl&uuml;ssel wurde erfolgreich umgebucht!";
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler! 3".$Anfrage;
        }
    }

    return $Antwort;
}
function schluessel_bearbeiten($SchluesselID, $NewID, $Farbe, $FarbeMatCSS, $RFID){

    $link = connect_db();
    $Antwort = array();
    $DAUcounter = 0;
    $DAUerror = "";

    //Kein Schlüssel gewählt
    if($SchluesselID == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen Schl&uuml;ssel ausw&auml;hlen!<br>";
    } else {

        //Schlüssel schon storniert?
        $Schluessel = lade_schluesseldaten($SchluesselID);
        if(intval($Schluessel['delete_user']) != 0){
            $DAUcounter++;
            $DAUerror .= "Der ausgew&auml;hlte Schl&uuml;ssel ist inzwischen storniert!<br>";
        }

        if($NewID!=""){
            if($SchluesselID!=$NewID){

                $AnfrageDAU = "SELECT * FROM schluessel WHERE id = '$NewID' AND delete_user = 0";
                $AbfrageDAU = mysqli_query($link, $AnfrageDAU);
                $AnzahlDAU = mysqli_num_rows($AbfrageDAU);

                if ($AnzahlDAU > 0){
                    $DAUcounter++;
                    $DAUerror .= "Ein Schlüssel mit dieser Nummer existiert bereits!<br>";
                }

            }
        }

    }

    if(($Farbe == "") AND ($FarbeMatCSS == "") AND ($RFID == "") AND ($NewID == "")){
        $DAUcounter++;
        $DAUerror .= "Du hast keine &Auml;nderungen eingegeben!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else {

        //Befehl bauen
        $Aenderungsbefehl = "";
        if ($Farbe != ""){
            $Aenderungsbefehl .= "farbe = '".$Farbe."'";
        }

        if ($FarbeMatCSS != ""){
            if($Aenderungsbefehl!=""){
                $Aenderungsbefehl .= ", ";
            }
            $Aenderungsbefehl .= "farbe_materialize = '".$FarbeMatCSS."'";
        }

        if ($RFID != ""){
            if($Aenderungsbefehl!=""){
                $Aenderungsbefehl .= ", ";
            }
            $Aenderungsbefehl .= "RFID = ".$RFID."";
        }

        $Anfrage = "UPDATE schluessel SET ".$Aenderungsbefehl." WHERE id = ".$SchluesselID;


        if (mysqli_query($link, $Anfrage)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "&Auml;nderungen am Schl&uuml;ssel ".$SchluesselID." erfolgreich eingetragen!";
            $EintragText = "Schl&uuml;ssel ".$SchluesselID." von Wart ".lade_user_id()." bearbeitet: ".$Aenderungsbefehl."";
            add_protocol_entry(lade_user_id(),$EintragText, 'schluessel');
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler 1!".$Anfrage;
        }

        if($NewID!=""){
            $Aenderungsbefehl = "id = '".$NewID."'";
            $Anfrage2 = "UPDATE schluessel SET ".$Aenderungsbefehl." WHERE id = '$SchluesselID'";
            if (mysqli_query($link, $Anfrage2)){
                $Antwort['success'] = TRUE;
                $Antwort['meldung'] = "&Auml;nderungen am Schl&uuml;ssel ".$SchluesselID." erfolgreich eingetragen!";
                $EintragText = "Schl&uuml;ssel ".$SchluesselID." von Wart ".lade_user_id()." bearbeitet: ".$Aenderungsbefehl."";
                add_protocol_entry(lade_user_id(),$EintragText, 'schluessel');
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Datenbankfehler 2!".$Anfrage2;
            }
        }
    }

    return $Antwort;
}
function schluessel_loeschen($SchluesselID){

    $link = connect_db();
    $Antwort = array();
    $DAUcounter = 0;
    $DAUerror = "";
    $Schluessel = lade_schluesseldaten($SchluesselID);

    if($SchluesselID == ""){
        $DAUcounter++;
        $DAUerror .= "Du hast keinen Schl&uuml;ssel ausgew&auml;hlt!<br>";
    } else {
        //Schluessel noch bei einem nicht-Wart
        $UserID = intval($Schluessel['akt_user']);

        if($UserID > 0){

            $User = lade_user_meta($UserID);

            if ($User['ist_wart'] != true){
                $DAUcounter++;
                $DAUerror .= "Der Schl&uuml;ssel ist noch bei einem User gebucht! Buche ihn zuerst zu dir oder in den R&uuml;ckgabeort zur&uuml;ck!<br>";
            }
        }

        //Schluessel bereits storniert
        if(intval($Schluessel['delete_user']) > 0){
            $DAUcounter++;
            $DAUerror .= "Der Schl&uuml;ssel ist bereits gel&ouml;scht!<br>";
        }
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else {

        $Anfrage = "DELETE FROM schluessel WHERE id = '".$SchluesselID."'";
        if (mysqli_query($link, $Anfrage)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Schl&uuml;ssel ".$SchluesselID." erfolgreich gel&ouml;scht!";
            $EintragText = "Schl&uuml;ssel ".$SchluesselID." von Wart ".lade_user_id()." gel&ouml;scht.";
            add_protocol_entry(lade_user_id(), $EintragText, 'schluessel');
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler 5!".$Anfrage;
        }
    }

    return $Antwort;
}
function schluessel_umbuchen_listenelement_parser($Schluessel, $AnWart, $AnOrt){

    $Antwort = array();
    $SchluesselData = lade_schluesseldaten($Schluessel);
    $DAUcounter = 0;
    $DAUerror = "";

    //Kein Schlüssel gewählt
    if($Schluessel == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen zu bewegenden Schl&uuml;ssel angeben!<br>";
    } else {
        //Schlüssel inzwischen storniert
        if($SchluesselData['delete_user'] != "0"){
            $DAUcounter++;
            $DAUerror .= "Schl&uuml;ssel ist inzwischen storniert!<br>";
        }
        //Mehrfachwahl (2 von 3 und 3 von 3)
        if (($AnOrt != "") AND ($AnWart != "")){
            $DAUcounter++;
            $DAUerror .= "Schl&uuml;ssel kann nicht an mehrere Ziele gleichzeitig gebucht werden!<br>";
        }
        //Keine Auswahl
        if((($AnOrt == "") AND ($AnWart == ""))){
            $DAUcounter++;
            $DAUerror .= "Du hast kein Ziel ausgew&auml;hlt!<br>";
        }
    }

    if($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0) {

        //Falls aktueller User kein Wart ist, buchen wir eine Schlüsselrückgabe!
        $UserID = intval($SchluesselData['akt_user']);
        if($UserID > 0){
            $UserMeta = lade_user_meta($UserID);
            $Benutzerrollen = lade_user_meta($UserMeta['username']);
            if ($Benutzerrollen['ist_wart'] != true){
                schluesselrueckgabe_festhalten($Schluessel);
            }
        }

        $Antwort = schluessel_umbuchen($Schluessel, $AnWart, $AnOrt, lade_user_id());
    }

    return $Antwort;
}
function schluessel_an_user_ausgeben($UebergabeID, $Schluessel, $Wart){

    $Uebergabe = lade_uebergabe($UebergabeID);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $link = connect_db();
    $Timestamp = timestamp();

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    if($DAUcounter > 0){
        return false;
    } else if ($DAUcounter == 0) {

        $Anfrage = "INSERT INTO schluesselausgabe (uebergabe, wart, user, reservierung, schluessel, ausgabe) VALUES ('$UebergabeID', '$Wart', '".$Reservierung['user']."', '".$Reservierung['id']."', '$Schluessel', '$Timestamp')";

        mysqli_query($link, $Anfrage);

        $AnfrageZwei = "UPDATE schluessel SET akt_user ='".$Reservierung['user']."' WHERE id = '$Schluessel'";
        mysqli_query($link, $AnfrageZwei);

        add_protocol_entry(lade_user_id(), '&Uuml;bergabe '.$UebergabeID.' durch Wart '.$Wart.' durchgefuehrt. Schluessel '.$Schluessel.' ausgegeben.', 'schluessel');

        return true;
    }


}
function spalte_anstehende_rueckgaben(){

    $link = connect_db();
    zeitformat();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header center-align'>Anstehende R&uuml;ckgaben</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe IS NOT NULL AND rueckgabe IS NULL ORDER BY schluessel ASC";
    $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
    $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);

    if ($AnzahlLadeAlleSchluesselausgaben == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Keine anstehenden R&uuml;ckgaben!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeAlleSchluesselausgaben > 0){

        $Counter = 0;

        for($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

            $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

            //Reservierung vorbei oder storniert?
            $Reservierung = lade_reservierung($Ausgabe['reservierung']);

            if ((strtotime($Reservierung['ende']) < time()) OR ($Reservierung['storno_user'] != "0")){
                //darf er dan Schlüssel weiter behalten?
                $AnfrageWeitereReservierungenMitDiesemSchluessel = "SELECT id, wart, user, reservierung FROM schluesselausgabe WHERE user = '".$Ausgabe['user']."' AND schluessel = '".$Ausgabe['schluessel']."' AND storno_user = '0' AND rueckgabe is NULL AND id <> '".$Ausgabe['id']."'";
                $AbfrageWeitereReservierungenMitDiesemSchluessel = mysqli_query($link, $AnfrageWeitereReservierungenMitDiesemSchluessel);
                $AnzahlWeitereReservierungenMitDiesemSchluessel = mysqli_num_rows($AbfrageWeitereReservierungenMitDiesemSchluessel);

                if ($AnzahlWeitereReservierungenMitDiesemSchluessel > 0){

                    //Er darf den schlüssel noch weiter behalte

                } else if ($AnzahlWeitereReservierungenMitDiesemSchluessel == 0){

                    $Counter++;
                    $ErgebnisWeitereReservierungenMitDiesemSchluessel = mysqli_fetch_assoc($AbfrageWeitereReservierungenMitDiesemSchluessel);
                    $wart = lade_user_meta($Ausgabe['wart']);
                    $UserInfos = lade_user_meta($Ausgabe['user']);
                    $Schluessel = lade_schluesseldaten($Ausgabe['schluessel']);

                    $FahrtZuendeSeit = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($Reservierung['ende']));
                    $LetzteUsererinnerungLaden = lade_letze_erinnerung_schluesselrueckgabe($Ausgabe['reservierung']);
                    if($LetzteUsererinnerungLaden == NULL){
                        $LetzeErinnerung = "Nie erfolgt.";
                    } else {
                        $LetzeErinnerung = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($LetzteUsererinnerungLaden));
                    }

                    //Er soll den schlüssel zurück geben
                    $HTML .= "<li>";
                    if($Schluessel['RFID']!=''){
                        $HTML .= "<div class='collapsible-header'><i class='large material-icons ".$Schluessel['farbe_materialize']."'>vpn_key</i>Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']." <i class='tiny material-icons'>bluetooth_audio</i></div>";
                    } else {
                        $HTML .= "<div class='collapsible-header'><i class='large material-icons ".$Schluessel['farbe_materialize']."'>vpn_key</i>Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."</div>";
                    }
                    $HTML .= "<div class='collapsible-body'>";
                    $HTML .= "<div class='container'>";
                    $HTML .= "<form method='post'>";
                    $HTML .= "<ul class='collection'>";
                    $HTML .= "<li class='collection-item'>User: ".$UserInfos['vorname']." ".$UserInfos['nachname']."</li>";
                    $HTML .= "<li class='collection-item'>Austeilende*r Wart*in: ".$wart['vorname']." ".$wart['nachname']."</li>";
                    $HTML .= "<li class='collection-item'>Fahrtende: ".$FahrtZuendeSeit."</li>";
                    $HTML .= "<li class='collection-item'>Letzte Erinnerung: ".$LetzeErinnerung."</li>";
                    $HTML .= collection_item_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_rueckgabe_festhalten', 'Rückgabe', 'action', 'send', ''));
                    $HTML .= collection_item_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_rueckgabe_und_hannes', 'Mitnehmen', 'action', 'send', ''));
                    $HTML .= collection_item_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_erinnerung_senden', 'Erinnerung', 'action', 'send', ''));
                    $HTML .= "</ul>";
                    $HTML .= "</form>";
                    $HTML .= "</div>";
                    $HTML .= "</div>";
                    $HTML .= "</li>";
                }
            }
        }

        if ($Counter == 0){
            $HTML .= "<li>";
            $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Keine anstehenden R&uuml;ckgaben!</div>";
            $HTML .= "</li>";
        }
    }

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_anstehende_rueckgaben_parser(){

    $link = connect_db();

    $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe IS NOT NULL AND rueckgabe IS NULL ORDER BY schluessel ASC";
    $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
    $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);
    $UserID = lade_user_id();

    for ($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

        $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

        $ActionName = "action_schluessel_".$Ausgabe['schluessel']."_rueckgabe_festhalten";
        $ErinnerungName = "action_schluessel_".$Ausgabe['schluessel']."_erinnerung_senden";
        $PostNameGenerierenHerausnehmen = "action_schluessel_".$Ausgabe['schluessel']."_rueckgabe_und_hannes";

        if (isset($_POST[$ActionName])){

            $Antwort = schluessel_umbuchen($Ausgabe['schluessel'], '', 'rueckgabekasten', $UserID);
            schluesselrueckgabe_festhalten($Ausgabe['schluessel']);
            $Event = "Schl&uuml;ssel ".$Ausgabe['schluessel']." von ".$UserID." als zurückgegeben vermerkt";
            add_protocol_entry($UserID, $Event, 'schluessel');

        }

        if(isset($_POST[$PostNameGenerierenHerausnehmen])){
            schluesselrueckgabe_festhalten($Ausgabe['schluessel']);
            $Antwort = schluessel_umbuchen($Ausgabe['schluessel'], $UserID, '', $UserID);
            $Event = "Schl&uuml;ssel ".$Ausgabe['schluessel']." von ".$UserID." aus R&uuml;ckgabekasten genommen und die Rückgabe gespeichert";
            add_protocol_entry($UserID, $Event, 'schluessel');
        }


        if (isset($_POST[$ErinnerungName])){
            $Typ = "mail_erinnerung_schluesselrueckgabe_intervall-".$Ausgabe['reservierung']."";

                $Reservierung = lade_reservierung($Ausgabe['reservierung']);
                $UserMeta = lade_user_meta($Ausgabe['user']);
                $DifferenzTage = tage_differenz_berechnen(timestamp(), $Reservierung['ende']);

                $Bausteine = array();
                $Bausteine['[vorname_user]'] = $UserMeta['vorname'];
                $Bausteine['[tage_seit_ende_res]'] = $DifferenzTage;

            if(mail_senden('mail_erinnerung_schluesselrueckgabe_intervall', $UserMeta['mail'], $Bausteine, $Typ)){
                $Antwort['success'] = true;
                $Antwort['meldung'] = 'Erinnerungsmail erfolgreich gesendet!';
            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = 'Fehler beim Senden der Erinnerungsmail!';
            }
        }

    }

    return $Antwort;
}