<?php
function decrease_baustein_rank_parser($Baustein, $Site){

    $link = connect_db();

    if(intval($Baustein)>0){

        $BausteinMeta = lade_baustein($Baustein);
        $BausteinRang = $BausteinMeta['rang'];

        #Calculate new Rang
        $NewRang = $BausteinRang - 1;

        #Load the other item
        $Anfrage = "SELECT * FROM homepage_bausteine WHERE ort = '".$Site."' AND rang = ".$NewRang." AND storno_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Update corresponding Item
        update_website_baustein_item($Ergebnis['id'], 'rang', $BausteinRang);

        # Update selected Item
        update_website_baustein_item($Baustein, 'rang', $NewRang);

        return true;
    } else {
        return false;
    }

}
function add_website_item_parser($Baustein){
    if(intval($Baustein)>0){
        startseiteninhalt_einfuegen($Baustein, 'Neues Element', '', '', '', 'Hier entsteht ein neues Element', '', 'announcement', 'brown-text');
        return true;
    } else {
        return false;
    }
}
function decrease_item_rank_parser($Item){

    $link = connect_db();

    if(intval($Item)>0){

        $ItemMeta = lade_seiteninhalt($Item);
        $ItemRang = $ItemMeta['rang'];

        #Calculate new Rang
        $NewRang = $ItemRang - 1;

        #Load the other item
        $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = ".$ItemMeta['id_baustein']." AND rang = ".$NewRang." AND storno_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Update selected Item
        update_website_content_item($Item, 'rang', $NewRang);

        # Update corresponding Item
        update_website_content_item($Ergebnis['id'], 'rang', $ItemRang);

        return true;
    } else {
        return false;
    }

}
function decrease_page_rank_parser($Rang, $Name){

    if((intval($Rang)>0) and (!empty($Name))){
        $link = connect_db();

        #Calculate new Rang
        $NewRang = $Rang + 1;

        #Load the other item
        $Anfrage = "SELECT * FROM homepage_sites WHERE menue_rang = ".$NewRang." AND delete_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Update corresponding Item
        update_website_page_item($Ergebnis['name'], 'menue_rang', $Rang);

        # Update selected Item
        update_website_page_item($Name, 'menue_rang', $NewRang);

        return true;
    } else {
        return false;
    }
}
function delete_website_baustein_parser($Baustein){
    if(intval($Baustein)>0){
        startseitenelement_loeschen($Baustein);
        return true;
    } else {
        return false;
    }
}
function delete_website_item_parser($Baustein){
    if(intval($Baustein)>0){
        startseiteninhalt_loeschen($Baustein);
        return true;
    } else {
        return false;
    }
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
function parse_collection_item_edit($Item){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'html_content', $HTMLValue);

}
function parse_collapsible_item_edit($Item){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);
    update_website_content_item($Item, 'icon', $_POST['item_icon']);
    update_website_content_item($Item, 'icon_farbe', $_POST['item_icon_color']);
    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'html_content', $HTMLValue);

}
function parse_kostenstaffel_item_edit($Item){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'zweite_ueberschrift_farbe', $_POST['item_panel_color']);
    update_website_content_item($Item, 'html_content', $HTMLValue);

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
function parse_slider_item_edit($Item){

    update_website_content_item($Item, 'ueberschrift', $_POST['item_title']);
    update_website_content_item($Item, 'ueberschrift_farbe', $_POST['item_title_color']);
    update_website_content_item($Item, 'zweite_ueberschrift', $_POST['second_item_title']);
    update_website_content_item($Item, 'zweite_ueberschrift_farbe', $_POST['second_item_title_color']);
    update_website_content_item($Item, 'uri_bild', $_POST['item_pic_uri']);

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
        } elseif ($BausteinMeta['typ'] == 'collection_container'){
            parse_collection_item_edit($Item);
        } elseif ($BausteinMeta['typ'] == 'collapsible_container'){
            parse_collapsible_item_edit($Item);
        } elseif ($BausteinMeta['typ'] == 'kostenstaffel_container'){
            parse_kostenstaffel_item_edit($Item);
        } elseif ($BausteinMeta['typ'] == 'slider_mit_ueberschrift'){
            parse_slider_item_edit($Item);
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
function parse_add_collapsible_item($Baustein){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    return startseiteninhalt_einfuegen($Baustein, $_POST['item_title'], '', '', '', $HTMLValue, '', $_POST['item_icon'], $_POST['item_icon_color']);

}

function parse_add_slider_item($Baustein){
    #var_dump($_POST);
    return startseiteninhalt_einfuegen($Baustein, $_POST['item_title'], $_POST['second_item_title'], $_POST['item_title_color'], $_POST['second_item_title_color'], '', $_POST['item_pic_uri'], '', '');

}

function parse_add_collection_item($Baustein){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    return startseiteninhalt_einfuegen($Baustein, $_POST['item_title'], '', '', '', $HTMLValue, '', '', '');

}
function parse_add_row_item($Baustein){

    #Remove certain HTML Tags from HTML-Textarea-Input
    $HTMLValue = $_POST['item_html'];
    $HTMLValue = str_replace('<pre>','',$HTMLValue);
    $HTMLValue = str_replace('<code>','',$HTMLValue);
    $HTMLValue = str_replace('</code>','',$HTMLValue);
    $HTMLValue = str_replace('</pre>','',$HTMLValue);

    return startseiteninhalt_einfuegen($Baustein, $_POST['item_title'], '', $_POST['item_title_color'], '', $HTMLValue, '', $_POST['item_icon'], $_POST['item_icon_color']);

}
function increase_baustein_rank_parse($Baustein, $Site){

    $link = connect_db();

    if(intval($Baustein)>0){

        $BausteinMeta = lade_baustein($Baustein);
        $BausteinRang = $BausteinMeta['rang'];

        #Calculate new Rang
        $NewRang = $BausteinRang + 1;

        #Load the other item
        $Anfrage = "SELECT * FROM homepage_bausteine WHERE ort = '".$Site."' AND rang = ".$NewRang." AND storno_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Update corresponding Item
        update_website_baustein_item($Ergebnis['id'], 'rang', $BausteinRang);

        # Update selected Item
        update_website_baustein_item($Baustein, 'rang', $NewRang);

        return true;
    } else {
        return false;
    }

}
function increase_item_rank_parse($Item){

    $link = connect_db();

    if(intval($Item)>0){

        $ItemMeta = lade_seiteninhalt($Item);
        $ItemRang = $ItemMeta['rang'];

        #Calculate new Rang
        $NewRang = $ItemRang + 1;

        #Load the other item
        $Anfrage = "SELECT * FROM homepage_content WHERE id_baustein = ".$ItemMeta['id_baustein']." AND rang = ".$NewRang." AND storno_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Update corresponding Item
        update_website_content_item($Ergebnis['id'], 'rang', $ItemRang);

        # Update selected Item
        update_website_content_item($Item, 'rang', $NewRang);

        return true;
    } else {
        return false;
    }

}
function increase_page_rank_parse($Rang, $Name){

    $link = connect_db();

    if((intval($Rang)>0) and (!empty($Name))){

        #Calculate new Rang
        $NewRang = $Rang - 1;

        #Load the other item
        $Anfrage = "SELECT * FROM homepage_sites WHERE menue_rang = ".$NewRang." AND delete_user = 0";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Ergebnis = mysqli_fetch_assoc($Abfrage);

        # Update selected Item
        update_website_page_item($Name, 'menue_rang', $NewRang);

        # Update corresponding Item
        update_website_page_item($Ergebnis['name'], 'menue_rang', $Rang);

        return true;
    } else {
        return false;
    }

}