<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Inhaltselement hinzufügen - " . lade_db_einstellung('site_name');

$Baustein = $_GET['baustein'];
if(!intval($Baustein)>0){
    header("Location: admin_edit_startpage.php");
    die();
}

# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Inhaltselement hinzufügen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Inhaltselement hinzufügen</h1>';
$HTML = section_builder($PageTitle);

##BAUSTEIN INFOS
$BausteinMeta = lade_baustein($Baustein);
$BausteinInfoTable = table_row_builder(table_header_builder('Seite').table_data_builder($BausteinMeta['ort']));
$BausteinInfoTable .= table_row_builder(table_header_builder('Typ').table_data_builder($BausteinMeta['typ']));
$BausteinInfoTable .= table_row_builder(table_header_builder('Name Baustein').table_data_builder($BausteinMeta['name']));
$BausteinInfos = '<h3>Infos zum Baustein</h3>';
$BausteinInfos .= table_builder($BausteinInfoTable);
$HTML .= section_builder($BausteinInfos);

##ADDER FORMULAR
$HTMLform = '<h3>Neues Element anlegen</h3>';
if ($BausteinMeta['typ'] == 'row_container'){
    if(isset($_POST['action_add_site_item'])){
        $Parser = parse_add_row_item($Baustein);
        if(isset($Parser['meldung'])){
            $HTMLform .= "<h5>".$Parser['meldung']."</h5>";
        }
    }
    $HTMLform .= generate_row_item_change_form($Item, 'create');
} elseif ($BausteinMeta['typ'] == 'collection_container'){
    if(isset($_POST['action_add_site_item'])){
        $Parser = parse_add_collection_item($Baustein);
        if(isset($Parser['meldung'])){
            $HTMLform .= "<h5>".$Parser['meldung']."</h5>";
        }
    }
    $HTMLform .= generate_collection_change_form($Item, 'create');
} elseif ($BausteinMeta['typ'] == 'collapsible_container'){
    if(isset($_POST['action_add_site_item'])){
        var_dump($_POST);
        $Parser = parse_add_collapsible_item($Baustein);
        if(isset($Parser['meldung'])){
            $HTMLform .= "<h5>".$Parser['meldung']."</h5>";
        }
    }
    $HTMLform .= generate_collapsible_change_form($Item, 'create');
} elseif ($BausteinMeta['typ'] == 'slider_mit_ueberschrift'){
    if(isset($_POST['action_add_site_item'])){
        var_dump($_POST);
        $Parser = parse_add_slider_item($Baustein);
        if(isset($Parser['meldung'])){
            $HTMLform .= "<h5>".$Parser['meldung']."</h5>";
        }
    }
    #$HTMLform .= 'slider_mit_ueberschrift';
    $HTMLform .= generate_slider_change_form($Item, 'create');
}
$HTML .= section_builder($HTMLform);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);