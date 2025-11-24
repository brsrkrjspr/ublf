<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit;
}
$studentNo = $_SESSION['student']['StudentNo'];
$name = $_POST['profileName'] ?? '';
$email = $_POST['profileEmail'] ?? '';
$phone = $_POST['profilePhone'] ?? '';
$password = $_POST['profilePassword'] ?? '';
$bio = $_POST['profileBio'] ?? '';
$profilePhoto = $_SESSION['student']['ProfilePhoto'] ?? null;

// Handle profile photo upload
if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_', true) . '.' . $ext;
    $target = __DIR__ . '/../assets/uploads/' . $filename;
    if (move_uploaded_file($_FILES['profilePhoto']['tmp_name'], $target)) {
        $profilePhoto = 'assets/uploads/' . $filename;
        // Insert into profile_photo_history
        require_once __DIR__ . '/../classes/Admin.php';
        $adminObj = new Admin();
        $adminObj->addProfilePhotoSubmission($studentNo, $profilePhoto);
    }
}

$db = new Database();
$conn = $db->getConnection();

// Fetch current values if any field is empty
if (empty($name) || empty($email) || empty($phone)) {
    $stmt = $conn->prepare('SELECT StudentName, Email, PhoneNo FROM student WHERE StudentNo = :studentNo LIMIT 1');
    $stmt->execute(['studentNo' => $studentNo]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($name)) $name = $current['StudentName'];
    if (empty($email)) $email = $current['Email'];
    if (empty($phone)) $phone = $current['PhoneNo'];
}
// Check for email uniqueness
$stmt = $conn->prepare('SELECT StudentNo FROM student WHERE Email = :email AND StudentNo != :studentNo LIMIT 1');
$stmt->execute(['email' => $email, 'studentNo' => $studentNo]);
if ($stmt->fetch()) {
    $_SESSION['dashboard_msg'] = 'Email is already in use by another account.';
    header('Location: profile.php');
    exit;
}

// Enforce UB email format
$expectedEmail = $studentNo . '@ub.edu.ph';
if (strtolower($email) !== strtolower($expectedEmail)) {
    $_SESSION['dashboard_msg'] = 'Email must be your student number followed by @ub.edu.ph (e.g., ' . $expectedEmail . ')';
    header('Location: profile.php');
    exit;
}

// Update statement
if (!empty($password)) {
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE student SET StudentName = :name, Email = :email, PhoneNo = :phone, PasswordHash = :password, ProfilePhoto = :photo, Bio = :bio, PhotoConfirmed = :photoConfirmed WHERE StudentNo = :studentNo');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $passwordHash,
        'photo' => $profilePhoto,
        'bio' => $bio,
        'photoConfirmed' => ($profilePhoto !== $_SESSION['student']['ProfilePhoto']) ? 0 : ($_SESSION['student']['PhotoConfirmed'] ?? 0),
        'studentNo' => $studentNo
    ]);
} else {
    $stmt = $conn->prepare('UPDATE student SET StudentName = :name, Email = :email, PhoneNo = :phone, ProfilePhoto = :photo, Bio = :bio, PhotoConfirmed = :photoConfirmed WHERE StudentNo = :studentNo');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'photo' => $profilePhoto,
        'bio' => $bio,
        'photoConfirmed' => ($profilePhoto !== $_SESSION['student']['ProfilePhoto']) ? 0 : ($_SESSION['student']['PhotoConfirmed'] ?? 0),
        'studentNo' => $studentNo
    ]);
}
// Refresh session data
$stmt = $conn->prepare('SELECT * FROM student WHERE StudentNo = :studentNo LIMIT 1');
$stmt->execute(['studentNo' => $studentNo]);
$_SESSION['student'] = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['dashboard_msg'] = 'Profile updated successfully!';
header('Location: profile.php');
exit; 