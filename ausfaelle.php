<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Kahnausf&auml;lle - " . lade_db_einstellung('site_name');

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Ausf&auml;lle des Kahns verwalten</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Ausf&auml;lle verwalten</h1>';
$HTML = section_builder($PageTitle);

$HTML .= spalte_pausen();
$HTML .= spalte_sperrungen();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function spalte_pausen(){

    //Grundsätzliches
    $link = connect_db();
    $Timestamp = timestamp();

    //Lade aktive Pausen
    $AnfrageLadeAktivePausen = "SELECT id, typ, titel, beginn, ende, erklaerung, ersteller FROM pausen WHERE ende > '$Timestamp' AND storno_user = '0'";
    $AbfrageLadeAktivePausen = mysqli_query($link, $AnfrageLadeAktivePausen);
    $AnzahlLadeAktivePausen = mysqli_num_rows($AbfrageLadeAktivePausen);

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header center-align'>Betriebspausen</h5>";

    if ($AnzahlLadeAktivePausen == 0){
        $HTML .= section_builder("<p class='caption'>Derzeit gibt es keine aktiven Betriebspausen! <br><a href='ausfall_hinzufuegen.php?typ=pause'><i class='tiny material-icons'>note_add</i> hinzuf&uuml;gen</a></p>", '', 'center-align');
    } else if ($AnzahlLadeAktivePausen > 0){
        $HTML .= "<div class='section'>";
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

            for ($a = 1; $a <= $AnzahlLadeAktivePausen; $a ++){

                $Pause = mysqli_fetch_assoc($AbfrageLadeAktivePausen);
                $Wart = lade_user_meta($Pause['ersteller']);
                zeitformat();
                $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Pause['beginn']))."</b>&nbsp;bis&nbsp;<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Pause['ende']))."</b>";

                $Titel = '<b>'.$Pause['titel'].'</b>&nbsp;-&nbsp;'.date("d.m.Y G:i",strtotime($Pause['beginn'])).'&nbsp;Uhr&nbsp;bis&nbsp;'.date("d.m.Y G:i",strtotime($Pause['ende'])).'&nbsp;Uhr';
                $Content = table_row_builder(table_header_builder('Pausentyp').table_data_builder($Pause['typ']));
                $Content .= table_row_builder(table_header_builder('Zeitraum').table_data_builder($Zeitraum));
                $Content .= table_row_builder(table_header_builder('Erklärung').table_data_builder($Pause['erklaerung']));
                $Content .= table_row_builder(table_header_builder('Erstellt von').table_data_builder($Wart['vorname']." ".$Wart['nachname']));
                $Content .= table_row_builder(table_header_builder(button_link_creator('Bearbeiten', 'ausfall_bearbeiten.php?typ=pause&id='.$Pause['id'].'', 'mode_edit', '')." ".button_link_creator('Löschen', 'ausfall_loeschen.php?typ=pause&id='.$Pause['id'].'', 'delete', '')).table_data_builder(''));
                $Content = table_builder($Content);
                $HTML .= collapsible_item_builder($Titel, $Content, 'label_outline');
            }

        $HTML .= "<li><div class='collapsible-header'><a href='ausfall_hinzufuegen.php?typ=pause'><i class='small material-icons'>note_add</i> hinzuf&uuml;gen</a></div></li>";

        $HTML .= "</ul>";
        $HTML .= "</div>";
    }

    $HTML .= "</div>";

    return $HTML;
}

function spalte_sperrungen(){

//Grundsätzliches
    $link = connect_db();
    $Timestamp = timestamp();

    //Lade aktive Pausen
    $AnfrageLadeAktiveSperrungen = "SELECT id, typ, titel, beginn, ende, erklaerung, ersteller FROM sperrungen WHERE ende > '$Timestamp' AND storno_user = '0'";
    $AbfrageLadeAktiveSperrungen = mysqli_query($link, $AnfrageLadeAktiveSperrungen);
    $AnzahlLadeAktiveSperrungen = mysqli_num_rows($AbfrageLadeAktiveSperrungen);

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header center-align'>Sperrungen</h5>";

    if ($AnzahlLadeAktiveSperrungen == 0){
        $HTML .= section_builder("<p class='caption'>Derzeit gibt es keine aktiven Sperrungen! <br><a href='ausfall_hinzufuegen.php?typ=sperrung'><i class='tiny material-icons'>note_add</i> hinzuf&uuml;gen</a></p>", '', 'center-align');
    } else if ($AnzahlLadeAktiveSperrungen > 0){
        $HTML .= "<div class='section'>";
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

        for ($a = 1; $a <= $AnzahlLadeAktiveSperrungen; $a ++){

            $Sperrung = mysqli_fetch_assoc($AbfrageLadeAktiveSperrungen);
            $Wart = lade_user_meta($Sperrung['ersteller']);
            zeitformat();
            $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Sperrung['beginn']))."</b>&nbsp;bis&nbsp;<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Sperrung['ende']))."</b>";


            $Titel = '<b>'.$Sperrung['titel'].'</b>&nbsp;-&nbsp;'.date("d.m.Y G:i",strtotime($Sperrung['beginn'])).'&nbsp;Uhr&nbsp;bis&nbsp;'.date("d.m.Y G:i",strtotime($Sperrung['ende'])).'&nbsp;Uhr';
            $Content = table_row_builder(table_header_builder('Sperrungstyp').table_data_builder($Sperrung['typ']));
            $Content .= table_row_builder(table_header_builder('Zeitraum').table_data_builder($Zeitraum));
            $Content .= table_row_builder(table_header_builder('Erklärung').table_data_builder($Sperrung['erklaerung']));
            $Content .= table_row_builder(table_header_builder('Erstellt von').table_data_builder($Wart['vorname']." ".$Wart['nachname']));
            $Content .= table_row_builder(table_header_builder(button_link_creator('Bearbeiten', 'ausfall_bearbeiten.php?typ=sperrung&id='.$Sperrung['id'].'', 'mode_edit', '')." ".button_link_creator('Löschen', 'ausfall_loeschen.php?typ=sperrung&id='.$Sperrung['id'].'', 'delete', '')).table_data_builder(''));
            $Content = table_builder($Content);
            $HTML .= collapsible_item_builder($Titel, $Content, 'label_outline');

        }

        $HTML .= "<li><div class='collapsible-header'><a href='ausfall_hinzufuegen.php?typ=sperrung'><i class='large material-icons'>note_add</i> hinzuf&uuml;gen</a></div></li>";

        $HTML .= "</ul>";
        $HTML .= "</div>";
    }

    $HTML .= "</div>";

    return $HTML;
}

?>