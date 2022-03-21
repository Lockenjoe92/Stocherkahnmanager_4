<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 13.06.18
 * Time: 15:24
 */

function startseite_inhalt_home(){

    $link = connect_db();
    $Tab = $_GET['tab'];
    if (!isset($Tab)){$Tab='index';}

    #Lade alle Websiteteile
    if (!($stmt = $link->prepare("SELECT * FROM homepage_bausteine WHERE ort = ? AND storno_user = '0' ORDER BY rang ASC"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("s",$Tab)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Anzahl = mysqli_num_rows($res);

    #Iteriere über die Seiteninhalte
    $HTML = '';
    if($Anzahl == 0){
        $HTML .= container_builder(section_builder('<h2>Hier entsteht eine neue Seite!</h2>'));
    } elseif($Anzahl > 0) {
        $i = 1;
        while ($i <= $Anzahl){
            # Lade Informationen
            $Ergebnis = mysqli_fetch_assoc($res);
            $HTML .= generiere_startseite_content($Ergebnis);
            $i++;
        }
    }

    return $HTML;
}

function site_exists($Sitename){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT * FROM homepage_sites WHERE name = ? AND delete_user = '0'"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("s",$Sitename)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Anzahl = mysqli_num_rows($res);
    if($Anzahl==1){
        return true;
    } else {
        return false;
    }
}

function generiere_startseite_content($Baustein){

    $HTML = '';

    #Unterscheidung je nach Typ:
    if($Baustein['typ'] == 'parallax_mit_text'){
        $HTML .= parallax_mit_text_generieren($Baustein['id']);
    } elseif($Baustein['typ'] == 'row_container'){
        $HTML .= row_container_generieren($Baustein['id']);
    } elseif($Baustein['typ'] == 'html_container'){
        $HTML .= html_container_generieren($Baustein['id']);
    } elseif($Baustein['typ'] == 'kalender_container'){
        $HTML .= kalender_container_generieren('startpage');
    } elseif ($Baustein['typ'] == 'kostenstaffel_container'){
        $HTML .= kostenstaffel_container_generieren($Baustein['id']);
    } elseif ($Baustein['typ'] == 'collapsible_container'){
        $HTML .= collapsible_container_generieren($Baustein['id']);
    } elseif ($Baustein['typ'] == 'collection_container'){
        $HTML .= collection_container_generieren($Baustein['id']);
    } elseif ($Baustein['typ'] == 'slider_mit_ueberschrift'){
        $HTML .= slider_mit_ueberschrift_container_generieren($Baustein['id']);
    }

    return $HTML;
}

function kalender_container_generieren($Seitenmodus){

    $HTML = container_builder(kalender_mobil($Seitenmodus), 'kalender_mobil_container', 'hide-on-med-and-up');
    $HTML .= container_builder(kalender_gross($Seitenmodus), 'kalender_gross_container', 'hide-on-small-and-down');

    return $HTML;

}

function kostenstaffel_container_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
         return 'Aktuell kein Inhalt auffindbar!';
    } else {
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $Titel = $Ergebnis['ueberschrift'];
        $HTMLcontent = $Ergebnis['html_content'];
        $PanelColor = $Ergebnis['zweite_ueberschrift_farbe'];

        $HTML = '<div class="card-panel '.$PanelColor.'">';
        $Nutzergruppen = lade_alle_nutzgruppen();
        $TableHeader = table_header_builder('');
        $MaxAnzahlStundenRes = lade_xml_einstellung('max-dauer-einer-reservierung');
        $Stundenarray = array();
        foreach ($Nutzergruppen as $Nutzergruppe){
            if($Nutzergruppe['visible_for_user'] == 'true'){
                $TableHeader .= table_header_builder($Nutzergruppe['name']);
                $NutzergruppeKostenarray = array();
                for($a=1;$a<=$MaxAnzahlStundenRes;$a++){
                    $Schluessel = 'kosten_'.$a.'_h';
                    $NutzergruppeKostenarray[$a] = lade_nutzergruppe_meta($Nutzergruppe['id'], $Schluessel);
                }
                array_push($Stundenarray, $NutzergruppeKostenarray);
            }
        }

        ## tabelle Body berechnen
        $TableBody='';
        for($b=1;$b<=$MaxAnzahlStundenRes;$b++){
            if($b==1){
                $Stundenerklaerung = '1 Stunde';
            } else {
                $Stundenerklaerung = $b.' Stunden';
            }

            $Staffelwerte = '';
            foreach ($Stundenarray as $NutzergruppeStaffel) {
                $Staffelwerte .= table_data_builder($NutzergruppeStaffel[$b].'&euro;');
            }

            $TableBody .= table_row_builder(table_data_builder($Stundenerklaerung).$Staffelwerte);
        }

        $TableHTML = $TableHeader;
        $TableHTML .= $TableBody;
        $HTML .= "<h3 class='center-align'>".$Titel."</h3>";
        $HTML .= section_builder(table_builder($TableHTML));
        $HTML .= section_builder($HTMLcontent);
        $HTML .= "</div>";
        return container_builder($HTML, 'kostenstaffel', '');
    }
}

function slider_mit_ueberschrift_container_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
        $Content = 'Kein Inhalt auffindbar!';
    } else {

        $HTML = '<div class="slider">';
        $HTML .= '<ul class="slides">';

        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);

            $HTML .= '<li>';
            $HTML .= '<img class="responsive-img" src="'.$Ergebnis['uri_bild'].'">';
            $HTML .= '<div class="caption center-align">';
            $HTML .= '<h3 class="'.$Ergebnis['ueberschrift_farbe'].'">'.$Ergebnis['ueberschrift'].'</h3>';
            $HTML .= '<h5 class="'.$Ergebnis['zweite_ueberschrift_farbe'].'">'.$Ergebnis['zweite_ueberschrift'].'</h5>';
            $HTML .= '</div>';
            $HTML .= '</li>';
        }

        $HTML .= '</ul>';
        $HTML .= '</div>';

        $Content = section_builder($HTML);
        $Container = container_builder($Content);
    }

    return $Container;
}

