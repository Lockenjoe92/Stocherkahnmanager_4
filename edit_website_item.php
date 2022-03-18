<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 10:06
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Item = $_GET['item'];
if(intval($Item)>0){

    #Parse Input
    parse_edit_website_item_page($Item);


    #Generate content
    # Page Title
    $Header = "Webseite Editieren - " . lade_db_einstellung('site_name');
    $PageTitle = '<h1>Webseiteinhalt bearbeiten</h1>';
    $HTML = section_builder($PageTitle);
    $HTML .= section_builder('<h5>Lokalisation</h5>');
    $HTML .= section_builder(website_item_info_table_generator($Item));

    # Form depending on type
    $ItemMeta = lade_seiteninhalt($Item);
    $BausteinMeta = lade_baustein($ItemMeta['id_baustein']);
    $HTML .= section_builder('<h5>Inhaltselement</h5>');
    if ($BausteinMeta['typ'] == 'row_container'){
        $HTML .= generate_row_item_change_form($Item);
    } elseif ($BausteinMeta['typ'] == 'parallax_mit_text'){
        $HTML .= generate_parallax_change_form($Item);
    } elseif ($BausteinMeta['typ'] == 'html_container'){
        $HTML .= generate_html_change_form($Item);
    } elseif ($BausteinMeta['typ'] == 'collection_container'){
        $HTML .= generate_collection_change_form($Item);
    } elseif ($BausteinMeta['typ'] == 'collapsible_container'){
        $HTML .= generate_collapsible_change_form($Item);
    } elseif ($BausteinMeta['typ'] == 'kostenstaffel_container'){
        $HTML .= generate_kostenstaffel_change_form($Item);
    } elseif ($BausteinMeta['typ'] == 'slider_mit_ueberschrift'){
        $HTML .= generate_slider_change_form($Item);
    }

    # Output site
    $HTML = container_builder($HTML, 'websiteinhalt_bearbeiten_container');
    echo site_header($Header);
    echo site_body($HTML);

} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}

