<?php

include_once "./ressources/ressourcen.php";
zeitformat();
session_manager('ist_rundmail');
$Header = "Rundmailsystem - " . lade_db_einstellung('site_name');
$Parser = parse_rundmail();
$link = connect_db();

# Generate content
# Page Title
$PageTitle = "<h1 class='center'>Rundmailsystem</h1>";
$HTML = section_builder($PageTitle);

# Statistik-Modul
$statisticHTML = "<h3 class='center'>Letzte Rundmails</h3>";
$statisticHTML .= section_recent_rundmails($link, 5);
$HTML .= section_builder($statisticHTML, '', 'center');

# Rundmail versenden Modul
$versendenHTML = "<h3 class='center'>Rundmail versenden</h3>";
if($Parser['parse_answer'] === NULL){
    $versendenHTML .= section_build_rundmail_1('');
} elseif ($Parser['parse_answer'] == FALSE){
    if($Parser['continue_form'] == 1){
        $versendenHTML .= section_build_rundmail_1($Parser);
    } elseif ($Parser['continue_form'] == 2){
        $versendenHTML .= section_build_rundmail_2($Parser);
    } elseif ($Parser['continue_form'] == 3){
        $versendenHTML .= section_build_rundmail_3($Parser, $link);
    } elseif ($Parser['continue_form'] == 4){
        $versendenHTML .= section_build_rundmail_4($Parser);
    }
} elseif ($Parser['parse_answer'] == TRUE){
    if($Parser['continue_form'] == 2){
        $versendenHTML .= section_build_rundmail_2($Parser);
    } elseif ($Parser['continue_form'] == 3){
        $versendenHTML .= section_build_rundmail_3($Parser, $link);
    } elseif ($Parser['continue_form'] == 4){
        $versendenHTML .= section_build_rundmail_4($Parser);
    }
}

$HTML .= section_builder($versendenHTML, '', '');

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function section_recent_rundmails($link, $Anzahl){

    if (!($stmt = $link->prepare("SELECT * FROM rundmails ORDER BY id DESC LIMIT ?"))) {
        $Antwort['erfolg'] = false;
        echo  __LINE__;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("i", $Anzahl)) {
        $Antwort['erfolg'] = false;
        echo  __LINE__;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo  __LINE__;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $res = $stmt->get_result();
    }

    $Hits = mysqli_num_rows($res);
    $DropdownContent = "";

    if ($Hits==0){
        $DropdownContent = collapsible_item_builder('Keine Rundmails bislang versendet!', '', 'send');
    } else {
        for($a=0;$a<$Hits;$a++){
            $Details = mysqli_fetch_assoc($res);
            #Parse details
            $Title = strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Details['send_time']))." - ".$Details['subject'];
            $ErstellerMeta = lade_user_meta($Details['creator']);
            $Ersteller = $ErstellerMeta['vorname'].' '.$ErstellerMeta['nachname'];
            $Empfaenger = load_past_recipients_rundmail($Details['id'], $link);

            $ContentTable = table_row_builder(table_header_builder('Modus').table_data_builder($Details['recipient_group']));
            $ContentTable .= table_row_builder(table_header_builder('Betreff').table_data_builder($Details['subject']));
            $ContentTable .= table_form_html_area_item('Inhalt', 'nada', $Details['content'], true);
            $ContentTable .= table_row_builder(table_header_builder('Ersteller').table_data_builder($Ersteller));
            $ContentTable .= table_row_builder(table_header_builder('Anzahl EmpfängerInnen').table_data_builder($Empfaenger[0]));

            $Content = table_builder($ContentTable);

            #Build Collapsible
            $DropdownContent .= collapsible_item_builder($Title, $Content, 'send');
        }
    }

    return collapsible_builder($DropdownContent);
}

