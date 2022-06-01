<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Reservierungsmanagement - " . lade_db_einstellung('site_name');

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Reservierungsmanagement</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Reservierungen verwalten</h1>';
$HTML = section_builder($PageTitle);

#ParserStuff
$Meldung = parser_resmanagement();
if($Meldung!=''){
    $HTML .= "<h5 class='center-align'>".$Meldung."</h5>";
}

#FIND OUT PAYPAL STATUS
$UserMeta = lade_user_meta(lade_user_id());
if(($UserMeta['ist_kasse']=='true') AND (lade_xml_einstellung('paypal-aktiv')=='on')){
    $PayPal = TRUE;
} else {
    $PayPal = FALSE;
}

//Stats (Durchgeführte Reservierungen diese Saison, Reservierungen insgesamt)
$HTML .= spalte_stats();

//Aktive Reservierungen
//Objekt: ID, Datum, Von-Bis, User, Übergabestatus, Schlüsselstatus, Zahlstatus; Funktionen: bearbeiten, stornieren
//Reservierung hinzufügen
$HTML .= spalte_aktive_reservierungen($PayPal);
//Vergangene Reservierungen
//Objekt: ID, Datum, Von-Bis, User, Übergabestatus, Schlüsselstatus, Zahlstatus; Funktionen: bearbeiten, stornieren
$HTML .= spalte_vergangene_reservierungen($PayPal);
//Stornierte Reservierungen
//Objekt: ID, Datum, Von-Bis, User, Übergabestatus, Schlüsselstatus, Zahlstatus; Funktionen: storno-aufheben
$HTML .= spalte_stornierte_reservierungen($PayPal);
$HTML = form_builder($HTML, '#', 'post', '', '');