function html_container_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
        $Content = 'Kein Inhalt auffindbar!';
    } else {
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        $HTML = $Ergebnis['html_content'];
        $Content = section_builder($HTML);
        $Container = container_builder($Content);
    }

    return $Container;
}

function collapsible_container_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
        $Content = 'Kein Inhalt auffindbar!';
    } else {

        $Items = '';
        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $title = $Ergebnis['ueberschrift'];
            $Content =$Ergebnis['html_content'];
            $Icon = $Ergebnis['icon'];
            $IconColor = $Ergebnis['icon_farbe'];
            $Items .= collapsible_item_builder($title, $Content, $Icon, $IconColor);
        }

        $HTML = collapsible_builder($Items);
        $Content = section_builder($HTML);
    }
    $Container = container_builder($Content);
    return $Container;
}

function collection_container_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
        $Content = 'Kein Inhalt auffindbar!';
    } else {

        $Items = '';
        for($a=1;$a<=$Anzahl;$a++){
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $title = $Ergebnis['ueberschrift'];
            $Content = "<h3>".$title."</h3><p>".$Ergebnis['html_content']."</p>";
            $Items .= collection_item_builder($Content);
        }

        $HTML = collection_builder($Items);
        $Content = section_builder($HTML);
    }
    $Container = container_builder($Content);
    return $Container;
}

function parallax_mit_text_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
        $Content = 'Kein Inhalt auffindbar!';
    } else {

        # Daten laden
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Content generieren
        $Ueberschrift = "<br><br><h1 class='header center ".$Ergebnis['ueberschrift_farbe']."'>" . html_entity_decode($Ergebnis['ueberschrift'],ENT_QUOTES | ENT_IGNORE, "UTF-8") . "</h1>";

        if ($Ergebnis['zweite_ueberschrift'] != '') {
            $Ueberschrift2 = "<div class='row center'><h5 class='header col s12 ".$Ergebnis['zweite_ueberschrift_farbe']."'>" . html_entity_decode($Ergebnis['zweite_ueberschrift'], ENT_QUOTES | ENT_IGNORE, "UTF-8") . "</h5></div>";
        } else {
            $Ueberschrift2 = '';
        }

        if ($Ergebnis['html_content'] != '') {
            $HTML = "<div class='row center'>" . html_entity_decode($Ergebnis['html_content'], ENT_QUOTES | ENT_IGNORE, "UTF-8") . "</div><br><br>";
        } else {
            $HTML = '';
        }

        $Content = ($Ueberschrift . $Ueberschrift2 . $HTML);
        $ContainerContent = container_builder($Content);
        $SectionContainerContent = section_builder($ContainerContent, '', 'no-pad-bot');

        #Bild
        $BildHTML = '<img class="responsive-img" src="' . $Ergebnis['uri_bild'] . '" alt="startseite background img">';
        $Bild = parallax_content_builder($BildHTML, '', '');

        $Content = parallax_container(($SectionContainerContent . $Bild), 'index-banner', '');

    }

    return $Content;
}

