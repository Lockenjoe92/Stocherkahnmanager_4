<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.06.18
 * Time: 20:27
 */

#include_once "./ressources/ressourcen.php";

function site_header($PageTitle, $LoginCheckActive=Null){

    #Redirect wenn Login erforderlich und login nicht erfolgt
    if($LoginCheckActive == True){
        return null;
    }

    #Initialize HTML
    $HTML = '<!DOCTYPE html>';
    $HTML .= '<html lang="de">';

    #Initialize header
    $HTML .= '<head>';

    #Meta infos
    $HTML .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    $HTML .= '<meta name="viewport" content="width=device-width, initial-scale=1"/>';
   

    #Page Title
    if(!isset($PageTitle)){$PageTitle = 'Stocherkahnmanager';}
    $HTML .= '<title>'.$PageTitle.'</title>';

    #CSS
    $HTML .= '  <!-- CSS  -->';
    $HTML .= '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
 	$HTML .= '<link rel="stylesheet" type="text/css" href="https://pretix.tueticket.shop/medikahn/grundkurs/widget/v1.css">';
    $HTML .= '<link href="/materialize/css/materialize.css" type="text/css" rel="stylesheet" media="screen,projection"/>';
    $HTML .= '<link href="/materialize/css/style.css" type="text/css" rel="stylesheet" media="screen,projection"/>';
	
	$HTML .= '<script type="text/javascript" src="https://pretix.tueticket.shop/widget/v1.de.js" async></script>';

    #End header
    $HTML .= '</head>';

    return $HTML;
}

function site_body($BodyHTMLcontent, $Navbar=true, $Footer=true){

    # Initialize body
    $HTML = '<!--  body  -->';
    $HTML .= '<body>';

    # Add navbar
    if($Navbar){
        $HTML .= site_navbar();
    }

    # Add content
    #$HTML .= "<main class='".lade_db_einstellung('site_background_color')."'>";
    #$HTML .= "<main>";#
    $HTML .= $BodyHTMLcontent;
    #$HTML .= "</main>";#


    # Add footer
    if($Footer) {
        $HTML .= site_footer();
    }

    # Run skripts
    $HTML .= site_skripts();

    # Close body
    $HTML .= '</body>';

    # End html
    $HTML .= '</html>';

    return $HTML;
}

function site_skripts(){

    $HTML = '  <!--  Scripts-->';
    $HTML .= '<script src="/materialize/js/jquery.min.js"></script>';
    $HTML .= '<script src="/materialize/js/materialize.js"></script>';
    #$HTML .= '<script src="/materialize/js/init.js"></script>';
    #$HTML .= '<script src="/materialize/js/select.js"></script>';
    $HTML .= '<script src="/materialize/js/main.js"></script>';

    return $HTML;
}

function site_footer(){

    # Initialize Footer
    $HTML = '<!--  footer-->';
    $HTML .= '<footer class="page-footer '.lade_db_einstellung('site_footer_color').'">';
    $HTML .= footer_container();

    #Close footer
    $HTML .= '</footer>';

    return $HTML;
}

function footer_container(){

    #Initialize container
    $HTML = '  <!--  footer container -->';

    # Display big Footer if so chosen
    if (lade_db_einstellung('display_big_footer') == 'on'){
        $HTML .= footer_content_left_column();
        $HTML .= footer_content_right_column();
        $HTML = row_builder($HTML, 'big_footer_row');
        $HTML = container_builder($HTML, 'big_footer_container');
    }

    # Copyright
    $HTML .= footer_content_copyright();

    return $HTML;
}

function footer_content_right_column(){

    # Initialize container
    $HTML = '  <!--  content footer about -->';
    $HTML .= '<div class="col l3 s12">';

    # Title
    $HTML .= lade_db_einstellung('big_footer_right_column_html');

    # Close container
    $HTML .= '</div>';

    return $HTML;

}

function footer_content_left_column(){

    # Initialize column
    $HTML = '  <!--  content footer connect -->';
    $HTML .= '<div class="col l6 s12">';

    # Content
    $HTML .= lade_db_einstellung('big_footer_left_column_html');

    # Close column
    $HTML .= '</div>';

    return $HTML;

}

