<?php
include_once "./ressources/ressourcen.php";

//Receive from TTN
sleep(1);
$postdata = file_get_contents('php://input');
#$postdata = file_get_contents('./log_example.json');
$data=json_decode($postdata);
$voltage = $data->uplink_message->decoded_payload->batteryVoltage;
$rx_metadata = $data->uplink_message->rx_metadata;
$rssi = $rx_metadata[0]->rssi;
$key1 = $data->uplink_message->decoded_payload->key0;
$key2 = $data->uplink_message->decoded_payload->key1;
$key3 = $data->uplink_message->decoded_payload->key2;
$key4 = $data->uplink_message->decoded_payload->key3;
$key5 = $data->uplink_message->decoded_payload->key4;
$key6 = $data->uplink_message->decoded_payload->key5;
$key7 = $data->uplink_message->decoded_payload->key6;
$key8 = $data->uplink_message->decoded_payload->key7;
$key9 = $data->uplink_message->decoded_payload->key8;
$key10 = $data->uplink_message->decoded_payload->key9;
$key11 = $data->uplink_message->decoded_payload->key10;
$key12 = $data->uplink_message->decoded_payload->key11;
$key13 = $data->uplink_message->decoded_payload->key12;
$key14 = $data->uplink_message->decoded_payload->key13;
$key15 = $data->uplink_message->decoded_payload->key14;
$key16 = $data->uplink_message->decoded_payload->key15;
$key17 = $data->uplink_message->decoded_payload->key16;
$key18 = $data->uplink_message->decoded_payload->key17;
$key19 = $data->uplink_message->decoded_payload->key18;
$key20 = $data->uplink_message->decoded_payload->key19;
$key21 = $data->uplink_message->decoded_payload->key20;
$key22 = $data->uplink_message->decoded_payload->key21;
$key23 = $data->uplink_message->decoded_payload->key22;
$key24 = $data->uplink_message->decoded_payload->key23;

$schluesselcsv = $key1.",".$key2.",".$key3.",".$key4.",".$key5.",".$key6.",".$key7.",".$key8.",".$key9.",".$key10.",".$key11.",".$key12.",".$key13.",".$key14.",".$key15.",".$key16.",".$key17.",".$key18.",".$key19.",".$key20.",".$key21.",".$key22.",".$key23.",".$key24."";
$timestamp = timestamp();
$link = connect_db();

#mail('marc@haefeker.de', 'lora debugging', $postdata);

if (!($stmt = $link->prepare("INSERT INTO lora_logs (timestamp, voltage, schluessel, db) VALUES (?,?,?,?)"))){
    $Antwort['success'] = false;
    echo "Prepare failed: (" . $link->errno . ") " . $link->error;
} else {
    if (!$stmt->bind_param("sssi", $timestamp, $voltage, $schluesselcsv, $rssi)) {
        $Antwort['success'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        if (!$stmt->execute()) {
            $Antwort['success'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            echo "ok";
        }
    }
}