function section_build_rundmail_1($Parser){

    if($Parser['error_message'] != ''){
        $ErrorMessage = "<p class='center'><b>".$Parser['error_message']."</b></p>";
    }
    if($Parser['parse_content']!=''){$Content = "<input type='hidden' name='old_content' value='".$Parser['parse_content']."'>";
    } else {$Content = '';}

    if(isset($Parser['parse_subject'])){
        $Betreff = $Parser['parse_subject'];
    } else {
        $Betreff = $_POST['subject'];
    }

    if(isset($Parser['parse_rundmail_mode'])){
        $Mode = $Parser['parse_rundmail_mode'];
    } else {
        $Mode = $_POST['rundmail_mode'];
    }

    if(isset($Parser['parse_rundmail_nutzergruppe'])){
        $Nutzergruppe = $Parser['parse_rundmail_nutzergruppe'];
    } else {
        $Nutzergruppe = $_POST['nutzergruppe'];
    }

    $Nutzergruppen = lade_alle_nutzgruppen();

    $FormHTML = table_form_string_item('Betreff', 'subject', $Betreff);
    $FormHTML .= table_row_builder(table_header_builder('Rundmailmodus auswählen').table_data_builder(dropdown_rundmailmodus_waehlen('rundmail_mode', $Mode)));
    $FormHTML .= table_form_dropdown_nutzergruppen_waehlen('Optional: bestimmte Nutzergruppe aussuchen', 'nutzergruppe', $Nutzergruppe, $Nutzergruppen, 'user');
    $FormHTML .= table_row_builder(table_header_builder($Content).table_data_builder(form_button_builder('check_input', 'Weiter', 'action', 'send')));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, './rundmailsystem.php', 'POST');

    return $ErrorMessage.$FormHTML;
}

