<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$link = connect_db();
#Parse Input
delete_site_parser();
add_website_bausteine_parser();
parse_change_items_rang();

#Generate content
# Page Title
$Header = "Webseite Editieren - " . lade_db_einstellung('site_name');
$PageTitle = '<h1>Webseite Editieren</h1>';
$HTML .= section_builder($PageTitle);

//
$PageParser = add_new_site_parser();
if($PageParser['success']!=null){
    $HTML .= section_builder(error_button_creator($PageParser['meldung'], 'error', ''));
}

# Load Subsites
$Anfrage = "SELECT * FROM homepage_sites WHERE delete_user = 0 ORDER BY name ASC";
$Abfrage = mysqli_query($link, $Anfrage);
$Anzahl = mysqli_num_rows($Abfrage);
$CollapsibleItems = '';
$ZeroRangCounter = 0;

for($x=1;$x<=$Anzahl;$x++){

    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    if($Ergebnis['menue_rang'] == intval(0)){$ZeroRangCounter++;}

    #Build Title Content
    $TitleHTML = $Ergebnis['menue_text'];
    parse_change_page_rang($TitleHTML, $Ergebnis['menue_rang']);
    parse_change_bausteine_rang($Ergebnis['name']);

    #Build Card Content
    $ContentHTML = generate_bausteine_view($Ergebnis['name']);
    $ContentHTML .= generate_baustein_adder($Ergebnis['name']);
    $ContentHTML .= generate_delete_site_page($Ergebnis['name']);
    $ContentHTML .= section_builder(generate_move_buttons_page_level($Anzahl, $ZeroRangCounter, $Ergebnis['menue_rang'], $Ergebnis['name']));

    #Build the Item
    $CollapsibleItems .= collapsible_item_builder($TitleHTML, $ContentHTML, 'pageview');

}

#Include Add Page functionality
$CollapsibleItems .= generate_collapsible_add_page_item();

#Wrap Collapsibles
$CollapsibleList = collapsible_builder($CollapsibleItems);
$Form = form_builder($CollapsibleList, '#', 'post', 'admin_edit_startpage_form');
$HTML .= section_builder($Form);
$HTML = container_builder($HTML, 'admin_edit_startpage_container', '');

# Output site
echo site_header($Header);
echo site_body($HTML);

function generate_bausteine_view($Seite){

    $link = connect_db();
    $BausteineHTML = "";

    # Load Subsites
    $Anfrage = "SELECT * FROM homepage_bausteine WHERE storno_user = 0 AND ort = '".$Seite."' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){
        $Header = "Bislang noch keine Bausteine hinzugefügt!";
        $BausteineHTML .= section_builder(collection_with_header_builder($Header, ''));
    } else {
        for ($x = 1; $x <= $Anzahl; $x++) {

            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $ReferenceDelete = "delete_website_baustein_".$Ergebnis['id']."";
            $Operators = form_button_builder($ReferenceDelete, 'Löschen', 'action', 'delete_forever', '');
            $Operators .= " ".generate_move_buttons_baustein_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Seite);

            $Header = "" . $Ergebnis['rang'] . " - " . $Ergebnis['typ'] . " - " . $Ergebnis['name'] . " ".$Operators."";
            $Items = generate_inhalte_views($Ergebnis['id']);

            $BausteineHTML .= section_builder(collection_with_header_builder($Header, $Items));

        }
    }

    return $BausteineHTML;
}

