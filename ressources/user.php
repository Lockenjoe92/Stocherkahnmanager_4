<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.06.18
 * Time: 16:18
 */

function lade_user_id(){
    //Session initiieren
    session_start();
    $UserSessionID = intval($_SESSION['user_id']);
    return $UserSessionID;
}
function lade_user_meta($UserID){

    $link = connect_db();

    if (!($stmt = $link->prepare("SELECT * FROM user_meta WHERE user = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("s",$UserID)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Hits = mysqli_num_rows($res);
    $Result = array();
    for($a=1;$a<=$Hits;$a++){
        $Row = mysqli_fetch_assoc($res);
        $Result[$Row['schluessel']] = $Row['wert'];
    }

    if (!($stmt = $link->prepare("SELECT * FROM users WHERE id = ?"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("i", $UserID)) {
        $Antwort['erfolg'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $res = $stmt->get_result();
        $Anz = mysqli_num_rows($res);
        if($Anz>0){
            $Stuff = mysqli_fetch_assoc($res);
            $Result['registrierung'] = $Stuff['register'];
            $Result['mail'] = $Stuff['mail'];
        }
    }

    $Result['id'] = $UserID;

    return $Result;
}
function add_new_user($Vorname, $Nachname, $Strasse, $Hausnummer, $PLZ, $Stadt, $Mail, $PSWD, $Rollen, $TransferMode=false){

    $link = connect_db();

    $PSWD_hashed = password_hash($PSWD, PASSWORD_DEFAULT);
    if($PSWD_hashed == false){
        echo "Error with hashing";
    }

    #echo "adding user account";
    $ID_hash = generateRandomString(32);
    if (!($stmt = $link->prepare("INSERT INTO users (mail,secret,register_secret,register,pswd_needs_change) VALUES (?,?,?,?,?)"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    $z = 0;
    if (!$stmt->bind_param("ssssi", $Mail, $PSWD_hashed, $ID_hash, timestamp(), $z)) {
        $Antwort['erfolg'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;

    } else {
        #echo "selecting user id";
        if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
            $Antwort['erfolg'] = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("s", $Mail)) {
            $Antwort['erfolg'] = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $Ergebnis = mysqli_fetch_assoc($res);

        #Weitere Userinfos hinzufügen
        #echo "adding user meta";
        add_user_meta($Ergebnis['id'], 'vorname', $Vorname);
        add_user_meta($Ergebnis['id'], 'nachname', $Nachname);

        if(!$TransferMode){
            add_user_meta($Ergebnis['id'], 'strasse', $Strasse);
            add_user_meta($Ergebnis['id'], 'hausnummer', $Hausnummer);
            add_user_meta($Ergebnis['id'], 'plz', $PLZ);
            add_user_meta($Ergebnis['id'], 'stadt', $Stadt);
        }

        #Rollen eingeben
        foreach($Rollen as $Rolle => $Wert){
            add_user_meta($Ergebnis['id'], $Rolle, $Wert);
        }

        if(!$TransferMode) {
            $MailArray = array();
            $MailArray['[vorname_empfaenger]'] = $Vorname;
            $MailArray['[verify_link]'] = lade_xml_einstellung('site_url') . "/login.php?register_code=" . $ID_hash . "";
            mail_senden('registrierung_user', $Mail, $MailArray);
        }

        $Antwort['erfolg'] = True;
        $Antwort['meldung'] = "Dein Useraccount wurde erfolgreich angelegt!<br>Du erh&auml;ltst noch eine EMail, die den Vorgang best&auml;tigt!<br>Bitte best&auml;tige die Anmeldung indem du auf den Link in der Mail klickst!:)";
    }


    return $Antwort;
}
function add_user_meta($UserID, $Key, $Value){

    $link = connect_db();

    if (!($stmt = $link->prepare("INSERT INTO user_meta (user,schluessel,wert,timestamp) VALUES (?,?,?,?)"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("isss", $UserID, $Key, $Value, timestamp())) {
        $Antwort['erfolg'] = false;
        echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
       return true;
    }

}
function delete_user_meta($UserID, $Key, $Value){

    $link = connect_db();

    if (!($stmt = $link->prepare("DELETE FROM user_meta WHERE user = ? AND schluessel = ? AND wert = ?"))) {
        $Antwort['erfolg'] = false;
        var_dump("Prepare failed: (" . $link->errno . ") " . $link->error);
    }
    if (!$stmt->bind_param("iss", $UserID, $Key, $Value)) {
        $Antwort['erfolg'] = false;
        var_dump("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        var_dump("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    } else {
        return true;
    }

}
function update_user_meta($UserID, $Key, $Value){

    $link = connect_db();

    if($Key == 'mail'){

        if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
            $Antwort['erfolg'] = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("s",$Value)) {
            $Antwort['erfolg'] = false;
            echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            return false;
        } else {
            $Result = $stmt->get_result();
            $Anzahl = mysqli_num_rows($Result);
            if($Anzahl==0){
                if (!($stmt = $link->prepare("UPDATE users SET mail = ? WHERE id = ?"))) {
                    $Antwort['erfolg'] = false;
                    echo "Prepare failed: (" . $link->errno . ") " . $link->error;
                }
                if (!$stmt->bind_param("si", $Value, $UserID)) {
                    $Antwort['erfolg'] = false;
                    echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                if (!$stmt->execute()) {
                    $Antwort['erfolg'] = false;
                    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                } else {
                    return true;
                }
            } else {
                #Account mit gleicher Mail existiert bereits
                return false;
            }
        }
    } else {
        if (!($stmt = $link->prepare("SELECT id FROM user_meta WHERE user = ? AND schluessel = ?"))) {
            $Antwort['erfolg'] = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("is",$UserID, $Key)) {
            $Antwort['erfolg'] = false;
            echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {

            $Result = $stmt->get_result();
            $Nums = mysqli_num_rows($Result);
            if($Nums>0){
                if (!($stmt = $link->prepare("UPDATE user_meta SET wert = ?, timestamp = ? WHERE user = ? AND schluessel = ?"))) {
                    $Antwort['erfolg'] = false;
                    echo "Prepare failed: (" . $link->errno . ") " . $link->error;
                }
                $Timestamp = timestamp();
                if (!$stmt->bind_param("ssis", $Value, $Timestamp, $UserID, $Key)) {
                    $Antwort['erfolg'] = false;
                    echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                if (!$stmt->execute()) {
                    $Antwort['erfolg'] = false;
                    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                } else {
                    return true;
                }
            } elseif($Nums==0) {
                return add_user_meta($UserID, $Key, $Value);
            }
        }
    }
}
function verify_user_mail($UserID){

    $link = connect_db();
    $Anfrage = "UPDATE users SET mail_verified = '".date("Y-m-d")."' WHERE id = ".$UserID."";
    return mysqli_query($link, $Anfrage);

}
function check_password($PSWD) {

    // Define URL for haveibeenpwned.com API as constant
    define('PWNED_URL', 'https://api.pwnedpasswords.com/range/');

    // Check if password is at least 10 chars long
    if (strlen($PSWD) < 10)
        return 'Das Passwort ist zu kurz. Es muss mindestens 10 Zeichen lang sein';

    // Check if password contains numbers and letters
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9]).*$/', $PSWD))
        return 'Das Passwort muss Zahlen und Buchstaben enthalten';

    // Check if password contains at least 3 numbers
    if (preg_match_all('/[0-9]/', $PSWD) < 3)
        return 'Das Passwort muss mindestens drei Zahlen enthalten';

    // Check if password contains the words password or passwort (case insensitive)
    if (preg_match('/pa[s\$]{0,2}w[o0]{0,1}rd|pa[s\$]{0,2}w[o0]{0,1}rt/i', $PSWD))
        return 'Das Passwort darf die W&ouml;rter Passwort, Password oder Variationen davon nicht enthalten';

    // Check if password contains too many repeating chars
    if (preg_match('/(.)\1{3,}/i', $PSWD))
        return 'Das Passwort darf nicht mehr als 3 gleiche Zeichen (egal ob gro&szlig;/klein) hintereinander enthalten';

    if (preg_match('/(.{3,})\1{2,}/i', $PSWD))
        return 'Das Passwort darf keine sich wiederholenden Zeichenketten enthalten (egal ob gro&szlig;/klein, z.B. abcABCabc)';

    // Check if password contains continuous alphabetical rows
    if (preg_match('/abcde|bcdef|cdefg|defgh|efghi|fghij|ghijk|hijkl|ijklm|jklmn|klmno|lmnop|mnopq|nopqr|opqrs|pqrst|qrstu|rstuv|stuvw|tuvwx|uvwxy|vwxyz/i', $PSWD))
        return 'Das Passwort darf keine alphabetischen Zeichenketten enthalten (z.B. abcde)';

    // Check if password contains continuous ascending or descending numbers
    if (preg_match('/.*1\D{0,1}2\D{0,1}3.*|.*2\D{0,1}3\D{0,1}4.*|.*3\D{0,1}4\D{0,1}5.*|.*4\D{0,1}5\D{0,1}6.*|.*5\D{0,1}6\D{0,1}7.*|.*6\D{0,1}7\D{0,1}8.*|.*7\D{0,1}8\D{0,1}9.*|.*8\D{0,1}9\D{0,1}0.*|.*9\D{0,1}8\D{0,1}7.*|.*8\D{0,1}7\D{0,1}6.*|.*7\D{0,1}6\D{0,1}5.*|.*6\D{0,1}5\D{0,1}4.*|.*5\D{0,1}4\D{0,1}3.*|.*4\D{0,1}3\D{0,1}2.*|.*3\D{0,1}2\D{0,1}1.*/', $PSWD))
        return 'Das Passwort darf keine fortlaufenden Zahlenreihen enthalten (z.B. 1234, 9o8i7u6z)';

    // Check if password contains continuous chars from keyboard rows
    if (preg_match('/qwert|asdfg|yxcvb|zxcvb|<yxcv|<zxcv|poiuz|poiuy|üpoiu|\+üpoi|lkjhg|äölkj|mnbvc|-.,mn/i', $PSWD))
        return 'Das Passwort darf keine fortlaufenden Buchstabenreihen der Tastatur enthalten (z.B. qwert, asdfg)';

    // Check if password begins or ends with 1 or 2 numbers and does not contain any other numbers
    if (preg_match('/^[0-9]{1,2}|[0-9]{1,2}$/', $PSWD) && !preg_match('/[0-9]/', substr($PSWD, 2, -2)))
        return 'Das Passwort darf Zahlen nicht nur als Pr&auml;fix oder Suffix enthalten (z.B. passwort99)';

    // Compute SHA1 hash + convert to uppercase
    $hash_to_check = strtoupper(sha1($PSWD));

    // Split hash in two parts
    $hash_to_check_prefix = substr($hash_to_check, 0, 5);
    $hash_to_check_suffix = substr($hash_to_check, 5);

    // query haveibeenpwned.com, submit first 5 chars of the hash
    $pwned_response = file_get_contents(PWNED_URL . $hash_to_check_prefix);

    // Check HTTP connection
    if (!strpos($http_response_header[0], '200 OK')){
        $Message = "HTTP connection error to ".PWNED_URL.": ".$http_response_header[0]."";
        add_protocol_entry(0, 'check_password', $Message);
    }

    // Check, if second part of the hash is in the received list
    if (strpos($pwned_response, $hash_to_check_suffix))
        return 'Dieses Passwort wurde in geleakten Daten gefunden, bitte ein anderes verwenden.';
    else
        return 'OK';
}
function wart_verfuegbare_schluessel($IDuser, $OverrideWartschluessel=false){

    $link = connect_db();
    $ZugeteilteSchluessel = user_zugeteilte_schluessel($IDuser, $OverrideWartschluessel);

    $TageGrenze = intval(lade_xml_einstellung('zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage'));
    $ZeitBefehl = "+ ".$TageGrenze." days";
    $Grenzzeit = date("Y-m-d G:i:s", strtotime($ZeitBefehl));

    $Anfrage = "SELECT id FROM uebergaben WHERE wart = '$IDuser' AND durchfuehrung IS NULL AND beginn < '".$Grenzzeit."' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl= mysqli_num_rows($Abfrage);

    $Differenz = $ZugeteilteSchluessel - $Anzahl;

    return $Differenz;
}
function user_zugeteilte_schluessel($IDuser, $OverrideWartschluessel=false){

    $link = connect_db();

    if($OverrideWartschluessel){
        $Anfrage = "SELECT id FROM schluessel WHERE akt_user = '$IDuser' AND delete_user = '0'";
    } else {
        $Anfrage = "SELECT id FROM schluessel WHERE akt_user = '$IDuser' AND ist_wartschluessel = 'off' AND delete_user = '0'";
    }
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    return $Anzahl;
}
function generiere_kontaktinformation_fuer_usermail_wart($IDwart){

    $User = lade_user_meta($IDwart);
    $Antwort = "";
    $Antwort .= "".$User['vorname']." ".$User['nachname']."";

    //Gemäß Wartwunsch
    if ($User['mail-userinfo'] == "true"){
        $Antwort .= " - Mail: ".$User['mail']."";
    }

    if ($User['tel-userinfo'] == "true"){
        $Antwort .= " - Telefon (f&uuml;r dringende F&auml;lle): ".$User['telefon']."";
    }

    return $Antwort;
}
function get_sorted_user_array_with_user_meta_fields_old($orderBy='nachname'){

    $link = connect_db();
    if($orderBy!='id'){
        if (!($stmt = $link->prepare("SELECT schluessel, wert, user FROM user_meta WHERE schluessel = ?"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        if (!$stmt->bind_param("s",$orderBy)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    } else {
        if (!($stmt = $link->prepare("SELECT user FROM user_meta WHERE schluessel = ? ORDER BY id ASC"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        $Schluesel = 'nachname';
        if (!$stmt->bind_param("s",$Schluesel)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    }
    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $num_user = mysqli_num_rows($res);

    if($orderBy!='id') {
        $SortArray = array();
        for ($a = 1; $a <= $num_user; $a++) {
            $Ergebnis = mysqli_fetch_assoc($res);
            $array = array();
            $array['wert'] = $Ergebnis['wert'];
            $array['user'] = $Ergebnis['user'];
            array_push($SortArray, $array);
        }
    } else {
        $SortArray = array();
        for ($a = 1; $a <= $num_user; $a++) {
            $Ergebnis = mysqli_fetch_assoc($res);
            $array = array();
            $array['user'] = $Ergebnis['user'];
            array_push($SortArray, $array);
        }
    }

    asort($SortArray);

    $ReturnArray = array();
    foreach ($SortArray as $Array){
        array_push($ReturnArray, lade_user_meta($Array['user']));
    }

    return $ReturnArray;
}
function get_sorted_user_array_with_user_meta_fields($orderBy='nachname'){

    $link = connect_db();
    if($orderBy!='id'){
        if (!($stmt = $link->prepare("SELECT user FROM user_meta WHERE schluessel = ? ORDER BY wert COLLATE utf8_german2_ci"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        if (!$stmt->bind_param("s",$orderBy)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    } else {
        if (!($stmt = $link->prepare("SELECT user FROM user_meta WHERE schluessel = ? ORDER BY id ASC"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }

        $Schluesel = 'nachname';
        if (!$stmt->bind_param("s",$Schluesel)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    }
    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $num_user = mysqli_num_rows($res);
    $ReturnArray = array();

    for ($a = 1; $a <= $num_user; $a++) {
        $Ergebnis = mysqli_fetch_assoc($res);
        array_push($ReturnArray, lade_user_meta($Ergebnis['user']));
    }

    return $ReturnArray;
}
function reset_user_pswd($Mail, $Mode='selbst'){

    $link = connect_db();

    #echo "selecting user id";
    if (!($stmt = $link->prepare("SELECT * FROM users WHERE mail = ? AND deaktiviert = 0"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("s", $Mail)) {
        $Antwort['erfolg'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    $res = $stmt->get_result();
    $Anzahl = mysqli_num_rows($res);

    if($Anzahl == 1){
        $Ergebnis = mysqli_fetch_assoc($res);
        $UserMeta = lade_user_meta($Ergebnis['id']);
        if($UserMeta['ist_gesperrt'] == 'true'){
            return 'gesperrt';
        } else {
            $ID_hash = generateRandomString(32);
            $PSWD_hash = generateRandomString(32);
            $PSWD_hashed = password_hash($PSWD_hash, PASSWORD_DEFAULT);
            if($PSWD_hashed == false){
                #var_dump('Hashing Fail');
                return 'hash kaputt';
            } else {
                $Anfrage = "UPDATE users SET secret = '".$PSWD_hashed."', pswd_needs_change = 1 WHERE id = ".$Ergebnis['id']."";
                $Abfrage = mysqli_query($link, $Anfrage);
                if($Abfrage){
                    $MailInfos = array();
                    $MailInfos['[vorname_user]']=$UserMeta['vorname'];
                    $MailInfos['[passwort]']=$PSWD_hash;
                    $MailInfos['[verify_link]'] = lade_xml_einstellung('site_url') . "/login.php?register_code=" . $Ergebnis['register_secret'] . "";
                    #var_dump($MailInfos);
                    if($Mode=='selbst'){
                        if(mail_senden('passwort-zurueckgesetzt-selbst', $Mail, $MailInfos)){
                            return true;
                        }else{
                            #var_dump('MAIL fail');
                            return false;
                        }
                    } elseif($Mode=='wart'){
                        if(mail_senden('passwort-zurueckgesetzt-wart', $Mail, $MailInfos, 'passwort-zurueckgesetzt-wart')){
                            return true;
                        }else{
                            #var_dump('MAIL fail');
                            return false;
                        }
                    } elseif($Mode=='transfer'){
                        return true;
                    }

                } else {
                    #var_dump($Anfrage);
                    #var_dump('UPDATE fail');
                    return 'update fail';
                }
            }
        }
    } else {
        #var_dump('too many users');
        return 'zu viele user';
    }
}
function user_needs_pswd_change($UserID){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT pswd_needs_change FROM users WHERE id = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("i",$UserID)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Ergebnis = mysqli_fetch_assoc($res);

    if($Ergebnis['pswd_needs_change'] == 1){
        return true;
    } else {
        return false;
    }

}
function lade_user_id_from_mail($MailAdresse){
    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("s",$MailAdresse)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Ergebnis = mysqli_fetch_assoc($res);
    return $Ergebnis['id'];
}
function change_pswd_user($UserID, $PSWD, $PSWDrpt){

    $link = connect_db();
    ## DAU CHECKS BEFORE LOGIN ATTEMPT ##
    $DAUcounter = 0;
    $DAUerror = "";

    if(empty($PSWD)){
        $DAUcounter ++;
        $DAUerror .= "Gib bitte ein Passwort an!<br>";
    } else {

        $PSWDcheck = check_password($PSWD);
        if($PSWD != $PSWDrpt){
            $DAUcounter ++;
            $DAUerror .= "Die eingegebenen Passw&ouml;rter sind nicht identisch!<br>";
        }

        if($PSWDcheck != 'OK') {
            $DAUcounter++;
            $DAUerror .= $PSWDcheck;
        }
    }

    ## DAU auswerten
    if ($DAUcounter > 0){
        $Antwort['meldung'] = $DAUerror;
        $Antwort['success'] = false;
        return $Antwort;
    } else {
        $PSWD_hashed = password_hash($PSWD, PASSWORD_DEFAULT);
        if (!($stmt = $link->prepare("UPDATE users SET secret = ?, pswd_needs_change = ? WHERE id = ?"))) {
            $Antwort['success'] = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("sii", $PSWD_hashed, intval(0), $UserID)) {
            $Antwort['success'] = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['success'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
       
        } else {
            $Antwort['success']=true;
        }
    }
	
	return $Antwort;
}

function default_warteinstellungen_anlegen($User){

    add_user_meta($User, 'mail-wart-neue-uebergabe', 'true');
    add_user_meta($User, 'mail-kurzfristig-uebernahme-abgesagt', 'true');
    add_user_meta($User, 'erinnerung-wart-schluesseluebergabe-eintragen', 'true');
    add_user_meta($User, 'mail-wart-daily-update', 'true');
    add_user_meta($User, 'mail-wart-weekly-update', 'true');

}

?>