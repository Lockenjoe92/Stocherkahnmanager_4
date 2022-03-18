<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
needs_dse_mv_update();
$Header = "Übergabeinfos - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="hide-on-med-and-down">Informationen zur Übergabe</h1>';
$PageTitle .= '<h1 class="hide-on-large-only">Übergabeinfos</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= seiteninhalt_uebergabe_infos_generieren();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function seiteninhalt_uebergabe_infos_generieren(){

    zeitformat();
    $UebergabeID = $_GET['id'];
    $Uebergabe = lade_uebergabe($UebergabeID);
    $Terminangebot = lade_terminangebot($Uebergabe['terminangebot']);
    $WartMeta = lade_user_meta($Uebergabe['wart']);
    if(1==1){
        $Wartinfos = "".$WartMeta['vorname']." ".$WartMeta['nachname']."";
    }

    $TableHTML = table_row_builder(table_header_builder("<i class='material-icons tiny'>today</i> Datum:").table_data_builder(strftime("%A, %d. %B %G", strtotime($Uebergabe['beginn']))));
    $TableHTML .= table_row_builder(table_header_builder("<i class='material-icons tiny'>alarm_on</i> Beginn:").table_data_builder(strftime("%H:%M Uhr", strtotime($Uebergabe['beginn']))));
    $TableHTML .= table_row_builder(table_header_builder("<i class='material-icons tiny'>schedule</i> Dauer:").table_data_builder("ca. ".lade_xml_einstellung('dauer-uebergabe-minuten')." Minuten"));
    $TableHTML .= table_row_builder(table_header_builder("<i class='material-icons tiny'>room</i> Treffpunkt:").table_data_builder($Terminangebot['ort']));
    $TableHTML .= table_row_builder(table_header_builder("<i class='material-icons tiny'>toll</i> Kosten deiner Reservierung:").table_data_builder("".kosten_reservierung($Uebergabe['res'])."&euro;"));
    $TableHTML .= table_row_builder(table_header_builder("<i class='material-icons tiny'>android</i> Zust&auml;ndiger Wart:").table_data_builder($Wartinfos));
    $TableHTML .= table_row_builder(table_header_builder(button_link_creator('Zurück', 'my_reservations.php', 'arrow_back', '')).table_data_builder(button_link_creator('Andere &Uuml;bergabe', "neue_uebergabe_ausmachen.php?res=".$Uebergabe['res']."", '', '')."&nbsp;".button_link_creator('Löschen', "uebergabe_stornieren_user.php?id=".$UebergabeID."", '', '')));
    $HTML = section_builder(table_builder($TableHTML));

    $CollapsibleItems = collapsible_item_builder('Was muss ich dabei haben?', lade_xml_einstellung('text-info-uebergabe-dabei-haben'), 'info', '', '');
    $CollapsibleItems .= collapsible_item_builder('Ablauf?', lade_xml_einstellung('text-info-uebergabe-ablauf'), 'info', '', '');
    $CollapsibleItems .= collapsible_item_builder('Einweisung?', lade_xml_einstellung('text-info-uebergabe-einweisung'), 'info', '', '');
    $HTML .= section_builder(collapsible_builder($CollapsibleItems));

    return $HTML;
}

?>