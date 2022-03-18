<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Mietverträge - " . lade_db_einstellung('site_name');
$ParserAnlegen = mv_anlegen_parser();

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Ausleihverträge</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Mietverträge</h1>';
$HTML .= section_builder($PageTitle);

$DSE = lade_mietvertrag(aktuellen_mietvertrag_id_laden());
$Titel = "Aktueller Mietvertrag - Version: ".$DSE['version']."";
$Content = $DSE['inhalt'];
$Collapsible = collapsible_item_builder($Titel, $Content, '');

# Eigene Reservierungen Normalo-user
$Collapsible .= collapsible_item_builder('Neuen Mietvertrag anlegen', mv_anlegen_formular($ParserAnlegen, $Content), '');

# Vorschau generieren
if(isset($_POST['add_mv_action'])) {
    if($ParserAnlegen['success'] == TRUE){
        $Titel = "Vorschau neuer Mietvertrag";
        $CleanedText = str_replace( '<pre><code>', '', $_POST['text']);
        $CleanedText = str_replace( '</code></pre>', '', $CleanedText);
        #$Content = container_builder($_POST['text']);
        $Collapsible .= collapsible_item_builder($Titel, $CleanedText, 'add_new');
    }
}

$HTML .= section_builder(collapsible_builder($Collapsible));

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);