$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function spalte_stats(){

    $link = connect_db();
    $AnfangDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $EndeDiesesJahr = "".date("Y")."-12-31 23:59:59";

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header hide-on-med-and-down'>Jahresstats</h5>";
    $HTML .= "<h5 class='header hide-on-large-only center-align'>Jahresstats</h5>";

    //Reservierungen Laden
    $AnfrageReservierungenLaden = "SELECT id FROM reservierungen WHERE storno_user = '0' AND beginn > '$AnfangDiesesJahr' AND ende < '$EndeDiesesJahr'";
    $AbfrageReservierungenLaden = mysqli_query($link, $AnfrageReservierungenLaden);
    $AnzahlReservierungenLaden = mysqli_num_rows($AbfrageReservierungenLaden);

    //Übergaben
    $AnfrageUebergabenLaden = "SELECT id FROM uebergaben WHERE storno_user = '0' AND durchfuehrung IS NOT NULL AND beginn > '$AnfangDiesesJahr' AND beginn < '$EndeDiesesJahr'";
    $AbfrageUebergabenLaden = mysqli_query($link, $AnfrageUebergabenLaden);
    $AnzahlUebergabenLaden = mysqli_num_rows($AbfrageUebergabenLaden);

    //Einnahmen-Ausgaben-Rechner
    $Gesamteinnahmen = gesamteinnahmen_jahr(date("Y"));
    $Gesamtdifferenz = $Gesamteinnahmen - gesamtausgaben_jahr(date("y"));

    $HTML .= "<p><table>";

    if(lade_xml_einstellung('paypal-aktiv')=='on'){

        $PayPalKonto = lade_paypal_konto_id();
        $GesamteinnahmenPaypal = gesamteinnahmen_jahr_konto(date("Y"), $PayPalKonto['id']);
        $ProzentPayPal = $GesamteinnahmenPaypal/$Gesamteinnahmen*100;
        $ProzentPayPal = round($ProzentPayPal,1);

        $HTML .= "<tr><th>Reservierungen</th><th>&Uuml;bergaben</th><th>Einnahmen</th><th>Davon Paypal</th></tr>";
        $HTML .= "<tr><td>".$AnzahlReservierungenLaden."</td><td>".$AnzahlUebergabenLaden."</td><td>".$Gesamteinnahmen."&euro;</td><td>".$ProzentPayPal."%</td></tr>";
    }else{
        $HTML .= "<tr><th>Reservierungen</th><th>&Uuml;bergaben</th><th>Einnahmen</th></tr>";
        $HTML .= "<tr><td>".$AnzahlReservierungenLaden."</td><td>".$AnzahlUebergabenLaden."</td><td>".$Gesamteinnahmen."&euro;</td></tr>";
    }
    $HTML .= "</table></p>";

    $HTML .= "</div>";

    return $HTML;
}
function spalte_aktive_reservierungen($PayPal){

    $HTML = "";
    $link = connect_db();
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";

    //Lade Alle anstehenden Reservierungen
    $Anfrage = "SELECT id FROM reservierungen WHERE ende > '".timestamp()."' AND storno_user = '0' AND beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr' ORDER BY ende ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>report_problem</i>Keine Reservierungen</div>";
        $HTML .= "</li>";

    } else if ($Anzahl > 0){

        for ($a = 1; $a <= $Anzahl; $a++){
            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $HTML .= reservierungsobjekt_generieren($Reservierung['id'], TRUE, TRUE, FALSE, FALSE, $PayPal);
        }

    }

    $HTML .= "<li>";
    $HTML .= "<div class='collapsible-header'><a href='reservierung_hinzufuegen.php'><i class='large material-icons'>note_add</i>Reservierung hinzuf&uuml;gen</a></div>";
    $HTML .= "</li>";

    $ReturnHTML = "<div class='section'>";
    $ReturnHTML .= "<h5 class='header hide-on-med-and-down'>Anstehende Reservierungen</h5>";
    $ReturnHTML .= "<h5 class='header hide-on-large-only center-align'>Anstehende Reservierungen</h5>";
    $ReturnHTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";
    $ReturnHTML .= $HTML;
    $ReturnHTML .= "</ul>";
    $ReturnHTML .= "</div>";

    return $ReturnHTML;
}
function spalte_vergangene_reservierungen($PayPal){
    $HTML = "";
    $link = connect_db();
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";

    //Lade Alle anstehenden Reservierungen
    $Anfrage = "SELECT id FROM reservierungen WHERE ende < '".timestamp()."' AND user <> '188' AND storno_user = '0' AND beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr' ORDER BY ende DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>report_problem</i>Keine Reservierungen</div>";
        $HTML .= "</li>";

    } else if ($Anzahl > 0){

        for ($a = 1; $a <= $Anzahl; $a++){
            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $HTML .= reservierungsobjekt_generieren($Reservierung['id'], TRUE, TRUE, TRUE, TRUE, $PayPal);
        }

    }

    $ReturnHTML = "<div class='section'>";
    $ReturnHTML .= "<h5 class='header hide-on-med-and-down'>Vergangene Reservierungen</h5>";
    $ReturnHTML .= "<h5 class='header hide-on-large-only center-align'>Vergangene Reservierungen</h5>";
    $ReturnHTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";
    $ReturnHTML .= $HTML;
    $ReturnHTML .= "</ul>";
    $ReturnHTML .= "</div>";

    return $ReturnHTML;
}
function spalte_stornierte_reservierungen($PayPal){

    $HTML = "";
    $link = connect_db();
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";

    //Lade Alle anstehenden Reservierungen
    $Anfrage = "SELECT id FROM reservierungen WHERE storno_user <> '0' AND beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr' AND user <> '188' ORDER BY beginn DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>report_problem</i>Keine Reservierungen</div>";
        $HTML .= "</li>";

    } else if ($Anzahl > 0){

        for ($a = 1; $a <= $Anzahl; $a++){
            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $HTML .= reservierungsobjekt_generieren($Reservierung['id'], TRUE, FALSE, TRUE, FALSE, $PayPal);
        }

    }

    $ReturnHTML = "<div class='section'>";
    $ReturnHTML .= "<h5 class='header hide-on-med-and-down'>Stornierte Reservierungen</h5>";
    $ReturnHTML .= "<h5 class='header hide-on-large-only center-align'>Stornierte Reservierungen</h5>";
    $ReturnHTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";
    $ReturnHTML .= $HTML;
    $ReturnHTML .= "</ul>";
    $ReturnHTML .= "</div>";

    return $ReturnHTML;
}
function parser_resmanagement(){

    $link = connect_db();
    $UserMeta = lade_user_meta(lade_user_id());
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";
    $AnfrageLadeAlleReservierungenDiesesJahr = "SELECT id FROM reservierungen WHERE beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr'";
    $AbfrageLadeAlleReservierungenDiesesJahr = mysqli_query($link, $AnfrageLadeAlleReservierungenDiesesJahr);
    $AnzahlLadeAlleReservierungenDiesesJahr = mysqli_num_rows($AbfrageLadeAlleReservierungenDiesesJahr);

    for ($a = 1; $a <= $AnzahlLadeAlleReservierungenDiesesJahr; $a++){

        $Reservierung = mysqli_fetch_assoc($AbfrageLadeAlleReservierungenDiesesJahr);

        //Stornieren?
        $HTMLstornieren = "action_reservierung_".$Reservierung['id']."_stornieren";
        if(isset($_POST[$HTMLstornieren])){
            $Begruendung = "Durch den Stocherkahnwart ".$UserMeta['vorname']." ".$UserMeta['nachname']." aus betrieblichen Gr&uuml;nden storniert.";
            $Ergebnis = reservierung_stornieren($Reservierung['id'], lade_user_id(), $Begruendung);
            return $Ergebnis['meldung'];
        }

        //Bearbeiten?
        $HTMLbearbeiten = "action_reservierung_".$Reservierung['id']."_bearbeiten";
        if(isset($_POST[$HTMLbearbeiten])){
            header("Location: ./reservierung_bearbeiten.php?id=".$Reservierung['id']."");
            die();
        }

        //PayPal?
        $HTMLPayPal = "action_paypal_zahlung_".$Reservierung['id'];
        if(isset($_POST[$HTMLPayPal])){
            $ForderungsbetragRes = kosten_reservierung($Reservierung['id']);
            $Ergebnis = nachzahlung_reservierung_festhalten($Reservierung['id'], $ForderungsbetragRes, lade_user_id(), TRUE);
            return $Ergebnis['meldung'];
        }

        //Storno aufheben?
        $HTMLstornoaufheben = "action_reservierung_".$Reservierung['id']."_storno_aufheben";
        if(isset($_POST[$HTMLstornoaufheben])){
            $Ergebnis = reservierung_storno_aufheben($Reservierung['id']);
            return $Ergebnis;
        }

    }
}
function reservierungsobjekt_generieren($ResID, $Bearbeiten, $Stornieren, $StornoAufheben, $RechnungAnbieten=false, $PayPalKnopfAnzeigen=false){

    //Allgemeines laden
    $link = connect_db();
    zeitformat();
    $Reservierung = lade_reservierung($ResID);
    $UserReservierung = lade_user_meta($Reservierung['user']);

    //Inhaltspunkte generieren
    $AngabenUser = "".$UserReservierung['vorname']." ".$UserReservierung['nachname']."";
    $FahrtDatum = strftime("%A, %d. %B %G", strtotime($Reservierung['beginn']));
    $Fahrzeiten = "".date("G", strtotime($Reservierung['beginn']))." bis ".date("G", strtotime($Reservierung['ende']))." Uhr";

    $KeineZahlungMehr = false;

    //Finanzen:
    if ($Reservierung['gratis_fahrt'] == "1"){
        $Finanzen = "<b>Gratisfahrt</b>";
        $Zahlungen = "nicht notwendig";
        $KeineZahlungMehr = true;
    } else if (intval($Reservierung['preis_geaendert']) > 0){

        //Nachsehen ob schon gezahlt wurde
        $Forderung = lade_forderung_res($ResID);
        $Zahlungen = lade_gezahlte_summe_forderung($Forderung['id']);

        if ($Zahlungen == 0){

            $Finanzen = "Preis&auml;nderung: ".$Reservierung['preis_geaendert']."&euro;";
            $Zahlungen = "keine";

        } else {

            if ($Zahlungen >= intval($Forderung['betrag'])){

                $Finanzen = "Preis&auml;nderung: ".$Reservierung['preis_geaendert']."&euro;";
                $Zahlungen = "bezahlt";
                $KeineZahlungMehr = true;

            } else if ($Zahlungen < intval($Forderung['betrag'])){

                $Finanzen = "Preis&auml;nderung: ".$Reservierung['preis_geaendert']."&euro;";
                $Zahlungen = "unvollst&auml;ndig: ".$Zahlungen."&euro;";

            }

        }

    } else if (($Reservierung['gratis_fahrt'] == "0") AND ($Reservierung['preis_geaendert'] == "0")){

        $Kosten = kosten_reservierung($ResID);

        $Finanzen = $Kosten."&euro;";
        if($Kosten == 0){
            $KeineZahlungMehr = true;
            $Zahlungen = "nicht notwendig";
        } else {
            //Nachsehen ob schon gezahlt wurde
            $Forderung = lade_forderung_res($ResID);
            $Zahlungen = lade_gezahlte_summe_forderung($Forderung['id']);

            if ($Zahlungen == 0){
                $Zahlungen = "keine";
            } else {
                if ($Zahlungen >= intval($Forderung['betrag'])){
                    $KeineZahlungMehr = true;
                    $Zahlungen = "bezahlt";
                } else if ($Zahlungen < intval($Forderung['betrag'])){
                    $Zahlungen = "unvollst&auml;ndig: ".$Zahlungen."&euro;";
                }
            }
        }
    }

    //Verknüpfungsfähigkeit
    $Anfrage = "SELECT id FROM reservierungen WHERE ((beginn = '".$Reservierung['ende']."') OR (ende = '".$Reservierung['beginn']."')) AND user = '".$Reservierung['user']."' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl > 0){
        $ReservierungVerknuepfung = mysqli_fetch_assoc($Abfrage);

        //Span generieren
        $SpanUebergabeNotwendig = "<span class=\"new badge green darken-2\" data-badge-caption=\"Verkn&uuml;pfung m&ouml;glich\"></span>";

        //Button generieren
        $VerknuepfenButton = button_link_creator('Verknüpfen', 'reservierung_verknuepfen.php?res='.$Reservierung['id'].'&res2='.$ReservierungVerknuepfung['id'].'', 'call_merge', '');
    }

    $Titel = "".$ResID." - ".$FahrtDatum." - ".$Fahrzeiten." - ".$AngabenUser." ".$SpanUebergabeNotwendig."";
    $TableHTML = table_row_builder(table_header_builder("User:").table_data_builder($AngabenUser));
    $TableHTML .= table_row_builder(table_header_builder("Datum:").table_data_builder($FahrtDatum));
    $TableHTML .= table_row_builder(table_header_builder("Fahrzeit:").table_data_builder($Fahrzeiten));
    $TableHTML .= table_row_builder(table_header_builder("Kosten:").table_data_builder($Finanzen));
    $TableHTML .= table_row_builder(table_header_builder("Zahlungen:").table_data_builder($Zahlungen));
    $TableHTML .= table_row_builder(table_header_builder("&Uuml;bergabestatus:").table_data_builder(uebergabewesen($ResID, 'wart')));
    $TableHTML .= table_row_builder(table_header_builder("Schl&uuml;sselstatus:").table_data_builder(schluesselwesen($ResID, 'wart')));
    if ($StornoAufheben == TRUE) {
        if($Reservierung['storno_user']>0){
            $StornoUser = lade_user_meta($Reservierung['storno_user']);
            $StornoText = "Storniert am ".strftime("%A, %d. %b. %G", strtotime($Reservierung['storno_zeit']))." durch ".$StornoUser['vorname']." ".$StornoUser['nachname']."";
            $TableHTML .= table_row_builder(table_header_builder("Storno:").table_data_builder($StornoText));

        }
    }
    $ButtonBearbeiten = form_button_builder('action_reservierung_'.$Reservierung['id'].'_bearbeiten', 'Bearbeiten', 'submit', 'mode_edit', '');
    $ButtonStornieren = form_button_builder('action_reservierung_'.$Reservierung['id'].'_stornieren', 'Stornieren', 'submit', 'delete', '');
    $ButtonStornoAufheben = form_button_builder('action_reservierung_'.$Reservierung['id'].'_storno_aufheben', 'Storno aufheben', 'submit', 'replay', '');
    $ButtonPayPalZahlung = form_button_builder('action_paypal_zahlung_'.$Reservierung['id'], 'PayPal Zahlung festhalten', 'submit', 'attach_money', 'blue');

    $Buttons = $VerknuepfenButton;
    if ($Bearbeiten == TRUE){
        $Buttons .= " ".$ButtonBearbeiten;
    }
    if ($Stornieren == TRUE){
        $Buttons .= " ".$ButtonStornieren;
    }
    if ($StornoAufheben == TRUE){
        $Buttons .= " ".$ButtonStornoAufheben;
    }

    if($PayPalKnopfAnzeigen){
        if($KeineZahlungMehr!=TRUE){
            $Buttons .= " ".$ButtonPayPalZahlung;
        }
    }


    if($RechnungAnbieten == TRUE){
        $Forderung = lade_forderung_res($ResID);
        if($Forderung['betrag']>0){
            if(lade_xml_einstellung('rechnungsfunktion_global')=='on'){
                if(lade_xml_einstellung('rechnungsfunktion_wart')=='on'){
                    $Buttons .= " ".button_link_creator('Rechnung', './rechnung_anzeigen.php?forderung_id='.$Forderung['id'], 'payment', '');
                }
            }
        }
    }

    $TableHTML .= table_row_builder(table_header_builder($Buttons).table_data_builder(""));
    $Content = table_builder($TableHTML);
    $CollapsibleItem = collapsible_item_builder($Titel, $Content, 'today', '');

    return $CollapsibleItem;
}

?>