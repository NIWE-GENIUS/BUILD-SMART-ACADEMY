<?php
// courses/enroll.php
// Free enrollment handler

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = getCurrentUserId();

if (!$course_id) {
    redirect('courses/');
}

// Get course details
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id, is_paid, price FROM courses WHERE id = ? AND status = 'published'");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect('courses/');
}

// Check if already enrolled
if (isEnrolled($user_id, $course_id)) {
    redirect('courses/course-details.php?id=' . $course_id);
}

// Check if course is paid
if ($course['is_paid'] && !hasLifetimeFree()) {
    redirect('payment/checkout.php?course_id=' . $course_id);
}

// Enroll user for free
$stmt = $db->prepare("
    INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)
");
$stmt->execute([$user_id, $course_id]);

// Initialize user unit progress for all units
$stmt = $db->prepare("
    SELECT id FROM course_units WHERE course_id = ?
");
$stmt->execute([$course_id]);
$units = $stmt->fetchAll();

$stmt = $db->prepare("
    INSERT INTO user_unit_progress (user_id, course_id, unit_id, status)
    VALUES (?, ?, ?, 'locked')
");
foreach ($units as $unit) {
    $stmt->execute([$user_id, $course_id, $unit['id']]);
}

// Unlock first unit
$first_unit = $units[0] ?? null;
if ($first_unit) {
    $stmt = $db->prepare("
        UPDATE user_unit_progress SET status = 'in_progress'
        WHERE user_id = ? AND course_id = ? AND unit_id = ?
    ");
    $stmt->execute([$user_id, $course_id, $first_unit['id']]);
}

// Create notification
createNotification($user_id, 'Course Enrolled', 'You have successfully enrolled in ' . $course['title'], 'course');

// Redirect to course player
redirect('courses/continue.php?id=' . $course_id);
?>