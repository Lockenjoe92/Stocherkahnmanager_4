<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Terminangebot l&ouml;schen - " . lade_db_einstellung('site_name');
$IDangebot = $_GET['id'];
$AngebotLoeschenParser = parser_angebot_loeschen($IDangebot);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Terminangebot l&ouml;schen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Terminangebot l&ouml;schen</h1>';
$HTML .= section_builder($PageTitle);

if ($AngebotLoeschenParser['success'] === NULL){
    $HTML .= infos_section($IDangebot);
    $HTML .= prompt_karte_generieren('action', 'L&ouml;schen', 'termine.php', 'Abbrechen', 'M&ouml;chtest du das Terminangebot l&ouml;schen? Bereits entstandene &Uuml;bergaben werden hiervon nicht betroffen!', FALSE, '');
} else if ($AngebotLoeschenParser['success'] === FALSE){
    $HTML .= zurueck_karte_generieren(FALSE, $AngebotLoeschenParser['meldung'], 'termine.php');
} else if ($AngebotLoeschenParser['success'] === TRUE){
    $HTML .= zurueck_karte_generieren(TRUE, 'Terminangebot wurde erfolgreich gel&ouml;scht!', 'termine.php');
}

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function parser_angebot_loeschen($IDangebot){

    $DAUcounter = 0;
    $DAUerror = "";
    $Antwort = array();
    $Angebot = lade_terminangebot($IDangebot);

    //Terminangebot bereits gelöscht?
    if($Angebot['storno_user'] === "1"){
        $DAUcounter++;
        $DAUerror .= "Das Angebot wurde bereits storniert!<br>";
    }

    //Keine ID in URL
    if($IDangebot === ""){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine zu l&ouml;schende &Uuml;bergabe ausgew&auml;hlt!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){
        $link = connect_db();
        if(isset($_POST['action'])){
            $Anfrage = "UPDATE terminangebote SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = '$IDangebot'";
            if(mysqli_query($link, $Anfrage)){
                $Antwort['success'] = TRUE;
                $Antwort['meldung'] = "Terminangebot erfolgreich storniert!";
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Datenbankfehler!";
            }
        } else if (!isset($_POST['action'])) {
            $Antwort['success'] = NULL;
        }
    }

    return $Antwort;
}


?>