function generate_inhalte_views($BausteinID){

    $link = connect_db();
    $InhalteHTML = "";
    $Baustein = lade_baustein($BausteinID);

    # Load Content
    $Anfrage = "SELECT * FROM homepage_content WHERE storno_user = 0 AND id_baustein = '".$BausteinID."' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){
        if (($Baustein['typ'] == 'row_container') OR ($Baustein['typ'] == 'collection_container') OR ($Baustein['typ'] == 'collapsible_container')) {
            $ReferenceEdit = "./add_website_item.php?baustein=" . $BausteinID . "";
            $Header = "<a href='" . $ReferenceEdit . "'>Inhaltselement hinzufügen <i class='tiny material-icons'>edit</i></a> ";
            $InhalteHTML .= collection_item_builder($Header);
        } else {
            $Header = "Bislang noch keine Inhaltselemente hinzugefügt!";
            $InhalteHTML .= collection_item_builder($Header);
        }
    } else {
        for ($x=1;$x<=$Anzahl;$x++){

            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $ReferenceEdit = "./edit_website_item.php?item=".$Ergebnis['id']."";
            $ReferenceDelete = "./delete_website_item.php?item=".$Ergebnis['id']."";

            if($Baustein['typ'] == 'parallax_mit_text'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." - ".$Ergebnis['zweite_ueberschrift']." ".$Operators."";
            } elseif ($Baustein['typ'] == 'row_container'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> <a href='".$ReferenceDelete."'><i class='tiny material-icons'>delete_forever</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." ".$Operators."";
            } elseif ($Baustein['typ'] == 'html_container'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> <a href='".$ReferenceDelete."'><i class='tiny material-icons'>delete_forever</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." ".$Operators."";
            } elseif ($Baustein['typ'] == 'collection_container'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> <a href='".$ReferenceDelete."'><i class='tiny material-icons'>delete_forever</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." ".$Operators."";
            } elseif ($Baustein['typ'] == 'collapsible_container'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> <a href='".$ReferenceDelete."'><i class='tiny material-icons'>delete_forever</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." ".$Operators."";
            } elseif ($Baustein['typ'] == 'kostenstaffel_container'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> <a href='".$ReferenceDelete."'><i class='tiny material-icons'>delete_forever</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." ".$Operators."";
            } elseif ($Baustein['typ'] == 'slider_mit_ueberschrift'){
                $Operators = "<a href='".$ReferenceEdit."'><i class='tiny material-icons'>edit</i></a> <a href='".$ReferenceDelete."'><i class='tiny material-icons'>delete_forever</i></a> ";
                $Operators .= generate_move_buttons_item_level($Anzahl, $Ergebnis['id'], $Ergebnis['rang'], $Ergebnis['id_baustein']);
                $Header = "".$Ergebnis['rang']." - ".$Ergebnis['ueberschrift']." ".$Operators."";
            }

            $InhalteHTML .= collection_item_builder($Header);

        }

        if ($Baustein['typ'] == 'row_container') {
            if ($Anzahl < lade_db_einstellung('max_items_row_container')) {
                $ReferenceEdit = "./add_website_item.php?baustein=" . $BausteinID . "";
                $Header = "<a href='" . $ReferenceEdit . "'>Inhaltselement hinzufügen <i class='tiny material-icons'>edit</i></a> ";
                $InhalteHTML .= collection_item_builder($Header);
            }
        } elseif (($Baustein['typ'] == 'collection_container') OR ($Baustein['typ'] == 'collapsible_container')){
                $ReferenceEdit = "./add_website_item.php?baustein=" . $BausteinID . "";
                $Header = "<a href='" . $ReferenceEdit . "'>Inhaltselement hinzufügen <i class='tiny material-icons'>edit</i></a> ";
                $InhalteHTML .= collection_item_builder($Header);
        } elseif (($Baustein['typ'] == 'slider_mit_ueberschrift')){
            $ReferenceEdit = "./add_website_item.php?baustein=" . $BausteinID . "";
            $Header = "<a href='" . $ReferenceEdit . "'>Inhaltselement hinzufügen <i class='tiny material-icons'>edit</i></a> ";
            $InhalteHTML .= collection_item_builder($Header);
        }
    }

    return $InhalteHTML;

}

