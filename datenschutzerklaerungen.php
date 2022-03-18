32<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Datenschutz - " . lade_db_einstellung('site_name');
$ParserAnlegen = ds_anlegen_parser();

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Datenschutzerklärungen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Datenschutz</h1>';
$HTML .= section_builder($PageTitle);

$DSE = lade_ds(aktuelle_ds_id_laden());
$Titel = "Aktuelle DSE - Version: ".$DSE['version']."";
$Content = $DSE['inhalt'];
$Collapsible = collapsible_item_builder($Titel, $Content, '');

# Eigene Reservierungen Normalo-user
$Collapsible .= collapsible_item_builder('Neue Datenschutzerklärung anlegen', ds_anlegen_formular($ParserAnlegen, $Content), '');

if(isset($_POST['add_dse_action'])) {
    if($ParserAnlegen['success'] == TRUE){
        $Titel = "Vorschau DSE";
        $CleanedText = str_replace( '<pre><code>', '', $_POST['text']);
        $CleanedText = str_replace( '</code></pre>', '', $CleanedText);
        $Collapsible .= collapsible_item_builder($Titel, $CleanedText, 'add_new');
    }
}

$HTML .= section_builder(collapsible_builder($Collapsible));

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);
