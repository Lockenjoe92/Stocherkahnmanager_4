<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bernahme absagen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bernahme absagen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">&Uuml;bernahme absagen</h1>';
$HTML = section_builder($PageTitle);
$UebernahmeID = $_GET['uebernahme'];
$UserID = lade_user_id();
if(($UebernahmeID == "") OR ($UebernahmeID == "0")){
    header("Location: ./wartwesen.php");
    die();
} else {
    $Uebernahme = lade_uebernahme($UebernahmeID);
    $Reservierung = lade_reservierung($Uebernahme['reservierung']);
    $ReservierungDavor = lade_reservierung($Uebernahme['reservierung_davor']);
    $Benutzerrollen = lade_user_meta($UserID);

    if(($UserID != $Reservierung['user']) AND ($UserID != $ReservierungDavor['user']) AND ($Benutzerrollen['ist_wart'] != 'true')){
        header("Location: ./wartwesen.php");
        die();
    }

    if($UserID == $Reservierung['user']){
        $Mode = "selbst";
    } else if ($UserID == $ReservierungDavor['user']){
        $Mode = "vorfahrer";
    } else if ($Benutzerrollen['ist_wart'] == 'true'){
        $Mode = "wart";
    }
}
$Parser = parse_uebernahme_absagen($UebernahmeID, $Mode);

