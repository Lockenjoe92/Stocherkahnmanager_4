<?php

function uebernahme_moeglich($ReservierungID){

    $link = connect_db();

    $AnfrageLaden = "SELECT * FROM reservierungen WHERE id = '$ReservierungID'";
    $AbfrageLaden = mysqli_query($link, $AnfrageLaden);
    $Reservierung = mysqli_fetch_assoc($AbfrageLaden);
    $Benutzereinstellungen = lade_user_meta($Reservierung['user']);
    $Nutzergruppe = $Benutzereinstellungen['ist_nutzergruppe'];
    $NutzergruppeInfos = lade_nutzergruppe_infos($Nutzergruppe, 'name');
    $Verification = load_last_nutzergruppe_verification_user($NutzergruppeInfos['id'], $Reservierung['user']);

    if(user_darf_uebernahme($Reservierung['user'])){
        $Anfrage = "SELECT id FROM reservierungen WHERE ende = '".$Reservierung['beginn']."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl > 0){

            $Vorgehende = mysqli_fetch_assoc($Abfrage);

            //Überprüfen ob die vorhergehende res ne Übergabe hat
            $AnfrageDrei = "SELECT id FROM uebergaben WHERE res = '".$Vorgehende['id']."' AND storno_user = '0'";
            $AbfrageDrei = mysqli_query($link, $AnfrageDrei);
            $AnzahlDrei = mysqli_num_rows($AbfrageDrei);

            if ($AnzahlDrei > 0){
                //Übernahme möglich
                return true;
            } else {
                //Keine übernahme möglich
                return false;
            }
        }
    }
}

function uebernahme_stornieren($UebernahmeID, $Begruendung){

    $link = connect_db();
    zeitformat();

    $Uebernahme = lade_uebernahme($UebernahmeID);
    $ResUebernahme = lade_reservierung($Uebernahme['reservierung']);
    $UserResUebernahme = lade_user_meta($ResUebernahme['user']);
    $ResUebernahmeDavor = lade_reservierung($Uebernahme['reservierung_davor']);
    $UserResUebernahmeDavor = lade_user_meta($ResUebernahmeDavor['user']);

    $AnfrageStorno = "UPDATE uebernahmen SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = '$UebernahmeID'";
    if (mysqli_query($link, $AnfrageStorno)){
        if ($Begruendung != ""){
            //Nur wenns was zu erzählen gibt
            $BausteineUebernahmeMails = array();
            $BausteineUebernahmeMails['[vorname_user]'] = $UserResUebernahme['vorname'];
            $BausteineUebernahmeMails['[datum_resevierung]'] = strftime("%A, %d. %B %G", strtotime($ResUebernahme['beginn']));
            $BausteineUebernahmeMails['[begruendung]'] = htmlentities($Begruendung);
            mail_senden('uebernahme-storniert-user', $UserResUebernahme['mail'], $BausteineUebernahmeMails);
            mail_senden('uebernahme-storniert-user-davor', $UserResUebernahmeDavor['mail'], $BausteineUebernahmeMails);
        }
        return true;
    } else {
        return false;
    }
}