function footer_content_copyright(){

    # Initialize copyright div
    $HTML = '  <!--  content footer copyright -->';
    $HTML .= '<div class="footer-copyright">';

    # Open copyright container
    $HTML .= '<div class="container">';
    $HTML .= '&copy; '.date('Y').' '.lade_db_einstellung('site_footer_name');
    $HTML .= '</div>';

    # Close copyright div
    $HTML .= '</div>';

    return $HTML;
}

function site_navbar(){

    $HTML = '<!--  navbar   -->';

    $HTML .= "<ul id='dropdown1' class='dropdown-content'>";
    $HTML .= '<li><a href="./my_reservations.php">Reservierungen</a></li>';
    $HTML .= '<li><a href="./usereinstellungen.php">Einstellungen</a></li>';
    $HTML .= "</ul>";

    $HTML .= '<nav class="'.lade_db_einstellung('site_menue_color').'" role="navigation">';
    $HTML .= '<div class="nav-wrapper container '.lade_db_einstellung('site_menue_color').'">';

    $HTML .= navbar_links_big();
    $HTML .= navbar_links_mobile();

    $HTML .= '</div>';
    $HTML .= '</nav>';

    return $HTML;
}

function navbar_links_big(){

    if(lade_xml_einstellung('site_name_html')==''){
        $SiteName = lade_db_einstellung('site_name');
    } else {
        $SiteName = lade_xml_einstellung('site_name_html');
    }

    $HTML = '<a id="logo-container" href="./index.php" class="brand-logo">'.$SiteName.'</a>';
    $HTML .= '<ul class="right hide-on-med-and-down">';

    #Load all available Menue sites
    $link = connect_db();
    $Anfrage = "SELECT * FROM homepage_sites WHERE delete_user = 0 AND menue_rang > 0 AND show_in_main_menue = 'on' ORDER BY menue_rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for ($x=1;$x<=$Anzahl;$x++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $HTML .= "<li><a href='./index.php?tab=".$Ergebnis['name']."'>".$Ergebnis['menue_text']."</a></li>";
    }

    #Load available sites according to login and rights status
    $UserID = lade_user_id();
    $ColorSpan = "<span class='".lade_xml_einstellung('site_menue_text_color')."'>";
    $EndColorSpan = "</span>";

    if($UserID>0){

        #Load User Meta -> find user rights
        $UserMeta = lade_user_meta($UserID);

        if($UserMeta['ist_wart'] == 'true'){
            $HTML .= "<ul id='dropdown2' class='dropdown-content'>";
            $HTML .= '<li><a href="../wartwesen.php">'.$ColorSpan.'Wartwesen'.$EndColorSpan.'</a></li>';
            $HTML .= "<li><a href='termine.php'>".$ColorSpan."Termine".$EndColorSpan."</a></li>";
            $HTML .= "<li><a href=\"ausfaelle.php\">".$ColorSpan."Ausf&aumllle".$EndColorSpan."</a></li>";
            $HTML .= "<li><a href=\"reservierungsmanagement.php\">".$ColorSpan."Reservierungen".$EndColorSpan."</a></li>";
            $HTML .= "<li><a href=\"schluesselmanagement.php\">".$ColorSpan."Schl&uumlssel".$EndColorSpan."</a></li>";
            $HTML .= "<li><a href=\"benutzermanagement_wart.php\">".$ColorSpan."User".$EndColorSpan."</a></li>";
            $HTML .= "<li><a href=\"wartfinanzen.php\">".$ColorSpan."Wartfinanzen".$EndColorSpan."</a></li>";
            if(site_exists('wartwiki')){
                $HTML .= "<li><a href=\"index.php?tab=wartwiki\">".$ColorSpan."Wartwiki".$EndColorSpan."</a></li>";
            }
            if($UserMeta['ist_kasse'] == 'true'){
                $HTML .= '<li><a href="./kassenwart.php">'.$ColorSpan.'Kasse'.$EndColorSpan.'</a></li>';
            }
            if($UserMeta['ist_admin'] == 'true'){
                $HTML .= '<li><a href="./administration.php">'.$ColorSpan.'Admin'.$EndColorSpan.'</a></li>';
            }
            $HTML .= "</ul>";
            $HTML .= '<li><a class="dropdown-trigger" href="#!" data-target="dropdown2">'.$ColorSpan.'Wartfunktionen<i class="material-icons right">arrow_drop_down</i>'.$EndColorSpan.'</a></li>';
        } else {
            if($UserMeta['ist_admin'] == 'true'){
                $HTML .= "<ul id='dropdown2' class='dropdown-content'>";
                $HTML .= '<li><a href="./administration.php">'.$ColorSpan.'Admin'.$EndColorSpan.'</a></li>';
                $HTML .= "</ul>";
                $HTML .= '<li><a class="dropdown-trigger" href="#!" data-target="dropdown2">'.$ColorSpan.'Administration<i class="material-icons right">arrow_drop_down</i>'.$EndColorSpan.'</a></li>';
            }
        }

        $HTML .= '<li><a class="dropdown-trigger" href="#!" data-target="dropdown1">'.$ColorSpan.'Buchungstool<i class="material-icons right">arrow_drop_down</i>'.$EndColorSpan.'</a></li>';
        $HTML .= '<li><a href="./logout.php">'.$ColorSpan.'Logout'.$EndColorSpan.'</a></li>';

    } else{
        $HTML .= '<li><a href="'.lade_xml_einstellung('site_url').'/login.php">'.$ColorSpan.'Login'.$EndColorSpan.'</a></li>';
    }

    $HTML .= '</ul>';

    return $HTML;
}

