<?php

    function ortsvorlage_anlegen($Wart, $Angabe){

        $link = connect_db();
        $Anfrage = "INSERT INTO vorlagen_ortsangaben (wart, angabe, create_time, create_user, delete_time, delete_user) VALUES ('$Wart', '$Angabe', '".timestamp()."', '".lade_user_id()."', '0000-00-00 00:00:00', '0')";
        if (mysqli_query($link, $Anfrage)){
            return true;
        } else {
            return false;
        }
    }

    function ortsvorlage_loeschen($IDvorlage){

        $link = connect_db();

        $Anfrage = "UPDATE vorlagen_ortsangaben SET delete_time = '".timestamp()."', delete_user = '".lade_user_id()."' WHERE angabe = '$IDvorlage'";
        $Abfrage = mysqli_query($link, $Anfrage);

        if ($Abfrage){
            return true;
        } else {
            return false;
        }
    }

?>