<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Benutzerrolle l&ouml;schen - " . lade_db_einstellung('site_name');

//DAU Typ & ID abfangen
$Rolle = $_GET['rolle'];
$User = $_GET['user'];

//PARSER
$Parser = parser($Rolle, $User);
#var_dump($Parser);

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Benutzerrolle l&ouml;schen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Benutzerrolle l&ouml;schen</h1>';
$HTML = section_builder($PageTitle);

if($Parser==null){
    $UserMeta = lade_user_meta($User);
    $HTML .= section_builder(prompt_karte_generieren('loeschen', 'Löschen', './benutzermanagement_wart.php', 'Abbrechen', '<h5 class="center-align">Möchtest du die Benutzerrolle <b>'.$Rolle.'</b> beim User<b> '.$UserMeta['vorname'].' '.$UserMeta['nachname'].'</b> löschen?</h5>', false, ''));
} elseif ($Parser==true) {
    $HTML .= section_builder(zurueck_karte_generieren(true, 'Benutzerrolle erfolgreich gelöscht!', './benutzermanagement_wart.php'));
} elseif ($Parser==false) {
    $HTML .= section_builder(zurueck_karte_generieren(false, 'Fehler beim Löschen der Benutzerrolle!', './benutzermanagement_wart.php'));
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function parser($Rolle, $User){

    #var_dump($Rolle);
    if(isset($_POST['loeschen'])){
        
        if($Rolle=='ist_wart'){
            return wartrolle_loeschen($User);
        } elseif($Rolle=='ist_admin'){
            return adminrolle_loeschen($User);
        } elseif($Rolle=='ist_kasse'){
            return kassenrolle_loeschen($User);
        } else {
            $Nutzergruppen = lade_alle_nutzgruppen();
            foreach($Nutzergruppen as $Nutzergruppe){
                if($Nutzergruppe['name']==$Rolle){
                    return delete_user_meta($User, $Nutzergruppe['name'], 'true');
                }
            }
        }
    } else {
        return null;
    }
}