function row_container_generieren($BausteinID){

    $link = connect_db();

    #Lade den content
    $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = '".$BausteinID."' AND storno_user = '0' ORDER BY rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    #Debug
    if ($Anzahl == 0){
        $Content = 'Aktuell kein Inhalt auffindbar!';
    } else {

        $RowContent = '<div class="row">';
        $a = 1;
        $BreiteRowTeile = 12/$Anzahl;
        while ($a<=$Anzahl){

            # Daten laden
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $RowContent .= '<div class="col s12 m'.$BreiteRowTeile.'"><div class="icon-block"><h2 class="center '.$Ergebnis['icon_farbe'].'"><i class="material-icons">'.$Ergebnis['icon'].'</i></h2><h5 class="center">'.html_entity_decode($Ergebnis['ueberschrift'], ENT_QUOTES | ENT_IGNORE, "UTF-8").'</h5><p class="center-align">'.html_entity_decode($Ergebnis['html_content'], ENT_QUOTES | ENT_IGNORE, "UTF-8").'</p></div></div>';
            $a++;
        }
        $RowContent .= '</div>';

        $RowSection = section_builder($RowContent, '', '');
        $Content = container_builder($RowSection, '', '');

    }

    return $Content;

}

function startseitenelement_anlegen($Ort, $Typ, $Name){

    $link = connect_db();
    $errorcount = 0;
    $errorstr = '';

    #DAU-Check
    if(empty($Ort)){
        $errorcount++;
        $errorstr .= 'Kein Ort f&uuml;r das Object angegeben!<br>';
    }
    if (empty($Typ)){
        $errorcount++;
        $errorstr .= 'Kein Typ f&uuml;r das Object angegeben!<br>';
    }
    if (empty($Name)){
        $errorcount++;
        $errorstr .= 'Kein Name f&uuml;r das Object angegeben!<br>';
    }
    # Check ob Objekt mit gleichem Namen schon existiert
    if($errorcount == 0){
        $Anfrage = 'SELECT id FROM homepage_bausteine WHERE name = "'.$Name.'" AND ort = "'.$Ort.'" AND storno_user = "0"';
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);
        if($Anzahl>0){
            $errorcount++;
            $errorstr .= 'Ein Object mit dem gleichen Namen existiert bereits auf dieser Seite!<br>';
        }
    }

    #Catch Errors
    if($errorcount>0){
        $Antwort['erfolg'] = false;
        $Antwort['meldung'] = $errorstr;
    } else {

        #Anzahl Objekte vorher bestimmen
        $Anfrage2 = "SELECT id FROM homepage_bausteine WHERE ort = '".$Ort."' AND storno_user = '0'";
        $Abfrage2 = mysqli_query($link, $Anfrage2);
        $AnzahlBisherigerObjekte = mysqli_num_rows($Abfrage2);

        #Eintragen
        $Rang = $AnzahlBisherigerObjekte + 1;
        $Timestamp = timestamp();
        $LadeUserID = lade_user_id();
        $Anfrage3 = "INSERT INTO homepage_bausteine (ort, typ, rang, name, angelegt_am, angelegt_von) VALUES ('".$Ort."', '".$Typ."', '".$Rang."', '".$Name."', '".$Timestamp."', '".$LadeUserID."')";
        $Abfrage3 = mysqli_query($link, $Anfrage3);

        #Überprüfen ob es geklappt hat
        if($Abfrage3){
            $Antwort['erfolg'] = true;
            $Anfrage4 = "SELECT * FROM homepage_bausteine WHERE angelegt_am = '".$Timestamp."' AND angelegt_von = ".$LadeUserID." AND storno_user = 0";
            $Abfrage4 = mysqli_query($link, $Anfrage4);
            $Ergebnis4 = mysqli_fetch_assoc($Abfrage4);
            if($Typ == "parallax_mit_text"){
                startseiteninhalt_einfuegen($Ergebnis4['id'], 'Neues Element', '', 'teal-text text-lighten-2', 'light', '', '', '', '');
            }elseif($Typ == "html_container"){
                startseiteninhalt_einfuegen($Ergebnis4['id'], 'Neues Element', '', '', '', '<h3>Hello World!</h3>', '', '', '');
            }elseif($Typ == "collapsible_container"){
                startseiteninhalt_einfuegen($Ergebnis4['id'], 'Neues Element', '', '', '', '<h3>Hello World!</h3>', '', 'add_new', '');
            }elseif($Typ == "collection_container"){
                startseiteninhalt_einfuegen($Ergebnis4['id'], 'Neues Element', '', '', '', '<h3>Hello World!</h3>', '', '', '');
            }elseif($Typ == "kostenstaffel_container"){
                startseiteninhalt_einfuegen($Ergebnis4['id'], 'Aktuelle Preisstaffelung', '', '', 'amber z-depth-3', '<h3>Hello World!</h3>', '', '', '');
            }elseif($Typ == "slider_mit_ueberschrift"){
                startseiteninhalt_einfuegen($Ergebnis4['id'], 'Neues Bild mit Überschrift', '', '', '', '', '', '', '');
            }
        } else {
            $Antwort['erfolg'] = false;
            $Antwort['meldung'] = 'Fehler beim Eintragen des Bausteins:/';
        }
    }

    return $Antwort;
}

