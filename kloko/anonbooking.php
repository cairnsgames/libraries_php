<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/dbutils.php"; // Include dbutils for PrepareExecSQL
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../emailer/sendemail.php";

$appid = getAppId();

// Get input data
$firstname = getParam('firstname', "");
$lastname = getParam('lastname', "");
$cell_phone = getParam('cell_phone', "");
$email = getParam('email', "");
$event_id = getParam('event_id', "");
$booking_time = getParam('booking_time', "");
$status = 'anon';
$user_id = 0;

// Check if the email already exists
$query = "SELECT id FROM user WHERE email = ? and app_id = ?";
$result = PrepareExecSQL($query, 'ss', [$email, $appid]);
$user = $result->fetch_assoc();

if ($user) {
    $user_id = $user['id'];
} else {
    // Create a new user
    $insertUserQuery = "INSERT INTO user (app_id, firstname, lastname, email, active) VALUES (?, ?,?,?, 1)";
    PrepareExecSQL($insertUserQuery, 'ssss', [$appid, $firstname, $lastname, $email]);
    $user_id = $mysqli->insert_id;
    
    // Send welcome email
    $welcomeMessage = "Welcome to Juzt.Dance! Join us to get access to all your dance needs. Click here to change your password: [Change Password](http://yourwebsite.com/changepassword?user_id=$user_id)";
    // sendEmail($appid, $email, "Welcome to Juzt.Dance!", $welcomeMessage);
}

// Insert booking directly into kloko_booking table
$insertBookingQuery = "INSERT INTO kloko_booking (event_id, user_id, participant_email, status, booking_time) VALUES (?, ?, ?, ?, ?)";
if (PrepareExecSQL($insertBookingQuery, 'iisss', [$event_id, $user_id, $email, $status, $booking_time])) {
    // Send booking confirmation email
    $eventDetails = "You have successfully booked for the event. Event Details: Event Name - $event_id";
    // sendEmail($appid, $email, "Your Booking Confirmation", $eventDetails);
    
    echo json_encode(['success' => true, 'message' => 'Booking successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking failed']);
}
?>