function uebernahme_eintragen($ReservierungID, $Kommentar, $vorfahrerChosen=0){

    $Antwort = array();
    $link = connect_db();
    $UserID = lade_user_id();
    $UserAktuell = lade_user_meta($UserID);
    $Reservierung = lade_reservierung($ReservierungID);
    zeitformat();

    //Instant DAU checks:

    $DAUcounter = 0;
    $DAUerror = "";
    $Wartmode = FALSE;

    //Keine Reservierung übermittelt
    if ($ReservierungID == ""){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine Reservierung gew&auml;hlt!<br>";
    }

    //Reservierung gehört nicht dem User - ist es noch ein Wart?
    if ($UserID != intval($Reservierung['user'])){

        if ($UserAktuell['ist_wart'] != 'true'){
            $DAUcounter++;
            $DAUerror .= "Du hast nicht die n&ouml;tigen Rechte um diese Reservierung zu bearbeiten!<br>";
        } else if ($UserAktuell['ist_wart'] == 'true'){
            $Wartmode = TRUE;
        }
    }

    if ($DAUcounter > 0){

        //Check High priority Fails
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;

    } else {

        //User darf noch keine Übernahme machen
        if (!user_darf_uebernahme($UserID)){

            //Bei StoKaWart egal
            $UserAktuell = lade_user_meta(lade_user_id());
            if ($UserAktuell['ist_wart'] != 'true'){
                $DAUcounter++;
                $DAUerror .= "Du hast nicht die n&ouml;tigen Einweisungen um eine Schl&uuml;ssel&uuml;bernahme auszumachen!<br>";
            } else if ($UserAktuell['ist_wart'] == 'true'){
                $Wartmode = TRUE;
            }
        }

        //Reservierung hat schon ne gültige Schlüsselübergabe
        $AnfrageUebergabestatusDieseRes = "SELECT id FROM uebergaben WHERE res = '$ReservierungID' AND storno_user = '0'";
        $AbfrageUebergabestatusDieseRes = mysqli_query($link, $AnfrageUebergabestatusDieseRes);
        $AnzahlUebergabestatusDieseRes = mysqli_num_rows($AbfrageUebergabestatusDieseRes);

        if ($AnzahlUebergabestatusDieseRes > 0){

            $Uebergabe = mysqli_fetch_assoc($AbfrageUebergabestatusDieseRes);
            $DAUcounter++;
            $DAUerror .= "Du hast f&uuml;r diese Reservierung bereits eine Schl&uuml;ssel&uuml;bergabe ausgemacht! Falls du lieber den Schl&uuml;ssel der Vorgruppe &uuml;bernehmen m&ouml;chtest, <a href='uebergabe_stornieren_user.php?id=".$Uebergabe['id']."'>storniere bitte zuerst die &Uuml;bergabe!</a><br>";
        }

        //Reservierung hat schon eine Übernahme ausgemacht
        $AnfrageUebernahmeResSchonVorhanden = "SELECT id FROM uebernahmen WHERE reservierung = '$ReservierungID' AND storno_user = '0'";
        $AbfrageUebernahmeResSchonVorhanden = mysqli_query($link, $AnfrageUebernahmeResSchonVorhanden);
        $AnzahlUebernahmeResSchonVorhanden = mysqli_num_rows($AbfrageUebernahmeResSchonVorhanden);

        if ($AnzahlUebernahmeResSchonVorhanden > 0){
            $DAUcounter++;
            $DAUerror .= "Du hast hast f&uuml;r diese Reservierung bereits eine &Uuml;bernahme ausgemacht!<br>";
        }

        //Es gibt keine Vorfahrende Reservierung mit ausgemachter Übergabe mehr!
        $AnfrageLadeResVorher = "SELECT * FROM reservierungen WHERE (ende = '".$Reservierung['beginn']."') AND storno_user = '0'";
        $AbfrageLadeResVorher = mysqli_query($link, $AnfrageLadeResVorher);
        $AnfrageLadeResVorher = mysqli_num_rows($AbfrageLadeResVorher);

        if ($AnfrageLadeResVorher == 0){
            if($vorfahrerChosen==0) {
                $DAUcounter++;
                $DAUerror .= "Es gibt leider keine Reservierung mehr vor dir! <a href='./uebernahme_ausmachen.php?res=" . $ReservierungID . "'>Buche dir einfach eine Schl&uuml;ssel&uuml;bergabe</a> durch einen unserer Stocherkahnw&auml;rte:)<br>";
            } else {
                $ReservierungVorher = lade_reservierung($vorfahrerChosen);
                if($ReservierungVorher['storno_user']!=0){
                    $DAUcounter++;
                    $DAUerror .= "Ausgewählte vorfahrende Reservierung bereits gelöscht!<br>";
                }
            }
        } else if ($AnfrageLadeResVorher > 0){

            if($vorfahrerChosen==0){
                $ReservierungVorher = mysqli_fetch_assoc($AbfrageLadeResVorher);

                //Es gibt ne Res, aber hat sie auch eine ausgemachte/durchgeführte Schlüsselübergabe?
                $AnfrageUebergabestatus = "SELECT id FROM uebergaben WHERE res = '".$ReservierungVorher['id']."' AND storno_user = '0'";
                $AbfrageUebergabestatus = mysqli_query($link, $AnfrageUebergabestatus);
                $AnzahlUebergabestatus = mysqli_num_rows($AbfrageUebergabestatus);

                if ($AnzahlUebergabestatus == 0){

                    //Hat die reservierung vielleicht eine Schlüsselübernahme gebucht? -> Wenn ja, einstellung Checken ob man Schlüssel über mehrere Reservierungen weitergeben darf:
                    if (lade_xml_einstellung('schluesseluebernahme-ueber-mehrere-res') == "true"){

                        $AnfrageHatVorfahrendeReservierungUebernahme = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$ReservierungVorher['id']."' AND storno_user = '0'";
                        $AbfrageHatVorfahrendeReservierungUebernahme = mysqli_query($link, $AnfrageHatVorfahrendeReservierungUebernahme);
                        $AnzahlHatVorfahrendeReservierungUebernahme = mysqli_num_rows($AbfrageHatVorfahrendeReservierungUebernahme);

                        if ($AnzahlHatVorfahrendeReservierungUebernahme == 0){
                            $DAUcounter++;
                            $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigeneSchl&uuml;ssel&uuml;bergabe</a>!<br>";
                        }

                    } else {
                        $DAUcounter++;
                        $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigeneSchl&uuml;ssel&uuml;bergabe</a>!<br>";
                    }
                }
            } else {
                $ReservierungVorher = lade_reservierung($vorfahrerChosen);
                if($ReservierungVorher['storno_user']!=0){
                    $DAUcounter++;
                    $DAUerror .= "Ausgewählte vorfahrende Reservierung bereits gelöscht!<br>";
                }
            }
        }

        if ($DAUcounter > 0){
            //Check low priority fails
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $DAUerror;
        } else {

            if($ReservierungVorher['user']!=$Reservierung['user']){
                //Inform Gruppe davor:
                $UserReservierungDavor = lade_user_meta($ReservierungVorher['user']);
                $UserReservierung = lade_user_meta($Reservierung['user']);
                $BausteineGruppeDavor = array();
                $BausteineGruppeDavor['[vorname_user]'] = $UserReservierungDavor['vorname'];
                $BausteineGruppeDavor['[angaben_reservierung_datum]'] = strftime("%A, den %d. %B %G", strtotime($ReservierungVorher['beginn']));
                $BausteineGruppeDavor['[angaben_reservierung_beginn]'] = strftime("%H", strtotime($ReservierungVorher['beginn']));
                $BausteineGruppeDavor['[angaben_reservierung_ende]'] = strftime("%H", strtotime($ReservierungVorher['ende']));
                $BausteineGruppeDavor['[name_nachfolgender_user]'] = "".$UserReservierung['vorname']." ".$UserReservierung['nachname']."";
                if ($Kommentar != ""){
                    $BausteineGruppeDavor['[kommentar]'] = "<p>Kommentar des anlegenden Users: ".$Kommentar."</p>";
                } else {
                    $BausteineGruppeDavor['[kommentar]'] = '';
                }

                #var_dump($BausteineGruppeDavor);

                if (mail_senden('uebernahme-angelegt-vorgruppe', $UserReservierungDavor['mail'], $BausteineGruppeDavor, 'uebernahme-angelegt-vorgruppe-res-'.$ReservierungVorher['id'])){

                    $BausteineGruppe = array();
                    $BausteineGruppe['[vorname_user]'] = $UserReservierungDavor['vorname'];
                    $BausteineGruppe['[angaben_reservierung_datum]'] = strftime("%A, den %d. %B %G", strtotime($Reservierung['beginn']));
                    $BausteineGruppe['[angaben_reservierung_beginn]'] = strftime("%H", strtotime($Reservierung['beginn']));
                    $BausteineGruppe['[angaben_reservierung_ende]'] = strftime("%H", strtotime($Reservierung['ende']));
                    $BausteineGruppe['[name_vorheriger_user]'] = "".$UserReservierungDavor['vorname']." ".$UserReservierungDavor['nachname']."";
                    if ($Kommentar != ""){
                        $BausteineGruppe['[kommentar]'] = "<p>Hier der Kommentar des anlegenden Users: ".$Kommentar."</p>";
                    } else {
                        $BausteineGruppeDavor['[kommentar]'] = '';
                    }

                    if (mail_senden('uebernahme-angelegt-nachgruppe', $UserReservierung['mail'], $BausteineGruppe, 'uebernahme-angelegt-nachgruppe-res-'.$Reservierung['id'])){

                        $AnfrageUebernahmeEintragen = "INSERT INTO uebernahmen (reservierung, reservierung_davor, create_time, create_user, kommentar) VALUES ('$ReservierungID', '".$ReservierungVorher['id']."', '".timestamp()."', '".lade_user_id()."', '$Kommentar')";
                        if (mysqli_query($link, $AnfrageUebernahmeEintragen)){
                            $Antwort['success'] = TRUE;
                            $Antwort['meldung'] = "Schl&uuml;ssel&uuml;bernahme erfolgreich eingetragen!";
                        } else {
                            $Antwort['success'] = FALSE;
                            $Antwort['meldung'] = "Fehler beim Eintragen der Schl&uuml;ssel&uuml;bernahme!";
                        }

                    } else {
                        $Antwort['success'] = FALSE;
                        $Antwort['meldung'] = "Fehler beim Informieren des Users.";
                    }

                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['meldung'] = "Fehler beim Informieren der vorfahrenden Gruppe.";
                }
            } else {
                $AnfrageUebernahmeEintragen = "INSERT INTO uebernahmen (reservierung, reservierung_davor, create_time, create_user, kommentar) VALUES ('$ReservierungID', '".$ReservierungVorher['id']."', '".timestamp()."', '".lade_user_id()."', '$Kommentar')";
                if (mysqli_query($link, $AnfrageUebernahmeEintragen)){
                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = "Schl&uuml;ssel&uuml;bernahme erfolgreich eingetragen!";
                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['meldung'] = "Fehler beim Eintragen der Schl&uuml;ssel&uuml;bernahme!";
                }
            }
        }
    }

    return $Antwort;

}