function startseiteninhalt_einfuegen($IDbaustein, $titel, $titel2, $titelColor, $titel2Color, $html, $uri_bild, $icon, $iconColor){

    $link = connect_db();
    $errorcount = 0;
    $errorstr = '';

    #DAU-Check
    if(empty($IDbaustein)){
        $errorcount++;
        $errorstr .= 'Kein Seitenelement angegeben!<br>';
    }

    # Check ob noch Platz für weiteres Objekt ist
    if($errorcount == 0){

        #Lade Informationen zum Baustein
        $Baustein = lade_seitenelement($IDbaustein);

        #Lade bisherige Inhalte
        $Anfrage = 'SELECT id FROM homepage_content WHERE id_baustein = "'.$IDbaustein.'" AND storno_user = "0"';
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if($Baustein['typ'] == 'row_container'){
            if($Anzahl>=lade_xml_einstellung('max_items_row_container', 'global')){
                $errorcount++;
                $errorstr .= 'Du kannst in diesem Element keine weiteren Inhalte hinzuf&uuml;gen!<br>';
            }
        }

        if($Baustein['typ'] == 'parallax_mit_text'){
            if($Anzahl>=1){
                $errorcount++;
                $errorstr .= 'Du kannst diesem Element keine weiteren Inhalte hinzuf&uuml;gen!<br>';
            }
        }

        if($Baustein['typ'] == 'parallax_ohne_text'){
            if($Anzahl>=1){
                $errorcount++;
                $errorstr .= 'Du kannst diesem Element keine weiteren Inhalte hinzuf&uuml;gen!<br>';
            }
        }

        if($Baustein['typ'] == 'slider_mit_ueberschrift_container_generieren'){
            if($Anzahl>=4){
                $errorcount++;
                $errorstr .= 'Du kannst diesem Element keine weiteren Inhalte hinzuf&uuml;gen!<br>';
            }
        }

    }

    #Catch Errors
    if($errorcount>0){
        $Antwort['erfolg'] = false;
        $Antwort['meldung'] = $errorstr;
    } else {
        #Eintragen
        $Rang = $Anzahl + 1;
        $Anfrage2 = "INSERT INTO homepage_content (id_baustein, rang, ueberschrift, zweite_ueberschrift, ueberschrift_farbe, zweite_ueberschrift_farbe, html_content, uri_bild, icon, icon_farbe, angelegt_am, angelegt_von, storno_user, storno_time) VALUES ('".$IDbaustein."', '".$Rang."', '".htmlentities($titel)."', '".htmlentities($titel2)."', '".htmlentities($titelColor)."', '".htmlentities($titel2Color)."', '".htmlentities($html)."', '".$uri_bild."', '".$icon."', '".$iconColor."', '".timestamp()."', '".lade_user_id()."', '0', '0000-00-00 00:00:00')";
        $Abfrage2 = mysqli_query($link, $Anfrage2);

        #Überprüfen ob es geklappt hat
        if($Abfrage2){
            $Antwort['erfolg'] = true;
        } else {
            $Antwort['erfolg'] = false;
            $Antwort['meldung'] = 'Fehler beim Eintragen des Inhalts:/';
        }
    }

    return $Antwort;

}

