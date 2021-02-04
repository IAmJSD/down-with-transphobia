<?php
// Load compose.
require "vendor/autoload.php";

// Load the postgres connection.
$pgConn = pg_connect(getenv("POSTGRES_CONNECTION_STRING"));

// Defines the sendgrid client.
$sendgrid = new SendGrid(getenv("SENDGRID_API_KEY"));

// Check the key.
if ($_GET["key"] !== getenv("INBOUND_KEY")) {
    http_response_code(400);
    echo "Invalid key.";
    exit;
}

// Get the email this is to.
$matches = array();
$email = "";
if (preg_match("/^.+ <(.+)>$/", $_POST["to"], $array) === false) {
    $email = $_POST["to"];
} else {
    $email = $matches[1];
}

// Get the identifier.
$identifier = explode("@", $email)[0];

// Select from the database.
$result = pg_query_params($pgConn, "SELECT email FROM letters WHERE id = $1", $identifier);
if ($result) {
    $addr = pg_fetch_assoc($result)[0];
    $email = new SendGrid\Mail\Mail();
    $email->setFrom($identifier . "@sg.jakegealer.me", "Down With Transphobia");
    $email->setSubject("Email from GOV.UK: " . $_POST["subject"]);
    $email->addTo($addr, "");
    $email->addContent("text/html", $_POST["html"]);
    $sendgrid->send($email);
} else {
    http_response_code(400);
}
