<?php

    function pause_anlegen($BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung, $CreatorID, $OverrideReservations){

        //Eingaben auswerten
        $Auswertung = pauseneingabe_auswerten($BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung);

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
                        $ReservierungStornieren = reservierung_stornieren($ID, $CreatorID, $Begruendung);

                        if ($ReservierungStornieren['success'] == TRUE){
                            $ErfolgCounter++;
                        } else {
                            $ErrorMessage .= "Fehler beim Stornieren der Reservierung ".$ID."!<br>";
                        }
                    }

                    if ($ErfolgCounter == $AnzahlBetroffeneReservierungen){
                        $Antwort['erfolg'] = TRUE;
                        $Antwort['meldung'] = "Pause erfolgreich eingetragen! Es wurden ".$ErfolgCounter." Reservierungen storniert.<br>";
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
                $Eintrag = pause_eintragen($Typ, $BeginnPause, $EndePause, $Titel, $Erklaerung, $CreatorID);

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

function pause_bearbeiten($PauseID, $BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung, $OverrideReservations){

    //Eingaben auswerten
    $Auswertung = pauseneingabe_auswerten($BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung, 'edit');

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
                    $Eintrag = pause_bearbeiten_dostuff($PauseID, $Typ, $BeginnPause, $EndePause, $Titel, $Erklaerung);
                    $Antwort['erfolg'] = $Eintrag['success'];
                    $Antwort['meldung'] = "Pause erfolgreich bearbeitet! Es wurden ".$ErfolgCounter." Reservierungen storniert.<br>";
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
            $Eintrag = pause_bearbeiten_dostuff($PauseID, $Typ, $BeginnPause, $EndePause, $Titel, $Erklaerung);

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

function pause_bearbeiten_dostuff($ID, $Typ, $Beginn, $Ende, $Titel, $Erklaerung){

    $link = connect_db();

    $AnfragePauseEintragen = "UPDATE pausen SET typ = '$Typ', beginn = '$Beginn', ende = '$Ende', titel = '$Titel', erklaerung = '$Erklaerung' WHERE id = '$ID'";
    $AbfragePauseEintragen = mysqli_query($link, $AnfragePauseEintragen);

    if ($AbfragePauseEintragen == TRUE){
        $Antwort['success'] = TRUE;
    } else {
        $Antwort['success'] = FALSE;
        $Antwort['error'] = mysqli_error($link);
    }

    return $Antwort;
}

    function pause_eintragen($Typ, $Beginn, $Ende, $Titel, $Erklaerung, $Ersteller){

        $link = connect_db();

        $AnfragePauseEintragen = "INSERT INTO pausen (typ, beginn, ende, titel, erklaerung, ersteller, storno_user, storno_zeit) VALUES ('$Typ', '$Beginn', '$Ende', '$Titel', '$Erklaerung', '$Ersteller', '0', '0000-00-00 00:00:00')";
        $AbfragePauseEintragen = mysqli_query($link, $AnfragePauseEintragen);

        if ($AbfragePauseEintragen == TRUE){
            $Antwort['success'] = TRUE;
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['error'] = mysqli_error($link);
        }

        return $Antwort;
    }

    function pauseneingabe_auswerten($BeginnPause, $EndePause, $Typ, $Titel, $Erklaerung, $Mode='create'){

        //HOUSEKEEPING
        $link = connect_db();

        //DAU-Check:
        $DAUcounter = 0;
        $GeneralError = "";

        //Liegt das Ende vor dem Anfang?
        if (strtotime($EndePause) < strtotime($BeginnPause)){
            $DAUcounter++;
            $GeneralError .= "Der Beginn der Pause muss vor dem Ende liegen!<br>";
        }

        //Fehlen Typ, Titel oder Erklärung?
        if (empty($Typ)){
            $DAUcounter++;
            $GeneralError .= "Es muss ein Pausentyp angegeben werden!<br>";
        }

        if (empty($Titel)){
            $DAUcounter++;
            $GeneralError .= "Es muss ein Pausentitel angegeben werden!<br>";
        }

        if (empty($Erklaerung)){
            $DAUcounter++;
            $GeneralError .= "Es muss ein Erkl&auml;rungstext f&uuml;r die Pause angegeben werden!<br>";
        }

        if($Mode == 'create'){
            //Liegt bereits eine Pause in diesem Zeitraum vor?
            $AnfrageLadeVorhandenePausen = "SELECT id FROM pausen WHERE ((('$BeginnPause' <= beginn) AND (ende <= '$EndePause')) OR ((beginn <= '$BeginnPause') AND ('$BeginnPause' < ende)) OR ((beginn < '$EndePause') AND ('$EndePause' <= ende))) AND storno_user = '0'";
            $AbfrageLadeVorhandenePausen = mysqli_query($link, $AnfrageLadeVorhandenePausen);

            if (mysqli_num_rows($AbfrageLadeVorhandenePausen) > 0){
                $DAUcounter++;
                $GeneralError .= "In dem angegebenen Zeitfenster befindet sich bereits mindestens eine andere Pause! Bitte l&ouml;sche diese vorher!<br>";
            }
        }

            //Liegt bereits eine Sperrung in diesem Zeitraum vor?
            $AnfrageLadeVorhandeneSperrungen = "SELECT id FROM sperrungen WHERE ((('$BeginnPause' <= beginn) AND (ende <= '$EndePause')) OR ((beginn <= '$BeginnPause') AND ('$BeginnPause' < ende)) OR ((beginn < '$EndePause') AND ('$EndePause' <= ende))) AND storno_user = '0'";
            $AbfrageLadeVorhandeneSperrungen = mysqli_query($link, $AnfrageLadeVorhandeneSperrungen);

            if (mysqli_num_rows($AbfrageLadeVorhandeneSperrungen) > 0){
                $DAUcounter++;
                $GeneralError .= "In dem angegebenen Zeitfenster befindet sich bereits mindestens eine andere Sperrung! Bitte l&ouml;sche diese vorher!<br>";
            }

        //Zwischenauswertung - wenn hier schon was faul ist, schonen wir die DB
        if ($DAUcounter == 0){

            $FatalError = FALSE;

            //Sind Reservierungen von dieser Pause betroffen?
            $AnfrageLadeVorhandeneReservierungen = "SELECT id, user, beginn, ende FROM reservierungen WHERE ((('$BeginnPause' <= beginn) AND (ende <= '$EndePause')) OR ((beginn <= '$BeginnPause') AND ('$BeginnPause' < ende)) OR ((beginn < '$EndePause') AND ('$EndePause' <= ende))) AND storno_user = '0'";
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

    function pause_stornieren($ID, $User){

        //Housekeeping
        $link = connect_db();
        $Timestamp = timestamp();
        $Antwort = NULL;

        //DAU - ist pause schon storniert?
        $AnfrageDAU = "SELECT beginn, ende, storno_user FROM pausen WHERE id = '$ID'";
        $AbfrageDAU = mysqli_query($link, $AnfrageDAU);
        $ErgebnisDAU = mysqli_fetch_assoc($AbfrageDAU);

        if ($ErgebnisDAU['storno_user'] != 0){
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Die Betriebspause wurde bereits storniert!";
        } else if ($ErgebnisDAU['storno_user'] == 0){

            if ($User == ""){
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Es wurde keine UserID &uuml;bermittelt! Bitte Admin kontaktieren!";
            } else {

                $AnfragePauseLoeschen = "UPDATE pausen SET storno_user = '$User', storno_zeit = '$Timestamp' WHERE id = '$ID'";
                if (mysqli_query($link, $AnfragePauseLoeschen)){
                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = "Die Betriebspause wurde erfolgreich storniert!";
                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['meldung'] = "Datenbankfehler, bitte Admin kontaktieren!";
                }
            }
        }

        return $Antwort;
    }

?>