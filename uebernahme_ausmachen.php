<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bernahme ausmachen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bernahme ausmachen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Schl&uuml;ssel&uuml;bernahme ausmachen</h1>';
$HTML = section_builder($PageTitle);

$ReservierungID = $_GET['res'];
$Parser = parse_uebernahme_ausmachen($ReservierungID);
$HTML .= card_resinfos_generieren($ReservierungID);

if ($Parser['success'] === NULL){

    //Normale Erklärungsseite anzeigen!
    $HTML .= erklaerung_schluesseluebernahme_element();
    $HTML .= promt_schluesseluebernahme_element($Parser);

} else if ($Parser['success'] === TRUE){

    //Erfolgsmeldung anzeigen!
    if ($Parser['wartmode'] == TRUE){
        $HTML .= zurueck_karte_generieren(TRUE, 'Die Schl&uuml;ssel&uuml;bergabe wurde erfolgreich eingetragen und die Gruppe davor, als auch der User informiert.', './reservierungsmanagement.php');
    } else if ($Parser['wartmode'] == FALSE){
        $HTML .= zurueck_karte_generieren(TRUE, 'Deine Schl&uuml;ssel&uuml;bergabe wurde erfolgreich eingetragen und die Gruppe vor dir informiert. Bitte schaue ab jetzt &ouml;fters in deine Mail falls sich doch etwas &auml;ndern sollte und sei bitte p&uuml;nktlich an der Anlegestelle!:) Wir w&uuml;nschen eine gute Fahrt!:)', './my_reservations.php');
    }

} else if ($Parser['success'] === FALSE){

    //Fehlermeldung anzeigen!
    if ($Parser['wartmode'] == TRUE){
        $HTML .= zurueck_karte_generieren(FALSE, $Parser['meldung'], './reservierungsmanagement.php');
    } else if ($Parser['wartmode'] == FALSE){
        $HTML .= zurueck_karte_generieren(FALSE, $Parser['meldung'], './my_reservations.php');
    }

}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);




function parse_uebernahme_ausmachen($ReservierungID){

        $link = connect_db();
        $Reservierung = lade_reservierung($ReservierungID);

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
            if (lade_user_id() != intval($Reservierung['user'])){

                $UserAktuell = lade_user_meta(lade_user_id());

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
            $Benutzereinstellungen = lade_user_meta(lade_user_id());

            $AnfrageEinweisungenJemals = "SELECT id FROM schluesselausgabe WHERE user = '".lade_user_id()."' AND storno_user = '0' AND rueckgabe IS NOT NULL";
            $AbfrageEinweisungenJemals = mysqli_query($link,$AnfrageEinweisungenJemals);
            $AnzahlEinweisungenJemals = mysqli_num_rows($AbfrageEinweisungenJemals);

            if ($AnzahlEinweisungenJemals == 0){
                if ($Benutzereinstellungen['ist_wart'] != 'true'){
                    $DAUcounter++;
                    $DAUerror .= "Du hast nicht die n&ouml;tigen Einweisungen um eine Schl&uuml;ssel&uuml;bernahme auszumachen!<br>";
                } else if ($Benutzereinstellungen['ist_wart'] == 'true'){
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
                $DAUerror .= "Du hast hast f&uuml;r diese Reservierung bereits eine &Uuml;bergabe ausgemacht!<br>";
            }

            //Es gibt keine Vorfahrende Reservierung mit ausgemachter Übergabe mehr!
            $AnfrageLadeResVorher = "SELECT id FROM reservierungen WHERE ende = '".$Reservierung['beginn']."' AND storno_user = '0'";
            $AbfrageLadeResVorher = mysqli_query($link, $AnfrageLadeResVorher);
            $AnfrageLadeResVorher = mysqli_num_rows($AbfrageLadeResVorher);

            if ($AnfrageLadeResVorher == 0){
                $DAUcounter++;
                $DAUerror .= "Es gibt leider keine Reservierung mehr vor dir! <a href='./uebergabe_ausmachen.php?res=".$ReservierungID."'>Buche dir einfach eine Schl&uuml;ssel&uuml;bergabe</a> durch einen unserer Stocherkahnw&auml;rte:)<br>";
            } else if ($AnfrageLadeResVorher > 0){

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
                            $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebergabe_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigene Schl&uuml;ssel&uuml;bergabe</a>!<br>";
                        }

                    } else {
                        $DAUcounter++;
                        $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebergabe_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigene Schl&uuml;ssel&uuml;bergabe</a>!<br>";
                    }
                }
            }

            if ($DAUcounter > 0){

                //Check low priority fails

                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = $DAUerror;
            } else {

                if (isset($_POST['eintragen'])){

                    $Antwort = uebernahme_eintragen($ReservierungID, $_POST['kommentar']);

                } else {
                    $Antwort['success'] = NULL;
                    $Antwort['meldung'] = "";
                    $Antwort['wartmode'] = $Wartmode;
                }
            }

        }

        return $Antwort;
    }

function erklaerung_schluesseluebernahme_element(){
    
    $HTML = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $HTML .= lade_xml_einstellung('erklaerung_schluesseluebernahme');
    $HTML .= "</div>";

    return section_builder($HTML);
}

function promt_schluesseluebernahme_element($Parser){

    if ($Parser['wartmode'] == TRUE){
        $HTML = prompt_karte_generieren('eintragen', 'Eintragen', './reservierungsmanagement.php', 'Abbrechen', 'M&ouml;chtest du eine Schl&uuml;ssel&uuml;bernahme eintragen?', TRUE, 'kommentar');
    } else if ($Parser['wartmode'] == FALSE) {
        $HTML = prompt_karte_generieren('eintragen', 'Eintragen', './my_reservations.php', 'Abbrechen', 'M&ouml;chtest du eine Schl&uuml;ssel&uuml;bernahme eintragen?', TRUE, 'kommentar');
    }

    return $HTML;
}

?>