if($Parser['success'] === NULL){
    if($Mode == "wart"){
        $ReservierungUserMeta = lade_user_meta($Reservierung['user']);
        $PromptTextWart = "Du bist im Begriff die &Uuml;bernahme von <a href='benutzermanagement_wart.php?user=".$Reservierung['user']."'>".$ReservierungUserMeta['vorname']." ".$ReservierungUserMeta['nachname']."</a> abzusagen. Es w&auml;re gut, wenn du schon eine Alternative dazu h&auml;ttest und ein Kommentar eintragen k&ouml;nntest;)<br>M&ouml;chtest du die &Uuml;bernahme absagen?";
        $HTML .= prompt_karte_generieren('absagen_wart', 'Absagen', 'wartwesen.php', 'Abbrechen', $PromptTextWart, TRUE, 'kommentar_wart');

    } else if ($Mode == "selbst"){

        $PromptTextSelbst = "Du bist im Begriff die von dir ausgemachte Schl&uuml;ssel&uuml;bernahme abzusagen. Wir k&ouml;nnen dir nicht garantieren, dass du danach noch eine Gelegenheit hast an einen Schl&uuml;ssel zu kommen! <br>M&ouml;chtest du die &Uuml;bernahme trotzdem absagen?";
        $HTML .= prompt_karte_generieren('absagen_selbst', 'Absagen', 'my_reservations.php', 'Abbrechen', $PromptTextSelbst, TRUE, 'kommentar_selbst');

    } else if ($Mode == "vorfahrer"){

        $ReservierungUserMeta = lade_user_meta($Reservierung['user']);
        $PromptTextVorfahrer = "Du bist im Begriff die &Uuml;bernahme des Kahnschl&uuml;ssels nach deiner Fahrt durch ".$ReservierungUserMeta['vorname']." ".$ReservierungUserMeta['nachname']." abzusagen. Wir respektieren dies nat&uuml;rlich und werden versuchen die Fahrt f&uuml;r die Gruppe trotzdem noch m&ouml;glich zu machen, auch wenn wir es nicht garantieren k&ouml;nnen.<br>M&ouml;chtest du die &Uuml;bernahme sicher absagen?";
        $HTML .= prompt_karte_generieren('absagen_vorfahrer', 'Absagen', 'my_reservations.php', 'Abbrechen', $PromptTextVorfahrer, TRUE, 'kommentar_vorfahrer');
    }

} else if (($Parser['success'] === FALSE) OR ($Parser['success'] === TRUE)) {
    if($Mode == "wart"){
        $HTML .= zurueck_karte_generieren($Parser['success'], $Parser['meldung'], 'wartwesen.php');
    } else if ($Mode == "selbst"){
        $HTML .= zurueck_karte_generieren($Parser['success'], $Parser['meldung'], 'my_reservations.php');
    } else if ($Mode == "vorfahrer"){
        $HTML .= zurueck_karte_generieren($Parser['success'], $Parser['meldung'], 'my_reservations.php');
    }

}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function parse_uebernahme_absagen($UebernahmeID, $Mode){

    $link = connect_db();
    $Antwort = array();

    if ((isset($_POST['absagen_wart'])) OR (isset($_POST['absagen_selbst'])) OR (isset($_POST['absagen_vorfahrer']))){

        $Uebernahme = lade_uebernahme($UebernahmeID);
        $Reservierung = lade_reservierung($Uebernahme['reservierung']);

        $DAUcounter = 0;
        $DAUerror = "";

        //Ist schon abgesagt
        if ($Uebernahme['storno_user'] != "0"){
            $DAUcounter++;
            $DAUerror .= "Die &Uuml;bernahme wurde inzwischen bereits storniert!<br>";
        }

        if($UebernahmeID == ""){
            $DAUcounter++;
            $DAUerror .= "Keine &Uuml;bernahme gew&auml;hlt!<br>";
        }

        if ($DAUcounter > 0){
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $DAUerror;
        } else {

            if ($Mode == "wart"){
                $Kommentar = $_POST['kommentar_wart'];
            } else if ($Mode == "selbst"){
                $Kommentar = $_POST['kommentar_selbst'];
            } else if($Mode == "vorfahrer"){
                $Kommentar = $_POST['kommentar_vorfahrer'];
            }

            if (uebernahme_stornieren($UebernahmeID, $Kommentar)){

                //Solle Übernahme innerhalb eines gewissen zeitfensters vor Resbeginn abgesagt worden sein, benachrichtigung an warte sofern nicht wartmode
                $Zeitgrenze = lade_xml_einstellung('kritischer-abstand-storno-vor-beginn');
                $BefehlZeit = "+ ".$Zeitgrenze." hours";
                $Befehltime = strtotime($BefehlZeit);

                if($Befehltime > strtotime($Reservierung['beginn'])){

                    if ($Mode != "wart"){
                        $AnfrageLadeAlleWaerte = "SELECT user FROM user_rollen WHERE recht = 'wart' AND storno_user = '0'";
                        $AbfrageLadeAlleWaerte = mysqli_query($link, $AnfrageLadeAlleWaerte);
                        $AnzahlLadeAlleWaerte = mysqli_num_rows($AbfrageLadeAlleWaerte);
                        for ($a =1; $a <= $AnzahlLadeAlleWaerte; $a++){

                            $Wart = mysqli_fetch_assoc($AbfrageLadeAlleWaerte);
                            $WartMeta = lade_user_meta($Wart['user']);

                            if($WartMeta['mail-kurzfristig-uebernahme-abgesagt'] == "1"){

                                $Vorlage = "warnung-wart-uebernahme-kurzfristig-abgesagt";
                                $TypAngabe = "".$Vorlage."-".$UebernahmeID."";
                                $Bausteine = array();
                                $reservuirungUserMeta = lade_user_meta($Reservierung['user']);

                                $Bausteine['vorname_wart'] = $WartMeta['vorname'];
                                $Bausteine['reservierung_nummer'] = $Reservierung['id'];
                                $Bausteine['reservierung_datum'] = date("d. m. Y", strtotime($Reservierung['beginn']));
                                $Bausteine['reservierung_beginn'] = date("G", strtotime($Reservierung['beginn']));
                                $Bausteine['reservierung_user'] = "".$reservuirungUserMeta['vorname']." ".$reservuirungUserMeta['nachname']."";

                                if ($Mode == "selbst"){
                                    $Bausteine['zustandekommen'] = "durch den User selbst";
                                } else if($Mode == "vorfahrer"){
                                    $Bausteine['zustandekommen'] = "durch die vorfahrende Gruppe";
                                }

                                if($Kommentar != ""){
                                    $Bausteine['kommentar'] = "Kommentar des Stornierenden: ".htmlentities($Kommentar)."";
                                } else {
                                    $Bausteine['kommentar'] = "Kein Kommentar des Stornierenden!";
                                }

                                mail_senden($Vorlage, $WartMeta['mail'], $Bausteine);

                            }
                        }
                    }

                    if ($Mode == "wart"){
                        $Erfolgsmeldung = "&Uuml;bernahme wurde erfolgreich gel&ouml;scht!";
                    } else if ($Mode == "selbst"){
                        $Erfolgsmeldung = "&Uuml;bernahme wurde erfolgreich gel&ouml;scht! Bitte versuche stattdessen z&uuml;gig eine Schl&uuml;ssel&uuml;bergabe auszumachen, solltest du noch fahren wollen.";
                    } else if($Mode == "vorfahrer"){
                        $Erfolgsmeldung = "Die &Uuml;bernahme wurde erfolgreich abgesagt!";
                    }

                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = $Erfolgsmeldung;

                } else {

                    if ($Mode == "wart"){
                        $Erfolgsmeldung = "&Uuml;bernahme wurde erfolgreich gel&ouml;scht!";
                    } else if ($Mode == "selbst"){
                        $Erfolgsmeldung = "&Uuml;bernahme wurde erfolgreich gel&ouml;scht!";
                    } else if($Mode == "vorfahrer"){
                        $Erfolgsmeldung = "Die &Uuml;bernahme wurde erfolgreich abgesagt!";
                    }

                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = $Erfolgsmeldung;
                }

            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Fehler beim stornieren der &Uuml;bergabe!";
            }
        }

    } else {
        $Antwort['success'] = NULL;
    }

    return $Antwort;
}