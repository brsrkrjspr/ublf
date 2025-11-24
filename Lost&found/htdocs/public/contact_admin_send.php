<?php
session_start();
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$to = 'foundlost004@gmail.com';
$subject = trim($_POST['contactSubject'] ?? '');
$message = trim($_POST['contactMessage'] ?? '');
$student = $_SESSION['student'];
$headers = "From: " . $student['Email'] . "\r\n";
$headers .= "Reply-To: " . $student['Email'] . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if ($subject && $message) {
    $body = "Message from: " . $student['StudentName'] . " (" . $student['Email'] . ")\n\n" . $message;
    if (mail($to, $subject, $body, $headers)) {
        $_SESSION['contact_msg'] = 'Your message has been sent to the admin.';
    } else {
        $_SESSION['contact_msg'] = 'Failed to send email. Please try again later.';
    }
} else {
    $_SESSION['contact_msg'] = 'Please fill in all fields.';
}
header('Location: contact_admin.php');
exit; 