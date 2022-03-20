<?php
function sperrung_anlegen($BeginnSperrung, $EndeSperrung, $Typ, $Titel, $Erklaerung, $CreatorID, $OverrideReservations){

    //Eingaben auswerten
    $Auswertung = sperrungeingabe_auswerten($BeginnSperrung, $EndeSperrung, $Typ, $Titel, $Erklaerung);

    //Liegt ein general Error vor?
    if ($Auswertung['fatal_error'] == TRUE){

        //Wir beenden den Vorgang
        $Antwort['erfolg'] = FALSE;
        $Antwort['meldung'] = $Auswertung['error_text'];

    } else if ($Auswertung['fatal_error'] == FALSE){

        //Überprüfen ob Reservierungen von der Pause betroffen sind
        if ($Auswertung['reservierung_betroffen'] == TRUE){

            $AnzahlBetroffeneReservierungen = mysqli_num_rows($Auswertung['betroffene_reservierungen']);

            //Überprüfen ob wir im override-mode sind
            if ($OverrideReservations == TRUE){
                $ErfolgCounter = 0;
                $ErrorMessage = "";

                //Generieren der Begründung für Mail an den User
                $Begruendung = "<p>Zum Zeitpunkt deiner Reservierung musste leider eine Sperrung des Kahnbetriebs eingetragen werden.</p>";
                $Begruendung .= "<p>Hier die Daten zur Sperrung:<br>";
                $Begruendung .= "Typ: ".$Typ."<br>";
                $Begruendung .= "Titel: ".$Titel."<br>";
                $Begruendung .= "Details: ".$Erklaerung."</p>";

                //Wir iterieren und stornieren die betroffenen Reservierungen
                for ($a = 1; $a <= $AnzahlBetroffeneReservierungen; $a++){

                    $BetroffeneReservierung = mysqli_fetch_assoc($Auswertung['betroffene_reservierungen']);
                    $ID = $BetroffeneReservierung['id'];
                    $ReservierungStornieren = reservierung_stornieren($ID, $CreatorID, $Begruendung);

                    if ($ReservierungStornieren['success'] == TRUE){
                        $ErfolgCounter++;
                    } else {
                        $ErrorMessage .= "Fehler beim Stornieren der Reservierung ".$ID."!<br>";
                    }
                }

                if ($ErfolgCounter == $AnzahlBetroffeneReservierungen){
                    $Antwort['erfolg'] = TRUE;
                    $Antwort['meldung'] = "Sperrung erfolgreich eingetragen! Es wurden ".$ErfolgCounter." Reservierungen storniert.<br>";
                } else {
                    $Antwort['erfolg'] = FALSE;
                    $Antwort['meldung'] = $ErrorMessage;
                }

            } else if ($OverrideReservations == FALSE){
                //Wir geben eine Fehlermeldung der betroffenen Reservierungven zurück
                $Antwort['erfolg'] = FALSE;
                $Antwort['reservierungen_betroffen'] = $AnzahlBetroffeneReservierungen;
            }

        } else if ($Auswertung['reservierung_betroffen'] == FALSE){

            //Wir tragen die Pause direkt ein
            $Eintrag = sperrung_eintragen($Typ, $BeginnSperrung, $EndeSperrung, $Titel, $Erklaerung, $CreatorID);

            if ($Eintrag['success'] == TRUE){
                $Antwort['erfolg'] = TRUE;
                $Antwort['meldung'] = "Die Sperrung wurde erfolgreich in der Datenbank abgelegt!";
            } else {
                $Antwort['erfolg'] = FALSE;
                $Antwort['meldung'] = $Eintrag['error'];
            }
        }
    }

    return $Antwort;
}

function sperrung_eintragen($Typ, $Beginn, $Ende, $Titel, $Erklaerung, $Ersteller){

    $link = connect_db();

    $AnfragePauseEintragen = "INSERT INTO sperrungen (typ, beginn, ende, titel, erklaerung, ersteller, storno_user, storno_zeit) VALUES ('$Typ', '$Beginn', '$Ende', '$Titel', '$Erklaerung', '$Ersteller', '0','0000-00-00 00:00:00')";
    $AbfragePauseEintragen = mysqli_query($link, $AnfragePauseEintragen);

    if ($AbfragePauseEintragen == TRUE){
        $Antwort['success'] = TRUE;
    } else {
        $Antwort['success'] = FALSE;
        $Antwort['error'] = mysqli_error($link);
    }

    return $Antwort;
}