function navbar_links_mobile(){

    $HTML = '<ul id="nav-mobile" class="sidenav '.lade_db_einstellung('site_menue_color').'">';

    $TextColor = lade_xml_einstellung('site_menue_text_color');

    #Load all available Menue sites
    $link = connect_db();
    $Anfrage = "SELECT * FROM homepage_sites WHERE delete_user = 0 AND menue_rang != 0 AND show_in_main_menue = 'on' ORDER BY menue_rang ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    for ($x=1;$x<=$Anzahl;$x++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $HTML .= "<li><a href='./index.php?tab=".$Ergebnis['name']."' class='".$TextColor."'>".$Ergebnis['menue_text']."</a></li>";
    }

    #Load available sites according to login and rights status
    $UserID = lade_user_id();
    if($UserID>0){
        #Load User Meta -> find user rights
        $UserMeta = lade_user_meta($UserID);
        if($UserMeta['ist_admin'] == 'true'){
            $HTML .= '<li><a href="./administration.php" class="'.$TextColor.'">Admin</a></li>';
        }

        if($UserMeta['ist_wart'] == 'true'){
            $HTML .= "<li><div class=\"divider\"></div></li>";
            $HTML .= "<li><a class=\"subheader ".$TextColor."\">Wartfunktionen</a></li>";
            $HTML .= '<li><a href="../wartwesen.php" class="'.$TextColor.'">Wartwesen</a></li>';
            $HTML .= "<li><a href=\"../termine.php\" class='".$TextColor."'>Termine</a></li>";
            $HTML .= "<li><a href=\"../ausfaelle.php\" class='".$TextColor."'>Ausf&aumllle</a></li>";
            $HTML .= "<li><a href=\"../reservierungsmanagement.php\" class='".$TextColor."'>Reservierungen</a></li>";
            $HTML .= "<li><a href=\"../schluesselmanagement.php\" class='".$TextColor."'>Schl&uumlssel</a></li>";
            $HTML .= "<li><a href=\"../benutzermanagement_wart.php\" class='".$TextColor."'>User</a></li>";
            $HTML .= "<li><a href=\"wartfinanzen.php\" class='".$TextColor."'>Wartfinanzen</a></li>";
            if(site_exists('wartwiki')){
                $HTML .= "<li><a href=\"index.php?tab=wartwiki\" class='".$TextColor."'>Wartwiki</a></li>";
            }
            if($UserMeta['ist_kasse'] == 'true'){
                $HTML .= '<li><a href="./kassenwart.php" class="'.$TextColor.'">Kasse</a></li>';
            }
        }

        $HTML .= "<li><div class=\"divider\"></div></li>";
        $HTML .= '<li><a href="./my_reservations.php" class="'.$TextColor.'">Reservierungen</a></li>';
        $HTML .= '<li><a href="./usereinstellungen.php" class="'.$TextColor.'">Einstellungen</a></li>';
        $HTML .= '<li><a href="./logout.php"class="'.$TextColor.'">Logout</a></li>';
    } else{
        $HTML .= '<li><a href="'.lade_xml_einstellung('site_url').'/login.php"class="'.$TextColor.'">Login</a></li>';
    }

    $HTML .= '</ul>';
    $HTML .= '<a href="./index.php" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons '.$TextColor.'">menu</i></a>';

    return $HTML;
}

?>