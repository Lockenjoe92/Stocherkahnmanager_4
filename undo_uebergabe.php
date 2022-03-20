<?php

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
zeitformat();
$Header = "Storno Übergabe - " . lade_db_einstellung('site_name');
$Uebergabe = $_GET['uebergabe'];
$Parser = parse_undo_uebergabe($Uebergabe);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align">Schlüsselübergabe stornieren</h1>';
$HTML .= section_builder($PageTitle);

if($Parser['success'] === null){
    $Uebergabe = lade_uebergabe($Uebergabe);
    $Res = lade_reservierung($Uebergabe['res']);
    $UsrRes = lade_user_meta($Res['user']);
    $TextPrompt = 'Willst du deine Übergabe an '.$UsrRes['vorname'].' '.$UsrRes['nachname'].' vom '.strftime("%A, den %d. %B %G", strtotime($Uebergabe['durchfuehrung'])).' sicher rückgängig machen?';
    $HTML .= section_builder(prompt_karte_generieren('delete_uebergabe', 'Stornieren', 'termine.php', 'Abbrechen', $TextPrompt, true, 'storno_kommentar'));
} elseif ($Parser['success'] === true){
    $HTML .= section_builder(zurueck_karte_generieren(true, 'Übergabe erfolgreich rückgängig gemacht!', 'termine.php'));
} elseif ($Parser['success'] === false){
    $HTML .= section_builder(zurueck_karte_generieren(false, $Parser['meldung'], 'termine.php'));
}

#Put it all in a container
$HTML = container_builder($HTML, 'undo_uebergabe_page');

# Output site
echo site_header($Header);
echo site_body($HTML);


function parse_undo_uebergabe($Uebergabe){

    $Antwort['success']=null;
    $Antwort['meldung']=null;

    if(isset($_POST['delete_uebergabe'])){

        $link = connect_db();
        $Stornierender = lade_user_id();
        $Wartkonto = lade_konto_user($Stornierender);
        $Wartkontostand = lade_kontostand($Wartkonto);
        $Uebergabe = lade_uebergabe($Uebergabe);
        $Res = lade_reservierung($Uebergabe['res']);
        $Forderung = lade_forderung_res($Res['id'],true);
        if($_POST['storno_kommentar']!=''){
            $Kommentar = ", storno_kommentar = '".$_POST['storno_kommentar']."'";
        } else {
            $Kommentar = "";
        }

        //1. Schluesselausgabe löschen
        $Anfrage = "UPDATE schluesselausgabe SET storno_user = ".$Stornierender.", storno_time = '".timestamp()."'".$Kommentar." WHERE reservierung = ".$Res['id']." AND uebergabe = ".$Uebergabe['id']."";
        if(mysqli_query($link, $Anfrage)){

            //2. Zahlungen Laden
            $Anfrage = "SELECT id, betrag FROM finanz_einnahmen WHERE forderung_id = ".$Forderung['id']." AND konto_id = ".$Wartkonto['id']." AND storno_user = 0";
            $Abfrage = mysqli_query($link, $Anfrage);
            if($Abfrage){
                $ErgebnisZahlungLaden = mysqli_fetch_assoc($Abfrage);

                //3. Zahlungen Löschen
                $Anfrage = "UPDATE finanz_einnahmen SET storno_user = ".$Stornierender.", storno = '".timestamp()."' WHERE forderung_id = ".$Forderung['id']." AND konto_id = ".$Wartkonto['id']."";
                if(mysqli_query($link, $Anfrage)){

                    //4. Kontostand Wart updaten
                    $NeuerKontostand = $Wartkontostand - $ErgebnisZahlungLaden['betrag'];
                    if(update_kontostand($Wartkonto, $NeuerKontostand)){

                        //5. Uebergabedurchführung wieder auf 0000 setzen
                        $Anfrage = "UPDATE uebergaben SET durchfuehrung IS NULL WHERE id = ".$Uebergabe['id']."";
                        if(mysqli_query($link, $Anfrage)){

                            //6. Schluessel umbuchen
                            $Antwort = schluessel_umbuchen($Uebergabe['schluessel'], $Stornierender, '', $Stornierender);

                        } else {
                            $Antwort['success']=false;
                            $Antwort['meldung']='Datenbankfehler beim Zurücksetzen der Übergabe!';
                        }
                    } else {
                        $Antwort['success']=false;
                        $Antwort['meldung']='Datenbankfehler beim Updaten deines Kontostandes!';
                    }
                } else {
                    $Antwort['success']=false;
                    $Antwort['meldung']='Datenbankfehler beim Löschen der festgehaltenen Zahlung!';
                }
            } else {
                $Antwort['success']=false;
                $Antwort['meldung']='Datenbankfehler beim Laden der erfolgten Zahlungen!';
            }
        } else {
            $Antwort['success']=false;
            $Antwort['meldung']='Datenbankfehler beim Zurücksetzen der Übergabe';
        }
    }

    return $Antwort;
}