function lade_uebernahme($UebernahmeID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebernahmen WHERE id = '$UebernahmeID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;
}

function uebernahme_planen_listenelement_generieren(){

    //Ausgabe
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header'><i class='large material-icons'>sync</i>&Uuml;bernahme vorplanen</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<div class='container'>";
    $HTML .= "<form method='post'>";

    //Reservierung und deren modifizierung
    $HTML .= "<h4>Reservierung w&auml;hlen</h4>";

    $Parser = uebernahme_planen_listenelement_parser();
    if(isset($Parser)){
        $HTML .= "<h5>".$Parser."</h5>";
    }

    $HTML .= "<div class='input-field'>";
    $HTML .= "<i class='material-icons prefix'>today</i>";
    $HTML .= dropdown_aktive_res_spontanuebergabe('reservierung_uebernahme_vorplanen');
    $HTML .= "</div>";

    $HTML .= "<div class='input-field'>";
    $HTML .= form_button_builder('action_uebernahme_vorplanen_durchfuehren', 'Vorplanen', 'submit', 'send');
    $HTML .= "</div>";
    $HTML .= "</form>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function uebernahme_planen_listenelement_parser(){

    if (isset($_POST['action_uebernahme_vorplanen_durchfuehren'])){

        if (intval($_POST['reservierung_uebernahme_vorplanen']) > 0){
            $Reservation = str_replace('#','', $_POST['reservierung_uebernahme_vorplanen']);
            $header = "Location: ./uebernahme_vorplanen.php?res=".$Reservation."";
            header($header);
            die();

        } else {
            $Antwort = 'Du hast keine Reservierung ausgew&auml;hlt!';
            return $Antwort;
        }
    }
}

