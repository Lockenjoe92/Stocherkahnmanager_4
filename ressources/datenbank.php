<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 14.06.18
 * Time: 23:31
 */

function connect_db(){

    $host = lade_xml_einstellung('db_host', 'db');
    $user = lade_xml_einstellung('db_user', 'db');
    $pswd = lade_xml_einstellung('db_pswd', 'db');
    $name = lade_xml_einstellung('db_dbname', 'db');

    $sql = new mysqli($host,$user,$pswd,$name);

    /* check for an error code */
    if ( mysqli_connect_errno() ) {
        /* oh no! there was an error code, what's the problem?! */
        echo 'There was an error with your connection: '.mysqli_connect_error();
    }

    return $sql;

}

?>