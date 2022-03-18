<?php

    function sms_senden($VorlageName, $WerteArray, $EmpfaengerID, $Mode)
    {

        /**
         * $VorlageName - dient dem Laden der entsprechenden Vorlage aus der DB
         * $WerteArray - enthält die erforderlichen Werte für die Platzhalter des SMS textes
         * $EmpfaengerID - enthält die ID des Empfängers
         * $Mode - Optionale angabe, ansonsten wird der standard-Wert aus der SettingsXML verwendet
         */

        //HOUSEKEEPING
        $link = connect_db();
        $Timestamp = timestamp();

        //EINSTELLUNGEN LADEN

        $UserSMS = lade_xml_einstellung('user-sms');
        $PSWD = lade_xml_einstellung('key-sms');
        $Absender = lade_xml_einstellung('absender-sms');

        //Mode laden, wenn NULL
        if ($Mode == NULL) {
            $Mode = lade_xml_einstellung('type-sms');
        }

        //USERDATEN LADEN
        $User = lade_user_meta($EmpfaengerID);
        $Telefon = $User['telefon'];

        $VorlageText = lade_smsvorlage($VorlageName);

        //TEXT GENERIEREN
        if ($WerteArray == NULL) {
            $TextZumSenden = $VorlageText;
        } else {
            $TextZumSenden = str_replace(array_keys($WerteArray), array_values($WerteArray), $VorlageText);
        }

        //SMS GENERIEREN
        $params = array(
            'u' => $UserSMS,
            'p' => $PSWD,
            'to' => $Telefon,
            'type' => $Mode,
            'text' => $TextZumSenden,
            'from' => $Absender
        );

        //SMS SENDEN
        $url = 'https://gateway.sms77.de/?' . http_build_query($params);
        $ret = file_get_contents_curl($url);

        //ERGEBNIS AUSWERTEN
        if ($ret == '100') {

            //Erfolg eintragen
            $AnfrageErfolgEintragen = "INSERT INTO sms_protokoll (name, user, timestamp, fail, errorcode) VALUES ('$VorlageName', '$EmpfaengerID', '$Timestamp', '0', '$ret')";
            if (mysqli_query($link, $AnfrageErfolgEintragen)) {

                $Antwort['erfolg'] = true;
            } else {

                $Antwort['erfolg'] = false;
                $Antwort['error'] = "SMS verschickt - Fehler beim speichern!";
            }

        } else if ($ret === FALSE){

            echo "PHP error";

        } else {

            //Fehler eintragen
            $AnfrageFehlerEintragen = "INSERT INTO sms_protokoll (name, user, timestamp, fail, errorcode) VALUES ('$VorlageName', '$EmpfaengerID', '$Timestamp', '1', '$ret')";
            if (mysqli_query($link, $AnfrageFehlerEintragen)) {
                $Antwort['erfolg'] = false;
                $Antwort['error'] = "Fehler " .$ret. " beim Senden der SMS! ". $url. "";
            } else {
                $Antwort['error'] = true;
                $Antwort['erfolg'] = "SMS konnte nicht versendet werden - Fehlercode: " .$ret. " - Fehler beim Speichern des Fehlers!";
            }
        }

        return $Antwort;
    }

    function file_get_contents_curl($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

?>