function user_darf_uebernahme($UserID){

    $Benutzereinstellungen = lade_user_meta($UserID);
    $Nutzergruppe = $Benutzereinstellungen['ist_nutzergruppe'];
    $NutzergruppeInfos = lade_nutzergruppe_infos($Nutzergruppe, 'name');
    $Verification = load_last_nutzergruppe_verification_user($NutzergruppeInfos['id'], $UserID);
    $Antwort = false;

    if($NutzergruppeInfos['req_verify']=='yearly'){
        if($Verification['erfolg'] == 'true'){
            if(date('Y', strtotime($Verification['timestamp']))){
                $Antwort = true;
            }
        }
    } elseif($NutzergruppeInfos['req_verify']=='once'){
        if($Verification['erfolg'] == 'true'){
            $Antwort = true;
        }
    } elseif($NutzergruppeInfos['req_verify']=='false'){
        $Antwort = true;
    }

    return $Antwort;
}

function lade_uebernahme_res($IDres){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebernahmen WHERE reservierung = '$IDres' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;
}

function seiteninhalt_uebernahme_vorplanen_generieren($Reservierung){

    $link = connect_db();
    //DAU-Abfangen:
    $DAUcounter = 0;
    $DAUerror = "";
    $HTML = '';

    //Keine reservierung angegeben
    if(($Reservierung == "") OR ($Reservierung == "0")){
        $DAUcounter++;
        $DAUerror .= "Du hast keine Reservierung angegeben!<br>";
    } else if (intval($Reservierung) > 0) {

        //Reservierung inzwischen abgelaufen/storniert
        $ReservierungInfo = lade_reservierung($Reservierung);
        if(($ReservierungInfo['storno_user'] != "0") OR (time() > strtotime($ReservierungInfo['ende']))){
            $DAUcounter++;
            $DAUerror .= "Reservierung inzwischen abgelaufen oder storniert!<br>";
        } else {
            //Reservierung bereits mit Übernahme/Übergabe versorgt
            $AnfrageUebergaben = "SELECT id FROM uebergaben WHERE res = '$Reservierung' AND storno_user = 0";
            $AbfrageUebergaben = mysqli_query($link, $AnfrageUebergaben);
            if(mysqli_num_rows($AbfrageUebergaben)>0){
                $DAUcounter++;
                $DAUerror .= "Es ist bereits eine Übergabe angelegt!<br>";
            }

            $AnfrageUebernahmen = "SELECT id FROM uebernahmen WHERE reservierung = '$Reservierung' AND storno_user = 0";
            $AbfrageUebernahmen = mysqli_query($link, $AnfrageUebernahmen);
            if(mysqli_num_rows($AbfrageUebernahmen)>0){
                $DAUcounter++;
                $DAUerror .= "Es ist bereits eine Übergabe angelegt!<br>";
            }
        }
    }

    //DAU auswerten
    if($DAUcounter > 0){

        $HTML .= zurueck_karte_generieren(FALSE, $DAUerror, 'wartwesen.php');

    } else {

        //Vollkommen egal von welcher Reservierung übernommen wird - hauptsache sie ist noch nicht abgeschlossen und hat entweder ne Übernahme oder Übergabe gebucht:)
        $ReservierungInfos = lade_reservierung($Reservierung);
        $Anfrage = "SELECT * FROM reservierungen WHERE (beginn > '".date('Y')."-01-01 00:00:01' AND ende = '".$ReservierungInfos['beginn']."') OR (beginn > '".date('Y')."-01-01 00:00:01' AND user = '".$ReservierungInfos['user']."') AND id != ".$Reservierung." AND storno_user = '0' ORDER BY beginn ASC";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        $Options = "";

        if ($Anzahl == 0){
            $HTML .= "<h3>Keine passenden Reservierungen!</h3>";
        } else if ($Anzahl > 0){
            $HTML .= "<h3>Passende Reservierung gefunden!</h3>";
            for ($a = 1; $a <= $Anzahl; $a++){
                //Hat Res eine Schlüsselausgabe die bereits zurückgegeben wurde? -> Reservierung ist abgeschlossen:
                $GefundeneReservierung = mysqli_fetch_assoc($Abfrage);
                $AnfrageZwei = "SELECT rueckgabe FROM schluesselausgabe WHERE reservierung = '".$GefundeneReservierung['id']."' AND storno_user = '0'";
                $AbfrageZwei = mysqli_query($link, $AnfrageZwei);
                $AnzahlZwei = mysqli_num_rows($AbfrageZwei);

                if ($AnzahlZwei == 0){

                    //Kein Schlüssel bislang ausgegeben -> Prüfen ob Übergabe/Übernahme geplant
                    if(res_hat_uebergabe($GefundeneReservierung['id'])){
                        $HTMLitems = "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat Übergabe gebucht<br>";
                        $Resinfos = lade_reservierung($GefundeneReservierung['id']);
                        $UserResOption = lade_user_meta($Resinfos['user']);
                        $Content = table_row_builder(table_header_builder('User').table_data_builder(''.$UserResOption['vorname'].' '.$UserResOption['nachname'].''));
                        $Content .= table_row_builder(table_header_builder('Reservierungsdaten').table_data_builder('Beginn: '.strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($Resinfos['beginn'])).'<br>Ende: '.strftime("%H:%M Uhr", strtotime($Resinfos['ende']))));
                        $Content .= table_row_builder(table_header_builder('').table_data_builder(form_button_builder('uebernahme_vorplanen_'.$GefundeneReservierung['id'].'', 'Eintragen', 'action', 'send', '')));
                        $Content = table_builder($Content);
                        $Options .= collapsible_item_builder($HTMLitems, $Content, 'today');
                    }
                    if(res_hat_uebernahme($GefundeneReservierung['id'])){
                        $HTMLitems = "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat Übernahme gebucht<br>";
                        $Resinfos = lade_reservierung($GefundeneReservierung['id']);
                        $UserResOption = lade_user_meta($Resinfos['user']);
                        $Content = table_row_builder(table_header_builder('User').table_data_builder(''.$UserResOption['vorname'].' '.$UserResOption['nachname'].''));
                        $Content .= table_row_builder(table_header_builder('Reservierungsdaten').table_data_builder('Beginn: '.strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($Resinfos['beginn'])).'<br>Ende: '.strftime("%H:%M Uhr", strtotime($Resinfos['ende']))));
                        $Content .= table_row_builder(table_header_builder('').table_data_builder(form_button_builder('uebernahme_vorplanen_'.$GefundeneReservierung['id'].'', 'Eintragen', 'action', 'send', '')));
                        $Content = table_builder($Content);
                        $Options .= collapsible_item_builder($HTMLitems, $Content, 'today');
                    }

                } else if ($AnzahlZwei == 1){

                    $ErgebnisZwei = mysqli_fetch_assoc($AbfrageZwei);
                    if ($ErgebnisZwei['rueckgabe'] == NULL){
                        $HTMLitems = "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat bereits einen Schlüssel<br>";
                        $Resinfos = lade_reservierung($GefundeneReservierung['id']);
                        $UserResOption = lade_user_meta($Resinfos['user']);
                        $Content = table_row_builder(table_header_builder('User').table_data_builder(''.$UserResOption['vorname'].' '.$UserResOption['nachname'].''));
                        $Content .= table_row_builder(table_header_builder('Reservierungsdaten').table_data_builder('Beginn: '.strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($Resinfos['beginn'])).'<br>Ende: '.strftime("%H:%M Uhr", strtotime($Resinfos['ende']))));
                        $Content .= table_row_builder(table_header_builder('').table_data_builder(form_button_builder('uebernahme_vorplanen_'.$GefundeneReservierung['id'].'', 'Eintragen', 'action', 'send', '')));
                        $Content = table_builder($Content);
                        $Options .= collapsible_item_builder($HTMLitems, $Content, 'today');
                    }
                }
            }

            $HTML .= collapsible_builder($Options);
        }
    }

    $HTML .= section_builder(button_link_creator('Zurück', 'wartwesen.php', 'arrow_back', ''));

    return $HTML;
}

