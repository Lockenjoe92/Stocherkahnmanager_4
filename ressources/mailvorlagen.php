<?php

    function lade_mailvorlage($name){

        $xml = simplexml_load_file("./ressources/mailvorlagen.xml");
        $Betreff = $xml->$name->betreff;
        $Text = $xml->$name->text;

        $StrBetreff = (string) $Betreff;
        //$StrBetreff = htmlentities($StrBetreff);
        $StrText = (string) $Text;
        $StrText = htmlentities($StrText);

        $Antwort['betreff'] = $StrBetreff;
        $Antwort['text'] = $StrText;

        return $Antwort;
    }

function edit_mailvorlage($name, $Mode, $Content){

    $Content = utf8_encode($Content);

    if($Mode == 'betreff'){
        $xml = simplexml_load_file("./ressources/mailvorlagen.xml");
        $Betreff = $xml->$name->betreff;
        $xmlDoc = new DOMDocument();
        $xmlDoc->load("./ressources/mailvorlagen.xml");
        $y=$xmlDoc->getElementsByTagName($name)[0];
        $z=$y->getElementsByTagName('betreff')[0];
        $cdata = $z->firstChild;
        $cdata->replaceData(0,strlen($Betreff),$Content);
        $xmlDoc->save("./ressources/mailvorlagen.xml");
        return true;
    } elseif ($Mode == 'text'){
        $xml = simplexml_load_file("./ressources/mailvorlagen.xml");
        $Text = $xml->$name->text;
        $xmlDoc = new DOMDocument();
        $xmlDoc->load("./ressources/mailvorlagen.xml");
        $y=$xmlDoc->getElementsByTagName($name)[0];
        $z=$y->getElementsByTagName('text')[0];
        $cdata = $z->firstChild;
        $cdata->replaceData(0,strlen($Text),$Content);
        $xmlDoc->save("./ressources/mailvorlagen.xml");
        return true;
    } else {
        return false;
    }
}
?>