function sperrungeingabe_auswerten($BeginnSperrung, $EndeSperrung, $Typ, $Titel, $Erklaerung, $Mode='add'){

    //HOUSEKEEPING
    $link = connect_db();

    //DAU-Check:
    $DAUcounter = 0;
    $GeneralError = "";

    //Liegt das Ende vor dem Anfang?
    if (strtotime($EndeSperrung) < strtotime($BeginnSperrung)){
        $DAUcounter++;
        $GeneralError .= "Der Beginn der Sperrung muss vor dem Ende liegen!<br>";
    }

    //Fehlen Typ, Titel oder Erklärung?
    if (empty($Typ)){
        $DAUcounter++;
        $GeneralError .= "Es muss ein Sperrungstyp angegeben werden!<br>";
    }

    if (empty($Titel)){
        $DAUcounter++;
        $GeneralError .= "Es muss ein Sperrungstitel angegeben werden!<br>";
    }

    if (empty($Erklaerung)){
        $DAUcounter++;
        $GeneralError .= "Es muss ein Erkl&auml;rungstext f&uuml;r die Sperrung angegeben werden!<br>";
    }

    if($Mode=='add'){
        //Liegt bereits eine Pause in diesem Zeitraum vor?
        $AnfrageLadeVorhandenePausen = "SELECT id FROM pausen WHERE ((('$BeginnSperrung' <= beginn) AND (ende <= '$EndeSperrung')) OR ((beginn <= '$BeginnSperrung') AND ('$BeginnSperrung' < ende)) OR ((beginn < '$EndeSperrung') AND ('$EndeSperrung' <= ende))) AND storno_user = '0'";
        $AbfrageLadeVorhandenePausen = mysqli_query($link, $AnfrageLadeVorhandenePausen);

        if (mysqli_num_rows($AbfrageLadeVorhandenePausen) > 0){
            $DAUcounter++;
            $GeneralError .= "In dem angegebenen Zeitfenster befindet sich bereits mindestens eine andere Pause! Bitte l&ouml;sche diese vorher!<br>";
        }

        //Liegt bereits eine Sperrung in diesem Zeitraum vor?
        $AnfrageLadeVorhandeneSperrungen = "SELECT id FROM sperrungen WHERE ((('$BeginnSperrung' <= beginn) AND (ende <= '$EndeSperrung')) OR ((beginn <= '$BeginnSperrung') AND ('$BeginnSperrung' < ende)) OR ((beginn < '$EndeSperrung') AND ('$EndeSperrung' <= ende))) AND storno_user = '0'";
        $AbfrageLadeVorhandeneSperrungen = mysqli_query($link, $AnfrageLadeVorhandeneSperrungen);

        if (mysqli_num_rows($AbfrageLadeVorhandeneSperrungen) > 0){
            $DAUcounter++;
            $GeneralError .= "In dem angegebenen Zeitfenster befindet sich bereits mindestens eine andere Sperrung! Bitte l&ouml;sche diese vorher!<br>";
        }
    }

    //Zwischenauswertung - wenn hier schon was faul ist, schonen wir die DB
    if ($DAUcounter == 0){

        $FatalError = FALSE;

        //Sind Reservierungen von dieser Pause betroffen?
        $AnfrageLadeVorhandeneReservierungen = "SELECT id, user, beginn, ende FROM reservierungen WHERE ((('$BeginnSperrung' <= beginn) AND (ende <= '$EndeSperrung')) OR ((beginn <= '$BeginnSperrung') AND ('$BeginnSperrung' < ende)) OR ((beginn < '$EndeSperrung') AND ('$EndeSperrung' <= ende))) AND storno_user = '0'";
        $AbfrageLadeVorhandeneReservierungen = mysqli_query($link, $AnfrageLadeVorhandeneReservierungen);

        if (mysqli_num_rows($AbfrageLadeVorhandeneReservierungen) > 0){
            $ReservierungenBetroffen = TRUE;
        } else if (mysqli_num_rows($AbfrageLadeVorhandeneReservierungen) == 0){
            $ReservierungenBetroffen = FALSE;
        }

    } else {

        $FatalError = TRUE;
        $ReservierungenBetroffen = NULL;
        $VorhandeneReservierungen = NULL;

    }

    $Antwort['fatal_error'] = $FatalError;
    $Antwort['error_text'] = $GeneralError;
    $Antwort['reservierung_betroffen'] = $ReservierungenBetroffen;
    $Antwort['betroffene_reservierungen'] = $AbfrageLadeVorhandeneReservierungen;

    return $Antwort;
}

function sperrung_stornieren($ID, $User){

    //Housekeeping
    $link = connect_db();
    $Timestamp = timestamp();
    $Antwort = NULL;

    //DAU - ist pause schon storniert?
    $AnfrageDAU = "SELECT beginn, ende, storno_user FROM sperrungen WHERE id = '$ID'";
    $AbfrageDAU = mysqli_query($link, $AnfrageDAU);
    $ErgebnisDAU = mysqli_fetch_assoc($AbfrageDAU);

    if ($ErgebnisDAU['storno_user'] != 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = "Die Sperrung wurde bereits storniert!";
    } else if ($ErgebnisDAU['storno_user'] == 0){

        if ($User == ""){
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Es wurde keine UserID &uuml;bermittelt! Bitte Admin kontaktieren!";
        } else {

            $AnfragePauseLoeschen = "UPDATE sperrungen SET storno_user = '$User', storno_zeit = '$Timestamp' WHERE id = '$ID'";
            if (mysqli_query($link, $AnfragePauseLoeschen)){
                $Antwort['success'] = TRUE;
                $Antwort['meldung'] = "Die Sperrung wurde erfolgreich storniert!";
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = mysqli_error($link);
            }
        }
    }

    return $Antwort;
}