function section_build_rundmail_2($Parser){
    if(isset($Parser['parse_content'])){
        $Placeholdertext = $Parser['parse_content'];
    } elseif (isset($_POST['old_content'])){
        $Placeholdertext = $_POST['old_content'];
    } else {
        $Placeholdertext = "<p>Hallo [vorname],</p><p>Dies ist eine Vorlage für eine Rundmail.</p><p>Beste Grüße,<br>Das Team vom Stocherkahn Medizin Tübingen e.V.</p>";
    }

    if($Parser['error_message'] != ''){
        $ErrorMessage = "<p class='center'><b>".$Parser['error_message']."</b></p>";
    } else {
        $ErrorMessage = '';
    }

    ### Empfängerinfos zusammenfassen ###
    $ListeEmpfaenger = load_recipients_rundmail($Parser['parse_rundmail_mode'], $Parser['parse_rundmail_nutzergruppe']);
    $AnzEmpfaenger = sizeof($ListeEmpfaenger);

    ### Collapsible mit allen Empfaengern ###
    $CollapsibleContent = "";
    foreach ($ListeEmpfaenger as $Empfaenger){
        $Vorname = $Empfaenger['vorname'];
        $Nachname = $Empfaenger['nachname'];
        $CollapsibleContent .= "- ".$Vorname." ".$Nachname."<br>";
    }
    $Collapsible = collapsible_builder(collapsible_item_builder('Auflistung aller Empfänger', $CollapsibleContent, 'people'));


    #Get Variable descriptions
    if($AnzEmpfaenger>200){
        $DescriptionVariables = "Bei mehr als 200 Nutzern können aus technischen Gründen keine personalisierten Rundmails versendet werden!<br>Bitte schreibe also einen unpersönlichen aber (trotzdem netten;)) Text.";
    } else {
        if($Parser['parse_rundmail_mode'] == 'debug'){
            $DescriptionVariables = "<ul>
        <li>[vorname] - Vorname der/s Users/in</li>
        <li>[nachname] - Nachname der/s Users/in</li>
        <li>[nutzergruppe] - Aktuelle Nutzergruppe der/s Users/in</li>
        </ul>";
        } elseif($Parser['parse_rundmail_mode'] == 'nur_ich'){
            $DescriptionVariables = "<ul>
        <li>[vorname] - Vorname der/s Users/in</li>
        <li>[nachname] - Nachname der/s Users/in</li>
        <li>[nutzergruppe] - Aktuelle Nutzergruppe der/s Users/in</li>
        </ul>";
        } elseif($Parser['parse_rundmail_mode'] == 'alle_nutzer'){
            $DescriptionVariables = "<ul>
        <li>[vorname] - Vorname der/s Users/in</li>
        <li>[nachname] - Nachname der/s Users/in</li>
        <li>[nutzergruppe] - Aktuelle Nutzergruppe der/s Users/in</li>
        </ul>";
        } elseif($Parser['parse_rundmail_mode'] == 'nur_warte'){
            $DescriptionVariables = "<ul>
        <li>[vorname] - Vorname der/s Users/in</li>
        <li>[nachname] - Nachname der/s Users/in</li>
        <li>[nutzergruppe] - Aktuelle Nutzergruppe der/s Users/in</li>
        </ul>";
        }
        elseif($Parser['parse_rundmail_mode'] == 'nur_nutzergruppe'){
            $DescriptionVariables = "<ul>
        <li>[vorname] - Vorname der/s Users/in</li>
        <li>[nachname] - Nachname der/s Users/in</li>
        <li>[nutzergruppe] - Aktuelle Nutzergruppe der/s Users/in</li>
        </ul>";
        }
    }

    $FormHTML = table_row_builder(table_header_builder('Modus').table_data_builder("<input type='hidden' name='rundmail_mode' value='".$_POST['rundmail_mode']."'>".$_POST['rundmail_mode']."<br>"."<input type='hidden' name='nutzergruppe' value='".$Parser['parse_rundmail_nutzergruppe']."'>".$Parser['parse_rundmail_nutzergruppe']));
    $FormHTML .= table_row_builder(table_header_builder('Betreff').table_data_builder("<input type='hidden' name='subject' value='".$Parser['parse_subject']."'>".$Parser['parse_subject']));
    $FormHTML .= table_row_builder(table_header_builder($AnzEmpfaenger.' Empfänger').table_data_builder("<input type='hidden' name='content' value='".$Parser['parse_content']."'>".$Collapsible));
    $FormHTML .= table_row_builder(table_header_builder('Verfügbare Variablen für Personalisierung des Mailinhaltes').table_data_builder($DescriptionVariables));
    $FormHTML .= table_form_html_area_item('Mailinhalt', 'content', $Placeholdertext);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('return_input_2', 'Zurück', 'action', 'arrow_back')).table_data_builder(form_button_builder('check_input_2', 'Eingaben prüfen', 'action', 'send')));
    $FormHTML = table_builder($FormHTML);

    $FormHTML = form_builder($FormHTML, './rundmailsystem.php', 'POST', '');

    return $ErrorMessage.$FormHTML;
}

function section_build_rundmail_3($Parser, $link){

    if($Parser['error_message'] != ''){
        $ErrorMessage = "<p class='center'><b>".$Parser['error_message']."</b></p>";
    } else {
        $ErrorMessage = '';
    }

    ### Empfängerinfos zusammenfassen ###
    $ListeEmpfaenger = load_recipients_rundmail($Parser['parse_rundmail_mode'], $Parser['parse_rundmail_nutzergruppe']);
    $AnzEmpfaenger = sizeof($ListeEmpfaenger);

    ### Collapsible mit allen Empfaengern ###
    $CollapsibleContent = "";
    foreach ($ListeEmpfaenger as $Empfaenger){
        $Vorname = $Empfaenger['vorname'];
        $Nachname = $Empfaenger['nachname'];
        $CollapsibleContent .= "- ".$Vorname." ".$Nachname."<br>";
    }
    $Collapsible = collapsible_builder(collapsible_item_builder('Auflistung aller Empfänger', $CollapsibleContent, 'people'));

    ### Mailinfos zusammenfassen ###
    $MailHTML = table_row_builder(table_header_builder('Modus').table_data_builder("<input type='hidden' name='rundmail_mode' value='".$_POST['rundmail_mode']."'>".$_POST['rundmail_mode']."<br>"."<input type='hidden' name='nutzergruppe' value='".$Parser['parse_rundmail_nutzergruppe']."'>".$Parser['parse_rundmail_nutzergruppe']));
    $MailHTML .= table_row_builder(table_header_builder('Betreff').table_data_builder("<input type='hidden' name='subject' value='".$Parser['parse_subject']."'>".$Parser['parse_subject']));
    $MailHTML .= table_row_builder(table_header_builder('Mailvorschau').table_data_builder("<input type='hidden' name='content' value='".$Parser['parse_content']."'>".$Parser['parse_content']));
    $MailHTML .= table_row_builder(table_header_builder($AnzEmpfaenger.' Empfänger').table_data_builder("<input type='hidden' name='content' value='".$Parser['parse_content']."'>".$Collapsible));
    $MailHTML .= table_row_builder(table_header_builder(form_button_builder('return_input_3', 'Zurück', 'action', 'arrow_back')).table_data_builder(form_button_builder('check_input_3', 'Senden', 'action', 'send')));
    $MailHTML = table_builder($MailHTML);

    $FormHTML = form_builder($MailHTML, './rundmailsystem.php', 'POST', '');

    return $ErrorMessage.$FormHTML;
}

