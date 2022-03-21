<?php
/**
 * Gets called from cronjob
 *
 * Jede Schlüsselübernahme wird geprüft: Wenn Anfangsres vorbei ist, passiert folgendes:
 *  - Schlüssel wird für anfangsres als zurückgegeben markiert
 *  - Schlüssel wird an folgeres ausgeteilt
 */

include_once "./ressources/ressourcen.php";

auto_update_uebernahmen();
auto_delete_user();
lora_stuff();
pegelstand_fetcher();
automatische_sperrung_wasserstand();

function auto_update_uebernahmen(){

    $link = connect_db();

    $AnfrageLadeAlleUebernahmen = "SELECT * FROM uebernahmen WHERE storno_user = '0'";
    $AbfrageLadeAlleUebernahmen = mysqli_query($link, $AnfrageLadeAlleUebernahmen);
    $AnzahlLadeAlleUebernahmen = mysqli_num_rows($AbfrageLadeAlleUebernahmen);

    echo "<h3>Auto Update &Uuml;bernahmen</h3>";
    echo "<p>Anzahl aller &Uuml;bernahmen: ".$AnzahlLadeAlleUebernahmen."</p><p>";

    for ($a = 1; $a <= $AnzahlLadeAlleUebernahmen; $a++){
        $Uebernahme = mysqli_fetch_assoc($AbfrageLadeAlleUebernahmen);

        $IDReservierungDavor = $Uebernahme['reservierung_davor'];
        $IDReservierungDanach = $Uebernahme['reservierung'];
        $ReservierungDavor = lade_reservierung($IDReservierungDavor);#

        if (time() < strtotime($ReservierungDavor['ende'])){

            echo "".$Uebernahme['id'].": Reservierung davor noch nicht vorbei!<br>";

        } else if (time() > strtotime($ReservierungDavor['ende'])) {

            echo "".$Uebernahme['id'].": ";

            $SchluesselausgabeDavor = lade_schluesselausgabe_reservierung($IDReservierungDavor);

            //Nur weiter wenn Rückgabe noch nicht bereits festgehalten!
            if ($SchluesselausgabeDavor['rueckgabe'] == "0000-00-00 00:00:00"){
                //Rückgabe des Schlüssels festhalten
                schluesselrueckgabe_festhalten($SchluesselausgabeDavor['schluessel']);
                echo "R&uuml;ckgabe Reservierung davor festgehalten - ";

                //Neuausgabe an Reservierung danach festhalten
                schluessel_an_user_weitergeben($SchluesselausgabeDavor['uebergabe'], $SchluesselausgabeDavor['schluessel'], $IDReservierungDanach, $SchluesselausgabeDavor['wart']);
                echo "Schl&uuml;ssel an Reservierung danach ausgegeben.";
            } else {
                echo "R&uuml;ckgabe bereits festgehalten!";
            }

            echo "<br>";
        }
    }

    echo "</p>";

}

function auto_delete_user(){

	$link = connect_db();
	$Users = get_sorted_user_array_with_user_meta_fields('id');
	
	foreach ($Users as $User){
	    if(date('Y')!=date('Y', strtotime($User['registrierung']))){
            $StopCount=0;
            if($User['ist_wart']=='true'){
                $StopCount++;
            }
            if($User['ist_admin']=='true'){
                $StopCount++;
            }

            if($StopCount==0){

                $yearNow = date("Y", strtotime('-'.lade_xml_einstellung('delete-inactive-users-after-x-years').' years'));
                $ZeitGrenze = $yearNow."-12-31 23:59:59";
                $Anfrage2 = "SELECT id FROM reservierungen WHERE user = ".$User['id']." AND beginn > '".$ZeitGrenze."'";
                #var_dump($Anfrage2);

                $Abfrage2 = mysqli_query($link, $Anfrage2);
                $Anzahl2 = mysqli_num_rows($Abfrage2);

                if($Anzahl2>0){
                    echo $User['id']." war aktiv<br>";
                } elseif($Anzahl2==0) {
                    #echo $User['id']." war seit über einem Jahr INAKTIV - KANN GELÖSCHT WERDEN!!!! - ";
                    $Anfrage3 = "DELETE FROM users WHERE id = ".$User['id']."";
                    $Abfrage3 = mysqli_query($link, $Anfrage3);
                    if($Abfrage3){

                        $Anfrage4 = "DELETE FROM user_meta WHERE user = ".$User['id']." AND schluessel != 'vorname' AND schluessel != 'nachname'";
                        $Abfrage4 = mysqli_query($link, $Anfrage4);

                        if($Abfrage4){
                            #echo "LÖSCHEN ERFOLGREICH!!!<br>";
                        } else {
                            #echo "FEHLER BEIM META LÖSCHEN!!!<br>";
                        }

                    } else {
                        #echo "FEHLER BEIM LÖSCHEN!!!<br>";
                    }

                }
            } elseif($StopCount>0) {
                #echo $User['id'].' ist WICHTIG!<br>';
            }
        } else {
            #echo $User['id'].' hat sich erst DIESES JAHR REGISTRIERT!<br>';
        }
	}

	return null;
}