function sperrung_bearbeiten($PauseID, $BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung, $OverrideReservations){

    //Eingaben auswerten
    $Auswertung = sperrungeingabe_auswerten($BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung, 'edit');

    //Liegt ein general Error vor?
    if ($Auswertung['fatal_error'] == TRUE){

        //Wir beenden den Vorgang
        $Antwort['erfolg'] = FALSE;
        $Antwort['meldung'] = $Auswertung['error_text'];

    } else if ($Auswertung['fatal_error'] == FALSE){

        //Überprüfen ob Reservierungen von der Pause betroffen sind
        if ($Auswertung['reservierung_betroffen'] == TRUE){

            $AnzahlBetroffeneReservierungen = mysqli_num_rows($Auswertung['betroffene_reservierungen']);

            //Überprüfen ob wir im override-mode sind
            if ($OverrideReservations == TRUE){
                $ErfolgCounter = 0;
                $ErrorMessage = "";

                //Generieren der Begründung für Mail an den User
                $Begruendung = "<p>Zum Zeitpunkt deiner Reservierung musste leider eine betriebsbedingte Pause des Kahnbetriebs eingetragen werden.</p>";
                $Begruendung .= "<p>Hier die Daten zu der Betriebspause:<br>";
                $Begruendung .= "Typ: ".$Typ."<br>";
                $Begruendung .= "Titel: ".$Titel."<br>";
                $Begruendung .= "Details: ".$Erklaerung."</p>";

                //Wir iterieren und stornieren die betroffenen Reservierungen
                for ($a = 1; $a <= $AnzahlBetroffeneReservierungen; $a++){

                    $BetroffeneReservierung = mysqli_fetch_assoc($Auswertung['betroffene_reservierungen']);
                    $ID = $BetroffeneReservierung['id'];
                    $ReservierungStornieren = reservierung_stornieren($ID, lade_user_id(), $Begruendung);

                    if ($ReservierungStornieren['success'] == TRUE){
                        $ErfolgCounter++;
                    } else {
                        $ErrorMessage .= "Fehler beim Stornieren der Reservierung ".$ID."!<br>";
                    }
                }

                if ($ErfolgCounter == $AnzahlBetroffeneReservierungen){
                    $Antwort['erfolg'] = TRUE;
                    $Antwort['meldung'] = "Sperrung erfolgreich bearbeitet! Es wurden ".$ErfolgCounter." Reservierungen storniert.<br>";
                } else {
                    $Antwort['erfolg'] = FALSE;
                    $Antwort['meldung'] = $ErrorMessage;
                }

            } else if ($OverrideReservations == FALSE){
                //Wir geben eine Fehlermeldung der betroffenen Reservierungven zurück
                $Antwort['erfolg'] = FALSE;
                $Antwort['reservierungen_betroffen'] = $AnzahlBetroffeneReservierungen;
            }

        } else if ($Auswertung['reservierung_betroffen'] == FALSE){

            //Wir tragen die Pause direkt ein
            $Eintrag = sperrung_bearbeiten_dostuff($PauseID, $Typ, $BeginnPause, $EndePause, $Titel, $Erklaerung);

            if ($Eintrag['success'] == TRUE){
                $Antwort['erfolg'] = TRUE;
                $Antwort['meldung'] = "Die Betriebspause wurde erfolgreich in der Datenbank abgelegt!";
            } else {
                $Antwort['erfolg'] = FALSE;
                $Antwort['meldung'] = $Eintrag['error'];
            }
        }
    }

    return $Antwort;
}

function sperrung_bearbeiten_dostuff($ID, $Typ, $Beginn, $Ende, $Titel, $Erklaerung){

    $link = connect_db();

    $AnfragePauseEintragen = "UPDATE sperrungen SET typ = '$Typ', beginn = '$Beginn', ende = '$Ende', titel = '$Titel', erklaerung = '$Erklaerung' WHERE id = '$ID'";
    $AbfragePauseEintragen = mysqli_query($link, $AnfragePauseEintragen);

    if ($AbfragePauseEintragen == TRUE){
        $Antwort['success'] = TRUE;
    } else {
        $Antwort['success'] = FALSE;
        $Antwort['error'] = mysqli_error($link);
    }

    return $Antwort;
}

?>