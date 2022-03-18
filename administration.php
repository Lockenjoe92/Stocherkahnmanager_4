<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Adminpage - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1>Adminseite</h1>';
$HTML .= section_builder($PageTitle);

# Links Section
$Links = row_builder(button_link_creator('Startseite editieren', './admin_edit_startpage.php','brush', ''));
$Links .= row_builder(button_link_creator('Admineinstellungen', './admin_settings.php','edit', ''));
$Links .= row_builder(button_link_creator('Dateimanager', './datenmanager.php','folder_open', ''));
$Links .= row_builder(button_link_creator('Nutzergruppen', './admin_nutzergruppen.php','group', ''));
$Links .= row_builder(button_link_creator('Ausleihverträge', './ausleihvertraege_admin.php','assignment', ''));
$Links .= row_builder(button_link_creator('Datenschutzerklärungen', './datenschutzerklaerungen.php','security', ''));
$Links .= row_builder(button_link_creator('Mailvorlagen', './admin_edit_mails.php','mail', ''));
$HTML .= section_builder($Links);

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);