function lora_stuff(){

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
            #var_dump($KeystatusLastLog);
            $KeystatusSecondLastLog = explode(',', $secondLastLog['schluessel']);

            //1. Schlüsselkram
            $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> NULL AND rueckgabe = NULL ORDER BY schluessel ASC";
            $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
            $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);

            if ($AnzahlLadeAlleSchluesselausgaben > 0) {
                echo $AnzahlLadeAlleSchluesselausgaben." Schlüsselausgaben ausstehend!:<br>";

                for ($b = 1; $b <= $AnzahlLadeAlleSchluesselausgaben; $b++) {
                    $ErgebnisLadeAlleSchluesselausgaben = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);
                    $Schluesselinfos = lade_schluesseldaten($ErgebnisLadeAlleSchluesselausgaben['schluessel']);
                    $assocKeytagSchluessel = $Schluesselinfos['RFID'];

                    if($assocKeytagSchluessel==''){
                        echo "Schlüssel hat noch keinen LoRa Keytag!<br>";
                    } else {
                        //Checken, ob RFID Tag 2x in Folge gefunden wurde
                        $LastStatusKey = $KeystatusLastLog[$assocKeytagSchluessel];
                        $SecondLastStatusKey = $KeystatusSecondLastLog[$assocKeytagSchluessel];

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
                        } else {
                            echo "Schlüssel derzeit nicht im System";
                        }
                    }
                }

            } else {
                echo "Keine Rückgaben derzeit erwartet!<br><br>";
            }

            //SchlüsselKorrektur
            $AnfrageKorrektur = "SELECT id, akt_ort FROM schluessel WHERE delete_user = 0 ORDER BY id DESC";
            $AbfrageKorrektur = mysqli_query($link, $AnfrageKorrektur);
            $AnzahlKorrektur = mysqli_num_rows($AbfrageKorrektur);
            for($a=1;$a<=$AnzahlKorrektur;$a++){
                $ErgebnisKorrektur = mysqli_fetch_assoc($AbfrageKorrektur);
                $LastStatusKey = $KeystatusLastLog[$ErgebnisKorrektur['id']];
                $SecondLastStatusKey = $KeystatusSecondLastLog[$ErgebnisKorrektur['id']];

                if($LastStatusKey==$SecondLastStatusKey){
                    if($LastStatusKey==1){
                        ##Aktueller Ort ist Rückgabekasten
                        if($ErgebnisKorrektur['akt_ort']!='rueckgabekasten'){
                            schluessel_umbuchen($ErgebnisKorrektur['id'],'','rueckgabekasten',0);
                            echo "Schlüssel #".$ErgebnisKorrektur['id']." wieder zurück im Kasten<br>";
                        }
                    }
                }
            }

            //2. Batteriestatus auswerten
            $MinimalVoltage = lade_xml_einstellung('batterie_spannung_untergrenze');
            $MinimalVoltage = floatval($MinimalVoltage);
            if ($lastLog['voltage'] < $MinimalVoltage) {
                if(lade_xml_einstellung('warnung_lora_unterspannung_aktiv')=='on'){
                    echo "Unterspannungswarnung senden!<br>";

                    //Finde heraus ob mail schon mal gesendet wurde
                    if(mail_schon_gesendet(1,'lora_batt_unterspannung')){

                        //Mail nur einmal täglich senden
                        $TimestampLastMail = timestamp_letzte_mail_gesendet(1, 'lora_batt_unterspannung');
                        $SearchTime = strtotime('+1 day', strtotime($TimestampLastMail));
                        if(time()>$SearchTime){
                            mail_senden('lora_batt_unterspannung', 'marc@haefeker.de', '', 'lora_batt_unterspannung');
                            echo "Mail gesendet";
                        } else {
                            echo "Mail heute schon gesendet";
                        }

                    } else {
                        mail_senden('lora_batt_unterspannung', 'marc@haefeker.de', '', 'lora_batt_unterspannung');
                    }
                }
            }

            //3. Check ob Sender tot wenn zu lange kein Eintrag mehr
        } else {
            $anfrage2 = "SELECT * FROM lora_logs ORDER BY timestamp DESC";
            $abfrage2 = mysqli_query($link, $anfrage2);
            $ergebnis2 = mysqli_fetch_assoc($abfrage2);
            $lastTimestamp = $ergebnis2['timestamp'];
            $lastTimestamp = strtotime($lastTimestamp);
            $grenze = strtotime('+12 hours', $lastTimestamp);
            if (time()>$grenze){
                if(lade_xml_einstellung('warnung_lora_totmann_aktiv')=='on'){
                    echo "Lora meldet sich nicht!!<br>";
                    //Finde heraus ob mail schon mal gesendet wurde
                    if(mail_schon_gesendet(1,'lora_missing')){

                        //Mail nur einmal täglich senden
                        $TimestampLastMail = timestamp_letzte_mail_gesendet(1, 'lora_missing');
                        $SearchTime = strtotime('+1 day', strtotime($TimestampLastMail));
                        if(time()>$SearchTime){
                            mail_senden('lora_missing', 'marc@haefeker.de', '', 'lora_missing');
                            echo "Mail gesendet";
                        } else {
                            echo "Mail heute schon gesendet";
                        }

                    } else {
                        mail_senden('lora_missing', 'marc@haefeker.de', '', 'lora_missing');
                    }
                }
            }
        }

    } else {
        echo '<h1>Lora Funktion deaktiviert</h1>';
    }

}

