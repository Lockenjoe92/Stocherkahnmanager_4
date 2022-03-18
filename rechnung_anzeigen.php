<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$ForderungID = $_GET['forderung_id'];
$Forderung = lade_forderung($ForderungID);

if(($ForderungID=='')OR($Forderung['storno_user']>0)){
    header('Location: ./wartwesen.php');
    die;
} else {

    $Rechnungsnummer = $ForderungID.'-'.date('Y');

    //RES OR OTHER?
    if($Forderung['referenz_res']>0){
        $Res = lade_reservierung($Forderung['referenz_res']);
        $Stunden = stunden_differenz_berechnen($Res['beginn'], $Res['ende']);

        if($Stunden==1){
            $Stunden = "eine Stunde";
        } else {
            $Stunden = $Stunden." Stunden";
        }

        $Referenz = "Reservierung #".$Forderung['referenz_res']." - am ".date('d.m.Y', strtotime($Res['beginn'])).' für '.$Stunden;
    } else {
        $Referenz = $Forderung['referenz'];
    }

    $rechnungs_nummer = $ForderungID.'-'.date('Y');
    $rechnungs_datum = date("d.m.Y");
    $pdfAuthor = lade_db_einstellung('site_name');
    $rechnungs_header = lade_xml_einstellung('rechnungs_header');

    $Empfaenger = lade_user_meta($Forderung['von_user']);
    $rechnungs_empfaenger = $Empfaenger['vorname'].'&nbsp;'.$Empfaenger['nachname'].'<br>'.$Empfaenger['strasse'].'&nbsp;'.$Empfaenger['hausnummer'].'<br>'.$Empfaenger['plz'].'&nbsp;'.$Empfaenger['stadt'];

    $rechnungs_footer = lade_xml_einstellung('rechnungs_footer');

//Auflistung eurer verschiedenen Posten im Format [Produktbezeichnung, Menge, Einzelpreis]
    $rechnungs_posten = array(
        array($Referenz, 1, $Forderung['betrag']));

//Höhe eurer Umsatzsteuer. 0.19 für 19% Umsatzsteuer
    $umsatzsteuer = 0.0;

    $pdfName = "Rechnung_".$Rechnungsnummer.".pdf";

    $html = '
<table cellpadding="5" cellspacing="0" style="width: 100%; ">
 <tr>
 <td>'.nl2br(trim($rechnungs_header)).'</td>
    <td style="text-align: right">
Rechnungsnummer '.$rechnungs_nummer.'<br>
Rechnungsdatum: '.$rechnungs_datum.'<br>
 </td>
 </tr>
 
 <tr>
 <td style="font-size:1.3em; font-weight: bold;">
<br><br>
Rechnung
<br>
 </td>
 </tr>
 
 
 <tr>
 <td colspan="2">'.nl2br(trim($rechnungs_empfaenger)).'</td>
 </tr>
</table>
<br><br><br>
 
<table cellpadding="5" cellspacing="0" style="width: 100%;" border="0">
 <tr style="background-color: #cccccc; padding:5px;">
 <td style="padding:5px;"><b>Bezeichnung</b></td>
 <td style="text-align: center;"><b>Menge</b></td>
 <td style="text-align: center;"><b>Einzelpreis</b></td>
 <td style="text-align: center;"><b>Preis</b></td>
 </tr>';


    $gesamtpreis = 0;

    foreach($rechnungs_posten as $posten) {
        $menge = $posten[1];
        $einzelpreis = $posten[2];
        $preis = $menge*$einzelpreis;
        $gesamtpreis += $preis;
        $html .= '<tr>
                <td>'.$posten[0].'</td>
 <td style="text-align: center;">'.$posten[1].'</td> 
 <td style="text-align: center;">'.number_format($posten[2], 2, ',', '').' Euro</td>	
                <td style="text-align: center;">'.number_format($preis, 2, ',', '').' Euro</td>
              </tr>';
    }
    $html .="</table>";



    $html .= '
<hr>
<table cellpadding="5" cellspacing="0" style="width: 100%;" border="0">';
    if($umsatzsteuer > 0) {
        $netto = $gesamtpreis / (1+$umsatzsteuer);
        $umsatzsteuer_betrag = $gesamtpreis - $netto;

        $html .= '
 <tr>
 <td colspan="3">Zwischensumme (Netto)</td>
 <td style="text-align: center;">'.number_format($netto , 2, ',', '').' Euro</td>
 </tr>
 <tr>
 <td colspan="3">Umsatzsteuer ('.intval($umsatzsteuer*100).'%)</td>
 <td style="text-align: center;">'.number_format($umsatzsteuer_betrag, 2, ',', '').' Euro</td>
 </tr>';
    }

    $html .='
            <tr>
                <td colspan="3"><b>Gesamtsumme: </b></td>
                <td style="text-align: center;"><b>'.number_format($gesamtpreis, 2, ',', '').' Euro</b></td>
            </tr> 
        </table>
<br><br><br>';

    if($umsatzsteuer == 0) {
        $html .= 'Nach § 19 Abs. 1 UStG wird keine Umsatzsteuer berechnet.<br><br>';
    }

    $html .= nl2br($rechnungs_footer);

// Erstellung des PDF Dokuments
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Dokumenteninformationen
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfAuthor);
    $pdf->SetTitle('Rechnung '.$rechnungs_nummer);
    $pdf->SetSubject('Rechnung '.$rechnungs_nummer);


// Header und Footer Informationen
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Auswahl des Font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Auswahl der MArgins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Automatisches Autobreak der Seiten
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Image Scale
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Schriftart
    $pdf->SetFont('dejavusans', '', 10);

// Neue Seite
    $pdf->AddPage();

// Fügt den HTML Code in das PDF Dokument ein
    $pdf->writeHTML($html, true, false, true, false, '');

//Ausgabe der PDF

//Variante 1: PDF direkt an den Benutzer senden:
    $pdf->Output($pdfName, 'I');
    #
    #//Variante 2: PDF im Verzeichnis abspeichern:
#$pdf->Output(dirname(__FILE__).'/rechnungen/'.$pdfName, 'F');
#echo 'PDF herunterladen: <a href="'.$pdfName.'">'.$pdfName.'</a>';

}
