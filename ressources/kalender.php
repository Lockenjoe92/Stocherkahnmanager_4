<?php

function kalender_gross($Rolle){

    /**
     * $Rolle schaltet zwischen verschiedenen Ansichtsgraden hin und her:
     * 'startpage'
     * 'user'
     * 'wart'
     *
     * Je nach Ansichtsgrad herrscht mehr Privatsphäre
     */

    //WOCHENWECHSLER
    $Wochenverschiebung = parse_wochenwechseler_kalender();
    $Antwort = wochenwechsler_kalender($Wochenverschiebung, $Rolle);

    //DIVIDER
    $Antwort .= "<div class='divider'></div>";

    //KALENDER AUSGEBEN

    //Dati berechnen
    $DatumWochenbeginn = berechne_datum_wochenanfang_gewaehlte_woche($Wochenverschiebung);

    $Antwort .= "<table>";
    $Antwort .= berechne_kopfzeile_kalender_gross($DatumWochenbeginn);

    //JEDES STUNDENFENSTER ALS REIHE DARSTELLEN
    $Antwort .= berechne_body_kalender_gross($DatumWochenbeginn, $Rolle);

    $Antwort .= "</table>";

    return $Antwort;
}

function kalender_mobil($Rolle){

    /**
     * $Rolle schaltet zwischen verschiedenen Ansichtsgraden hin und her:
     * 'startpage'
     * 'user'
     * 'wart'
     *
     * Je nach Ansichtsgrad herrscht mehr Privatsphäre
     */

    //Lade Parameter
    $POSTdatum = $_POST['datum'];
    $FarbeNichtBuchbar = "".lade_db_einstellung('farbe-button-kalender-nicht-buchbar');
    $FarbeBuchbar = "".lade_db_einstellung('farbe-button-kalender-buchbar');
    $FarbeReserviert = "".lade_db_einstellung('farbe-button-kalender-reserviert');
    $Antwort = "";


    //Datum
    if (!isset($POSTdatum)){
        $Datum = date('Y-m-d');
    } else {
        $Datum = $POSTdatum;
    }

    //DAU abfangen - wir wollen nicht zu weit in die Vergangenheit schauen und maximal bis ins nächste Jahr
    $DatumVorEinerWoche = strtotime('- 1 week');
    $yearEnd = strtotime(date("Y-m-d",strtotime('Dec 31')));
    $DatumTime = strtotime($Datum);

    if ($DatumTime < $DatumVorEinerWoche){
        $Antwort .= toast("Du kannst maximal nur eine Woche in die Vergangenheit suchen!");
        $Datum = date('Y-m-d');
    }

    if ($DatumTime > $yearEnd){
        $Antwort .= toast("Der Kalender gilt nur f&uuml;r dieses Jahr!");
        $Datum = date('Y-m-d');
    }

    zeitformat();
    $DatumFormatiert = strftime('%e. %B %G', strtotime($Datum));
    $Wochentag = strftime('%A', strtotime($Datum));

    //Überschrift generieren
    if (strtotime($Datum) == strtotime(date('Y-m-d'))){
        $Ueberschrift = "Heute<br>" .$DatumFormatiert. "";
    } else {
        $Ueberschrift = "" .$Wochentag. "<br>" .$DatumFormatiert. "";
    }

    //Lade Zeitfenstereinstellungen
    $NameWochentagKlein = strtolower($Wochentag);
    $NameEinstellungBeginn = "von-" .$NameWochentagKlein. "";
    $NameEinstellungEnde = "bis-" .$NameWochentagKlein. "";

    $Beginn = intval(lade_db_einstellung($NameEinstellungBeginn));
    $Ende = intval(lade_db_einstellung($NameEinstellungEnde));


    $Antwort = "<h5 class='center-align'>" .$Ueberschrift. "</h5>";

    $Antwort .= "<table class='center-align'>";

    for ($a = $Beginn; $a < $Ende; $a++){

        $StundenfensterAnfang = $a;
        $StundenfensterEnde = $a + 1;

        //Verfügbarkeit Zeitfenster laden
        $Verfuegbarkeit = verfuegbarkeit_zeitfenster_laden(strtotime($Datum), $StundenfensterAnfang, $StundenfensterEnde);
        $Antwort .= "<tr><td class='center-align'>" .$StundenfensterAnfang. " - " .$StundenfensterEnde. " Uhr</td>";

        $Antwort .= "<td class='center-align'>";

        if ($Rolle == "wart"){
            //Bekommt am meisten Infos angezeigt
            if ($Verfuegbarkeit['verfuegbar'] == true){

                //BUCHBAR
                $Antwort .= button_link_creator('Buchbar', '', '','', $FarbeBuchbar);
                #$Antwort .= "<a class='btn " .$FarbeBuchbar. "'>Buchbar</a>";

            } else if ($Verfuegbarkeit['verfuegbar'] == false){

                //GRÜNDE PARSEN
                if ($Verfuegbarkeit['typ'] == "pause"){
                    $Antwort .= button_link_creator('Pause', '', '','', $FarbeNichtBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'>Pause</a>";
                } else if ($Verfuegbarkeit['typ'] == "sperrung"){
                    $Antwort .= button_link_creator('Sperre', '', '','', $FarbeNichtBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'>Sperre</a>";
                } else if ($Verfuegbarkeit['typ'] == "reservierung"){
                    $Antwort .= button_link_creator('belegt', '', '','', $FarbeReserviert);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeReserviert. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'>belegt</a>";
                }
            }

        } else if ($Rolle == "user") {

            //Bekommt Infos, aber keine persönlichen Details
            if ($Verfuegbarkeit['verfuegbar'] == true){

                //BUCHBAR
                $Antwort .= button_link_creator('Buchbar', '', '','', $FarbeBuchbar);
                #$Antwort .= "<a class='btn tooltipped " .$FarbeBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Buchbar'><i class='material-icons left'>thumb_up</i>Frei</a>";

            } else if ($Verfuegbarkeit['verfuegbar'] == false){

                //GRÜNDE PARSEN
                if ($Verfuegbarkeit['typ'] == "pause"){
                    $Antwort .= button_link_creator('Pause', '', '','', $FarbeNichtBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'>Pause</a>";
                } else if ($Verfuegbarkeit['typ'] == "sperrung"){
                    $Antwort .= button_link_creator('Sperre', '', '','', $FarbeNichtBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'>Sperre</a>";
                } else if ($Verfuegbarkeit['typ'] == "reservierung"){
                    $Antwort .= button_link_creator('belegt', '', '','', $FarbeReserviert);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Reserviert!'>belegt</a>";
                }
            }

        } else if ($Rolle == "startpage"){

            //Wenigste Infos
            if ($Verfuegbarkeit['verfuegbar'] == true){
                $Antwort .= button_link_creator('Frei', '', '','', $FarbeBuchbar);
                #$Antwort .= "<a class='btn tooltipped " .$FarbeBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Buchbar im Buchungstool'>Frei</a>";
            } else if ($Verfuegbarkeit['verfuegbar'] == false){
                $Antwort .= button_link_creator('Belegt', '', '','', $FarbeNichtBuchbar);
                #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Nicht buchbar!'>belegt</a>";
            }
        }

        $Antwort .= "</td>";

        $Antwort .= "</tr>";
    }

    $Antwort .= "</table>";

    $Antwort .= "<div class='divider'></div>";
    $HTMLDatepicker = table_builder(table_row_builder(table_header_builder(form_button_builder('change_cal_date', 'Los', 'submit', 'send', '')).table_data_builder(form_datepicker_reservation_item('Anderen Tag wählen', 'datum', '', false, '', ''))));
    $Antwort .= form_builder($HTMLDatepicker, '#', 'post', '', '');

    return $Antwort;
}

function lade_kalender_beginn(){
    //Lade Einstellungen
    $Beginne['montag'] = intval(lade_db_einstellung('von-montag'));
    $Beginne['dienstag'] = intval(lade_db_einstellung('von-dienstag'));
    $Beginne['mittwoch'] = intval(lade_db_einstellung('von-mittwoch'));
    $Beginne['donnerstag'] = intval(lade_db_einstellung('von-donnerstag'));
    $Beginne['freitag'] = intval(lade_db_einstellung('von-freitag'));
    $Beginne['samstag'] = intval(lade_db_einstellung('von-samstag'));
    $Beginne['sonntag'] = intval(lade_db_einstellung('von-sonntag'));

    //Sortiere Beginne von klein nach groß -> so kann man das erste Zeitfenster in der Woche herausfinden
    asort($Beginne);
    $Beginn = reset($Beginne);

    return $Beginn;
}

function lade_kalender_ende(){
    //Lade Einstellungen
    $Enden['montag'] = intval(lade_db_einstellung('bis-montag'));
    $Enden['dienstag'] = intval(lade_db_einstellung('bis-dienstag'));
    $Enden['mittwoch'] = intval(lade_db_einstellung('bis-mittwoch'));
    $Enden['donnerstag'] = intval(lade_db_einstellung('bis-donnerstag'));
    $Enden['freitag'] = intval(lade_db_einstellung('bis-freitag'));
    $Enden['samstag'] = intval(lade_db_einstellung('bis-samstag'));
    $Enden['sonntag'] = intval(lade_db_einstellung('bis-sonntag'));


    //Sortiere Enden von groß nach klein -> so kann das letzte Zeitfenster ermittelt werden
    arsort($Enden);
    $Ende = reset($Enden);

    return $Ende;
}

function parse_wochenwechseler_kalender(){

    //LADE WOCHENVERSCHIEBUNG
    if (isset($_POST['wochenverschiebung'])){
        $WochenverschiebungAlt = intval($_POST['wochenverschiebung']);
    } else {
        $WochenverschiebungAlt = 0;
    }

    //Befehle ausführen:

    //Woche zurück
    if (isset($_POST['woche_zurueck'])){
        $WochenverschiebungNeu = $WochenverschiebungAlt - 1;
    }

    //Aktuelle Woche
    if (isset($_POST['diese_woche'])){
        $WochenverschiebungNeu = 0;
    }

    //Woche weiter
    if (isset($_POST['woche_weiter'])){
        $WochenverschiebungNeu = $WochenverschiebungAlt + 1;
    }

    return $WochenverschiebungNeu;
}

function wochenwechsler_kalender($Wochenverschiebung, $Rolle){

    $Antwort = "<form method='POST'>";
    $Antwort .= "<table>";

    if ($Wochenverschiebung < 0){       //Wir blicken in die Vergangenheit

        if ($Rolle == "wart"){          //Scrollen in weitere Vergangenheit erlaubt
            $Antwort .= table_row_builder(table_data_builder(form_button_builder('woche_zurueck', 'Woche zur&uuml;ck', 'action', 'fast_rewind')).table_data_builder(form_button_builder('diese_woche', 'Aktuelle Woche', 'action', 'fast_rewind')).table_data_builder(form_button_builder('woche_weiter', 'Woche weiter', 'action', 'fast_forward')));
        } else {                        //Scrollen in weitere Vergangenheit für normale User verboten
            $Antwort .= table_row_builder(table_data_builder(form_button_builder('woche_zurueck', 'Woche zur&uuml;ck', 'action', 'fast_rewind', 'disabled')).table_data_builder(form_button_builder('diese_woche', 'Aktuelle Woche', 'action', 'fast_rewind')).table_data_builder(form_button_builder('woche_weiter', 'Woche weiter', 'action', 'fast_forward')));
        }

    } else if ($Wochenverschiebung == 0){   //Wir sind in dieser Woche - aktuelle woche button wird deaktiviert
        $Antwort .= table_row_builder(table_data_builder(form_button_builder('woche_zurueck', 'Woche zur&uuml;ck', 'action', 'fast_rewind')).table_data_builder(form_button_builder('diese_woche', 'Aktuelle Woche', 'action', 'fast_rewind', 'disabled')).table_data_builder(form_button_builder('woche_weiter', 'Woche weiter', 'action', 'fast_forward')));

    } else if ($Wochenverschiebung > 0){   //Wir sind in irgendeiner zukünftigen Woche - alles ist für jeden möglich
        $Antwort .= table_row_builder(table_data_builder(form_button_builder('woche_zurueck', 'Woche zur&uuml;ck', 'action', 'fast_rewind')).table_data_builder(form_button_builder('diese_woche', 'Aktuelle Woche', 'action', 'fast_rewind')).table_data_builder(form_button_builder('woche_weiter', 'Woche weiter', 'action', 'fast_forward')));
    }

    $Antwort .= "</table>";
    $Antwort .= "<input type='hidden' name='wochenverschiebung' value='" .$Wochenverschiebung. "'>";
    $Antwort .= "</form>";

    return $Antwort;
}

function berechne_datum_wochenanfang_gewaehlte_woche($Wochenverschiebung){

    $MontagDieseWoche = strtotime('monday this week');

    if ($Wochenverschiebung == 0){
        $MontagGewaehlteWoche = $MontagDieseWoche;
    } else if ($Wochenverschiebung > 0) {
        $MontagGewaehlteWoche = strtotime("+ " .$Wochenverschiebung. " weeks", $MontagDieseWoche);
    } else if ($Wochenverschiebung < 0){
        $MontagGewaehlteWoche = strtotime("" .$Wochenverschiebung. " weeks", $MontagDieseWoche);
    }

    return $MontagGewaehlteWoche;
}

function berechne_kopfzeile_kalender_gross($DatumWochenbeginn){

    $Antwort = "<tr><th></th>";

    for ($c = 0; $c <= 6; $c++){

        if ($c == 0){
            $DatumZelle = $DatumWochenbeginn;
        } else if ($c > 0){
            $DatumZelle = strtotime("+ " .$c. " days", $DatumWochenbeginn);
        }

        zeitformat();
        $DatumFormatiert = htmlentities(strftime('%e. %b %G', $DatumZelle));
        $Wochentag = strftime('%A', $DatumZelle);

        if ($DatumZelle == strtotime(date("Y-m-d"))){
            $Antwort .= "<th class='center-align'>Heute<br>" .$DatumFormatiert. "</th>";
        } else if ($DatumZelle != strtotime(date("Y-m-d"))) {
            $Antwort .= "<th class='center-align'>" .$Wochentag. "<br>" .$DatumFormatiert. "</th>";
        }
    }

    $Antwort .= "</tr>";

    return $Antwort;
}

function berechne_body_kalender_gross($DatumWochenbeginn, $Rolle){

    //Lade Zeitfenstereinstellungen
    $Beginn = lade_kalender_beginn();
    $Ende = lade_kalender_ende();
    $FarbeNichtBuchbar = "".lade_db_einstellung('farbe-button-kalender-nicht-buchbar');
    $FarbeBuchbar = "".lade_db_einstellung('farbe-button-kalender-buchbar');
    $FarbeReserviert = "".lade_db_einstellung('farbe-button-kalender-reserviert');

    $Antwort = "";
    $a = $Beginn;

    for ($a; $a < $Ende; $a++){
        $StundenfensterAnfang = $a;
        $StundenfensterEnde = $a + 1;

        //ANGABE STUNDENFENSTER
        $Antwort .= "<tr><th class='center-align'>" .$StundenfensterAnfang. ":00 - " .$StundenfensterEnde. ":00 Uhr</th>";

        //JEDEN WOCHENTAG BERECHNEN
        for ($b = 0; $b <= 6; $b++){

            if ($b == 0){
                $DatumZelle = $DatumWochenbeginn;
            } else if ($b > 0){
                $DatumZelle = strtotime("+ " .$b. " days", $DatumWochenbeginn);
            }

            //Verfügbarkeit Zeitfenster laden
            $Verfuegbarkeit = verfuegbarkeit_zeitfenster_laden($DatumZelle, $StundenfensterAnfang, $StundenfensterEnde);

            $Antwort .= "<td class='center-align'>";

            if ($Rolle == "wart"){
                //Bekommt am meisten Infos angezeigt
                if ($Verfuegbarkeit['verfuegbar'] == true){

                    //BUCHBAR
                    $Antwort .= button_link_creator('<i class=\'material-icons\'>thumb_up</i>', '', '','', $FarbeBuchbar);
                    #$Antwort .= "<a class='btn " .$FarbeBuchbar. "'><i class='material-icons'>thumb_up</i></a>";

                } else if ($Verfuegbarkeit['verfuegbar'] == false){

                    //GRÜNDE PARSEN
                    if ($Verfuegbarkeit['typ'] == "pause"){
                        $Antwort .= button_link_creator('<i class=\'material-icons\'>alarm_on</i>', '', '','', $FarbeNichtBuchbar);
                        #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'><i class='material-icons'>alarm_on</i></a>";
                    } else if ($Verfuegbarkeit['typ'] == "sperrung"){
                        $Antwort .= button_link_creator('<i class=\'material-icons\'>report_problem</i>', '', '','', $FarbeNichtBuchbar);
                        #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'><i class='material-icons'>report_problem</i></a>";
                    } else if ($Verfuegbarkeit['typ'] == "reservierung"){
                        $Antwort .= button_link_creator('<i class=\'material-icons\'>shopping_basket</i>', '', '','', $FarbeReserviert);
                        #$Antwort .= "<a class='btn tooltipped " .$FarbeReserviert. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'><i class='material-icons'>shopping_basket</i></a>";
                    }
                }

            } else if ($Rolle == "user") {

                //Bekommt Infos, aber keine persönlichen Details
                if ($Verfuegbarkeit['verfuegbar'] == true){

                    //BUCHBAR
                    $Antwort .= button_link_creator('<i class=\'material-icons\'>thumb_up</i>', '', '','', $FarbeBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Buchbar'><i class='material-icons'>thumb_up</i></a>";

                } else if ($Verfuegbarkeit['verfuegbar'] == false){

                    //GRÜNDE PARSEN
                    if ($Verfuegbarkeit['typ'] == "pause"){
                        $Antwort .= button_link_creator('<i class=\'material-icons\'>alarm_on</i>', '', '','', $FarbeNichtBuchbar);
                        #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'><i class='material-icons'>alarm_on</i></a>";
                    } else if ($Verfuegbarkeit['typ'] == "sperrung"){
                        $Antwort .= button_link_creator('<i class=\'material-icons\'>report_problem</i>', '', '','', $FarbeNichtBuchbar);
                        #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='" .$Verfuegbarkeit['tooltip']. "'><i class='material-icons'>report_problem</i></a>";
                    } else if ($Verfuegbarkeit['typ'] == "reservierung"){
                        $Antwort .= button_link_creator('<i class=\'material-icons\'>thumb_down</i>', '', '','', $FarbeNichtBuchbar);
                        #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Reserviert!'><i class='material-icons'>thumb_down</i></a>";
                    }
                }

            } else if ($Rolle == "startpage"){

                //Wenigste Infos
                if ($Verfuegbarkeit['verfuegbar'] == true){
                    $Antwort .= button_link_creator('<i class=\'material-icons\'>thumb_up</i>', '', '','', $FarbeBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Buchbar im Buchungstool'><i class='material-icons'>thumb_up</i></a>";
                } else if ($Verfuegbarkeit['verfuegbar'] == false){
                    $Antwort .= button_link_creator('<i class=\'material-icons\'>thumb_down</i>', '', '','', $FarbeNichtBuchbar);
                    #$Antwort .= "<a class='btn tooltipped " .$FarbeNichtBuchbar. "' data-position='bottom' data-delay='50' data-tooltip='Nicht buchbar!'><i class='material-icons'>thumb_down</i></a>";
                }
            }

            $Antwort .= "</td>";
        }
        $Antwort .= "</tr>";
    }
    return $Antwort;
}

function verfuegbarkeit_zeitfenster_laden($DatumZelle, $StundenfensterAnfang, $StundenfensterEnde){

    $link = connect_db();
    $TimestampBeginn = "" .date("Y-m-d", $DatumZelle). " " .$StundenfensterAnfang. ":00:00";
    $TimestampEnde = "" .date("Y-m-d", $DatumZelle). " " .$StundenfensterEnde. ":00:00";

    //1. Nachsehen, ob es eine Winterpause in diesem Zeitfenster gibt
    $AnfrageLadeBetroffeneWinterpausen = "SELECT id, typ, titel FROM pausen WHERE storno_user = '0' AND ((beginn <= '$TimestampBeginn') AND ('$TimestampEnde' <= ende)) OR (('$TimestampBeginn' < beginn) AND (beginn < '$TimestampEnde')) OR (('$TimestampBeginn' < ende) AND (ende < '$TimestampEnde'))";
    $AbfrageLadeBetroffeneWinterpausen = mysqli_query($link, $AnfrageLadeBetroffeneWinterpausen);
    $AnzahlLadeBetroffeneWinterpausen = mysqli_num_rows($AbfrageLadeBetroffeneWinterpausen);


    if ($AnzahlLadeBetroffeneWinterpausen > 0){

        //Vorgang endet schon hier -> Laden Erklärung
        $BetroffenePause = mysqli_fetch_assoc($AbfrageLadeBetroffeneWinterpausen);

        $Antwort['verfuegbar'] = false;
        $Antwort['typ'] = "pause";
        $Antwort['tooltip'] = "" .$BetroffenePause['titel']. "";

    } else if ($AnzahlLadeBetroffeneWinterpausen == 0){
        //2. Nachsehen, ob es eine Sperrung/Ausfall in diesem Zeitfenster gibt
        $AnfrageLadeBetroffeneSperrungen = "SELECT id, typ, titel FROM sperrungen WHERE storno_user = '0' AND ((beginn <= '$TimestampBeginn') AND ('$TimestampEnde' <= ende)) OR (('$TimestampBeginn' < beginn) AND (beginn < '$TimestampEnde')) OR (('$TimestampBeginn' < ende) AND (ende < '$TimestampEnde'))";
        $AbfrageLadeBetroffeneSperrungen = mysqli_query($link, $AnfrageLadeBetroffeneSperrungen);
        $AnzahlLadeBetroffeneSperrungen = mysqli_num_rows($AbfrageLadeBetroffeneSperrungen);

        if ($AnzahlLadeBetroffeneSperrungen > 0){

            $BetroffeneSperrung = mysqli_fetch_assoc($AbfrageLadeBetroffeneSperrungen);

            $Antwort['verfuegbar'] = false;
            $Antwort['typ'] = "sperrung";
            $Antwort['tooltip'] = "" .$BetroffeneSperrung['titel']. "";

        } else if ($AnzahlLadeBetroffeneSperrungen == 0){
            //3. Nachsehen, ob es eine Reservierung in diesem Zeitfenster gibt
            $AnfrageLadeBetroffeneReservierungen = "SELECT id, user FROM reservierungen WHERE storno_user = '0' AND (((beginn = '$TimestampBeginn') AND (ende >= '$TimestampEnde')) OR ((beginn < '$TimestampBeginn') AND (ende > '$TimestampEnde')) OR ((beginn <= '$TimestampBeginn') AND (ende = '$TimestampEnde')))";
            $AbfrageLadeBetroffeneReservierungen = mysqli_query($link, $AnfrageLadeBetroffeneReservierungen);
            $AnzahlLadeBetroffeneReservierungen = mysqli_num_rows($AbfrageLadeBetroffeneReservierungen);

            if ($AnzahlLadeBetroffeneReservierungen > 0){

                $BetroffeneReservierung = mysqli_fetch_assoc($AbfrageLadeBetroffeneReservierungen);
                $UserReservierung = lade_user_meta($BetroffeneReservierung['user']);

                $Antwort['verfuegbar'] = false;
                $Antwort['typ'] = "reservierung";
                $Antwort['tooltip'] = "#" .$BetroffeneReservierung['id']. " - " .$UserReservierung['vorname']. " " .$UserReservierung['nachname']. "";

            } else if ($AnzahlLadeBetroffeneReservierungen == 0){
                $Antwort['verfuegbar'] = true;
            }
        }
    }

    return $Antwort;
}

?>