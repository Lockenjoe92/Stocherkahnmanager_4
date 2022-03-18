<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Parser = parse_datei_upload_form();
$UebersichtParser = parse_datei_uebersicht_form();
$Header = "Dateimanager - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align">Dateimanager</h1>';
$HTML = section_builder($PageTitle);
$HTML .= generate_datei_uebersicht_form($UebersichtParser);
$HTML .= generate_datei_upload_form($Parser);
$Container = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($Container);

function generate_datei_upload_form($Parser){

    $TableRows = table_form_file_upload_builder('Datei auswählen', 'file_to_upload');
    $TableRows .= table_form_file_upload_directory_chooser_builder('Ort zum hochladen wählen', 'upload_dir');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', ''));
    $TableRowContent .= table_header_builder(form_button_builder('action_upload_file', 'Hochladen', 'action', 'file_upload', ''));
    $TableRows .= table_row_builder($TableRowContent);

    if($Parser!=''){
        $TableRowContent = table_header_builder(error_button_creator($Parser,'announcement', ''));
        $TableRowContent .= table_data_builder('');
        $TableRows .= table_row_builder($TableRowContent);
    }

    $Table = table_builder($TableRows);
    $Form = "<h3 class='center-align'>Dateien hochladen</h3>";
    $Form .= form_builder($Table, '#', 'post', 'file_uploader', 'multipart/form-data');
    $Section = section_builder($Form);

    return $Section;
}

function generate_datei_uebersicht_form($UebersichtParser){

    $dirDoc = "./media/documents/";
    $dirPic = "./media/pictures/";

    $AlleDocs = scandir($dirDoc, 1);
    $AllePics = scandir($dirPic, 1);

    $HTML = "<h3 class='center-align'>Dateien im System</h3>";

    if($UebersichtParser===true){
        $HTML .= "<h5 class='center-align'>Datei erfolgreich gelöscht!</h5>";
    }
    if($UebersichtParser===false){
        $HTML .= "<h5 class='center-align'>Fehler!</h5>";
    }

    $DocsHTML = table_row_builder(table_header_builder('Datei').table_header_builder('Dateigröße').table_header_builder('Dateipfad zur Verwendung im System').table_header_builder('Aktionen'));
    $Counter = 0;
    foreach ($AlleDocs as $Doc){
        $Counter++;
        if($Doc != '.'){
            if($Doc != '..'){

                $button = form_button_builder('delete_doc_'.$Counter.'', 'Löschen', 'action', 'delete_forever', '');
                $DocsHTML .= table_row_builder(table_data_builder($Doc).table_data_builder((filesize($dirDoc.$Doc)/1000).' kB').table_data_builder($dirDoc.$Doc).table_data_builder($button));
            }
        }
    }
    $Collapsible = collapsible_item_builder('Dokumente', form_builder(table_builder($DocsHTML), '#', 'post', 'delete_docs'), 'description');

    $PicsHTML = table_row_builder(table_header_builder('Datei').table_header_builder('Dateigröße').table_header_builder('Dateipfad zur Verwendung im System').table_header_builder('Aktionen'));;
    $Counter = 0;
    foreach ($AllePics as $Pic){
        $Counter++;
        if($Pic != '.') {
            if($Pic != '..') {
                #$button = form_button_builder('delete_pic_'.$Counter, 'Löschen', 'action', 'delete_forever', '');
                $PicsHTML .= table_row_builder(table_data_builder($Pic).table_data_builder((filesize($dirPic.$Pic)/1000).' kB').table_data_builder($dirPic.$Pic).table_data_builder($button));
            }
        }
    }

    $Collapsible .= collapsible_item_builder('Bilder', form_builder(table_builder($PicsHTML), '#', 'post', 'delete_pics'), 'image');

    $HTML .= collapsible_builder($Collapsible);

    return $HTML;
}

function parse_datei_upload_form(){

    if(isset($_POST['action_upload_file'])){

        $target_dir = $_POST['upload_dir'];
        $target_file = $target_dir . basename($_FILES["file_to_upload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        if($_POST['upload_dir'] == 'media/pictures'){
            if(isset($_POST["action_upload_file"])) {
                $check = getimagesize($_FILES["file_to_upload"]["tmp_name"]);
                if($check !== false) {
                    $Antwort = "Datei ist eine valide Bilddatei - " . $check["mime"] . ".";
                    $uploadOk = 1;
                } else {
                    $Antwort = "Datei ist keine Bilddatei.";
                    $uploadOk = 0;
                }
            }
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            $Antwort = "Sorry, die Datei existiert bereits.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["file_to_upload"]["size"] > lade_db_einstellung('max_size_file_upload')) {
            $Antwort = "Sorry, dei Datei ist zu groß!.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($_POST['upload_dir'] == 'media/pictures') {
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif") {
                $Antwort = "Sorry, nur JPG, JPEG, PNG & GIF Dateien sind zulässig.";
                $uploadOk = 0;
            }
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $Antwort = "Sorry, die Datei wurde nicht hochgeladen.";

            // if everything is ok, try to upload file
        } else {

            if($_POST['upload_dir'] == '/media/pictures/'){
                #$File = resize_image($_FILES["file_to_upload"]["tmp_name"], 1440, 743);
                $File = $_FILES["file_to_upload"]["tmp_name"];
            } else {
                $File = $_FILES["file_to_upload"]["tmp_name"];
            }

            if (move_uploaded_file($File, $target_file)) {
                $Antwort = "Die Datei ". basename( $_FILES["file_to_upload"]["name"]). " wurde hochgeladen.";
            } else {
                $Antwort = "Sorry, es gab einen Fehler beim Hochladen.";
            }
        }

        return $Antwort;
    }
}

function parse_datei_uebersicht_form(){

    $dirDoc = "./media/documents/";
    $dirPic = "./media/pictures/";

    $AlleDocs = scandir($dirDoc, 1);
    $AllePics = scandir($dirPic, 1);

    $Antwort = null;
    $Counter = 0;
    foreach ($AlleDocs as $Doc){
        $Counter++;
        if(isset($_POST['delete_doc_'.$Counter])){
            #var_dump(fileperms($_POST['delete_doc_'.$Doc]));
            #var_dump(chmod($_POST['delete_doc_'.$Doc], 0755));
            return unlink($dirDoc.$Doc);
        }
    }
    $Counter = 0;
    foreach ($AllePics as $Pic){
        $Counter++;
        if(isset($_POST['delete_pic_'.$Counter])){
            return unlink($dirPic.$Pic);
        }
    }
    return $Antwort;
}

function resize_image($file, $w, $h, $crop=FALSE) {
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width-($width*abs($r-$w/$h)));
        } else {
            $height = ceil($height-($height*abs($r-$w/$h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }
    }
    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    return $dst;
}

?>