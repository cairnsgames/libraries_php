<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
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
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ss', $email, $appid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $user_id = $user['id'];
} else {
    // Create a new user
    $insertUserQuery = "INSERT INTO user (app_id, firstname, lastname, email, active) VALUES (?, ?,?,?, 1)";
    $stmt = $mysqli->prepare($insertUserQuery);
    $stmt->bind_param('ssss', $appid, $firstname, $lastname, $email);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();
    
    // Send welcome email
    $welcomeMessage = "Welcome to Juzt.Dance! Join us to get access to all your dance needs. Click here to change your password: [Change Password](http://yourwebsite.com/changepassword?user_id=$user_id)";
    // sendEmailWithSendGrid($appid, $email, "Welcome to Juzt.Dance!", $welcomeMessage);
}

// Insert booking directly into kloko_booking table
$insertBookingQuery = "INSERT INTO kloko_booking (event_id, user_id, participant_email, status, booking_time) VALUES (?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($insertBookingQuery);
$stmt->bind_param('iisss', $event_id, $user_id, $email, $status, $booking_time);

if ($stmt->execute()) {
    // Send booking confirmation email
    $eventDetails = "You have successfully booked for the event. Event Details: Event Name - $event_id";
    // sendEmailWithSendGrid($appid, $email, "Your Booking Confirmation", $eventDetails);
    
    echo json_encode(['success' => true, 'message' => 'Booking successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking failed']);
}

$stmt->close();
?>
