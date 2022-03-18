<?php
include_once "./ressources/ressourcen.php";

session_manager('ist_wart');
$Header = "Reservierungen verkn&uuml;pfen - " . lade_db_einstellung('site_name');
$Res1 = $_GET['res'];
$Res2 = $_GET['res2'];
$Parser = parser($Res1, $Res2);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Reservierungen verkn&uuml;pfen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Reservierungen verkn&uuml;pfen</h1>';
$HTML = section_builder($PageTitle);

if($Parser['success'] === NULL){
    $Reservierung1 = lade_reservierung($Res1);
    $Reservierung2 = lade_reservierung($Res2);
    $UserMetaRes1 = lade_user_meta($Reservierung1['user']);

    $TextPrompt = "Du bist im Begriff die Reservierungen #".$Res1." und #".$Res2." (".$UserMetaRes1['vorname']." ".$UserMetaRes1['nachname'].") zu verkn&uuml;pfen. Eine davon wird dabei storniert und die andere entsprechend erweitert - bitte &uuml;berpr&uuml;fe anschlie&szlig;end die Kostenkonfiguration, Schl&uuml;sselsituation und Zahlungsverkehr!";
    $HTML .= prompt_karte_generieren('action', 'Verkn&uuml;pfen', 'reservierungsmanagement.php', 'Abbrechen', $TextPrompt, FALSE, '');
} else if ($Parser['success'] === FALSE){
    $HTML .= zurueck_karte_generieren(FALSE, $Parser['meldung'], 'reservierungsmanagement.php');
} else if ($Parser['success'] === TRUE){
    $HTML .= zurueck_karte_generieren(TRUE, 'Vernk&uuml;pfung erfolgreich - bitte &uuml;berpr&uuml;fe die vorhin genannten Punkte!', 'reservierungsmanagement.php');
}

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function parser($Res1, $Res2){

    $Antwort = array();
    $DAUcounter1 = 0;
    $DAUerror1 = "";

    if($Res1 == $Res2){
        $DAUcounter1++;
        $DAUerror1 .= "Fehler - zwei identische Reservierungen in URL!<br>";
    } else {

        if(($Res1 == "") OR ($Res2 == "") OR (($Res1 == "") AND ($Res2 == ""))){
            $DAUcounter1++;
            $DAUerror1 .= "Nicht alle Reservierungsfelder in URL gesetzt!<br>";
        } else {
            $Reservierung1 = lade_reservierung($Res1);
            $Reservierung2 = lade_reservierung($Res2);
            if(($Reservierung1['storno_user'] != "0") OR ($Reservierung2['storno_user'] != "0")){
                $DAUcounter1++;
                $DAUerror1 .= "Eine der Reservierungen ist inzwischen storniert!<br>";
            }

            if ($Reservierung1['user'] != $Reservierung2['user']){
                $DAUcounter1++;
                $DAUerror1 .= "Reservierungen geh&ouml;ren nicht zum gleichen User!<br>";
            }
        }
    }

    if($DAUcounter1 > 0){

        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror1;

    } else {

        if(isset($_POST['action'])){

            $Differenz = stunden_differenz_berechnen($Reservierung2['beginn'], $Reservierung2['ende']);
            $Res2Storno = reservierung_stornieren($Res2, lade_user_id(), 'KEINE ANGST - Die Reservierung wird nur mit deiner direkt anschl&szlig;enden Reservierung verkn&uuml;pft:) Du kannst den Rest der Mail ignorieren!');

            if($Res2Storno['success'] === TRUE){

                if(strtotime($Reservierung1['beginn']) > strtotime($Reservierung2['beginn'])){
                    //Res 2 liegt davor
                    $Verschiebekommando = "-".$Differenz."";
                    $Antwort = reservierung_bearbeiten($Res1, $Verschiebekommando, '');

                } else if (strtotime($Reservierung1['beginn']) < strtotime($Reservierung2['beginn'])){
                    //Res 2 liegt danach
                    $Verschiebekommando = "+".$Differenz."";
                    $Antwort = reservierung_bearbeiten($Res1, '', $Verschiebekommando);
                }

            } else {
                $Antwort = $Res2Storno;
            }

        } else {
            $Antwort['success'] = NULL;
        }

    }

    return $Antwort;
}
?>