function generate_bausteine_dropdown_menue($ItemName, $Label, $SpecialMode){

    $HTML = "<div class='input-field' ".$SpecialMode.">";
    $HTML .= "<select id='".$ItemName."' name='".$ItemName."'>";

    $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
    $HTML .= "<option value='row_container'>row_container</option>";
    $HTML .= "<option value='parallax_mit_text'>parallax_mit_text</option>";
    $HTML .= "<option value='slider_mit_ueberschrift'>slider_mit_ueberschrift</option>";
    $HTML .= "<option value='html_container'>html_container</option>";
    $HTML .= "<option value='collapsible_container'>collapsible_container</option>";
    $HTML .= "<option value='collection_container'>collection_container</option>";
    $HTML .= "<option value='kalender_container'>kalender_container</option>";
    $HTML .= "<option value='kostenstaffel_container'>kostenstaffel_container</option>";
    $HTML .= "</select>";

    if ($Label!=''){
        $HTML .= "<label>".$Label."</label>";
    }

    $HTML .= "</div>";

    return $HTML;
}

function generate_baustein_adder($SiteName){

    $NameNewBaustein = "name_new_baustein_".$SiteName."";
    $TypeNewBaustein = "type_new_baustein_".$SiteName."";
    $NameAddButtonBaustein = "add_new_baustein_".$SiteName."";

    $HTML = row_builder(divider_builder());
    $HTML .= row_builder('<h4>Baustein hinzufügen</h4>');
    $HTML .= row_builder(generate_bausteine_dropdown_menue($TypeNewBaustein, 'Baustein wählen', ''));
    $HTML .= row_builder(form_string_item($NameNewBaustein, 'gib dem Element einen Namen', ''));
    $HTML .= row_builder(form_button_builder($NameAddButtonBaustein, 'Hinzufügen', 'action', 'add_box', ''));

    $HTML = section_builder($HTML);

    return $HTML;
}

function generate_delete_site_page($SiteName){

    $NameDELETEsiteButtonBaustein = "delete_".$SiteName."";

    $HTML = row_builder(divider_builder());
    $HTML .= row_builder('<h4>Seite löschen</h4>');
    $HTML .= row_builder(form_button_builder($NameDELETEsiteButtonBaustein, 'Löschen', 'action', 'delete_forever'));
    $HTML = section_builder($HTML);

    return $HTML;
}

function generate_move_buttons_page_level($AnzahlGesamtSeiten, $ZeroRangCounter, $AktuellerRang, $AktuellerName){

    if ($AktuellerRang == 0){
        #This is a site not to be moved in relevance
        return '';
    } else {

        #NUmber of ranked sites
        $NumberRankedSites = $AnzahlGesamtSeiten - $ZeroRangCounter;

        #We are in a site with a rank
        if ($NumberRankedSites == 1){
            #This site cannot be moved as it is the only one with a rank
            return '';
        } elseif ($NumberRankedSites > 1){

            $Output = row_builder(divider_builder());
            $Output .= row_builder('<h4>Rang verschieben</h4>');
            $HTML = '';
            $ButtonDownName = "./decrease_page_rank_".$AktuellerName."";
            $ButtonUpName = "./increase_page_rank_".$AktuellerName."";
            $DownToo = false;

            #Can be moved down
            if($AktuellerRang < $NumberRankedSites){
                $HTML .= form_button_builder($ButtonDownName, 'Rang senken', 'action', 'arrow_downward', 'col s5');
                $DownToo = True;
            }

            #Can be moved up
            if($AktuellerRang > 1){
                if($DownToo){
                    $HTML .= form_button_builder($ButtonUpName, 'Rang erhöhen', 'action', 'arrow_upward', 'col s5 offset-s1');
                    } else {
                    $HTML .= form_button_builder($ButtonUpName, 'Rang erhöhen', 'action', 'arrow_upward', 'col s5');
                }
            }

            $Output .= row_builder($HTML);

            return $Output;
        }
    }
}

