<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/dbutils.php"; // Include dbutils for PrepareExecSQL
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

// Retrieve event_id from the request
$event_id = getParam('id', "");

if (!isset($event_id) || empty($event_id)) {
    sendBadRequestResponse("Event ID is required.");
}

// Fetch event details from the database
$query = "SELECT *, (SELECT COUNT(1) FROM kloko_booking where event_id = kloko_event.id) booked FROM kloko_event WHERE id = ?";
$result = PrepareExecSQL($query, 'i', [$event_id]);

if ($result->num_rows > 0) {
    $event = $result->fetch_assoc();
    $event["available"] = $event["max_participants"] - $event["booked"];
    header('Content-Type: application/json');
    echo json_encode($event);
} else {
    sendNotFoundResponse("Event not found.");
}
?>