function section_build_rundmail_4($Parser){
    if($Parser['parse_answer']==TRUE){
        return zurueck_karte_generieren(true, $Parser['error_message'], './rundmailsystem.php');
    } else {
        return zurueck_karte_generieren(false, $Parser['error_message'], './rundmailsystem.php');
    }
}

function parse_rundmail(){

    $link = connect_db();
    $Antwort['parse_answer'] = NULL;

    if(isset($_POST['check_input'])){

        #Check for simple input Fails
        $DAUcounter = 0;
        $DAUmessage = "";

        #Nix Betreff
        if(empty($_POST['subject'])){
            $DAUcounter++;
            $DAUmessage .= "Du musst der Mail noch ein Betreff geben!<br>";
        }

        #Nix Modus
        if(empty($_POST['rundmail_mode'])){
            $DAUcounter++;
            $DAUmessage .= "Du musst einen Rundmailmodus aussuchen!<br>";
        }

        #certain group mode
        if(($_POST['rundmail_mode']!='nur_nutzergruppe') && ($_POST['nutzergruppe']!='')){
            $DAUcounter++;
            $DAUmessage .= "Wenn du eine Rundmail nur an eine bestimmte Nutzergruppe senden möchtest, musst du den entsprechenden Rundmailmodus aussuchen!<br>";
        }

        #certain group mode
        if(($_POST['rundmail_mode']=='nur_nutzergruppe') && ($_POST['nutzergruppe']=='')){
            $DAUcounter++;
            $DAUmessage .= "Wenn du eine Rundmail nur an eine bestimmte Nutzergruppe senden möchtest, musst du den entsprechenden Nutzergruppe aussuchen!<br>";
        }

        #DAUauswerten
        if($DAUcounter>0){
            $Antwort['parse_answer'] = FALSE;
            $Antwort['error_message'] = $DAUmessage;
            $Antwort['parse_subject'] = $_POST['subject'];
            $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
            $Antwort['parse_rundmail_nutzergruppe'] = $_POST['nutzergruppe'];
            $Antwort['continue_form'] = 1;
        } else {
            #Next Step
            $Antwort['parse_answer'] = TRUE;
            $Antwort['parse_subject'] = $_POST['subject'];
            $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
            $Antwort['parse_rundmail_nutzergruppe'] = $_POST['nutzergruppe'];
            $Antwort['continue_form'] = 2;
        }

        return $Antwort;

    }
    elseif(isset($_POST['check_input_2'])){

        #Check for simple input Fails
        $DAUcounter = 0;
        $DAUmessage = "";

        #Nix Content
        if(empty($_POST['content'])){
            $DAUcounter++;
            $DAUmessage .= "Der Inhalt der Mail darf nicht leer sein!<br>";
        }

        #Eckige Klammern verkackt
        $KlammernAuf = substr_count($_POST['content'], '[');
        $KlammernZu = substr_count($_POST['content'], ']');
        if(($KlammernAuf-$KlammernZu)!=0){
            $DAUcounter++;
            $DAUmessage .= "Du hast beim Verwenden der Variablen eine Klammer vergessen - dies kann zu blödsinnigen Email-Ausgaben führen. Bitte überprüfe alle verwendeten eckigen Klammern!<br>";
        }

        #DAUauswerten
        if($DAUcounter>0){
            $Antwort['parse_answer'] = FALSE;
            $Antwort['error_message'] = $DAUmessage;
            $Antwort['continue_form'] = 2;
            $Antwort['parse_subject'] = $_POST['subject'];
            $Antwort['parse_content'] = $_POST['content'];
            $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
            $Antwort['parse_rundmail_nutzergruppe'] = $_POST['nutzergruppe'];
        } else {
            #Next Step
            $Antwort['parse_answer'] = TRUE;
            $Antwort['continue_form'] = 3;
            $Antwort['parse_subject'] = $_POST['subject'];
            $Antwort['parse_content'] = $_POST['content'];
            $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
            $Antwort['parse_rundmail_nutzergruppe'] = $_POST['nutzergruppe'];
        }

        return $Antwort;
    }
    elseif(isset($_POST['check_input_3'])){

        $Mode = $_POST['rundmail_mode'];
        $Recipients = load_recipients_rundmail($Mode, $_POST['nutzergruppe']);
        $NumberRecipients = sizeof($Recipients);
        $RundmailID = add_rundmail_protocol($link, $_POST['subject'], $_POST['content'], $Mode.'<br>'.$_POST['nutzergruppe'], $Recipients);
        $RundmailName = 'rundmail-'.$RundmailID;


        if($NumberRecipients==0){
            $Antwort['parse_answer'] = FALSE;
            $Antwort['error_message'] = 'Es gibt keine EmpfängerInnen in der ausgewählten Gruppe!';
            $Antwort['continue_form'] = 3;
        }
        elseif($NumberRecipients>200){

            $KlammernAuf = substr_count($_POST['content'], '[');
            $KlammernZu = substr_count($_POST['content'], ']');

            if(($KlammernAuf+$KlammernZu)>0){
                $Antwort['parse_answer'] = FALSE;
                $Antwort['error_message'] = "Bitte verwende keine Personalisierungsschlüssel da deine Rundmail an mehr als 200 Empfänger geht! Da diese durch eckige Klammern codiert werden, erscheint dieser Fehler auch sobald du eine eckige Klammer verwendet hast!";
                $Antwort['continue_form'] = 3;
                $Antwort['parse_subject'] = $_POST['subject'];
                $Antwort['parse_content'] = $_POST['content'];
                $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
            } else {
                //Instanz von PHPMailer bilden
                $mail = new PHPMailer\PHPMailer\PHPMailer();

                //Absenderadresse der E-Mail setzen
                $mail->addReplyTo(lade_xml_einstellung('reply_mail'), lade_xml_einstellung('site_name'));
                $mail->From = lade_xml_einstellung('absender_mail');
                $mail->Sender = lade_xml_einstellung('absender_mail');

                //HTML-Format setzen
                $mail->IsHTML(true);

                //Name des Abenders setzen
                $mail->FromName = lade_xml_einstellung('absender_name');

                //Betreff der E-Mail setzen
                $mail->Subject = $_POST['subject'];

                //Text der E-Mail setzen
                $mail->Body = html_entity_decode($_POST['content']);

                //E-Mail senden
                foreach ($Recipients as $User){
                    //Empfängeradresse setzen
                    $mail->addBcc($User['mail']);
                    add_user_rundmail_protocol($link, $User['id'], $RundmailName, 'true');
                }
                if($mail->Send()){
                    $Antwort['parse_answer'] = TRUE;
                    $Antwort['continue_form'] = 4;
                }
                else{
                    $Antwort['parse_answer'] = FALSE;
                    $Antwort['error_message'] = "Fehler beim Senden der Rundmail!";
                    $Antwort['continue_form'] = 4;
                }
            }
        }
        else {

            $SuccessCounter = 0;
            $FailCounter = 0;

            foreach ($Recipients as $Recipient){
                $IndividualBausteine = load_bausteine_rundmail($Recipient, $Mode);
                if (rundmail_senden($_POST['content'], $_POST['subject'], $Recipient['mail'], $IndividualBausteine, $RundmailName)){
                    $SuccessCounter++;
                } else {
                    $FailCounter++;
                }
            }

            if($SuccessCounter>0){
                $Antwort['parse_answer'] = TRUE;
                if($FailCounter==0){
                    $Antwort['error_message'] = "Rundmail wurde an alle UserInnen versendet!";
                } else {
                    $Antwort['error_message'] = "Rundmail konnte an ".$FailCounter." von ".sizeof($Recipients)." UserInnen nicht versendet werden!";
                }
                $Antwort['continue_form'] = 4;
            }else{
                $Antwort['parse_answer'] = FALSE;
                $Antwort['error_message'] = "";
                $Antwort['continue_form'] = 4;
            }
        }

        return $Antwort;
    }
    elseif(isset($_POST['return_input_2'])){
        $Antwort['parse_answer'] = FALSE;
        $Antwort['parse_subject'] = $_POST['subject'];
        $Antwort['parse_content'] = $_POST['content'];
        $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
        $Antwort['continue_form'] = 1;
        return $Antwort;
    }
    elseif(isset($_POST['return_input_3'])){
        $Antwort['parse_answer'] = FALSE;
        $Antwort['parse_subject'] = $_POST['subject'];
        $Antwort['parse_content'] = $_POST['content'];
        $Antwort['parse_rundmail_mode'] = $_POST['rundmail_mode'];
        $Antwort['continue_form'] = 2;
        return $Antwort;
    }
    else {
        return $Antwort;
    }
}
function load_all_current_users($link){

    $AnfrageParser = 'SELECT id, mail FROM users';
    $AbfrageParser = mysqli_query($link, $AnfrageParser);
    $AnzahlParser = mysqli_num_rows($AbfrageParser);
    $ParserArray = array();
    for($Pcount=1;$Pcount<=$AnzahlParser;$Pcount++){
        $ErgebnisParser = mysqli_fetch_assoc($AbfrageParser);
        array_push($ParserArray, lade_user_meta($ErgebnisParser['id']));
    }
    return $ParserArray;
}
function load_certain_current_users($link, $Mode){

    $AnfrageParser = 'SELECT id, mail FROM users';
    $AbfrageParser = mysqli_query($link, $AnfrageParser);
    $AnzahlParser = mysqli_num_rows($AbfrageParser);
    $ParserArray = array();
    for($Pcount=1;$Pcount<=$AnzahlParser;$Pcount++){
        $ErgebnisParser = mysqli_fetch_assoc($AbfrageParser);
        $Meta = lade_user_meta($ErgebnisParser['id']);
        if($Mode==='wart'){
            if($Meta['ist_wart']=='true'){
                array_push($ParserArray,$Meta);
            }
        } else {
            if($Meta['ist_nutzergruppe']==$Mode){
                array_push($ParserArray,$Meta);
            }
        }
    }
    return $ParserArray;
}