function generate_move_buttons_baustein_level($AnzahlGesamtBausteine, $AktuellerBausteinID, $AktuellerBausteinRang, $AktuelleSeiteName){

    #We are in a site with a rank
    if ($AnzahlGesamtBausteine == 1){
        #This site cannot be moved as it is the only one with a rank
        return '';
    } elseif ($AnzahlGesamtBausteine > 1){

        $HTML = '';

        #Can be moved down
        if($AktuellerBausteinRang < $AnzahlGesamtBausteine){
            $ButtonDownName = "increase_baustein_".$AktuellerBausteinID."";
            $HTML .= form_button_builder($ButtonDownName, 'Nach unten', 'action', 'arrow_downward', '');

        }

        #Can be moved up
        if($AktuellerBausteinRang > 1){
            $ButtonUpName = "decrease_baustein_".$AktuellerBausteinID."";
            $HTML .= form_button_builder($ButtonUpName, 'Nach oben', 'action', 'arrow_upward', '');
        }

        return $HTML;
    }
}

function generate_move_buttons_item_level($AnzahlGesamtItems, $AktuellerItemID, $AktuellerItemRang, $AktuellerBaustein){

    #We are in a site with a rank
    if ($AnzahlGesamtItems == 1){
        #This site cannot be moved as it is the only one with a rank
        return '';
    } elseif ($AnzahlGesamtItems > 1){

        $HTML = '';

        #Can be moved down
        if($AktuellerItemRang < $AnzahlGesamtItems){
            $ButtonDownName = "increase_item_rank_item_".$AktuellerItemID."";
            $HTML .= form_button_builder($ButtonDownName, 'Nach unten', 'action', 'arrow_downward', '');
        }

        #Can be moved up
        if($AktuellerItemRang > 1){
            $ButtonUpName = "decrease_item_rank_item_".$AktuellerItemID."";
            $HTML .= form_button_builder($ButtonUpName, 'Nach oben', 'action', 'arrow_upward', '');
        }

        return $HTML;
    }
}

function generate_collapsible_add_page_item(){

    $TitleHTML = "Neue Seite anlegen";
    $Icon = "add_box";

    # Form Table
    $TableHTML = table_form_string_item('Seitenname', 'new_site_name', '', false);
    $TableHTML .= table_form_string_item('Titel der Seite', 'new_site_title', '', false);
    $TableHTML .= table_form_swich_item('Sichtbarkeit im Hauptmenü', 'new_site_menue_visibility', 'unsichtbar', 'sichtbar', '', '');
    $TableButtons = table_header_builder('');
    $TableButtons .= table_data_builder(form_button_builder('add_new_site', 'Neue Seite anlegen', 'action', 'add_box', ''));
    $TableHTML .= table_row_builder($TableButtons);
    $ContentHTML = table_builder($TableHTML);

    return collapsible_item_builder($TitleHTML, $ContentHTML, $Icon);
}

function add_website_bausteine_parser(){

    $link = connect_db();
    $Action = null;

    # Load Subsites
    $Anfrage = "SELECT * FROM homepage_sites WHERE delete_user = 0 ORDER BY menue_rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for($x=1;$x<=$Anzahl;$x++) {

        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $SubsiteName = $Ergebnis['name'];
        $ActionButtonName = "add_new_baustein_".$SubsiteName."";

        if (isset($_POST[$ActionButtonName])){

            $NewBausteinType = "type_new_baustein_".$SubsiteName."";
            $NewBausteinName = "name_new_baustein_".$SubsiteName."";

            $Action = startseitenelement_anlegen($SubsiteName, $_POST[$NewBausteinType], $_POST[$NewBausteinName]);
        }
    }

    return $Action;
}

function parse_change_page_rang($PageName, $Rang){

    $ButtonDownName = "./decrease_page_rank_".$PageName."";
    $ButtonUpName = "./increase_page_rank_".$PageName."";

    if(isset($_POST[$ButtonDownName])){
        return decrease_page_rank_parser($Rang, $PageName);
    }
    if(isset($_POST[$ButtonUpName])){
        return increase_page_rank_parse($Rang, $PageName);
    }

}