function schluessel_an_user_weitergeben($UebergabeDavorID, $Schluessel, $ReservierungID, $Wart){

    $Reservierung = lade_reservierung($ReservierungID);
    $link = connect_db();
    $Timestamp = timestamp();

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    if($DAUcounter > 0){

    } else if ($DAUcounter == 0) {

        $Anfrage = "INSERT INTO schluesselausgabe (uebergabe, wart, user, reservierung, schluessel, ausgabe, rueckgabe, storno_user, storno_time, storno_kommentar) VALUES ('$UebergabeDavorID', '$Wart', '".$Reservierung['user']."', '".$ReservierungID."', '$Schluessel', '$Timestamp', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '')";

        mysqli_query($link, $Anfrage);

        $AnfrageZwei = "UPDATE schluessel SET akt_ort = '', akt_user ='".$Reservierung['user']."' WHERE id = '$Schluessel'";
        mysqli_query($link, $AnfrageZwei);

        #schluessel_protokoll_event_hinzufuegen($Schluessel, 'Schl&uuml;ssel '.$Schluessel.' durch Res. '.$ReservierungID.' &uuml;bernommen.');

    }


}

function lade_schluesselausgabe_reservierung($ResID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM schluesselausgabe WHERE reservierung = '$ResID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;
}

function uebernahme_vorplanen_parser($ReservierungID){

    $Link = connect_db();
    $Anfrage = "SELECT id FROM reservierungen";
    $Abfrage = mysqli_query($Link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Antwort['success'] = null;
    for($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        if(isset($_POST['uebernahme_vorplanen_'.$Ergebnis['id'].''])){
            $Antwort = uebernahme_eintragen($ReservierungID, 'Angelegt durch eine*n Stocherkahnwart*in', $Ergebnis['id']);
        }
    }
    return $Antwort;
}

?>