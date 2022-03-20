<?php

//Gets called every 5 minutes
include_once "./ressources/ressourcen.php";
echo "Mailbrain beginnt...";

mail_erinnerung_uebergabe_ausmachen_intervall_eins();
mail_erinnerung_uebergabe_ausmachen_intervall_zwei();
mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt();
mail_erinnerung_schluesselrueckgabe_intervall();
mail_erinnerung_schluesseluebergabe_eintragen_wart();
mail_statusuebersicht_daily_generieren();
mail_statusuebersicht_weekly_generieren();
mail_erinnerung_nachzahlung_res_intervall();
mail_wasserpegel_res();


function mail_wasserpegel_res(){

    echo "<h1>Wasserstandsmail</h1>";
    $link = connect_db();
    $SettingTimeslot = lade_xml_einstellung('wasserstand_mail_time');
    $SettingMailMode = lade_xml_einstellung('wasserstand_mail_mode');
    $SettingPegelstandAnfaenger = lade_xml_einstellung('wasserstand_vorwarnung_beginner');
    $SettingPegelstandErfahrene = lade_xml_einstellung('wasserstand_vorwarnung_erfahrene');
    $SettingPegelstandSperrung = lade_xml_einstellung('wasserstand_generelle_sperrung');

    $LastWasserstand = lade_letzten_wasserstand($link);
    if($SettingTimeslot==0){
        $Timeslot = date('Y-m-d G:i:s');
    } else {
        $Command = '+ '.$SettingTimeslot.' hours';
        $Timeslot = date('Y-m-d G:i:s', strtotime($Command));
    }

    $Bausteine = array();
    $NameVorlage = 'wasserstandsmail-res';
    $Bausteine['[wasserstand]'] = $LastWasserstand['f'];
    $Bausteine['[wasserstand_trend]'] = lade_wasserstand_trend_text($link);
    $Bausteine['[wasserstand_anfaenger]'] = $SettingPegelstandAnfaenger;
    $Bausteine['[wasserstand_erfahrene]'] = $SettingPegelstandErfahrene;
    $Bausteine['[wasserstand_sperrung]'] = $SettingPegelstandSperrung;

    if($LastWasserstand['f']<=$SettingPegelstandAnfaenger){
        $Bausteine['[einschaetzung]'] = "Wasserstand für Anfänger geeignet!<br>";
    }
    if($LastWasserstand['f']>$SettingPegelstandAnfaenger){
        if($LastWasserstand['f']<$SettingPegelstandSperrung){
            $Bausteine['[einschaetzung]'] = "Wasserstand nur noch für erfahrene Stocherer geeignet!<br>";
        }
    }
    if($LastWasserstand['f']>=$SettingPegelstandSperrung){
        $Bausteine['[einschaetzung]'] = "Gefährlicher Wasserstand! Sperrung des Kahns wird bald erfolgen!<br>";
    }

    if($LastWasserstand['f']!=0){
        $Anfrage = "SELECT * FROM reservierungen WHERE beginn <= '".$Timeslot."' AND ende > '".$Timeslot."' AND storno_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);
        if($Anzahl==0){
            echo "Keine Res. betroffen!<br>";
        }
        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $UserMeta = lade_user_meta($Ergebnis['user']);
            #$UserMail = 'marc@haefeker.de';
            $UserMail = $UserMeta['mail'];
            $TypMail = 'wasserstandsmail_res_'.$Ergebnis['id'];
            $Bausteine['[vorname_user]'] = $UserMeta['vorname'];

            if($SettingMailMode=='on'){
                //IMMER SCHICKEN
                if(!mail_schon_gesendet($Ergebnis['user'], $TypMail)){
                    //Mail senden
                    if (mail_senden($NameVorlage, $UserMail, $Bausteine, $TypMail)){
                        echo "Reservierung ".$Ergebnis['id']." - Mail gesendet!<br>";
                    } else {
                        echo "Reservierung ".$Ergebnis['id']." - Fehler beim senden der Mail!<br>";
                    }
                } else {
                    echo "Reservierung ".$Ergebnis['id']." - Mail schon gesendet!<br>";
                }

            } else {
                //NUR BEI HÖHEREN PEGELSTÄNDEN SCHICKEN
                if($LastWasserstand['f']>=$SettingPegelstandAnfaenger){
                    //Mail senden
                    if(!mail_schon_gesendet($Ergebnis['user'], $TypMail)){
                        //Mail senden
                        if (mail_senden($NameVorlage, $UserMail, $Bausteine, $TypMail)){
                            echo "Reservierung ".$Ergebnis['id']." - Mail gesendet!<br>";
                        } else {
                            echo "Reservierung ".$Ergebnis['id']." - Fehler beim senden der Mail!<br>";
                        }
                    } else {
                        echo "Reservierung ".$Ergebnis['id']." - Mail schon gesendet!<br>";
                    }
                } else {
                    echo "Reservierung #".$Ergebnis['id']." steht an, aber Wasserstand ist ok.<br>";
                }
            }
        }
    } else {
        echo "Letzte Pegelstandmessung fehlerhaft!<br><br>";
    }
}

    function mail_erinnerung_uebergabe_ausmachen_intervall_eins(){
        //Erinnerung eine Übergabe ausmachen - Intervall 1
        echo "<p>Erinnerung &Uuml;bergabe ausmachen - Intervall eins:<br>";
        $TageVorherIntervallEinsUebergabeAusmachen = lade_xml_einstellung('erinnerung-uebergabe-ausmachen-1');
        $BefehlIntervallEinsUebergabeAusmachen = "+ ".$TageVorherIntervallEinsUebergabeAusmachen." days";
        $ZeitgrenzeIntervallEinsUebergabeAusmachen = date("Y-m-d G:i:s", strtotime($BefehlIntervallEinsUebergabeAusmachen));

        $link = connect_db();
        $Anfrage = "SELECT * FROM reservierungen WHERE ende > '".$ZeitgrenzeIntervallEinsUebergabeAusmachen."' AND beginn < '".$ZeitgrenzeIntervallEinsUebergabeAusmachen."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl == 0){
            echo "Es stehen keine Reservierungen an!<br>";
        } else if ($Anzahl > 0){
            for ($a = 1; $a <= $Anzahl; $a++){
                $Reservierung = mysqli_fetch_assoc($Abfrage);
                $AnfrageLadeUebergaben = "SELECT * FROM uebergaben WHERE res = '".$Reservierung['id']."' AND storno_user = '0'";
                $AbfrageLadeUebergaben = mysqli_query($link, $AnfrageLadeUebergaben);
                $AnzahlLadeUebergaben = mysqli_num_rows($AbfrageLadeUebergaben);

                if ($AnzahlLadeUebergaben == 0){

                    //Hat er ne Übernahme gebucht?
                    $AnfrageUebernahmeGebucht = "SELECT * FROM uebernahmen WHERE reservierung = '".$Reservierung['id']."' AND storno_user = '0'";
                    $AbfrageUebernahmeGebucht = mysqli_query($link, $AnfrageUebernahmeGebucht);
                    $AnzahlUebernahmeGebucht = mysqli_num_rows($AbfrageUebernahmeGebucht);

                    if ($AnzahlUebernahmeGebucht == 0){

                        //Braucht er gemäß seiner Schlüsselrollen überhaupt eine Übergabe?
                        $Schluesselrollen = lade_user_meta($Reservierung['user']);
                        $Nutzerrollen = lade_alle_nutzgruppen();
                        $HatEigSchluessel = 0;

                        foreach($Nutzerrollen as $Nutzerrolle){
                            if($Schluesselrollen[$Nutzerrolle['name']]=='true'){
                                if($Nutzerrolle['darf_last_minute_res'] == 'true'){
                                    $HatEigSchluessel++;
                                }
                            }
                        }

                        if (($HatEigSchluessel>0)){
                            echo "Reservierung ".$Reservierung['id']." - User hat eigenen Schl&uuml;ssel<br>";
                        } else {
                            $NameVorlage = "erinnerung-uebergabe-ausmachen-intervall-eins";
                            $TypMail = "".$NameVorlage."-".$Reservierung['id']."";

                            //Mail schon gesendet worden?
                            if (mail_schon_gesendet($Reservierung['user'], $TypMail)){
                                echo "Reservierung ".$Reservierung['id']." - Mail schon gesendet!<br>";
                            } else {

                                //Angaben Mail generieren
                                if ($TageVorherIntervallEinsUebergabeAusmachen == "1"){
                                    $AngabeTage = "einem Tag";
                                } else {
                                    $AngabeTage = "".$TageVorherIntervallEinsUebergabeAusmachen." Tagen";
                                }

                                //Mail senden
                                $Bausteine = array();
                                $Bausteine['[vorname_user]'] = $Schluesselrollen['vorname'];
                                $Bausteine['[angabe_tage]'] = $AngabeTage;

                                if (mail_senden($NameVorlage, $Schluesselrollen['mail'], $Bausteine, $TypMail)){
                                    echo "Reservierung ".$Reservierung['id']." - Mail gesendet!<br>";
                                } else {
                                    echo "Reservierung ".$Reservierung['id']." - Fehler beim senden der Mail!<br>";
                                }
                            }
                        }

                    } else if ($AnzahlUebernahmeGebucht > 0){
                        echo "Reservierung ".$Reservierung['id']." - &Uuml;bernahme ausgemacht!<br>";
                    }

                } else if ($AnzahlLadeUebergaben > 0){
                    echo "Reservierung ".$Reservierung['id']." - &Uuml;bergabe ausgemacht!<br>";
                }
            }
        }

        echo "</p>";
    }

    function mail_erinnerung_uebergabe_ausmachen_intervall_zwei(){
        //Erinnerung eine Übergabe ausmachen - Intervall 2
        echo "<p>Erinnerung &Uuml;bergabe ausmachen - Intervall zwei:<br>";
        $TageVorherIntervallZweiUebergabeAusmachen = lade_xml_einstellung('erinnerung-uebergabe-ausmachen-2');
        $BefehlIntervallZweiUebergabeAusmachen = "+ ".$TageVorherIntervallZweiUebergabeAusmachen." days";
        $ZeitgrenzeIntervallZweiUebergabeAusmachen = date("Y-m-d G:i:s", strtotime($BefehlIntervallZweiUebergabeAusmachen));

        $link = connect_db();
        $Anfrage = "SELECT * FROM reservierungen WHERE ende > '".$ZeitgrenzeIntervallZweiUebergabeAusmachen."' AND beginn < '".$ZeitgrenzeIntervallZweiUebergabeAusmachen."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl == 0){
            echo "Es stehen keine Reservierungen an!<br>";
        } else if ($Anzahl > 0){
            for ($a = 1; $a <= $Anzahl; $a++){
                $Reservierung = mysqli_fetch_assoc($Abfrage);
                $AnfrageLadeUebergaben = "SELECT * FROM uebergaben WHERE res = '".$Reservierung['id']."' AND storno_user = '0'";
                $AbfrageLadeUebergaben = mysqli_query($link, $AnfrageLadeUebergaben);
                $AnzahlLadeUebergaben = mysqli_num_rows($AbfrageLadeUebergaben);

                if ($AnzahlLadeUebergaben == 0){

                    //Bei leuten mit eigenem Schlüssel nix machen
                    $Schluesselrollen = lade_user_meta($Reservierung['user']);
                    $Nutzerrollen = lade_alle_nutzgruppen();
                    $HatEigSchluessel = 0;

                    foreach($Nutzerrollen as $Nutzerrolle){
                        if($Schluesselrollen[$Nutzerrolle['name']]=='true'){
                            if($Nutzerrolle['darf_last_minute_res'] == 'true'){
                                $HatEigSchluessel++;
                            }
                        }
                    }

                    if ($HatEigSchluessel>0){

                        echo "Reservierung ".$Reservierung['id']." - User hat eigenen Schl&uuml;ssel<br>";

                    } else {

                        //Hat er ne Übernahme gebucht?
                        $AnfrageUebernahmeGebucht = "SELECT * FROM uebernahmen WHERE reservierung = '".$Reservierung['id']."' AND storno_user = '0'";
                        $AbfrageUebernahmeGebucht = mysqli_query($link, $AnfrageUebernahmeGebucht);
                        $AnzahlUebernahmeGebucht = mysqli_num_rows($AbfrageUebernahmeGebucht);

                        if ($AnzahlUebernahmeGebucht == 0){

                            $NameVorlage = "erinnerung-uebergabe-ausmachen-intervall-zwei";
                            $TypMail = "".$NameVorlage."-".$Reservierung['id']."";

                            //Mail schon gesendet worden?
                            if (mail_schon_gesendet($Reservierung['user'], $TypMail)){
                                echo "Reservierung ".$Reservierung['id']." - Mail schon gesendet!<br>";
                            } else {

                                //Angaben Mail generieren
                                if (intval($TageVorherIntervallZweiUebergabeAusmachen) == 1){
                                    $AngabeTage = "Morgen";
                                } else if (intval($TageVorherIntervallZweiUebergabeAusmachen) == 2) {
                                    $AngabeTage = "&Uuml;bermorgen";
                                } else if (intval($TageVorherIntervallZweiUebergabeAusmachen) > 2){
                                    $AngabeTage = "In ".$TageVorherIntervallZweiUebergabeAusmachen." Tagen";
                                }

                                //Mail senden
                                $Bausteine = array();
                                $Bausteine['[vorname_user]'] = $Schluesselrollen['vorname'];
                                $Bausteine['[angabe_tage]'] = $AngabeTage;

                                if (mail_senden($NameVorlage, $Schluesselrollen['mail'], $Bausteine, $TypMail)){
                                    echo "Reservierung ".$Reservierung['id']." - Mail gesendet!<br>";
                                } else {
                                    echo "Reservierung ".$Reservierung['id']." - Fehler beim senden der Mail!<br>";
                                }
                            }

                        } else if ($AnzahlUebernahmeGebucht > 0){
                            echo "Reservierung ".$Reservierung['id']." - &Uuml;bernahme ausgemacht!<br>";
                        }

                    }

                } else if ($AnzahlLadeUebergaben > 0){
                    echo "Reservierung ".$Reservierung['id']." - &Uuml;bergabe ausgemacht!<br>";
                }
            }
        }
        echo "</p>";
    }

    function mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt(){

        echo "<p>Erinnerung R&uuml;ckgabe nach Fahrt<br>";
        $link = connect_db();
        $GrenzeVorZweiTagen = date("Y-m-d G:i:s", strtotime("-1 days")); //Falls cronjob ausfällt und wir sonst zig mails auf einmal senden würden

        //Suche jede Reservierung die vorbei ist
        $Anfrage = "SELECT * FROM reservierungen WHERE storno_user = '0' AND beginn > '".$GrenzeVorZweiTagen."' AND ende < '".timestamp()."'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        //Iteriere über jede Res
        for ($a = 1; $a <= $Anzahl; $a++){

            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $Schluesselrollen = lade_user_meta($Reservierung['user']);
            $Nutzerrollen = lade_alle_nutzgruppen();
            $HatEigSchluessel = 0;

            foreach($Nutzerrollen as $Nutzerrolle){
                if($Schluesselrollen[$Nutzerrolle['name']]=='true'){
                    if($Nutzerrolle['darf_last_minute_res'] == 'true'){
                        $HatEigSchluessel++;
                    }
                }
            }

            //Schlüsselrollen beachten
            if ($HatEigSchluessel>0){
                echo "Reservierung ".$Reservierung['id']." - User hat eigenen Schl&uuml;ssel!<br>";
            } else {

                //Suche nach der Schlüsselausgabe
                $AnfrageLadeSchluesseluebergabe = "SELECT * FROM schluesselausgabe WHERE reservierung = '".$Reservierung['id']."' AND ausgabe <> NULL AND rueckgabe = NULL AND storno_user = '0'";
                $AbfrageLadeSchluesseluebergabe = mysqli_query($link, $AnfrageLadeSchluesseluebergabe);
                $AnzahlLadeSchluesseluebergabe = mysqli_num_rows($AbfrageLadeSchluesseluebergabe);

                //Wenn es eine gibt:
                if ($AnzahlLadeSchluesseluebergabe > 0){

                    //Feststellen ob danach direkt eine Übernahme stattfindet
                    $AnfrageLadePotentielleUbergabe = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$Reservierung['id']."' AND storno_user = '0'";
                    $AbfrageLadePotentielleUbergabe = mysqli_query($link, $AnfrageLadePotentielleUbergabe);
                    $AnzahlLadePotentielleUbergabe = mysqli_num_rows($AbfrageLadePotentielleUbergabe);

                    if ($AnzahlLadePotentielleUbergabe > 0){
                        $NameVorlage = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt_mit_uebernahme";
                        echo "UEBERNAHME DANACH ";
                    } else if ($AnzahlLadePotentielleUbergabe == 0){
                        $NameVorlage = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt";
                        echo "KEINE UEBERNAHME DANACH ";
                    }

                    $Typ = "".$NameVorlage."-".$Reservierung['id']."";
                    if (mail_schon_gesendet($Reservierung['user'], $Typ)){
                        //Nix machen
                        echo "Reservierung ".$Reservierung['id']." schon informiert!<br>";
                    } else {
                        //Mail senden
                        $Bausteine = array();
                        $Bausteine['[vorname_user]'] = $Schluesselrollen['vorname'];

                            if (mail_senden($NameVorlage, $Schluesselrollen['mail'], $Bausteine, $Typ)){
                                echo "Reservierung ".$Reservierung['id']." erfolgreich gesendet!<br>";
                            } else {
                                echo "Reservierung ".$Reservierung['id']." - Fehler beim Senden der Mail!<br>";
                            }
                    }

                } else {
                    echo "Reservierung ".$Reservierung['id']." - Es ist keine Schl&uuml;ssel&uuml;bergabe erfolgt!<br>";
                }
            }
        }

        if ($Anzahl == 0){
            echo "Keine Reservierungen betroffen!";
        }

        echo "</p>";
    }

    function mail_erinnerung_schluesselrueckgabe_intervall(){

        echo "<p>Erinnerung R&uuml;ckgabe Zyklus<br>";
        $link = connect_db();

        $AbAnzahlTage = lade_xml_einstellung('erinnerung-schluessel-zurueckgeben-intervall-beginn');
        $IntervallTage = lade_xml_einstellung('erinnerung-schluessel-zurueckgeben-intervall-groesse');

        $AnfrageLadeAlleOffenenAusgaben = "SELECT * FROM schluesselausgabe WHERE ausgabe <> NULL AND rueckgabe = NULL AND storno_user = '0'";
        $AbfrageLadeAlleOffenenAusgaben = mysqli_query($link, $AnfrageLadeAlleOffenenAusgaben);
        $AnzahlLadeAlleOffenenAusgaben = mysqli_num_rows($AbfrageLadeAlleOffenenAusgaben);

        if ($AnzahlLadeAlleOffenenAusgaben == 0){
            echo "Keine ausstehenden R&uuml;ckgaben!";
        } else if ($AnzahlLadeAlleOffenenAusgaben > 0){
            echo "".$AnzahlLadeAlleOffenenAusgaben." ausstehende R&uuml;ckgaben<br>";
            for ($a = 1; $a <= $AnzahlLadeAlleOffenenAusgaben; $a++){

                $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleOffenenAusgaben);
                $Reservierung = lade_reservierung($Ausgabe['reservierung']);
                $Typ = "mail_erinnerung_schluesselrueckgabe_intervall-".$Reservierung['id']."";

                //Sind wir über 2 Tage nach Res?
                $BefehlUeberZweiTage = "+ ".$AbAnzahlTage." days";
                if (time() > strtotime($BefehlUeberZweiTage, strtotime($Reservierung['ende']))){

                    $TimestampLetzteMail = timestamp_letzte_mail_gesendet($Reservierung['user'], $Typ);
                    $UserMeta = lade_user_meta($Reservierung['user']);
                    $DifferenzTage = tage_differenz_berechnen(timestamp(), $Reservierung['ende']);

                    $Bausteine = array();
                    $Bausteine['[vorname_user]'] = $UserMeta['vorname'];
                    $Bausteine['[tage_seit_ende_res]'] = $DifferenzTage;

                    if ($TimestampLetzteMail == FALSE){

                        //Es wurde noch nie eine Mail geschickt -> GO
                        if(mail_senden('mail_erinnerung_schluesselrueckgabe_intervall', $UserMeta['mail'], $Bausteine, $Typ)){

                            echo "Reservierung #".$Reservierung['id']." - Mail senden erfolgreich!<br>";
                        } else {
                            echo "Reservierung #".$Reservierung['id']." - Mail senden fehlgeschlagen!<br>";
                        }

                    } else {

                        //Es wurde bereits eine Mail geschickt -> Doublecheck ob wir wieder eine Senden dürfen
                        $DifferenzTage = tage_differenz_berechnen(timestamp(), $TimestampLetzteMail);
                        if ($DifferenzTage>=$IntervallTage){
                             if(mail_senden('mail_erinnerung_schluesselrueckgabe_intervall', $UserMeta['mail'], $Bausteine, $Typ)){
                                 echo "Reservierung #".$Reservierung['id']." - Mail senden erfolgreich!<br>";
                             } else {
                                 echo "Reservierung #".$Reservierung['id']." - Mail senden fehlgeschlagen!<br>";
                             }
                        } else {
                            echo "Reservierung #".$Reservierung['id']." - Intervall noch nicht wieder eingetreten!<br>";
                        }
                    }
                } else {
                    echo "Reservierung #".$Reservierung['id']." - Grenze Intervallbeginn noch nicht begonnen!<br>";
                }
            }
        }

        echo "</p>";
    }

    function mail_erinnerung_nachzahlung_res_intervall(){

    echo "<p>Erinnerung Nachzahlung Zyklus<br>";
    $link = connect_db();

    $AbAnzahlTage = lade_xml_einstellung('erinnerung-nachzahlung-intervall-beginn');
    $IntervallTage = lade_xml_einstellung('erinnerung-nachzahlung-intervall-groesse');

    $AnfrageLadeAlleOffenenAusgaben = "SELECT * FROM reservierungen WHERE beginn > '".date('Y')."-01-01 00:00:01' AND ende < '".date('Y-m-d G:i:s')."' AND storno_user = '0'";
    $AbfrageLadeAlleOffenenAusgaben = mysqli_query($link, $AnfrageLadeAlleOffenenAusgaben);
    $AnzahlLadeAlleOffenenAusgaben = mysqli_num_rows($AbfrageLadeAlleOffenenAusgaben);

    if ($AnzahlLadeAlleOffenenAusgaben == 0){
        echo "Keine Reservierungen dieses Jahr!";
    } else if ($AnzahlLadeAlleOffenenAusgaben > 0){
        echo "".$AnzahlLadeAlleOffenenAusgaben." Reservierungen dieses Jahr<br>";
        for ($a = 1; $a <= $AnzahlLadeAlleOffenenAusgaben; $a++){

            $Reservierung = mysqli_fetch_assoc($AbfrageLadeAlleOffenenAusgaben);

            //Check if Payment required
            $Forderung = lade_forderung_res($Reservierung['id']);
            $Zahlungen = lade_gezahlte_summe_forderung($Forderung['id']);
            if($Zahlungen<$Forderung['betrag']){
                $Typ = "mail_erinnerung_nachzahlung_intervall-".$Reservierung['id']."";
                //Sind wir über 2 Tage nach Res?
                $BefehlUeberZweiTage = "+ ".$AbAnzahlTage." days";
                if (time() > strtotime($BefehlUeberZweiTage, strtotime($Reservierung['ende']))){

                    $TimestampLetzteMail = timestamp_letzte_mail_gesendet($Reservierung['user'], $Typ);
                    $UserMeta = lade_user_meta($Reservierung['user']);

                    $Bausteine = array();
                    $Bausteine['[vorname_user]'] = $UserMeta['vorname'];
                    $Bausteine['[restbetrag]'] = $Forderung['betrag']-$Zahlungen;

                    if ($TimestampLetzteMail == FALSE){

                        $DifferenzTage = tage_differenz_berechnen(timestamp(), $Reservierung['ende']);
                        $Bausteine['[tage_seit_ende_res]'] = $DifferenzTage;

                        //Es wurde noch nie eine Mail geschickt -> GO
                        if(mail_senden('mail_erinnerung_nachzahlung_intervall', $UserMeta['mail'], $Bausteine, $Typ)){

                            echo "Reservierung #".$Reservierung['id']." - Mail senden erfolgreich!<br>";
                        } else {
                            echo "Reservierung #".$Reservierung['id']." - Mail senden fehlgeschlagen!<br>";
                        }

                    } else {

                        $DifferenzTage = tage_differenz_berechnen(timestamp(), $TimestampLetzteMail);
                        $Bausteine['[tage_seit_ende_res]'] = $DifferenzTage;

                        //Es wurde bereits eine Mail geschickt -> Doublecheck ob wir wieder eine Senden dürfen
                        if ($DifferenzTage>=$IntervallTage){
                            if(mail_senden('mail_erinnerung_nachzahlung_intervall', $UserMeta['mail'], $Bausteine, $Typ)){
                                echo "Reservierung #".$Reservierung['id']." - Mail senden erfolgreich!<br>";
                            } else {
                                echo "Reservierung #".$Reservierung['id']." - Mail senden fehlgeschlagen!<br>";
                            }
                        } else {
                            echo "Reservierung #".$Reservierung['id']." - Intervall noch nicht wieder eingetreten!<br>";
                        }
                    }
                } else {
                    echo "Reservierung #".$Reservierung['id']." - Grenze Intervallbeginn noch nicht begonnen!<br>";
                }
            }
        }
    }

    echo "</p>";
}

    function mail_erinnerung_schluesseluebergabe_eintragen_wart(){

        $link = connect_db();

        $Anfrage = "SELECT * FROM uebergaben WHERE storno_user = '0' AND beginn < '".timestamp()."' AND durchfuehrung = NULL";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        $Zeitgrenze = lade_xml_einstellung('stunden-bis-uebergabe-eingetragen-sein-soll');
        $Zeitbefehl = "+ ".$Zeitgrenze." hours";

        echo "<p>Erinnerung Wart Schl&uuml;ssel&uuml;bergabe nachzutragen<br>";
        for ($a = 1; $a <= $Anzahl; $a++){
            $Uebergabe = mysqli_fetch_assoc($Abfrage);
            echo "&Uuml;bergabe: ".$Uebergabe['id']." - ";

            //Sind wir schon im Zeitfenster?
            $Zeitfenster = strtotime($Zeitbefehl, strtotime($Uebergabe['beginn']));
            if($Zeitfenster < time()){
                echo "Zeitfenster ist eingetreten - ";

                //Einstellung Wart
                $Benutzersettings = lade_user_meta($Uebergabe['wart']);
                if($Benutzersettings['erinnerung-wart-schluesseluebergabe-eintragen'] == "true"){

                    //Mail schon gesendet?
                    $Typ = "erinnerung-wart-schluesseluebergabe-eintragen-".$Uebergabe['id']."";
                    if(mail_schon_gesendet($Uebergabe['wart'], $Typ)){
                        echo "Mail schon gesendet<br>";
                    } else {
                        //Mail senden
                        $WartMeta = lade_user_meta($Uebergabe['wart']);
                        $Reservierung = lade_reservierung($Uebergabe['res']);
                        $UserReservierungMeta = lade_user_meta($Reservierung['user']);

                        $Bausteine = array();
                        $Bausteine['[vorname_wart]'] = $WartMeta['vorname'];
                        $Bausteine['[uebergabe_id]'] = $Uebergabe['id'];
                        $Bausteine['[datum]'] = date("d.m.Y", strtotime($Uebergabe['beginn']));
                        $Bausteine['[zeitpunkt]'] = date("G:i", strtotime($Uebergabe['beginn']));
                        $Bausteine['[empfaenger]'] = "".$UserReservierungMeta['vorname']." ".$UserReservierungMeta['nachname']."";

                        if(mail_senden('erinnerung-wart-schluesseluebergabe-eintragen', $WartMeta['mail'], $Bausteine, $Typ)){
                            echo "Mail gesendet<br>";
                        } else {
                            echo "Fehler beim senden der Mail<br>";
                        }
                    }

                } else {
                    echo "Wart m&ouml;chte keine Mail erhalten<br>";
                }

            } else if ($Zeitfenster > time()){
                echo "Zeitfenster ist noch nicht eingetreten<br>";
            }
        }
        echo "</p>";
    }

    function mail_statusuebersicht_daily_generieren(){
        echo "Tägliche Statusmail:<br><br>";
        $SollUhrzeit = lade_xml_einstellung('soll_uhrzeit_daily_status_mail');
            #if((date('G:i')>date('G:i',strtotime($SollUhrzeit))) AND (date('G:i')<date('G:i',strtotime('+4 minutes', $SollUhrzeit)))){
            if((time()>=strtotime($SollUhrzeit)) AND (time()<strtotime('+4 minutes', strtotime($SollUhrzeit)))){
            #if((date('G:i')>$SollUhrzeit) AND (date('G:i')<date('G:i', strtotime('+4 minutes', $SollUhrzeit)))){

                $Bausteine = bausteine_mail_statusuebersicht_generieren(lade_xml_einstellung('future_daily_status_mail'));
                $link = connect_db();
                $Anfrage = "SELECT user FROM user_meta WHERE schluessel = 'ist_wart' AND wert = 'true'";
                $Abfrage = mysqli_query($link, $Anfrage);
                $Anzahl = mysqli_num_rows($Abfrage);
                for($a=1;$a<=$Anzahl;$a++){
                    $Ergebnis = mysqli_fetch_assoc($Abfrage);
                    $UserMeta = lade_user_meta($Ergebnis['user']);
                    if($UserMeta['mail-wart-daily-update']=='true'){

                        if($UserMeta['mail-wart-weekly-update']=='true'){
                            if(date('w')!=7){
                                $Bausteine['[vorname_user]']=$UserMeta['vorname'];

                                if(($UserMeta['mail_status_only_important']=='true') AND ($Bausteine['[anz_unversorgte_reservierungen]']==0)) {
                                    echo "User will keine unnötigen Mails erhalten!<br>";
                                } else {
                                    if(mail_senden('statusupdate-daily', $UserMeta['mail'], $Bausteine, 'statusupdate-daily')){
                                        echo "Senden an ".$UserMeta['vorname']." erfolgreich!<br>";
                                    } else {
                                        echo "Fehler beim Senden an ".$UserMeta['vorname']."<br>";
                                    }
                                }
                            }
                        } else {
                            $Bausteine['[vorname_user]']=$UserMeta['vorname'];
                            if(mail_senden('statusupdate-daily', $UserMeta['mail'], $Bausteine, 'statusupdate-daily')){
                                echo "Senden an ".$UserMeta['vorname']." erfolgreich!<br>";
                            } else {
                                echo "Fehler beim Senden an ".$UserMeta['vorname']."<br>";
                            }
                        }
                    }
                }
            } else {
                echo "Zeitfenster nicht erreicht!<br>";
            }
    }

    function mail_statusuebersicht_weekly_generieren(){
        echo "Wöchentliche Statusmail:<br><br>";
        $SollUhrzeit = lade_xml_einstellung('soll_uhrzeit_weekly_status_mail');
        if(date('w')==7){
            if((time()>=strtotime($SollUhrzeit)) AND (time()<strtotime('+4 minutes', strtotime($SollUhrzeit)))){
                $Bausteine = bausteine_mail_statusuebersicht_generieren(7);
                $link = connect_db();
                $Anfrage = "SELECT user FROM user_meta WHERE schluessel = 'ist_wart' AND wert = 'true'";
                $Abfrage = mysqli_query($link, $Anfrage);
                $Anzahl = mysqli_num_rows($Abfrage);
                for($a=1;$a<=$Anzahl;$a++){
                    $Ergebnis = mysqli_fetch_assoc($Abfrage);
                    $UserMeta = lade_user_meta($Ergebnis['user']);
                    if($UserMeta['mail-wart-weekly-update']=='true'){
                        $Bausteine['[vorname_user]']=$UserMeta['vorname'];

                        if(($UserMeta['mail_status_only_important']=='true') AND ($Bausteine['[anz_unversorgte_reservierungen]']==0)) {
                            echo "User will keine unnötigen Mails erhalten!<br>";
                        } else {
                            if(mail_senden('statusupdate-weekly', $UserMeta['mail'], $Bausteine, 'statusupdate-weekly')){
                                echo "Senden an ".$UserMeta['vorname']." erfolgreich!<br>";
                            } else {
                                echo "Fehler beim Senden an ".$UserMeta['vorname']."<br>";
                            }
                        }
                    }
                }
            } else {
                echo "Zeitfenster nicht erreicht!<br>";
            }
        } else {
            echo "Es ist der falsche Wochentag!<br>";
        }
    }

    function bausteine_mail_statusuebersicht_generieren($AnzTageZukunft){

        $BeginSearchTimestamp = date('Y-m-d', strtotime('+ 1 day')).' 00:00:01';
        $EndSearchTimestamp = date('Y-m-d', strtotime('+ '.$AnzTageZukunft.' days')).' 23:59:59';
        $link = connect_db();
        $BausteineMail=array();

        if($AnzTageZukunft==1){
            $BausteineMail['[tage_zukunft]']="den morgigen Tag";
        } else {
            $BausteineMail['[tage_zukunft]']="die kommenden ".$AnzTageZukunft." Tage";
        }

        ////////FIRST LOAD ALL FACTS, AFTERWARDS GENERATE CONTENT FOR MAIL //////////

        ////////LADE ALLE RESERVIERUNGEN/////////////
        $Anfrage = "SELECT * FROM reservierungen WHERE beginn > '".$BeginSearchTimestamp."' AND beginn < '".$EndSearchTimestamp."' AND storno_user = 0 ORDER BY beginn ASC";
        $Abfrage = mysqli_query($link, $Anfrage);
        $AnzahlResGesamt = mysqli_num_rows($Abfrage);
        $ResThatNeedSchluessel = array();
        $ResThatDontNeedSchluessel = array();
        $FoundUsersRes = array();
        for($a=1;$a<=$AnzahlResGesamt;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $ResUser = lade_user_meta($Ergebnis['user']);
            array_push($FoundUsersRes, $ResUser);
            if($ResUser['hat_eigenen_schluessel']=='true'){
                array_push($ResThatDontNeedSchluessel, $Ergebnis);
            } elseif ($ResUser['wg_hat_eigenen_schluessel']=='true'){
                array_push($ResThatDontNeedSchluessel, $Ergebnis);
            } else {
                //Okay - check nach Übergaben/Übernahmen
                if(res_hat_uebergabe($Ergebnis['id'])){
                    array_push($ResThatDontNeedSchluessel, $Ergebnis);
                } elseif (res_hat_uebernahme($Ergebnis['id'])){
                    array_push($ResThatDontNeedSchluessel, $Ergebnis);
                } else {
                    //Damn - Res braucht noch nen Schlüssel
                    array_push($ResThatNeedSchluessel, $Ergebnis);
                }
            }
        }

        ///GENERATE MAIL CONTENT
        $HTMLreservierungen = "<h4>".$AnzahlResGesamt." Reservierungen gesamt</h4>";
        if(sizeof($ResThatNeedSchluessel)>0){
            $HTMLreservierungen .= "<b>Davon benötigen folgende Reservierungen noch einen Schlüssel!</b><br>";
            foreach ($ResThatNeedSchluessel as $ResNeeds){
                $ResUsrCounter = 0;
                foreach ($FoundUsersRes as $UserRes){
                    if($ResUsrCounter==0) {
                        if ($UserRes['id'] == $ResNeeds['user']) {
                            $UserAngabe = $UserRes['vorname'] . " " . $UserRes['nachname'];
                            $Zeitangabe = date('d.m. G:i', strtotime($ResNeeds['beginn'])) . " bis " . date('G:i', strtotime($ResNeeds['ende'])) . " Uhr";
                            $HTMLreservierungen .= "<b>Res.#: " . $ResNeeds['id'] . " - " . $UserAngabe . "</b> - " . $Zeitangabe . "<br>";
                            $ResUsrCounter++;
                        }
                    }
                }
            }
        }
        if(sizeof($ResThatNeedSchluessel)>0){
            $HTMLreservierungen .= "<br>";
        }
        if(sizeof($ResThatDontNeedSchluessel)>0){
            $HTMLreservierungen .= "<b>Versorgte Reservierungen:</b><br>";
            foreach ($ResThatDontNeedSchluessel as $ResNeeds){
                $ResUsrCounter = 0;
                foreach ($FoundUsersRes as $UserRes){
                    if($ResUsrCounter==0){
                        if($UserRes['id']==$ResNeeds['user']){
                            $UserAngabe = $UserRes['vorname']." ".$UserRes['nachname'];
                            $Zeitangabe = date('d.m. G:i', strtotime($ResNeeds['beginn']))." bis ".date('G:i', strtotime($ResNeeds['ende']))." Uhr";
                            $HTMLreservierungen .= "<b>Res.#: ".$ResNeeds['id']." - ".$UserAngabe."</b> - ".$Zeitangabe."<br>";
                            $ResUsrCounter++;
                        }
                    }
                }
            }
        }
        $BausteineMail['[content_reservierungen]']=$HTMLreservierungen;
        $BausteineMail['[anz_unversorgte_reservierungen]']=sizeof($ResThatNeedSchluessel);

        ////////LADE ALLE TERMINANGEBOTE/////////////
        $AnfrageTerminangebote = "SELECT * FROM terminangebote WHERE von > '".$BeginSearchTimestamp."' AND bis < '".$EndSearchTimestamp."' AND storno_user = 0 ORDER BY wart,von ASC";
        $AbfrageTerminangebote = mysqli_query($link, $AnfrageTerminangebote);
        $AnzahlTerminangeboteGesamt = mysqli_num_rows($AbfrageTerminangebote);
        $VerfuegbareSchluesselCounter = 0;
        $Terminangebote = array();
        $AktiveWarte = array();
        for($b=1;$b<=$AnzahlTerminangeboteGesamt;$b++){
            $ErgebnisTerminangebote = mysqli_fetch_assoc($AbfrageTerminangebote);
            array_push($Terminangebote, $ErgebnisTerminangebote);
            $CountWartHits = 0;
            foreach ($AktiveWarte as $Wart){
                if($Wart==$ErgebnisTerminangebote['wart']){
                    $CountWartHits++;
                }
            }
            if($CountWartHits==0){
                array_push($AktiveWarte, $ErgebnisTerminangebote['wart']);
            }
        }
        foreach ($AktiveWarte as $Wart){
            $VerfuegbareSchluesselCounter = $VerfuegbareSchluesselCounter + wart_verfuegbare_schluessel($Wart);
        }
        $DifferenzFreieSchluesselZuReservierungenOhneSchluessel = $VerfuegbareSchluesselCounter-sizeof($ResThatNeedSchluessel);

        ///GENERATE MAIL CONTENT
        $HTMLangebote = '';
        if(sizeof($Terminangebote)==0){
            if($AnzTageZukunft==1){
                $HTMLangebote .= '<p>Morgen <b>keine</b> Terminangebote!</p>';
            } else {
                $HTMLangebote .= '<p><b>Keine</b> Terminangebote in den nächsten '.$AnzTageZukunft.' Tagen!</p>';
            }
        } else {
            foreach ($Terminangebote as $Terminangebot){
                foreach ($AktiveWarte as $Wart){
                    if($Terminangebot['wart']==$Wart){
                        $WartMeta = lade_user_meta($Wart);
                        $WartInfos = $WartMeta['vorname']." ".$WartMeta['nachname'];
                        $Zeitangabe = date('d.m. G:i', strtotime($Terminangebot['von']))." bis ".date('G:i', strtotime($Terminangebot['bis']))." Uhr";
                        $HTMLangebote .= '- '.$WartInfos.' - '.$Zeitangabe.' - Wart*in hat noch '.wart_verfuegbare_schluessel($Wart).' Schlüssel<br>';
                    }
                }
            }
        }
        $BausteineMail['[content_uebergabeangebote]']=$HTMLangebote;

        ////////LADE ALLE SCHLUESSEL IM KASTEN UND ERWARTETE RÜCKGABEN////////
        $AnfrageSchluesselImKasten = "SELECT * FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = 0";
        $AbfrageSchluesselImKasten = mysqli_query($link, $AnfrageSchluesselImKasten);
        $AnzahlSchluesselImKasten = mysqli_num_rows($AbfrageSchluesselImKasten);

        $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> NULL AND rueckgabe = NULL ORDER BY schluessel ASC";
        $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
        $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);
        $AnzahlAnstehendeSchluesselrueckgaben = 0;

        if ($AnzahlLadeAlleSchluesselausgaben > 0){

            for($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

                $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

                //Reservierung vorbei oder storniert?
                $Reservierung = lade_reservierung($Ausgabe['reservierung']);

                if ((strtotime($Reservierung['ende']) < time()) OR ($Reservierung['storno_user'] != "0")){
                    //darf er dan Schlüssel weiter behalten?
                    $AnfrageWeitereReservierungenMitDiesemSchluessel = "SELECT id, wart, user, reservierung FROM schluesselausgabe WHERE user = '".$Ausgabe['user']."' AND schluessel = '".$Ausgabe['schluessel']."' AND storno_user = '0' AND rueckgabe = NULL AND id <> '".$Ausgabe['id']."'";
                    $AbfrageWeitereReservierungenMitDiesemSchluessel = mysqli_query($link, $AnfrageWeitereReservierungenMitDiesemSchluessel);
                    $AnzahlWeitereReservierungenMitDiesemSchluessel = mysqli_num_rows($AbfrageWeitereReservierungenMitDiesemSchluessel);

                    if ($AnzahlWeitereReservierungenMitDiesemSchluessel == 0){
                        $AnzahlAnstehendeSchluesselrueckgaben++;
                    }
                }
            }
        }

        ///GENERATE MAIL CONTENT
        $HTMLschluessel = table_row_builder(table_header_builder('Benötigte Schlüssel').table_data_builder(sizeof($ResThatNeedSchluessel)));
        $HTMLschluessel .= table_row_builder(table_header_builder('Verfügbar bei Wart*innen').table_data_builder($VerfuegbareSchluesselCounter));
        $HTMLschluessel .= table_row_builder(table_header_builder('Verfügbar im Kasten').table_data_builder($AnzahlSchluesselImKasten));
        $HTMLschluessel .= table_row_builder(table_header_builder('Rückgabe erwartet').table_data_builder($AnzahlAnstehendeSchluesselrueckgaben));
        $HTMLschluessel = table_builder($HTMLschluessel);

        if($DifferenzFreieSchluesselZuReservierungenOhneSchluessel<0){
            $HTMLschluessel .= "<p>Bitte überprüfe, ob du dir evtl. mehr Schlüssel besorgen oder (weitere) Terminangebote anlegen kannst:)</p>";
        }
        $BausteineMail['[content_schluessel]']=$HTMLschluessel;

        ////LADE ANSTEHENDE PAUSEN UND SPERREN/////
        $AnfrageLadeSperren = "SELECT * FROM sperrungen WHERE (beginn < '".$EndSearchTimestamp."' AND ende > '".$EndSearchTimestamp."') OR (beginn > '".$BeginSearchTimestamp."' AND ende < '".$EndSearchTimestamp."') OR (ende > '".$BeginSearchTimestamp."' AND beginn < '".$BeginSearchTimestamp."') AND storno_user = 0";
        $AnfrageLadePausen = "SELECT * FROM pausen WHERE (beginn < '".$EndSearchTimestamp."' AND ende > '".$EndSearchTimestamp."') OR (beginn > '".$BeginSearchTimestamp."' AND ende < '".$EndSearchTimestamp."') OR (ende > '".$BeginSearchTimestamp."' AND beginn < '".$BeginSearchTimestamp."') AND storno_user = 0";
        $AbfrageLadeSperren = mysqli_query($link, $AnfrageLadeSperren);
        $AbfrageLadePausen = mysqli_query($link, $AnfrageLadePausen);
        $AnazhlLadeSperren = mysqli_num_rows($AbfrageLadeSperren);
        $AnazhlLadePausen = mysqli_num_rows($AbfrageLadePausen);
        $Sperren = array();
        $Pausen = array();
        for($e=1;$e<=$AnazhlLadeSperren;$e++){
            $ErgebnisLadeSperren = mysqli_fetch_assoc($AbfrageLadeSperren);
            array_push($Sperren, $ErgebnisLadeSperren);
        }
        for($f=1;$f<=$AnazhlLadePausen;$f++){
            $ErgebnisLadePausen = mysqli_fetch_assoc($AbfrageLadePausen);
            array_push($Pausen, $ErgebnisLadePausen);
        }

        ///GENERATE MAIL CONTENT
        return $BausteineMail;
    }
?>