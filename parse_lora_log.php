<?php
include_once "./ressources/ressourcen.php";
$link = connect_db();

//Soll ich überhaupt aktiv sein?
$shallIlive = lade_xml_einstellung('schluesselrueckgabe_automat_aktiv');
if($shallIlive == 'on'){

    //Lade letzte zwei Logs
    $minTimestampToday = date('Y-m-d')." 00:00:01";
    $anfrage = "SELECT * FROM lora_logs WHERE timestamp > '".$minTimestampToday."' ORDER BY timestamp DESC";
    $abfrage = mysqli_query($link, $anfrage);
    $anzahl = mysqli_num_rows($abfrage);

    if($anzahl>0) {

        $lastLog = array();
        $secondLastLog = array();

        for ($a = 0; $a < 2; $a++) {
            if ($a == 0) {
                $lastLog = mysqli_fetch_assoc($abfrage);
            } elseif ($a == 1) {
                $secondLastLog = mysqli_fetch_assoc($abfrage);
            }
        }

        $KeystatusLastLog = explode(',', $lastLog['schluessel']);
        $KeystatusSecondLastLog = explode(',', $secondLastLog['schluessel']);


        //1. Schlüsselkram
        $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> NULL AND rueckgabe = NULL ORDER BY schluessel ASC";
        $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
        $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);

        if ($AnzahlLadeAlleSchluesselausgaben > 0) {
            echo "Schlüsselausgaben ausstehend!:<br>";

            for ($b = 1; $b <= $AnzahlLadeAlleSchluesselausgaben; $b++) {
                $ErgebnisLadeAlleSchluesselausgaben = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);
                $Schluesselinfos = lade_schluesseldaten($ErgebnisLadeAlleSchluesselausgaben['schluessel']);
                $assocKeytagSchluessel = $Schluesselinfos['RFID'];

                //Checken, ob RFID Tag 2x in Folge gefunden wurde
                $ArrayNumberKey = $assocKeytagSchluessel - 1;
                $LastStatusKey = $KeystatusLastLog[$ArrayNumberKey];
                $SecondLastStatusKey = $KeystatusSecondLastLog[$ArrayNumberKey];

                //Fälle checken
                if ($LastStatusKey == 0) {
                    //Schlüssel fehlt noch
                    echo "Schlüssel noch nicht zurückgegeben!<br>";
                } elseif (($LastStatusKey == 1) && ($SecondLastStatusKey == 0)) {
                    echo "Schlüssel bislang nur einmal gefunden! Rückgabe wird im nächsten Zyklus festgehalten!<br>";
                } elseif (($LastStatusKey == 1) && ($SecondLastStatusKey == 1)) {
                    $Return = schluesselrueckgabe_festhalten($ErgebnisLadeAlleSchluesselausgaben['schluessel']);
                    if ($Return) {
                        echo "Schlüssel seit 2 Zyklen da, Rückgabe wird festgehalten!<br>";
                    } else {
                        echo "Schlüssel seit 2 Zyklen da, Fehler beim Festhalten der Rückgabe!<br>";
                    }
                }
            }

        } else {
            echo "Keine Rückgaben derzeit erwartet!<br><br>";
        }

        //2. Batteriestatus auswerten
        $MinimalVoltage = lade_xml_einstellung('batterie_spannung_untergrenze');
        $MinimalVoltage = floatval($MinimalVoltage);
        if ($lastLog['voltage'] < $MinimalVoltage) {
            if(lade_xml_einstellung('warnung_lora_unterspannung_aktiv')=='on'){
                echo "Unterspannungswarnung senden!<br>";
                mail_senden('lora_batt_unterspannung', 'marc@haefeker.de', '');
            }
        }

        //3. Check ob Sender tot wenn zu lange kein Eintrag mehr
    } else {
        $anfrage2 = "SELECT * FROM lora_logs ORDER BY timestamp DESC";
        $abfrage2 = mysqli_query($link, $anfrage2);
        $ergebnis2 = mysqli_fetch_assoc($abfrage2);
        $lastTimestamp = $ergebnis2['timestamp'];
        $lastTimestamp = strtotime($lastTimestamp);
        $grenze = strtotime('- 12 hours');
        if ($lastTimestamp<$grenze){
            if(lade_xml_einstellung('warnung_lora_totmann_aktiv')=='on'){
                echo "Lora meldet sich nicht!!<br>";
                mail_senden('lora_missing', 'marc@haefeker.de', '');
            }
        }
    }

} else {
    echo 'Funktion deaktiviert';
}