function load_recipients_rundmail($Mode, $Nutzergruppe=''){

    $AllUsers = [];
    $link = connect_db();

    if($Mode === 'debug'){
        array_push($AllUsers, array('mail'=>'marc@haefeker.de', 'vorname'=>'Marc', 'nachname'=>'Haefeker', 'id'=>'1'));
        array_push($AllUsers, array('mail'=>'marc.haefeker@icloud.com', 'vorname'=>'Marc2', 'nachname'=>'Haefeker', 'id'=>'1'));
        array_push($AllUsers, array('mail'=>'marc.haefeker@med.uni-tuebingen.de', 'vorname'=>'Marc3', 'nachname'=>'Haefeker', 'id'=>'1'));
    } elseif ($Mode === 'alle_nutzer'){
        $AllUsers = load_all_current_users($link);
    } elseif ($Mode === 'nur_ich'){
        $AllUsers = array(lade_user_meta(lade_user_id()));
    } elseif ($Mode==='nur_warte'){
        $AllUsers = load_certain_current_users($link, 'wart');
    } elseif ($Mode==='nur_nutzergruppe'){
        $AllUsers = load_certain_current_users($link, $Nutzergruppe);
    }

    return $AllUsers;
}
function load_bausteine_rundmail($RecipientData, $Mode = 'debug'){

    $Bausteine = array();

    if($Mode == 'debug'){
        $Bausteine['[vorname]'] = $RecipientData['vorname'];
        $Bausteine['[nachname]'] = $RecipientData['nachname'];
        $Bausteine['[nutzergruppe]'] = $RecipientData['ist_nutzergruppe'];

    }
    elseif($Mode == 'nur_ich'){
        $Bausteine['[vorname]'] = $RecipientData['vorname'];
        $Bausteine['[nachname]'] = $RecipientData['nachname'];
        $Bausteine['[nutzergruppe]'] = $RecipientData['ist_nutzergruppe'];
    }
    elseif($Mode == 'alle_nutzer'){
        $Bausteine['[vorname]'] = $RecipientData['vorname'];
        $Bausteine['[nachname]'] = $RecipientData['nachname'];
        $Bausteine['[nutzergruppe]'] = $RecipientData['ist_nutzergruppe'];
    }
    elseif($Mode == 'nur_warte'){
        $Bausteine['[vorname]'] = $RecipientData['vorname'];
        $Bausteine['[nachname]'] = $RecipientData['nachname'];
        $Bausteine['[nutzergruppe]'] = $RecipientData['ist_nutzergruppe'];
    }
    elseif($Mode == 'nur_nutzergruppe'){
        $Bausteine['[vorname]'] = $RecipientData['vorname'];
        $Bausteine['[nachname]'] = $RecipientData['nachname'];
        $Bausteine['[nutzergruppe]'] = $RecipientData['ist_nutzergruppe'];
    }

    return $Bausteine;
}
function add_rundmail_protocol($link, $Subject, $Content, $Mode, $RecipientList=''){

    $UserID = lade_user_id();

    if (!($stmt = $link->prepare("INSERT INTO rundmails (subject, content, recipient_group, recipient_list, creator) VALUES (?,?,?,?,?)"))) {
        $Antwort['erfolg'] = false;
        echo  __LINE__;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        return false;
    }
    if (!$stmt->bind_param("ssssi", $Subject, $Content, $Mode, $RecipientList, $UserID)) {
        $Antwort['erfolg'] = false;
        echo  __LINE__;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo  __LINE__;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        $res = $stmt->get_result();

        $Anfrage = "SELECT id FROM rundmails ORDER BY id DESC";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Result = mysqli_fetch_assoc($Abfrage);
        return $Result['id'];
    }
}
function add_user_rundmail_protocol($link, $user, $Type, $success='true'){
    $AnfrageMailMisserfolgSpeichern = "INSERT INTO mail_protokoll (timestamp, typ, empfaenger, erfolg) VALUES ('".timestamp()."', '$Type', '$user', '$success')";
    mysqli_query($link, $AnfrageMailMisserfolgSpeichern);
}
function load_past_recipients_rundmail($ID, $link){

    $Anfrage = "SELECT id, erfolg FROM mail_protokoll WHERE typ = 'rundmail-".$ID."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    $Yay = 0;
    $Nay = 0;

    for($a=0;$a<=$Anzahl;$a++){
        $Result = mysqli_fetch_assoc($Abfrage);
        if($Result['erfolg']=='true'){
            $Yay++;
        } else {
            $Nay++;
        }
    }

    return array($Yay,$Nay);
}