function pegelstand_fetcher(){

    $link = connect_db();
    echo "<h2>Wasserstandscraper</h2>";

    #lade URL
    #old version : $SearchURL = 'http://hochwasser-zentralen.de/js/hvz_peg_stmn.js';

	if(lade_xml_einstellung('search_URL_pegelstaende')!=""){
		$SearchURL = lade_xml_einstellung('search_URL_pegelstaende');
	} else {
		$SearchURL = 'https://www.hvz.baden-wuerttemberg.de/js/hvz_peg_stmn.js';
	}
	
	
    #fetch URL content

    $data = file_get_contents($SearchURL);
    $str = substr($data, 110);
    $array = explode('[', $str);

    foreach($array as $entry){
        $data = explode(',', $entry);

        if($data[1]=="'Horb'"){

            $Wasserstand = str_replace("'", '', $data[4]);
            $Fliessmenge = str_replace("'", '', $data[7]);
            $TimestampPegelsystem = str_replace("'", '', $data[6]);
            $TimestampPegelsystem = substr($TimestampPegelsystem, 0, 16);
            $TimestampPegelsystem = date('Y-m-d G:i:s', strtotime($TimestampPegelsystem));

            $Anfrage = "INSERT INTO pegelstaende (pegel,timestamp_pegel,q,f) VALUES ('horb','".$TimestampPegelsystem."','".$Fliessmenge."','".$Wasserstand."')";
            if(mysqli_query($link, $Anfrage)){
                echo "Eintrag erfolgreich!<br>";
            } else {
                echo "Datenbankfehler!<br>";
            }

            echo "Wasserstand: " .$Wasserstand. "cm<br>";
            echo "Fliessmenge: " .$Fliessmenge. "m^3/s<br>";
            echo "Letzte Messung: " .$TimestampPegelsystem. "<br>";
        }
    }

}

function automatische_sperrung_wasserstand(){

    if(lade_xml_einstellung('wasserstand_sperrungsautomatik_on_off')=='on'){
        echo "<h3>Automatische Sperrautomatik</h3>";
        $link = connect_db();
        $LetzteMessung = lade_letzten_wasserstand($link);
        if(strtotime($LetzteMessung['timestamp_pegel'])<strtotime('-30 minutes')){
            echo "<b>Fehler! Letzte Messung zu lange her!</b>";
        } else {
            if($LetzteMessung['f']==0){
                echo "<b>Fehler! Letzte Messung fehlerhaft!</b>";
            } else {
                if($LetzteMessung['f']>=lade_xml_einstellung('wasserstand_generelle_sperrung_auto')){
                    echo "<b>Sperrung sollte durchgeführt werden!</b>";

                    $Anfrage = "SELECT id, ende FROM sperrungen WHERE beginn < '".timestamp()."' AND ende > '".timestamp()."' AND storno_user = 0";
                    $Abfrage = mysqli_query($link, $Anfrage);
                    $Anzahl = mysqli_num_rows($Abfrage);
                    if($Anzahl==0){
                        $BeginnSperrung = date('Y-m-d G:i:s');
                        $Command = '+ '.lade_xml_einstellung('wasserstand_sperrungsautomatik_stunden').' hours';
                        $EndeSperrung = date('Y-m-d G:i:s', strtotime($Command));
                        #var_dump(sperrung_anlegen($BeginnSperrung, $EndeSperrung, 'Hochwasser', 'Automatische Hochwassersperre '.date('d.m.Y'), lade_xml_einstellung('wasserstand_sperrungsautomatik_text'), 1, true));
                    } else {
                        $Ergebnis = mysqli_fetch_assoc($Abfrage);
                        echo "<b>Sperrung bereits eingetragen - endet ".$Ergebnis['ende']."!</b>";
                    }
                } else {
                    echo "<b>Keine Sperrung notwendig!</b>";
                }
            }
        }
    } else {
        echo "<h3>Automatische Sperrautomatik deaktiviert</h3>";
    }
}
?>