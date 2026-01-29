<?php
header("Content-type: application/json;");
$SERVER = "localhost";
$USERNAME = "root";
$PASSWORD = "";
$DATABASE = "KagomaEnter";

$conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DATABASE);

if ($conn->connect_error) {
    echo json_encode(array(
        "status" => "error",
        "message" => "Connection Failed: " . $conn->connect_error
    ));
} else {
    echo json_encode(array(
        "status" => "success",
        "message" => "Connected successfully"
    ));
}
