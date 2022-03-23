<?php

function uebergabe_stornieren($ID, $Begruendung){

    $link = connect_db();
    $Benutzerrollen = lade_user_meta(lade_user_id());
    zeitformat();
    $Antwort = array();

    $Uebergabe = lade_uebergabe($ID);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $UserReservierung = lade_user_meta($Reservierung['user']);

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    //Keine Übergabe angegeben
    if (($ID == "") OR ($ID == 0)){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine zu stornierende &Uuml;bergabe angegeben!<br>";
    }

    //Übergabe bereits storniert
    if ($Uebergabe['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Die &Uuml;bergabe wurde bereits storniert!<br>";
    }

    if ($DAUcounter == 0){

        $Begruendung = htmlentities($Begruendung);

        $BausteineWartmails = array();
        $BausteineUsermails = array();
        $BausteineUsermails['vorname_user'] = $UserReservierung['vorname'];
        $BausteineUsermails['datum_resevierung'] = strftime("%A, %d. %B %G", strtotime($Reservierung['beginn']));
        $BausteineUsermails['begruendung_wart'] = $Begruendung;

        if ($Benutzerrollen['wart'] == true){

            //Wartmode - nur User und Übernahmen werden informiert - plus anderer Wart wird informiert
            $Anfrage = "UPDATE uebergaben SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."', storno_kommentar = '$Begruendung' WHERE id = '$ID'";
            if (mysqli_query($link, $Anfrage)){

                //User informieren
                mail_senden('uebergabe-storniert-user', $UserReservierung['mail'], $BausteineUsermails);

                //Übernahmen informieren
                $AnfrageLadeUebernahmen = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$Uebergabe['res']."' AND storno_user = '0'";
                $AbfrageLadeUebernahmen = mysqli_query($link, $AnfrageLadeUebernahmen);
                $AnzahlLadeUebernahmen = mysqli_num_rows($AbfrageLadeUebernahmen);

                for ($a = 1; $a <= $AnzahlLadeUebernahmen; $a++){

                    $Uebernahme = mysqli_fetch_assoc($AbfrageLadeUebernahmen);

                    $Begruendung = "Stornierung der Schl&uuml;ssel&uuml;bergabe der vor dir fahrenden Gruppe durch einen Stocherkahnwart: - ".$Begruendung."";
                    uebernahme_stornieren($Uebernahme['id'], $Begruendung);

                }

                //Wenn nicht eigene Übergabe - anderen Wart informieren
                if ($Uebergabe['wart'] != lade_user_id()){

                    $WartUebergabe = lade_user_meta($Uebergabe['wart']);
                    $WartStornierung = lade_user_meta(lade_user_id());
                    $BausteineWartmails['vorname_wart'] = $WartUebergabe['vorname'];
                    $BausteineWartmails['zeitangabe_uebergabe'] = strftime("%A, den %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));
                    $BausteineWartmails['loeschender_wart'] = "".$WartStornierung['vorname']." ".$WartStornierung['nachname']."";
                    $BausteineWartmails['kommentar_loeschender_wart'] = $Begruendung;
                    $BausteineWartmails['zeitpunkt_uebergabe'] = strftime("%A, %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));

                    mail_senden('uebergabe-storniert-anderer-wart', $WartUebergabe['mail'], $BausteineWartmails);
                    sms_senden('uebergabe-storniert', $BausteineWartmails, $Uebergabe['wart'], NULL);

                }

                $Antwort['success'] = true;

            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler";
            }

        } else {

            //Usermode - auch der Wart wird informiert
            $Anfrage = "UPDATE uebergaben SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."', storno_kommentar = '$Begruendung' WHERE id = '$ID'";

            if (mysqli_query($link, $Anfrage)){

                //Übernahmen informieren
                $AnfrageLadeUebernahmen = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$Uebergabe['res']."' AND storno_user = '0'";
                $AbfrageLadeUebernahmen = mysqli_query($link, $AnfrageLadeUebernahmen);
                $AnzahlLadeUebernahmen = mysqli_num_rows($AbfrageLadeUebernahmen);

                for ($a = 1; $a <= $AnzahlLadeUebernahmen; $a++){

                    $Uebernahme = mysqli_fetch_assoc($AbfrageLadeUebernahmen);

                    $Begruendung = "Stornierung der Schl&uuml;ssel&uuml;bergabe der vor dir fahrenden Gruppe. Somit kann dir nicht garantiert werden, dass du von dieser Gruppe den Schl&uuml;ssel &uuml;bernehmen kannst!";
                    uebernahme_stornieren($Uebernahme['id'], $Begruendung);

                }

                //Wart informieren
                $WartUebergabe = lade_user_meta($Uebergabe['wart']);
                $BausteineWartmails['[vorname_wart]'] = $WartUebergabe['vorname'];
                $BausteineWartmails['[zeitangabe_uebergabe]'] = strftime("%A, den %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));
                $BausteineWartmails['[kommentar_user]'] = $Begruendung;
                $BausteineWartmails['[zeitpunkt_uebergabe]'] = strftime("%A, %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));

                //Nach eigenen Einstellungen
                $Usersettings = lade_user_meta($Uebergabe['wart']);

                //Email
                if ($Usersettings['mail-wart-storno-uebergabe'] == "true"){
                    mail_senden('uebergabe-storniert-wart', $WartUebergabe['mail'], $BausteineWartmails);
                }

                //SMS
                if ($Usersettings['sms-wart-storno-uebergabe'] == "true"){
                    if (lade_xml_einstellung('sms-active') == "on"){
                        sms_senden('uebergabe-storniert', $BausteineWartmails, $Uebergabe['wart'], NULL);
                    }
                }

                $Antwort['success'] = true;

            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler";
            }

        }

    } else if ($DAUcounter > 0){

        $Antwort['success'] = false;
        $Antwort['meldung'] = $DAUerror;

    }

    return $Antwort;
}

function uebergabe_hinzufuegen($Res, $Wart, $Termin, $Beginn, $Kommentar, $Creator){

    $link = connect_db();
    $Timestamp = timestamp();
    $Antwort = array();
    zeitformat();

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    if ($Res == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss eine Reservierungsnummer angegeben sein!<br>";
    }

        //Hat die res schon ne gültige Übergabe?
        $AnfrageResSchonVersorgt = "SELECT id FROM uebergaben WHERE res = '$Res' AND storno_user = '0'";
        $AbfrageResSchonVersorgt = mysqli_query($link, $AnfrageResSchonVersorgt);
        $AnzahlResSchonVersorgt = mysqli_num_rows($AbfrageResSchonVersorgt);

        if ($AnzahlResSchonVersorgt > 0){
            $DAUcounter++;
            $DAUerror .= "F&uuml;r diese Resevierung gibt es schon eine Schl&uuml;ssel&uuml;bergabe! Bitte tauschen oder stornieren!<br>";
        }

    if ($Beginn == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen &Uuml;bergabezeitpunkt aussuchen!<br>";
    }

    if ($Wart == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Wart angegeben sein!<br>";
    }

    if ($Termin == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Terminangebot angegeben sein!<br>";
    }

    //Ist das angebot inzwischen storniert?
    $Terminangebot = lade_terminangebot($Termin);
    if ($Terminangebot['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Das Terminangebot ist inzwischen storniert!<br>";
    }

    //Limitierung schon abgelaufen?
    if($Terminangebot['terminierung'] != NULL){
        if (time() > strtotime($Terminangebot['terminierung'])){
            $DAUcounter++;
            $DAUerror .= "Das Terminangebot ist inzwischen abgelaufen!<br>";
        }
    }

    //Hat der User überhaupt noch schlüssel?
    if(wart_verfuegbare_schluessel($Terminangebot['wart']) == 0){
        $DAUcounter++;
        $DAUerror .= "Leider sind bei diesem Wart inzwischen alle verf&uuml;gbaren Schl&uuml;ssel vergeben!<br>";
    }

    //Ist die Reservierung inzwischen storniert?
    $Reservierung = lade_reservierung($Res);
    if ($Reservierung['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Deine Reservierung wurde inzwischen storniert!<br>";
    }

    //Ist der User selber gesperrt?
    $UserMeta = lade_user_meta($Reservierung['user']);
    if ($UserMeta['ist_gesperrt'] == 'true'){
        $DAUcounter++;
        $DAUerror .= "Dein Account wurde leider f&uuml;r Buchungen gesperrt. Bitte setze dich mit uns in Verbindung!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;

    } else if ($DAUcounter == 0){

        $AnfrageUebergabeEintragen = "INSERT INTO uebergaben (res, wart, terminangebot, beginn, angelegt_am, kommentar) VALUES ('$Res', '$Wart', '$Termin', '$Beginn', '$Timestamp', '$Kommentar')";
        if (mysqli_query($link, $AnfrageUebergabeEintragen)){

            $Terminangebot = lade_terminangebot($Termin);
            $Reservierung = lade_reservierung($Res);
            $UserMeta = lade_user_meta($Reservierung['user']);
            $Warteinstellungen = lade_user_meta($Wart);
            $KontaktdatenWart = $Warteinstellungen['vorname']." ".$Warteinstellungen['nachname']."<br>";
            if($Warteinstellungen['mail-userinfo']=='true'){
                $KontaktdatenWart .= "Mail: ".$Warteinstellungen['mail']."<br>";
            }
            if($Warteinstellungen['tel-userinfo']=='true'){
                $KontaktdatenWart .= "Telefon: ".$Warteinstellungen['telefon']."<br>";
            }

            //Mail an User
            $BausteineUser['[vorname_user]']=$UserMeta['vorname'];
            $BausteineUser['[datum_uebergabe]']=strftime("%A, %d. %B %G", strtotime($Beginn));;
            $BausteineUser['[uhrzeit_beginn]']=date('G:i', strtotime($Beginn)).' Uhr';
            $BausteineUser['[ort_uebergabe]']=$Terminangebot['ort'];
            $BausteineUser['[dauer_uebergabe]']=lade_xml_einstellung('dauer-uebergabe-minuten');
            $BausteineUser['[reservierungsnummer]']=$Res;
            $BausteineUser['[kosten_reservierung]']=kosten_reservierung($Res).'&euro;';
            $BausteineUser['[kontakt_wart]']=$KontaktdatenWart;
            if($Creator==$Reservierung['user']){
                mail_senden('uebergabe-angelegt-selbst', $UserMeta['mail'], $BausteineUser);
            } else {
                mail_senden('uebergabe-angelegt-wart', $UserMeta['mail'], $BausteineUser);
            }

            //Mail an Wart
            if($Warteinstellungen['mail-wart-neue-uebergabe']=='true'){
                $Bausteine['[vorname_wart]']=$Warteinstellungen['vorname'];
                $Bausteine['[datum_uebergabe]']=strftime("%A, %d. %B %G", strtotime($Beginn));;
                $Bausteine['[uhrzeit_beginn]']=date('G:i', strtotime($Beginn)).' Uhr';
                $Bausteine['[ort_uebergabe]']=$Terminangebot['ort'];
                $Bausteine['[reservierungsnummer]']=$Res;
                $Bausteine['[kosten_reservierung]']=kosten_reservierung($Res).'&euro;';
                $Bausteine['[kontakt_user]']=$UserMeta['vorname'].' '.$UserMeta['nachname'];
                if($Kommentar!=''){
                    $Bausteine['[kommentar_user]']='<p>Kommentar des Nutzers:<br>'.$Kommentar.'</p>';
                } else {
                    $Bausteine['[kommentar_user]']='';
                }

                mail_senden('uebergabe-bekommen-wart', $Warteinstellungen['mail'], $Bausteine);
            }

            //SMS        <text>Neue Übergabe: Wann? [zeitpunkt_uebergabe], Wo? [ort_uebergabe], Wer? [name_user] - [tel_user]:) [kommentar_user]
            if ($Warteinstellungen['sms-wart-neue-uebergabe'] == "true"){
                if (lade_xml_einstellung('sms-active') == "on"){
                    $Bausteine['[zeitpunkt_uebergabe]']=strftime("%A, %d. %B %G", strtotime($Beginn)).' '.date('G:i', strtotime($Beginn)).' Uhr';
                    $Bausteine['[ort_uebergabe]']=$Terminangebot['ort'];
                    $Bausteine['[name_user]']=$UserMeta['vorname'].' '.$UserMeta['nachname'];
                    if($Kommentar!=''){
                        $Bausteine['[kommentar_user]']='Kommentar des Nutzers: '.$Kommentar.'';
                    } else {
                        $Bausteine['[kommentar_user]']='';
                    }
                    sms_senden('neue-uebergabe-wart', $Bausteine, $Wart, NULL);
                }
            }

            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Übergabe erfolgreich eingetragen!";

        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler";
        }
    }

    return $Antwort;
}

function uebergabe_durchfuehren($IDuebergabe, $Schluessel, $GezahlterBetrag, $AndererPreis, $Gratisfahrt, $StatusVerified){

    $link = connect_db();
    $Antwort = array();
    $Uebergabe = lade_uebergabe($IDuebergabe);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $UserMeta = lade_user_meta($Reservierung['user']);

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    //Kein Schlüssel
    if (($Schluessel == "") OR ($Schluessel == 0)){
        $DAUcounter++;
        $DAUerror .= "Du hast keinen Schl&uuml;ssel angegeben!<br>";
    }

    //Schlüssel steht nicht mehr zur Verfügung
    $AnfrageLadeSchluesselVerfuegbar = "SELECT akt_user FROM schluessel WHERE id = '$Schluessel'";
    $AbfrageLadeSchluesselVerfuegbar = mysqli_query($link, $AnfrageLadeSchluesselVerfuegbar);
    $ErgebnisLadeSchluesselVerfuegbar = mysqli_fetch_assoc($AbfrageLadeSchluesselVerfuegbar);

    if ($ErgebnisLadeSchluesselVerfuegbar['akt_user'] != $Uebergabe['wart']){
        $DAUcounter++;
        $DAUerror .= "Der ausgew&auml;hlte Schl&uuml;ssel steht dir nicht mehr zur Ausgabe zur Verf&uuml;gung!<br>";
    }

    //Keine Übergabe angegeben
    if (($IDuebergabe == "") OR ($IDuebergabe == 0)){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine &Uuml;bergabeID &uuml;bergeben!<br>";
    }

    //Übergabe storniert
    if ($Uebergabe['storno_user'] != 0){
        $DAUcounter++;
        $DAUerror .= "Die &Uuml;bergabe wurde inzwischen storniert!<br>";
    }

    //Übergabe inzwischen durchgeführt
    if ($Uebergabe['durchfuehrung'] != NULL){
        $DAUcounter++;
        $DAUerror .= "Die &Uuml;bergabe wurde inzwischen durchgef&uuml;hrt!<br>";
    }

    //Gratisfahrt gewählt aber Betrag gezahlt
    if (($Gratisfahrt == TRUE) AND ($GezahlterBetrag > 0)){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt angeben und trotzdem Geld einnehmen!<br>";
    }

    //Gratisfahrt und anderer Preis angegeben
    if (($Gratisfahrt == TRUE) AND ($AndererPreis > 0)){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt und gleichzeitig einen anderen Tarif angeben!<br>";
    }

    //Zu viel gezahlt -> Betrag unrealistisch
    $Grenze = intval(lade_xml_einstellung('max-kosten-einer-reservierung'));
    if (($GezahlterBetrag > $Grenze) OR ($AndererPreis > $Grenze)){
        $DAUcounter++;
        $DAUerror .= "Der von dir eingenommene Betrag &uuml;bersteigt die H&ouml;chsteinnahmegrenze von ".$Grenze."&euro;!<br>";
    }

    //User inzwischen gesperrt - keine Übergabe durchführen!
    if ($UserMeta['ist_gesperrt'] == 'true'){
        $DAUcounter++;
        $DAUerror .= "Der User ist inzwischen gesperrt! Bitte &uuml;berpr&uuml;fen!<br>";
    }

    //Alternativer Preis negativ
    if ($AndererPreis < 0){
        $DAUcounter++;
        $DAUerror .= "Alternativer Preis darf nicht negativ sein!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['error'] = $DAUerror;

    } else {

        if ($Gratisfahrt == TRUE){
            reservierung_auf_gratis_setzen($Uebergabe['res']);
        }

        if ($AndererPreis > 0){
            reservierung_preis_aendern($Uebergabe['res'], $AndererPreis);
        }

        if(uebergabe_durchfuehrung_festhalten($IDuebergabe, $Schluessel)){
            if(schluessel_an_user_ausgeben($IDuebergabe, $Schluessel, lade_user_id())){
                if(einnahme_uebergabe_festhalten($IDuebergabe, $GezahlterBetrag, lade_user_id())){
                    if ($StatusVerified == TRUE){
                        if(verify_nutzergruppe($Reservierung['user'], lade_user_id())){
                            $Antwort['success'] = TRUE;
                            $Antwort['error'] = $DAUerror;
                        } else {
                            $Antwort['success'] = FALSE;
                            $Antwort['error'] = 'Fehler beim Festhalten der Nutzergruppenverifizierung!!';
                        }
                    } else {
                        $Antwort['success'] = TRUE;
                        $Antwort['error'] = $DAUerror;
                    }
                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['error'] = 'Fehler beim Festhalten der Einnahme!!';
                }
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['error'] = 'Fehler beim Ausgeben des Schlüssels!';
            }
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['error'] = 'Fehler beim Festhalten der Übergabe!';
        }
    }

    return $Antwort;
}

function uebergabe_durchfuehrung_festhalten($IDuebergabe, $Schluessel){

    $link = connect_db();
    $Timestamp = timestamp();

    $Anfrage = "UPDATE uebergaben SET durchfuehrung = '$Timestamp', schluessel = '$Schluessel' WHERE id = '$IDuebergabe'";
    if (mysqli_query($link, $Anfrage)){
        return true;
    } else {
        return false;
    }
}

function spontanuebergabe_durchfuehren($IDres, $IDschluessel, $Gratisfahrt, $AndererPreis, $GezahlterBetrag){

    $link =connect_db();

    $DAUcounter = 0;
    $DAUerror = "";
    $Antwort = array();

    //Reservierung inzwischen schon versorgt
    $AnfrageLadeUebergabeResevierung = "SELECT id FROM uebergaben WHERE res = '$IDres' AND storno_user = '0' AND durchfuehrung IS NOT NULL";
    $AbfrageLadeUebergabeResevierung = mysqli_query($link, $AnfrageLadeUebergabeResevierung);
    $AnzahlLadeUebergabeResevierung = mysqli_num_rows($AbfrageLadeUebergabeResevierung);

    if($AnzahlLadeUebergabeResevierung > 0){
        $DAUcounter++;
        $DAUerror .= "Die Reservierung wurde inzwischen schon versorgt!<br>";
    }

    //Reservierung inzwischen storniert
    $Reservierung = lade_reservierung($IDres);
    if($Reservierung['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Die Reservierung wurde inzwischen schon storniert!<br>";
    }

    //Anderer Preis ist keine Zahl
    if((!is_numeric($AndererPreis)) AND ($AndererPreis != "")){
        $DAUcounter++;
        $DAUerror .= "Alternativer Preis: Bitte gib nur ganze Zahlen an!<br>";
    }

    //Eingegebener Betrag ist keine Zahl
    if((!is_numeric($GezahlterBetrag)) AND ($GezahlterBetrag != "")){
        $DAUcounter++;
        $DAUerror .= "Zahlung: Bitte gib nur ganze Zahlen an!<br>";
    }

    //Schlüssel inzwischen schon vergeben
    $Schluessel = lade_schluesseldaten($IDschluessel);
    if($Schluessel['akt_user'] != lade_user_id()){
        $DAUcounter++;
        $DAUerror .= "Der von dir eingegebene Schl&uuml;ssel sollte nicht mehr zur Verf&uuml;gung stehen!<br>";
    }

    //Keine Reservierung gewählt
    if($IDres == ""){
        $DAUcounter++;
        $DAUerror .= "Du hast keine Reservierung ausgesucht!<br>";
    }

    //Kein Schlüssel gewählt
    if($IDschluessel == ""){
        $DAUcounter++;
        $DAUerror .= "Du hast keinen Schl&uuml;ssel ausgesucht!<br>";
    }

    //Gratisfahrt angekreuzt und Zahlung
    if(isset($Gratisfahrt) AND ($AndererPreis != "")){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt und einen verg&uuml;nstigten Tarif angeben!<br>";
    }

    //Gratisfahrt angekreuzt und vergünstigter Tarif
    if(isset($Gratisfahrt) AND ($GezahlterBetrag != "")){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt und gleichzeitig eine Zahlung angeben!<br>";
    }

    if($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        //Andere Übergabe stornieren, falls vorhanden
        $AnfrageLadeAndereUebergabe = "SELECT id FROM uebergaben WHERE res = '$IDres' AND storno_user = '0'";
        $AbfrageLadeAndereUebergabe = mysqli_query($link, $AnfrageLadeAndereUebergabe);
        $AnzahlLadeAndereUebergabe = mysqli_num_rows($AbfrageLadeAndereUebergabe);

        if ($AnzahlLadeAndereUebergabe > 0){
            $Uebergabe = mysqli_fetch_assoc($AbfrageLadeAndereUebergabe);
            uebergabe_stornieren($Uebergabe['id'], 'Es wurde an anderer Stelle eine Spontan&uuml;bergabe durchgef&uuml;hrt');
        }

        //Andere Übernahme stornieren, falls vorhanden
        $AnfrageLadeAndereUebernahme = "SELECT id FROM uebernahmen WHERE reservierung = '$IDres' AND storno_user = '0'";
        $AbfrageLadeAndereUebernahme = mysqli_query($link, $AnfrageLadeAndereUebernahme);
        $AnzahlLadeAndereUebernahme = mysqli_num_rows($AbfrageLadeAndereUebernahme);

        if ($AnzahlLadeAndereUebernahme > 0){
            $Uebernahme = mysqli_fetch_assoc($AbfrageLadeAndereUebernahme);
            uebernahme_stornieren($Uebernahme['id'], '');
        }

        //Spontanübergabe eintragen
        $Ergebnis = spontanuebergabe_eintragen($IDres, $IDschluessel, $Gratisfahrt, $AndererPreis, $GezahlterBetrag, lade_user_id());

        if ($Ergebnis['success'] == TRUE){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Spontan&uuml;bergabe erfolgreich eingetragen!";
        } else if ($Ergebnis['success'] == FALSE){
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $Ergebnis['meldung'];
        }
    }

    return $Antwort;
}

function spontanuebergabe_eintragen($IDres, $IDschluessel, $Gratisfahrt, $AndererPreis, $GezahlterBetrag, $Wart){

    $link = connect_db();
    $Errorcounter = 0;
    $Error = "";
    $Antwort = array();
    $Timestamp = timestamp();

    //Reservierung updaten bei Gratisfahrt
    if($Gratisfahrt == TRUE){
        if (!reservierung_auf_gratis_setzen($IDres)){
            $Errorcounter++;
            $Error .= "Fehler beim Eintragen der Gratisfahrt!<br>";
        }
    }

    //Reservierung updaten bei Anderer Preis
    if ($AndererPreis != ""){
        reservierung_preis_aendern($IDres, $AndererPreis);
    }

    //Übergabeobjekt anlegen
    $AnfrageSpontanuebergabeEintragen = "INSERT INTO uebergaben (res, wart, terminangebot, beginn, durchfuehrung, schluessel, angelegt_am, kommentar) VALUES ('$IDres', '$Wart', '0', '".$Timestamp."', '".$Timestamp."', '$IDschluessel', '".$Timestamp."', 'Spontan&uuml;bergabe')";
    if (!mysqli_query($link, $AnfrageSpontanuebergabeEintragen)){
        $Errorcounter++;
        $Error .= "Fehler beim Eintragen der Spontan&uuml;bergabe!<br>";
    } else {
        $AnfrageLadeIDuebergabe = "SELECT id FROM uebergaben WHERE angelegt_am = '".$Timestamp."' AND wart = '$Wart' AND storno_user = '0'";
        $AbfrageLadeIDuebergabe = mysqli_query($link, $AnfrageLadeIDuebergabe);
        $Spontanuebergabe = mysqli_fetch_assoc($AbfrageLadeIDuebergabe);

        //Schlüsselausgabe eintragen
        schluessel_an_user_ausgeben($Spontanuebergabe['id'], $IDschluessel, $Wart);
    }

    //Einnahme festhalten
    if ($GezahlterBetrag != ""){
        $Forderung = lade_forderung_res($IDres);
        $Konto = lade_konto_user($Wart);
        if(!einnahme_festhalten($Forderung['id'], $Konto['id'], $GezahlterBetrag, 19)){
            $Errorcounter++;
            $Error .= "Fehler beim Eintragen der Einnahme!<br>";
        }
    }

    //Meldungen zurückgeben
    if ($Errorcounter == 0){
        $Antwort['success'] = true;
    } else if ($Errorcounter > 0){
        $Antwort['success'] = false;
        $Antwort['meldung'] = $Error;
    }

    return $Antwort;
}

function lade_terminangebot($IDtermin){

    $link = connect_db();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Terminangebot = mysqli_fetch_assoc($Abfrage);

    return $Terminangebot;
}

function lade_uebergabe($IDuebergabe){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebergaben WHERE id = '$IDuebergabe'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Uebergabe = mysqli_fetch_assoc($Abfrage);

    return $Uebergabe;

}

function infos_section($IDangebot){

    $Angebot = lade_terminangebot($IDangebot);
    zeitformat();

    $HTML = "<div class='section'>";
    $HTML .= "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $HTML .= "<h5>Informationen zum &Uuml;bergabeangebot</h5>";
    $HTML .= "<ul class='collection'>";
    $HTML .= "<li class='collection-item'>Datum: ".strftime("%A, %d. %B %G", strtotime($Angebot['von']))."</li>";
    $HTML .= "<li class='collection-item'>Zeitraum: ".date("G:i", strtotime($Angebot['von']))." bis ".date("G:i", strtotime($Angebot['bis']))." Uhr</li>";
    $HTML .= "<li class='collection-item'>Treffpunkt: ".$Angebot['ort']."</li>";
    $HTML .= "<li class='collection-item'>Entstandene &Uuml;bergaben: ".lade_entstandene_uebergaben($IDangebot)."</li>";
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}

//LISTENELEMENTE
function terminangebot_listenelement_generieren($IDangebot){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDangebot'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Angebot = mysqli_fetch_assoc($Abfrage);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %B %G %H:%M", strtotime($Angebot['von']))."</b>&nbsp;bis&nbsp;<b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";
    $ZeitraumMobil = "<b>".strftime("%a, %d. %b - %H:%M", strtotime($Angebot['von']))."</b>&nbsp;bis&nbsp;<b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";

    if($Angebot['terminierung'] == NULL){
        $Terminierung = "keine Terminierung";
    } else {
        $Terminierung = "Terminierung: ".strftime("%a, %d. %b - %H:%M Uhr", strtotime($Angebot['terminierung']))."";
    }

    if($Angebot['kommentar'] == ""){
        $Kommentar = "kein Kommentar";
    } else {
        $Kommentar = $Angebot['kommentar'];
    }

    //Ausgabe
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons'>label_outline</i>".$Zeitraum."</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<ul class='collection'>";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>alarm_on</i> ".$Terminierung."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Angebot['ort']."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>settings_ethernet</i> ".lade_entstandene_uebergaben($IDangebot)."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Kommentar."";
    $HTML .= "<li class='collection-item'> <a href='angebot_bearbeiten.php?id=".$IDangebot."'><i class='tiny material-icons'>mode_edit</i> bearbeiten</a> <a href='angebot_loeschen.php?id=".$IDangebot."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</li>";
    $HTML .= "<li>";
    $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons'>label_outline</i>".$ZeitraumMobil."</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<ul class='collection'>";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>alarm_on</i> ".$Terminierung."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Angebot['ort']."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>settings_ethernet</i> ".lade_entstandene_uebergaben($IDangebot)."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Kommentar."";
    $HTML .= "<li class='collection-item'> <a href='angebot_bearbeiten.php?id=".$IDangebot."'><i class='tiny material-icons'>mode_edit</i> bearbeiten</a> <a href='angebot_loeschen.php?id=".$IDangebot."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function uebergabe_listenelement_generieren($IDuebergabe, $Action){

    zeitformat();
    $Uebergabe = lade_uebergabe($IDuebergabe);
    $Terminangebot = lade_terminangebot($Uebergabe['terminangebot']);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $UserRes = lade_user_meta($Reservierung['user']);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %B %G %H:%M Uhr", strtotime($Uebergabe['beginn']))."</b>";
    $ZeitraumMobil = "<b>".strftime("%a, %d. %b %H:%M Uhr", strtotime($Uebergabe['beginn']))."</b>";

    //Ausgabe
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons'>today</i>Schl&uuml;ssel&uuml;bergabe:&nbsp;".$Zeitraum."</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<ul class='collection'>";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Terminangebot['ort']."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> <a href='benutzermanagement_wart.php?user=".$Reservierung['user']."'>".$UserRes['vorname']." ".$UserRes['nachname']."</a>";
    if($Uebergabe['kommentar'] != ""){
        $HTML .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Uebergabe['kommentar']."";
    }
    if($Action == TRUE){
        $HTML .= collection_item_builder(button_link_creator('Durchführen', 'uebergabe_durchfuehren.php?id='.$IDuebergabe.'', 'play_circle_filled', '')."&nbsp;".button_link_creator('Absagen', 'uebergabe_loeschen_wart.php?id='.$IDuebergabe.'', 'delete', ''));
    }
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</li>";
    $HTML .= "<li>";
    $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons'>today</i>&Uuml;bergabe: ".$ZeitraumMobil."</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<ul class='collection'>";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Terminangebot['ort']."";
    $HTML .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> <a href='benutzermanagement_wart.php?user=".$Reservierung['user']."'>".$UserRes['vorname']." ".$UserRes['nachname']."</a>";
    if($Uebergabe['kommentar'] != ""){
        $HTML .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Uebergabe['kommentar']."";
    }
    if($Action == TRUE){
        $HTML .= collection_item_builder(button_link_creator('Durchführen', 'uebergabe_durchfuehren.php?id='.$IDuebergabe.'', 'play_circle_filled', '')."&nbsp;".button_link_creator('Absagen', 'uebergabe_loeschen_wart.php?id='.$IDuebergabe.'', 'delete', ''));
    }

    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function termin_listenelement_generieren($IDtermin){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM termine WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Termin = mysqli_fetch_assoc($Abfrage);

    $Creator = lade_user_meta($Termin['create_user']);
    $User = lade_user_meta($Termin['user']);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))."</b>";

    if($Termin['grund']=='ausgleich'){
        $Ausgleich = lade_offene_ausgleiche_res($Termin['id_grund']);
        $Forderung = lade_forderung_res($Termin['id_grund']);
        $Auszahlung = lade_einnahmen_forderung($Forderung['id']);
        $BisherigeAuszahlungen = lade_gezahlte_betraege_ausgleich($Ausgleich['id']);

        $Class="Geldrückzahlung";
        $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> ".$Class."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>info_outline</i> Auszahlbetrag: ".$Auszahlung."&euro;";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>info_outline</i> Bisherige Auszahlungen: ".$BisherigeAuszahlungen."&euro;";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Creator['vorname']." ".$Creator['nachname']."";
        $Content .= "<li class='collection-item'> <a href='termin_abhaken.php?termin=".$Termin['id']."'><i class='tiny material-icons'>check</i> abhaken</a> <a href='termin_loeschen.php?termin=".$Termin['id']."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    } elseif ($Termin['grund']=='grill_out'){
        $Class="Grillübergabe";
    } elseif ($Termin['grund']=='grill_return'){
        $Class="Grillrückgabe";
    } else {
        $Class=$Termin['grund'];
        $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> ".$Class."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Creator['vorname']." ".$Creator['nachname']."";
        $Content .= "<li class='collection-item'> <a href='termin_abhaken.php?termin=".$Termin['id']."'><i class='tiny material-icons'>check</i> abhaken</a> <a href='termin_loeschen.php?termin=".$Termin['id']."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    }



    //Ausgabe
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header'><i class='large material-icons'>label_outline</i>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))." - ".$Class.": ".$User['vorname']."</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<ul class='collection'>";
    $HTML .= $Content;
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function termin_listenelement_user_generieren($IDtermin){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM termine WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Termin = mysqli_fetch_assoc($Abfrage);

    $Creator = lade_user_meta($Termin['create_user']);
    $User = lade_user_meta($Termin['user']);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))."</b>";

    if($Termin['grund']=='ausgleich'){
        $Ausgleich = lade_offene_ausgleiche_res($Termin['id_grund']);
        $Forderung = lade_forderung_res($Termin['id_grund']);
        $Auszahlung = lade_einnahmen_forderung($Forderung['id']);
        $BisherigeAuszahlungen = lade_gezahlte_betraege_ausgleich($Ausgleich['id']);

        $Class="Geldrückzahlung";
        $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> ".$Class."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>info_outline</i> Auszahlbetrag: ".$Auszahlung."&euro;";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Creator['vorname']." ".$Creator['nachname']."";
        $Content .= "<li class='collection-item'> <a href='termin_loeschen.php?termin=".$Termin['id']."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    } elseif ($Termin['grund']=='grill_out'){
        $Class="Grillübergabe";
    } elseif ($Termin['grund']=='grill_return'){
        $Class="Grillrückgabe";
    } else {
        $Class=$Termin['grund'];
        $Content = "<li class='collection-item'><i class='tiny material-icons'>class</i> ".$Class."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>comment</i> Kommentar: ".$Termin['kommentar']."";
        $Content .= "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Creator['vorname']." ".$Creator['nachname']."";
        $Content .= "<li class='collection-item'> <a href='termin_loeschen.php?termin=".$Termin['id']."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    }



    //Ausgabe
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header'><i class='large material-icons'>label_outline</i>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))." - ".$Class."</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<ul class='collection'>";
    $HTML .= $Content;
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function spontanuebergabe_listenelement_parser(){

    $Ergebnis = array();

    if (isset($_POST['action_spontanuebergabe_durchfuehren'])){
        $Ergebnis = spontanuebergabe_durchfuehren($_POST['reservierung'], $_POST['schluessel'], $_POST['gratis_fahrt'], $_POST['verguenstigung'], $_POST['einnahme']);
    }

    return $Ergebnis;
}

function terminangebot_hinzufuegen($IDwart, $Beginn, $Ende, $Ort, $Kommentar, $Terminierung){

    //Eintragen
    $link = connect_db();
    $Timestamp = timestamp();

    //DAU
    $DAUcounter = 0;
    $DAUerror = "";

    if($Ort == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Angabe zum Treffpunkt geben!<br>";
    }

    if(!isset($IDwart)){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Wart angegeben sein!<br>";
    }

    if (strtotime($Ende) < strtotime($Beginn)){
        $DAUcounter++;
        $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
    }

    if (strtotime($Beginn) === strtotime($Ende)){
        $DAUcounter++;
        $DAUerror .= "Die Zeitpunkte d&uuml;rfen nicht identisch sein! Second check!<br>";
    }

    if (time() > strtotime($Ende)){
        $DAUcounter++;
        $DAUerror .= "Du kannst kein Angebot f&uuml;r die Vergangenheit eingeben!<br>";
    }

    //Überprüfe clash mit vorhandenem Angebot
    $AnfrageClash = "SELECT id FROM terminangebote WHERE wart = '$IDwart' AND (((von <= '$Beginn') AND (bis >= '$Ende')) OR (('$Beginn' < von) AND ('$Ende' > von)) OR (('$Beginn' < bis) AND ('$Ende' > bis))) AND storno_user = '0'";
    $AbfrageClash = mysqli_query($link, $AnfrageClash);
    $AnzahlClash = mysqli_num_rows($AbfrageClash);

    if ($AnzahlClash > 0){
        $DAUcounter++;
        $DAUerror .= "Zu dem angegebenen Zeitpunkt hast du bereits ein Angebot im System!<br>";
    }

    //DAU auswerten
    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else {

        if($Terminierung == NULL){
            $Anfrage = "INSERT INTO terminangebote (wart, von, bis, terminierung, ort, kommentar, create_time, create_user) VALUES ('$IDwart', '$Beginn','$Ende', NULL,'$Ort','$Kommentar','$Timestamp','".lade_user_id()."')";
        } else {
            $Anfrage = "INSERT INTO terminangebote (wart, von, bis, terminierung, ort, kommentar, create_time, create_user) VALUES ('$IDwart', '$Beginn','$Ende','$Terminierung','$Ort','$Kommentar','$Timestamp','".lade_user_id()."')";
        }

        if (mysqli_query($link, $Anfrage)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Terminangebot erfolgreich eingetragen!";
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler";
        }
    }

    return $Antwort;
}

function lade_entstandene_uebergaben($IDangebot){

    $link = connect_db();
    $Antwort = "";

    $Anfrage = "SELECT id FROM uebergaben WHERE terminangebot = '$IDangebot' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){
        $Antwort .= "Bislang keine resultierenden &Uuml;bergaben.";
    } else if ($Anzahl > 1){
        $Antwort .= "Bislang ".$Anzahl." resultierende &Uuml;bergaben.";
    } else if ($Anzahl == 1){
        $Antwort .= "Bislang eine resultierende &Uuml;bergabe.";
    }

    return $Antwort;
}

function uebergabe_planen_listenelement_generieren(){

    $HTML = "";
    if(isset($_POST['uebergabe_vorplanen_datum'])){
        $PlaceholderDatum = $_POST['uebergabe_vorplanen_datum'];
        $PlaceholderZeit = "".$_POST['uebergabe_vorplanen_zeit'].":00";
    } else {
        $PlaceholderDatum = date('Y-m-d');
        $PlaceholderZeit = date('G:i');
    }

    $Parser = uebergabe_planen_listenelement_parser();
    if($Parser!=''){
        $Content = "<div class='section center-align'>".$Parser."</div>";
    } else {
        $Content = '';
    }

    $Content .= "<h5 class='center-align'>Reservierung w&auml;hlen</h5>";
    $TableHTML = table_row_builder(table_header_builder('Reservierung wählen').table_data_builder(dropdown_aktive_res_spontanuebergabe('reservierung_uebergabe_vorplanen')));
    $TableHTML .= table_form_swich_item('Als Gratisfahrt eintragen', 'gratis_fahrt_uebergabe_vorplanen', 'Nein', 'Ja', '', false);
    $TableHTML .= table_form_select_item('Verg&uuml;nstigter Tarif', 'verguenstigung_uebergabe_vorplanen', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $_POST['verguenstigung_uebergabe_vorplanen'], '&euro;','Verg&uuml;nstigter Tarif', '');
    $Content .= table_builder($TableHTML);
    $Content .= "<h5 class='center-align'>&Uuml;bergabeort w&auml;hlen</h5>";
    $TableHTML = table_row_builder(table_header_builder('Vorlage w&auml;hlen').table_data_builder(dropdown_vorlagen_ortsangaben('ortsangabe_uebergabe_vorplanen', lade_user_id(), $_POST['ortsangabe_uebergabe_vorplanen'])));
    $TableHTML .= table_form_string_item('Ortsangabe manuell eingeben', 'ortsangabe_schriftlich_uebergabe_vorplanen', $_POST['ortsangabe_schriftlich_uebergabe_vorplanen'], false);
    $TableHTML .= table_form_string_item('Optional: weiterer Kommentar', 'kommentar_uebergabe_vorplanen', $_POST['kommentar_uebergabe_vorplanen'], false);
    $Content .= table_builder($TableHTML);
    $Content .= "<h5 class='center-align'>&Uuml;bergabezeit w&auml;hlen</h5>";
    $TableHTML = table_form_datepicker_reservation_item('Datum', 'uebergabe_vorplanen_datum', $PlaceholderDatum, false, true, '');
    $TableHTML .= table_form_timepicker_item('Uhrzeit', 'uebergabe_vorplanen_zeit', $PlaceholderZeit, false, true, '');
    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_uebergabe_vorplanen_durchfuehren', 'Anlegen', 'action', 'send', '')).table_data_builder(''));
    $Content .= table_builder($TableHTML);
    $Content = form_builder($Content, '#', 'post');
    $Titel = "&Uuml;bergabe vorplanen";
    $HTML .= collapsible_item_builder($Titel, $Content, 'open_in_browser');

    return $HTML;
}

function uebergabe_planen_listenelement_parser(){

    if (isset($_POST['action_uebergabe_vorplanen_durchfuehren'])){

        //Reservierung
        $ResID = $_POST['reservierung_uebergabe_vorplanen'];

        //GRatisfahrt?
        if (isset($_POST['gratis_fahrt_uebergabe_vorplanen'])){
            $Gratis = TRUE;
        } else {
            $Gratis = FALSE;
        }

        //Vergünstigung?
        $Verguenstigung = $_POST['verguenstigung_uebergabe_vorplanen'];

        //Übergabeort?
        $Dropdown = $_POST['ortsangabe_uebergabe_vorplanen'];
        $Manuell = $_POST['ortsangabe_schriftlich_uebergabe_vorplanen'];
        if (($Dropdown != "") AND ($Manuell != "")){
            return 'Du kannst nicht gleichzeitig eine Vorlage nutzen und einen &Uuml;bergabeort schriftlich eingeben!';
        } else {
            $Uebergabeort = "".$Dropdown."".$Manuell."";
        }

        //Kommentar
        $Kommentar = $_POST['kommentar_uebergabe_vorplanen'];

        //Übergabezeitpunkt
        $Zeitpunkt = "".$_POST['uebergabe_vorplanen_datum']." ".$_POST['uebergabe_vorplanen_zeit'].":00";

        $Parser = geplante_uebergabe_eintragen($ResID, $Gratis, $Verguenstigung, $Uebergabeort, $Kommentar, $Zeitpunkt);
        if ($Parser['meldung'] != ""){
            return $Parser['meldung'];
        }

    } else {
        return NULL;
    }
}

function geplante_uebergabe_eintragen($ResID, $Gratis, $Verguenstigung, $Uebergabeort, $Kommentar, $Zeitpunkt){

    $link = connect_db();
    $Antwort = array();
    $DAUcounter = 0;
    $DAUerror = "";

    //MEEEGGAAA DAU-Checks

    //Eingegebener Zeitpunkt liegt in Vergangenheit
    if(strtotime($Zeitpunkt) < time()){
        $DAUcounter++;
        $DAUerror .= "- Der eingegebene Zeitpunkt liegt in der Vergangenheit!<br>";
    }

    //Kein Übergabeort gewählt
    if($Uebergabeort == ""){
        $DAUcounter++;
        $DAUerror .= "- Du musst eine Angabe zum &Uuml;bergabeort eingeben!<br>";
    }

    //Keine Reservierung gewählt
    if(($ResID == 0) OR ($ResID == "")){
        $DAUcounter++;
        $DAUerror .= "- Du musst eine Reservierung anw&auml;hlen!<br>";
    } else {

        //Reservierung bereits storniert
        $Reservierung = lade_reservierung($ResID);
        if($Reservierung['storno_user'] != "0"){
            $DAUcounter++;
            $DAUerror .= "- Die Reservierung wurde inzwischen storniert!<br>";
        }

        //Reservierung hat schon eine Übergabe geklickt
        $Uebergaben = lade_uebergabe_res($ResID);
        if($Uebergaben != NULL){
            $DAUcounter++;
            $DAUerror .= "- Die Reservierung hat inzwischen schon eine &Uuml;bergabe ausgemacht!<br>";
        }

        //Reservierung ist schon abgelaufen
        if(strtotime($Reservierung['ende'] < time())){
            $DAUcounter++;
            $DAUerror .= "- Die Reservierung ist inzwischen abgelaufen!<br>";
        }
    }

    //Gratis und Vergünstigung
    if(($Gratis === TRUE) AND (intval($Verguenstigung) != 0)){
        $DAUcounter++;
        $DAUerror .= "- Du kannst nicht eine Verg&uuml;nstigung und eine Gratisfahrt gleichzeitig eingeben!<br>";
    }

    //Vergünstigung negativ
    if(intval($Verguenstigung) < 0){
        $DAUcounter++;
        $DAUerror .= "- Du kannst keine Reservierungen f&uuml;r negative Preise einstellen!<br>";
    }

    //DAU auswerten
    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = "<p><b>Fehler beim Eintragen der &Uuml;bergabe:</b></p><p>".$DAUerror."</p>";
    } else {

        //Terminangebot erstellen

        $Antwort = geplante_uebergabe_hinzufuegen($ResID, lade_user_id(), $Gratis, $Verguenstigung, $Uebergabeort, $Zeitpunkt, $Kommentar);

    }

    return $Antwort;

}

function geplante_uebergabe_hinzufuegen($ResID, $Wart, $Gratis, $Verguenstigung, $Uebergabeort, $Zeitpunkt, $Kommentar){

    $link = connect_db();
    $Errorcounter = 0;
    $Error = "";
    $Antwort = array();
    $Timestamp = timestamp();

    //Reservierung updaten bei Gratisfahrt
    if($Gratis == TRUE){
        if (!reservierung_auf_gratis_setzen($ResID)){
            $Errorcounter++;
            $Error .= "Fehler beim Eintragen der Gratisfahrt!<br>";
        }
    }

    //Reservierung updaten bei Anderer Preis
    if ($Verguenstigung != ""){
        reservierung_preis_aendern($ResID, $Verguenstigung);
    }

    //Terminobjekt anlegen
    $ZeitpunktZwei = date("Y-m-d G:i:s", strtotime("+ 10 Minutes", strtotime($Zeitpunkt)));
    $Hinzufuegen = terminangebot_hinzufuegen($Wart, $Zeitpunkt, $ZeitpunktZwei, $Uebergabeort, $Kommentar, NULL);

    if ($Hinzufuegen['success'] == FALSE){

        //Überprüfe clash mit vorhandenem Angebot
        $AnfrageClash = "SELECT id, ort FROM terminangebote WHERE wart = '$Wart' AND (((von <= '$Zeitpunkt') AND (bis >= '$ZeitpunktZwei')) OR (('$Zeitpunkt' < von) AND ('$ZeitpunktZwei' > von)) OR (('$Zeitpunkt' < bis) AND ('$ZeitpunktZwei' > bis))) AND storno_user = '0'";
        $AbfrageClash = mysqli_query($link, $AnfrageClash);
        $AnzahlClash = mysqli_num_rows($AbfrageClash);

        if ($AnzahlClash > 0){

            //Wenn Ortsangabe mit Clash überein stimmt, weitermachen mit clashangebot, ansonsten, Error!
            $ClashAngebot = mysqli_fetch_assoc($AbfrageClash);

            if($Uebergabeort != $ClashAngebot['ort']){
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Fehler: zu dem eingegebenen Zeitpunkt hast du bereits ein anderes &Uuml;bergabeangebot!<br>Wenn du dieses verwenden m&ouml;chtest, verwende im Formular die identische Ortsangabe: ".$ClashAngebot['ort']."";
            } else if ($Uebergabeort == $ClashAngebot['ort']){

                //Weitermachen!!
                $Antwort = uebergabe_hinzufuegen($ResID, $Wart, $ClashAngebot['id'], $Zeitpunkt, $Kommentar, lade_user_id());
            }

        } else {
            $Antwort = $Hinzufuegen;
        }

    } else {
        //ID Terminangebot laden
        $AnfrageLadeAngebotID = "SELECT id FROM terminangebote WHERE wart = '$Wart' AND von = '$Zeitpunkt' AND bis = '$ZeitpunktZwei' AND storno_user = '0' AND ort = '$Uebergabeort'";
        $AbfrageLadeAngebotID = mysqli_query($link, $AnfrageLadeAngebotID);
        $AnzahlLadeAngebotID = mysqli_num_rows($AbfrageLadeAngebotID);
        if ($AnzahlLadeAngebotID == 0){
            //Fehler
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Anlegen des &Uuml;bergabeangebotes! Kein Objekt angelegt!";
        } else if ($AnzahlLadeAngebotID > 1){
            //Fehler
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Anlegen des &Uuml;bergabeangebotes! Es existieren zu viele Objekte!";
        } else if ($AnzahlLadeAngebotID == 1){

            //Weiter
            $Angebot = mysqli_fetch_assoc($AbfrageLadeAngebotID);
            $Antwort = uebergabe_hinzufuegen($ResID, $Wart, $Angebot['id'], $Zeitpunkt, $Kommentar, lade_user_id());
        }
    }

    return $Antwort;

}

function schluesseluebergabe_ausmachen_moeglichkeiten_anzeigen($IDres, $Mode){

    $link = connect_db();
    $Reservierung = lade_reservierung($IDres);
    $HTML = "";
    if((res_hat_uebergabe($IDres)) AND ($Mode!='change')){

        $HTML .= "<div class='card-panel materialize-" .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
        $HTML .= "<h5 class='center-align'>Fehler!</h5>";
        $HTML .= "<div class='section center-align'>";
        $HTML .= "<p>Du hast f&uuml;r diese Reservierung bereits eine Schl&uuml;ssel&uuml;bergabe ausgemacht!</p>";
        $HTML .= button_link_creator('Zurück', './my_reservations.php', 'arrow_back', '');
        $HTML .= "</div>";
        $HTML .= "</div>";

    } else {

        $BefehlGrenz = "- ".lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe')." days";
        $BefehlGrenzZwei = "- ".lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')." minutes";
        $GrenzstampNachEinstellung = date("Y-m-d G:i:s", strtotime($BefehlGrenz, strtotime($Reservierung['beginn'])));
        $GrenzstampNachEinstellungZwei = date("Y-m-d G:i:s", strtotime($BefehlGrenzZwei, strtotime($Reservierung['beginn'])));

        //Passen zeitlich?
        $AnfrageSucheTerminangebote = "SELECT * FROM terminangebote WHERE von > '$GrenzstampNachEinstellung' AND von < '$GrenzstampNachEinstellungZwei' AND bis > '".timestamp()."' AND storno_user = '0' ORDER BY von ASC";
        $AbfrageSucheTerminangebote = mysqli_query($link, $AnfrageSucheTerminangebote);
        $AnzahlSucheTerminangebote = mysqli_num_rows($AbfrageSucheTerminangebote);

        $HTMLcollapsible = "";
        if ($AnzahlSucheTerminangebote == 0){

            //Für seine reservierung gibts nichts passendes
            $PasstnedText = 'Derzeit gibt es keinen passenden Termin f&uuml;r deine Reservierung. Bitte schau daher einfach in K&uuml;rze wieder vorbei:) <br> Bitte beachte: &Uuml;bergaben k&ouml;nnen aus logistischen Gr&uuml;nden fr&uuml;hestens '.lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe').' Tage vor Beginn deiner Fahrt ausgemacht werden.';
            $HTMLcollapsible .= collapsible_item_builder('Kein passender Termin verf&uuml;gbar!', $PasstnedText, 'error');

        } else if ($AnzahlSucheTerminangebote > 0){

            $Counter = 0;

            for ($a = 1; $a <= $AnzahlSucheTerminangebote; $a++){

                $Terminangebot = mysqli_fetch_assoc($AbfrageSucheTerminangebote);

                if($Terminangebot['terminierung']==NULL){
                    //Hat der Wart noch Schlüssel?
                    if(wart_verfuegbare_schluessel($Terminangebot['wart']) > 0){
                        $Counter++;
                        $HTMLcollapsible .= terminangebot_listenelement_buchbar_generieren($Terminangebot['id'], $IDres, $Mode);
                    }
                } else {
                    if(strtotime($Terminangebot['terminierung'])>time()){
                        //Hat der Wart noch Schlüssel?
                        if(wart_verfuegbare_schluessel($Terminangebot['wart']) > 0){
                            $Counter++;
                            $HTMLcollapsible .= terminangebot_listenelement_buchbar_generieren($Terminangebot['id'], $IDres, $Mode);
                        }
                    }
                }
            }

            if ($Counter == 0){
                //Hier gibts nichts, aber Zeit wäre dazu - liegt an shchlüsseln
                $HTMLcollapsible .= collapsible_item_builder('Keine Schl&uuml;ssel verf&uuml;gbar!', 'Derzeit sind alle Schl&uuml;ssel im Umlauf. Daher k&ouml;nnen wir dir aktuell keinen Termin anbieten. Wir arbeiten daran immer schnell wieder an welche zu kommen. Bitte schau daher einfach in K&uuml;rze wieder vorbei:)', 'error');
            }
        }

        $HTML .= section_builder(collapsible_builder($HTMLcollapsible));
        $HTML .= section_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''));
    }

    return $HTML;
}

function parser_uebergabe_hinzufuegen_ueser($gebni, $Mode='fresh'){

    $link = connect_db();
    $Reservierung = lade_reservierung($gebni);

    $BefehlGrenz = "- ".lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe')." days";
    $BefehlGrenzZwei = "- ".lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')." minutes";
    $GrenzstampNachEinstellung = date("Y-m-d G:i:s", strtotime($BefehlGrenz, strtotime($Reservierung['beginn'])));
    $GrenzstampNachEinstellungZwei = date("Y-m-d G:i:s", strtotime($BefehlGrenzZwei, strtotime($Reservierung['beginn'])));

    //Passen zeitlich?
    $AnfrageSucheTerminangebote = "SELECT * FROM terminangebote WHERE von > '$GrenzstampNachEinstellung' AND von < '$GrenzstampNachEinstellungZwei' AND bis > '".timestamp()."'  AND storno_user = '0'";
    $AbfrageSucheTerminangebote = mysqli_query($link, $AnfrageSucheTerminangebote);
    $AnzahlSucheTerminangebote = mysqli_num_rows($AbfrageSucheTerminangebote);

    $Antwort = NULL;

    if ($AnzahlSucheTerminangebote == 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = ("Leider stehen derzeit keine Terminangebote zur Verf&uuml;gung!");
    } else if ($AnzahlSucheTerminangebote > 0){

        for ($a = 1; $a <= $AnzahlSucheTerminangebote; $a++){

            $Termin = mysqli_fetch_assoc($AbfrageSucheTerminangebote);

            $Suchbefehl = "action_termin_".$Termin['id']."";
            $Terminfeld = "zeitfenster_gewaehlt_terminangebot_".$Termin['id']."";
            $Kommentarfeld = "kommentar_uebergabe_".$Termin['id']."";

            if (isset($_POST[$Suchbefehl])){

                if($Mode == 'change'){
                    $AnfrageResSchonVersorgt = "SELECT id FROM uebergaben WHERE res = '$gebni' AND storno_user = '0'";
                    $AbfrageResSchonVersorgt = mysqli_query($link, $AnfrageResSchonVersorgt);
                    $AnzahlResSchonVersorgt = mysqli_num_rows($AbfrageResSchonVersorgt);
                    if($AnzahlResSchonVersorgt == 1){
                        $Ergebnis = mysqli_fetch_assoc($AbfrageResSchonVersorgt);
                        uebergabe_stornieren($Ergebnis['id'], 'User hat auf eine andere Übergabe gewechselt!');
                    }
                }

                $hinzufueger = uebergabe_hinzufuegen($gebni, $Termin['wart'], $Termin['id'], $_POST[$Terminfeld], $_POST[$Kommentarfeld], lade_user_id());
                $Antwort = $hinzufueger;
            }
        }
    }

    return $Antwort;
}

function uebergabe_erfolgreich_eingetragen_user(){

    $Antwort = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $Antwort .= "<h5 class='center-align'>Gl&uuml;ckwunsch!</h5>";
    $Antwort .= "<div class='section center-align'>";
    $Antwort .= "<p>Nun hast du erfolgreich eine Schl&uuml;ssel&uuml;bergabe ausgemacht! Jetzt muss nur noch das Treffen klappen und es steht deinem Stocherabenteuer nichts mehr im Wege!</p>";
    $Antwort .= "<p><a href='my_reservations.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></p>";
    $Antwort .= "</div>";
    $Antwort .= "</div>";

    return $Antwort;

}

function terminangebot_listenelement_buchbar_generieren($IDangebot, $RESID, $Mode){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDangebot'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Angebot = mysqli_fetch_assoc($Abfrage);

    $Wart = lade_user_meta($Angebot['wart']);

    //Textinhalte generieren
    #$Zeitraum = "<b>".strftime("%A, %d. %B %G %H:%M", strtotime($Angebot['von']))."</b> bis <b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";
    $ZeitraumMobil = "<b>".strftime("%a, %d. %b - %H:%M", strtotime($Angebot['von']))."</b>&nbsp;bis&nbsp;<b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";

    if($Angebot['kommentar'] == ""){
        $Kommentar = "";
    } else {
        $Kommentar = collection_item_builder("<i class='tiny material-icons'>comment</i> Kommentar: ".$Angebot['kommentar']."");
    }

    $Dropdownname = "zeitfenster_gewaehlt_terminangebot_".$IDangebot."";
    $ZeileMitBuchung = table_form_dropdown_terminzeitfenster_generieren('Zeitfenster wählen', $Dropdownname, $IDangebot, '');
    $ZeileMitBuchung .= table_form_string_item('Kommentar', 'kommentar_uebergabe_'.$IDangebot.'', $_POST['kommentar_uebergabe_'.$IDangebot.''], false);
    $ZeileMitBuchung .= table_row_builder(table_header_builder('').table_data_builder(form_button_builder('action_termin_'.$IDangebot.'', '&Uuml;bergabe ausmachen', 'action', 'send')));
    $TabelleMitBuchung = table_builder($ZeileMitBuchung);
    if($Mode=='fresh'){
        $Formular = form_builder($TabelleMitBuchung, './uebergabe_ausmachen.php?res='.$RESID.'', 'post', '', '');
    } elseif($Mode=='change'){
        $Formular = form_builder($TabelleMitBuchung, './neue_uebergabe_ausmachen.php?res='.$RESID.'', 'post', '', '');
    }
    $FormularCollection = collection_item_builder($Formular);

    //Ausgabe
    $Collection = collection_item_builder("<i class='tiny material-icons'>room</i> Ort: ".$Angebot['ort']."");
    $Collection .= $Kommentar;
    $Collection .= collection_item_builder("<i class='tiny material-icons'>perm_identity</i> Schl&uuml;sselwart: ".$Wart['vorname']." ".$Wart['nachname']."");
    $Collection .= $FormularCollection;
    $Collection = collection_builder($Collection);
    $HTML = collapsible_item_builder("Terminangebot:&nbsp;".$ZeitraumMobil."", $Collection, 'today');

    return $HTML;
}

function spontanuebergabe_listenelement_generieren(){

    $HTML = "";

    $Parser = spontanuebergabe_listenelement_parser();
    if(isset($Parser['meldung'])){
        $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
    } else {
        $FormHTML = table_row_builder(table_header_builder('Reservierung').table_data_builder(dropdown_aktive_res_spontanuebergabe('reservierung')));
        $FormHTML .= table_row_builder(table_header_builder('Schlüssel').table_data_builder(dropdown_verfuegbare_schluessel_wart('schluessel', lade_user_id(), true)));
        $FormHTML .= table_form_swich_item('Gratisfahrt', 'gratis_fahrt', 'Nein', 'Ja', '', false);
        $FormHTML .= table_form_select_item('Vergünstigung', 'verguenstigung', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), '', '&euro;', 'Vergünstigung', '', false);
        $FormHTML .= table_form_select_item('Einnahmen', 'einnahme', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), '', '&euro;', 'Einnahmen', '', false);
        $FormHTML = table_builder($FormHTML);
        $FormHTML .= divider_builder();
        $FormHTML .= table_builder(table_row_builder(table_header_builder(form_button_builder('action_spontanuebergabe_durchfuehren', 'Durchf&uuml;hren', 'submit', 'send', '')).table_data_builder('')));
        $HTML .= form_builder($FormHTML, '#', 'post');
    }

    $HTML = collapsible_item_builder('Spontanübergabe', $HTML, 'star');

    return $HTML;
}

function termin_anlegen($User, $Wart, $Terminangebot, $Zeitpunkt, $Reason, $ReasonID='', $Comment=''){
    $link = connect_db();

    //Last doublecheck:
    if (!($stmt = $link->prepare("SELECT id FROM termine WHERE user = ? AND wart = ? AND terminangebot = ? AND zeitpunkt = ? AND grund = ? AND kommentar = ? AND storno_user = 0"))) {
        $Antwort['success'] = false;
        $Antwort['meldung'] = "Datenbankfehler!";
        #echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("iiisss", $User, $Wart, $Terminangebot, $Zeitpunkt, $Reason, $Kommentar)) {
        $Antwort['success'] = false;
        $Antwort['meldung'] = "Datenbankfehler!";
        #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['success'] = false;
        $Antwort['meldung'] = "Datenbankfehler!";
        #echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $Res = $stmt->get_result();
        $Nums = mysqli_num_rows($Res);
        if($Nums==0){
            if (!($stmt = $link->prepare("INSERT INTO termine (user, wart, terminangebot, zeitpunkt, create_time, create_user, grund, id_grund, kommentar) VALUES (?,?,?,?,?,?,?,?,?)"))) {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler!";
                #echo "Prepare failed: (" . $link->errno . ") " . $link->error;
            }
            if (!$stmt->bind_param("iiissisis", $User, $Wart, $Terminangebot, $Zeitpunkt, timestamp(), lade_user_id(), $Reason, $ReasonID, $Comment)) {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler!";
                echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->execute()) {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler!";
                #echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            } else {
                $MailUser = lade_user_meta($Wart);
                $TerminUser = lade_user_meta($User);
                $TerminangebotMeta = lade_terminangebot($Terminangebot);
                zeitformat();
                $Bausteine = array('[vorname_wart]'=>$MailUser['vorname'], '[datum_uebergabe]'=>strftime("%A, %d. %B %G", strtotime($Zeitpunkt)), '[ort_uebergabe]'=>$TerminangebotMeta['ort'], '[uhrzeit_beginn]'=>date('G:i', strtotime($Zeitpunkt)), '[termininfos]'=>'Grund: '.$Reason, '[kontakt_user]'=>$TerminUser['vorname'].' '.$TerminUser['nachname'], '[kommentar_user]'=>$Kommentar);
                mail_senden('termin-bekommen-wart', $MailUser['mail'], $Bausteine);

                $Antwort['success'] = true;
                $Antwort['meldung'] = "Termin erfolgreich angelegt!";
            }
        } else {
            $Antwort['success'] = false;
            $Antwort['meldung'] = "Ein identischer Termin liegt bereits vor!";
        }
    }

    return $Antwort;
}

function lade_termin($IDtermin){
    $link = connect_db();

    $Anfrage = "SELECT * FROM termine WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Termin = mysqli_fetch_assoc($Abfrage);

    return $Termin;
}

function termin_durchfuehren($TerminID){

    $link = connect_db();
    $Anfrage = "UPDATE termine SET durchfuehrung = '".timestamp()."' WHERE id = ".$TerminID."";
    if(mysqli_query($link, $Anfrage)){
        $Antwort['success'] = true;
        $Antwort['meldung'] = 'Durchführung erfolgreich festgehalten!';
    } else {
        $Antwort['success'] = false;
        $Antwort['meldung'] = 'Datenbankfehler!';
    }

    return $Antwort;
}

function termin_loeschen($TerminID, $Kommentar){

    $link = connect_db();
    $Termin = lade_termin($TerminID);
    $AktUser = lade_user_id();
    if($Kommentar==''){
        $Anfrage = "UPDATE termine SET storno_time = '".timestamp()."', storno_user = '".$AktUser."' WHERE id = ".$TerminID."";
    } else {
        $Anfrage = "UPDATE termine SET storno_time = '".timestamp()."', storno_user = '".$AktUser."', storno_kommentar = '".$Kommentar."' WHERE id = ".$TerminID."";
    }

    if(mysqli_query($link, $Anfrage)){
        zeitformat();
        //SEND SOME MAILS HERE
        if($Termin['user']==$AktUser){
            //Mail an Wart
            $MailUser = lade_user_meta($Termin['wart']);
            $TerminUser = lade_user_meta($Termin['user']);
            $Bausteine = array('[vorname_wart]'=>$MailUser['vorname'], '[zeitangabe_uebergabe]'=>strftime("%A, %d. %B %G", strtotime($Termin['zeitpunkt'])), '[angaben_user]'=>$TerminUser['vorname'].' '.$TerminUser['nachname'], '[kommentar_user]'=>$Kommentar);
            mail_senden('termin-storniert-wart', $MailUser['mail'], $Bausteine);
        } elseif ($Termin['wart']==$AktUser){
            //Mail an User
            $MailUser = lade_user_meta($Termin['user']);
            $Bausteine = array('[vorname_user]'=>$MailUser['vorname'], '[zeitangabe_uebergabe]'=>strftime("%A, %d. %B %G", strtotime($Termin['zeitpunkt'])), '[begruendung_wart]'=>$Kommentar);
            mail_senden('termin-storniert-user', $MailUser['mail'], $Bausteine);
        }

        $Antwort['success'] = true;
        $Antwort['meldung'] = 'Löschen erfolgreich festgehalten!';
    } else {
        $Antwort['success'] = false;
        $Antwort['meldung'] = 'Datenbankfehler!';
    }

    return $Antwort;
}
?>