function startseitenelement_loeschen($IDbaustein){

    $UserID = lade_user_id();
    $Timestamp = timestamp();
    $link = connect_db();

    if (!($stmt = $link->prepare("UPDATE homepage_bausteine SET storno_user = ?, storno_time = ? WHERE id = ?"))) {
        echo "Prepare 1 failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("iss",$UserID, $Timestamp, $IDbaustein)) {
        echo "Binding 1 parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute 1 failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {

        #Delete all Inhalte
        if (!($stmt = $link->prepare("SELECT * FROM homepage_content WHERE id_baustein = ?"))) {
            echo "Prepare 2 failed: (" . $link->errno . ") " . $link->error;
        }

        if (!$stmt->bind_param("s",$IDbaustein)) {
            echo "Binding 2 parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            echo "Execute 2 failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $Anzahl = mysqli_num_rows($res);

        for($x=1;$x<=$Anzahl;$x++){
            $Array = mysqli_fetch_assoc($res);
            $IDElement = intval($Array['id']);
            startseiteninhalt_loeschen($IDElement);
        }
    }

}

function startseiteninhalt_loeschen($IDElement){

    $UserID = intval(lade_user_id());
    $OldElement = lade_seiteninhalt($IDElement);
    $Timestamp = timestamp();
    $link = connect_db();

    if (!($stmt = $link->prepare("UPDATE homepage_content SET storno_user = ?, storno_time = ? WHERE id = ?"))) {
        echo "Prepare 3 failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("isi",$UserID, $Timestamp, intval($IDElement))) {
        echo "Binding 3 parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute 3 failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    //Update all following items
    if (!($stmt = $link->prepare("SELECT id FROM homepage_content WHERE id_baustein = ? AND rang > ? AND storno_user = 0"))) {
        echo "Prepare 3 failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("ii",$OldElement['id_baustein'], $OldElement['rang'])) {
        echo "Binding 3 parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute 3 failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Anzahl = mysqli_num_rows($res);
    for($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($res);
        decrease_item_rank_parser($OldElement['id_baustein'], $Ergebnis['id']);
    }
}

function lade_seitenelement($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM homepage_bausteine WHERE id = '".$ID."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;

}

function lade_seiteninhalt($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM homepage_content WHERE id = '".$ID."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;

}

function lade_seite($SiteName){

    $link = connect_db();

    $Anfrage = "SELECT * FROM homepage_sites WHERE name = '".$SiteName."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;

}

function update_website_content_item($Item, $Column, $Value){

    $link = connect_db();

    if (!($stmt = $link->prepare("UPDATE homepage_content SET ".$Column." = ? WHERE id = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("si",$Value,$Item)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        return true;
    }
}

function update_website_baustein_item($Item, $Column, $Value){

    $link = connect_db();

    if (!($stmt = $link->prepare("UPDATE homepage_bausteine SET ".$Column." = ? WHERE id = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("si",$Value,$Item)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        return true;
    }
}

function update_website_page_item($PageName, $Column, $Value){

    $link = connect_db();

    if (!($stmt = $link->prepare("UPDATE homepage_sites SET ".$Column." = ? WHERE name = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("ss",$Value,$PageName)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        return true;
    }
}

function website_item_info_table_generator($Item){

    $ItemMeta = lade_seiteninhalt($Item);
    $BausteinMeta = lade_baustein($ItemMeta['id_baustein']);
    $SeiteMeta = lade_seite($BausteinMeta['ort']);

    $TableRowContent = table_header_builder('Subseite:');
    $TableRowContent .= table_data_builder($SeiteMeta['menue_text']);
    $TableRows = table_row_builder($TableRowContent);
    $TableRowContent = table_header_builder('Subseite-URL:');
    $TableRowContent .= table_data_builder("./index.php?tab=".$SeiteMeta['name']."");
    $TableRows .= table_row_builder($TableRowContent);
    $TableRowContent = table_header_builder('Baustein:');
    $TableRowContent .= table_data_builder($BausteinMeta['name']);
    $TableRows .= table_row_builder($TableRowContent);
    $TableRowContent = table_header_builder('Element:');
    $TableRowContent .= table_data_builder($ItemMeta['ueberschrift']);
    $TableRows .= table_row_builder($TableRowContent);

    $Table = table_builder($TableRows);
    return $Table;
}

function website_item_baustein_table_generator($Item){

    $BausteinMeta = lade_baustein($Item);
    $SeiteMeta = lade_seite($BausteinMeta['ort']);

    $TableRowContent = table_header_builder('Subseite:');
    $TableRowContent .= table_data_builder($SeiteMeta['menue_text']);
    $TableRows = table_row_builder($TableRowContent);
    $TableRowContent = table_header_builder('Subseite-URL:');
    $TableRowContent .= table_data_builder("./index.php?tab=".$SeiteMeta['name']."");
    $TableRows .= table_row_builder($TableRowContent);
    $TableRowContent = table_header_builder('Baustein:');
    $TableRowContent .= table_data_builder($BausteinMeta['name']);
    $TableRows .= table_row_builder($TableRowContent);

    $Table = table_builder($TableRows);
    return $Table;
}

function generate_row_item_change_form($Item, $Mode='change'){

    if($Mode=='change'){
        $ItemMeta = lade_seiteninhalt($Item);
        $Ueberschrift = $ItemMeta['ueberschrift'];
        $UeberschriftFarbe = $ItemMeta['item_title_color'];
        $InhaltHTML = $ItemMeta['html_content'];
        $Icon = $ItemMeta['icon'];
        $IconFarbe = $ItemMeta['icon_farbe'];
    } elseif ($Mode=='create'){
        $Ueberschrift = $_POST['item_title'];
        $UeberschriftFarbe = $_POST['item_title_color'];
        #Remove certain HTML Tags from HTML-Textarea-Input
        $HTMLValue = $_POST['item_html'];
        $HTMLValue = str_replace('<pre>','',$HTMLValue);
        $HTMLValue = str_replace('<code>','',$HTMLValue);
        $HTMLValue = str_replace('</code>','',$HTMLValue);
        $HTMLValue = str_replace('</pre>','',$HTMLValue);
        $InhaltHTML = $HTMLValue;
        $Icon = $_POST['item_icon'];
        $IconFarbe = $_POST['item_icon_color'];
    }

    $TableRows = table_form_string_item('Überschrift', 'item_title', $Ueberschrift, '');
    $TableRows .= table_form_string_item('Überschrift Farbe', 'item_title_color', $UeberschriftFarbe, '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $InhaltHTML, '');
    $TableRows .= table_form_string_item('Icon', 'item_icon', $Icon, '');
    $TableRows .= table_form_string_item('Icon Farbe', 'item_icon_color', $IconFarbe, '');
    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    if($Mode=='change') {
        $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    } elseif ($Mode=='create'){
        $TableRowContent .= table_header_builder(form_button_builder('action_add_site_item', 'Anlegen', 'action', 'send', ''));
    }
    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;

}

function generate_parallax_change_form($Item){

    $ItemMeta = lade_seiteninhalt($Item);

    $TableRows = table_form_string_item('Überschrift', 'item_title', $ItemMeta['ueberschrift'], '');
    $TableRows .= table_form_string_item('Überschriftfarbe', 'item_title_color', $ItemMeta['ueberschrift_farbe'], '');
    $TableRows .= table_form_string_item('Zweite Überschrift', 'second_item_title', $ItemMeta['zweite_ueberschrift'], '');
    $TableRows .= table_form_string_item('Zweite Überschriftfarbe', 'second_item_title_color', $ItemMeta['zweite_ueberschrift_farbe'], '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $ItemMeta['html_content'], '');
    $TableRows .= table_form_mediapicker_dropdown('URI Bild', 'item_pic_uri', $ItemMeta['uri_bild'], 'media/pictures', 'Wähle ein Bild aus', '');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;
}

function generate_slider_change_form($Item, $Mode='change'){

    if($Mode=='change'){
        $ItemMeta = lade_seiteninhalt($Item);
        $Button = table_data_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    } else {
        $Button = table_data_builder(form_button_builder('action_add_site_item', 'Anlegen', 'action', 'send', ''));
        $ItemMeta = array();
    }

    $TableRows = table_form_string_item('Überschrift', 'item_title', $ItemMeta['ueberschrift'], '');
    $TableRows .= table_form_string_item('Überschriftfarbe', 'item_title_color', $ItemMeta['ueberschrift_farbe'], '');
    $TableRows .= table_form_string_item('Zweite Überschrift', 'second_item_title', $ItemMeta['zweite_ueberschrift'], '');
    $TableRows .= table_form_string_item('Zweite Überschriftfarbe', 'second_item_title_color', $ItemMeta['zweite_ueberschrift_farbe'], '');
    $TableRows .= table_form_mediapicker_dropdown('URI Bild', 'item_pic_uri', $ItemMeta['uri_bild'], 'media/pictures', 'Wähle ein Bild aus', '');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    $TableRowContent .= $Button;
    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;
}

function generate_html_change_form($Item){

    $ItemMeta = lade_seiteninhalt($Item);

    $TableRows = table_form_string_item('Überschrift (wird nicht angezeigt)', 'item_title', $ItemMeta['ueberschrift'], '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $ItemMeta['html_content'], '');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;
}

function generate_kostenstaffel_change_form($Item){

    $ItemMeta = lade_seiteninhalt($Item);

    $TableRows = table_form_string_item('Überschrift', 'item_title', $ItemMeta['ueberschrift'], '');
    $TableRows .= table_form_string_item('Panel Farbe', 'item_panel_color', $ItemMeta['zweite_ueberschrift_farbe'], '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $ItemMeta['html_content'], '');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;
}

function generate_collapsible_change_form($Item, $Mode='change'){

    if($Mode=='change'){
        $ItemMeta = lade_seiteninhalt($Item);
        $Ueberschrift = $ItemMeta['ueberschrift'];
        $InhaltHTML = $ItemMeta['html_content'];
        $Icon = $ItemMeta['icon'];
        $IconFarbe = $ItemMeta['icon_farbe'];
    } elseif ($Mode=='create'){
        $Ueberschrift = $_POST['item_title'];
        #Remove certain HTML Tags from HTML-Textarea-Input
        $HTMLValue = $_POST['item_html'];
        $HTMLValue = str_replace('<pre>','',$HTMLValue);
        $HTMLValue = str_replace('<code>','',$HTMLValue);
        $HTMLValue = str_replace('</code>','',$HTMLValue);
        $HTMLValue = str_replace('</pre>','',$HTMLValue);
        $InhaltHTML = $HTMLValue;
        $Icon = $_POST['item_icon'];
        $IconFarbe = $_POST['item_icon_color'];
    }

    $TableRows = table_form_string_item('Überschrift', 'item_title', $Ueberschrift, '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $InhaltHTML, '');
    $TableRows .= table_form_string_item('Icon', 'item_icon', $Icon, '');
    $TableRows .= table_form_string_item('Icon Farbe', 'item_icon_color', $IconFarbe, '');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    if($Mode=='change') {
        $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    } elseif ($Mode=='create'){
        $TableRowContent .= table_header_builder(form_button_builder('action_add_site_item', 'Anlegen', 'action', 'send', ''));
    }    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;
}

function generate_collection_change_form($Item, $Mode='change'){

    if($Mode=='change'){
        $ItemMeta = lade_seiteninhalt($Item);
        $Ueberschrift = $ItemMeta['ueberschrift'];
        $InhaltHTML = $ItemMeta['html_content'];
    } elseif ($Mode=='create'){
        $Ueberschrift = $_POST['item_title'];
        #Remove certain HTML Tags from HTML-Textarea-Input
        $HTMLValue = $_POST['item_html'];
        $HTMLValue = str_replace('<pre>','',$HTMLValue);
        $HTMLValue = str_replace('<code>','',$HTMLValue);
        $HTMLValue = str_replace('</code>','',$HTMLValue);
        $HTMLValue = str_replace('</pre>','',$HTMLValue);
        $InhaltHTML = $HTMLValue;
    }

    $TableRows = table_form_string_item('Überschrift (optional)', 'item_title', $Ueberschrift, '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $InhaltHTML, '');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    if($Mode=='change') {
        $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
    } elseif ($Mode=='create'){
        $TableRowContent .= table_header_builder(form_button_builder('action_add_site_item', 'Anlegen', 'action', 'send', ''));
    }    $TableRows .= table_row_builder($TableRowContent);
    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'item_change_form');
    $Section = section_builder($Form);

    return $Section;
}



