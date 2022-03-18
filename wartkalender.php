<?php

include_once "./ressources/ressourcen.php";
$link = connect_db();

//Funktionsbeschreibung:
	//Der Wart bekommt einen Link ausgeteilt, mit dem er auf das Skript zugreifen kann
	//Per Get bekommen wir den Usernamen mitgeteilt und wissen somit um welchen Wart es sich handelt
	//Wir laden alle Übergaben (2) dieses Jahres und machen für jede Übergabe einen Eintrag in die Kalenderdatei
	//Wir generieren eine Kalenderdatei, welche zum Schluss ausgespuckt wird und vom Client gelesen werden kann

//FUNKTION
	//Wart aus GET laden
	$IDWart = $_GET['wart'];
    $Benutzersettings = lade_user_meta($IDWart);

	if($Benutzersettings['kalenderabo'] == "true"){
        //VORNAME DES WARTES LADEN FÜR KALENDER
        $WartMeta = lade_user_meta($IDWart);

        //ÜBERGABEN LADEN
        $AnfrageUebergabenLaden = "SELECT * FROM uebergaben WHERE wart = '$IDWart' AND storno_user = '0'";
        $AbfrageUebergabenLaden = mysqli_query($link, $AnfrageUebergabenLaden);
        $AnzahlUebergabenLaden = mysqli_num_rows($AbfrageUebergabenLaden);

        //ÜBERGABEANGEBOTE LADEN
        $AnfrageAngeboteLaden = "SELECT * FROM terminangebote WHERE wart = '$IDWart' AND storno_user = '0'";
        $AbfrageAngeboteLaden = mysqli_query($link, $AnfrageAngeboteLaden) OR die("Fehler 3");
        $AnzahlAngeboteLaden = mysqli_num_rows($AbfrageAngeboteLaden);

        //KALENDER SCHREIBEN

        define('DATE_ICAL', 'Ymd\THis');
        $output = "BEGIN:VCALENDAR\r\nMETHOD:PUBLISH\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n";
        $BefehlUebergabedauer = "+ ".lade_xml_einstellung('dauer-uebergabe-minuten')." minutes";

        //Für jedes Übergabeangebot einen Eintrag basteln!
        for ($b=1;$b<=$AnzahlAngeboteLaden;$b++) {

            $ErgebnisAngeboteLaden = mysqli_fetch_assoc($AbfrageAngeboteLaden);

            $output .= "BEGIN:VEVENT\r\nSUMMARY:Übergabeangebot\r\nUID:".$ErgebnisAngeboteLaden['id']."\r\nSTATUS: CONFIRMED\r\nDTSTART;TZID=Europe/Berlin:" . date(DATE_ICAL, strtotime($ErgebnisAngeboteLaden['von'])) . "\r\nDTEND;TZID=Europe/Berlin:" . date(DATE_ICAL, strtotime($ErgebnisAngeboteLaden['bis'])) . "\r\nLAST-MODIFIED:" . date(DATE_ICAL, strtotime(timestamp())) . "\r\nLOCATION: ".$ErgebnisAngeboteLaden['ort']."\r\nEND:VEVENT\r\n";

        }

        //Für jeden ÜbergabeTreffer einen Eintrag basteln:
        for ($a=1;$a<=$AnzahlUebergabenLaden;$a++) {

            $ErgebnisUebergabenLaden = mysqli_fetch_assoc($AbfrageUebergabenLaden);
            $Terminangebot = lade_terminangebot($ErgebnisUebergabenLaden['terminangebot']);
            $output .= "BEGIN:VEVENT\r\nSUMMARY:Schlüsselübergabe\r\nUID:".$ErgebnisUebergabenLaden['id']."\r\nSTATUS: CONFIRMED\r\nDTSTART;TZID=Europe/Berlin:" . date(DATE_ICAL, strtotime($ErgebnisUebergabenLaden['beginn'])) . "\r\nDTEND;TZID=Europe/Berlin:" . date(DATE_ICAL, strtotime($BefehlUebergabedauer, strtotime($ErgebnisUebergabenLaden['beginn']))) . "\r\nLAST-MODIFIED:" . date(DATE_ICAL, strtotime(timestamp())) . "\r\nLOCATION: ".$Terminangebot['ort']."\r\nBEGIN:VALARM\r\nTRIGGER:-PT15M\r\nACTION:DISPLAY\r\nDESCRIPTION:Schluesseluebergabe\r\nEND:VALARM\r\nEND:VEVENT\r\n";

        }

        //Kalender schließen
        $output .= "END:VCALENDAR";

        //Kalender ausgeben
        echo $output;
    } else {
	    echo "Du hast den Kalender noch nicht aktiviert!";
    }

?>