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
    }

    # Output site
    $HTML = container_builder($HTML, 'websiteinhalt_bearbeiten_container');
    echo site_header($Header);
    echo site_body($HTML);

} else {
    header("Location: ./admin_edit_startpage.php");
    die();
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

function generate_row_item_change_form($Item){

    $ItemMeta = lade_seiteninhalt($Item);

    $TableRows = table_form_string_item('Überschrift', 'item_title', $ItemMeta['ueberschrift'], '');
    $TableRows .= table_form_string_item('Überschrift Farbe', 'item_title_color', $ItemMeta['ueberschrift_farbe'], '');
    $TableRows .= table_form_html_area_item('Inhalt HTML', 'item_html', $ItemMeta['html_content'], '');
    $TableRows .= table_form_string_item('Icon', 'item_icon', $ItemMeta['icon'], '');
    $TableRows .= table_form_string_item('Icon Farbe', 'item_icon_color', $ItemMeta['icon_farbe'], '');
    $TableRowContent = table_data_builder(button_link_creator('Zurück', './admin_edit_startpage.php', 'arrow_back', ''));
    $TableRowContent .= table_header_builder(form_button_builder('action_edit_site_item', 'Bearbeiten', 'action', 'edit', ''));
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

function parse_edit_website_item_page($Item){

    if (isset($_POST['action_edit_site_item'])){
        $ItemMeta = lade_seiteninhalt($Item);
        $BausteinMeta = lade_baustein($ItemMeta['id_baustein']);

        if ($BausteinMeta['typ'] == 'row_container'){
            parse_row_item_edit($Item);
        } elseif ($BausteinMeta['typ'] == 'parallax_mit_text'){
            parse_parallax_item_edit($Item);
        } elseif ($BausteinMeta['typ'] == 'html_container'){
            parse_html_item_edit($Item);
        }
    }
}

function parse_row_item_edit($Item){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'ueberschrift_farbe', $_POST['item_title_color']);
    update_website_content_item($Item, 'html_content', $HTMLValue);
    update_website_content_item($Item, 'icon', $_POST['item_icon']);
    update_website_content_item($Item, 'icon_farbe', $_POST['item_icon_color']);


}

function parse_parallax_item_edit($Item){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'ueberschrift_farbe', $_POST['item_title_color']);
    update_website_content_item($Item, 'zweite_ueberschrift', $_POST['second_item_title']);
    update_website_content_item($Item, 'zweite_ueberschrift_farbe', $_POST['second_item_title_color']);
    update_website_content_item($Item, 'html_content', $HTMLValue);
    update_website_content_item($Item, 'uri_bild', $_POST['item_pic_uri']);

}

function parse_html_item_edit($Item){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'html_content', $HTMLValue);

}