function parse_change_bausteine_rang($PageName){

    $link = connect_db();
    $Anfrage = "SELECT id,ort FROM homepage_bausteine";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $Baustein = $Ergebnis['id'];
        $ButtonDownName = "increase_baustein_".$Baustein."";
        $ButtonUpName = "decrease_baustein_".$Baustein."";
        $ReferenceDelete = "delete_website_baustein_".$Baustein."";
        if($Ergebnis['ort']==$PageName){
            if(isset($_POST[$ButtonDownName])){
                return increase_baustein_rank_parse($Baustein, $PageName);
            }
            if(isset($_POST[$ButtonUpName])){
                return decrease_baustein_rank_parser($Baustein, $PageName);
            }
            if(isset($_POST[$ReferenceDelete])){
                return delete_website_baustein_parser($Baustein);
            }
        }
    }
}

function parse_change_items_rang(){
    $link = connect_db();
    $Anfrage = "SELECT id FROM homepage_content";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    for($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $Item = $Ergebnis['id'];
        $ButtonUpName = "decrease_item_rank_item_".$Item."";
        $ButtonDownName = "increase_item_rank_item_".$Item."";
        if(isset($_POST[$ButtonUpName])){
            return decrease_item_rank_parser($Item);
        }
        if(isset($_POST[$ButtonDownName])){
            return increase_item_rank_parse($Item);
        }
    }
}

function add_new_site_parser(){

    if(isset($_POST['add_new_site'])){

        $link = connect_db();
        $DAUcounter = 0;
        $DAUmessage = '';

        if(empty($_POST['new_site_title'])){
            $DAUcounter++;
            $DAUmessage .= 'Bitte gebe einen Seitentitel an!<br>';
        }

        if(empty($_POST['new_site_name'])){
            $DAUcounter++;
            $DAUmessage .= 'Bitte gebe einen Seitennamen an!<br>';
        } else {

            if (!($stmt = $link->prepare("SELECT id FROM homepage_sites WHERE name = ? AND delete_user = 0"))) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            if (!$stmt->bind_param("s", $_POST['new_site_name'])) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            if (!$stmt->execute()) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            $res = $stmt->get_result();
            if(mysqli_num_rows($res)>0){
                $DAUcounter++;
                $DAUmessage .= 'Eine Seite mit diesem Seitennamen existiert bereits!<br>';
            }
        }

        if($DAUcounter>0){
            $Antwort['success']=false;
            $Antwort['meldung']=$DAUmessage;
        }else{

            $AnfrageListSites = "SELECT id FROM homepage_sites WHERE menue_rang > 0 AND delete_user = 0";
            $AbfrageListSites = mysqli_query($link,$AnfrageListSites);
            $AktZahlRang = mysqli_num_rows($AbfrageListSites);
            $RangNewPage = $AktZahlRang+1;
            if(isset($_POST['new_site_menue_visibility'])){
                $Visibility = 'on';
            } else {
                $Visibility = 'off';
                $RangNewPage = 0;
            }

            if (!($stmt = $link->prepare("INSERT INTO homepage_sites (name, menue_text, menue_rang, show_in_main_menue) VALUES (?,?,?,?)"))) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            if (!$stmt->bind_param("ssis", $_POST['new_site_name'], $_POST['new_site_title'], $RangNewPage, $Visibility)) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            if (!$stmt->execute()) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            } else {
                $Antwort['success']=true;
            }
        }
    } else {
        $Antwort['success']=null;
    }

    return $Antwort;
}

function delete_site_parser(){

    $link = connect_db();
    $Action = null;

    # Load Subsites
    $Anfrage = "SELECT * FROM homepage_sites WHERE delete_user = 0 ORDER BY menue_rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for($x=1;$x<=$Anzahl;$x++) {

        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $SubsiteName = $Ergebnis['name'];
        $ActionButtonName = "delete_".$SubsiteName."";

        if (isset($_POST[$ActionButtonName])){

            if (!($stmt = $link->prepare("UPDATE homepage_sites SET delete_user = ?, delete_time = ? WHERE name = ?"))) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            if (!$stmt->bind_param("iss", lade_user_id(), timestamp(), $SubsiteName)) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            }
            if (!$stmt->execute()) {
                $Antwort['success'] = false;
                $Antwort['meldung']='Datenbankfehler';
            } else {
                $Antwort['success']=true;
                $Antwort['meldung']='Seite erfolgreich gelöscht!';
            }
        }
    }

    return $Antwort;

}
?>