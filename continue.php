<?php
// courses/continue.php
// Find the next incomplete unit and redirect

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = getCurrentUserId();

if (!$course_id) {
    redirect('dashboard/my-courses.php');
}

// Check if user is enrolled
if (!isEnrolled($user_id, $course_id)) {
    redirect('courses/course-details.php?id=' . $course_id);
}

// Get all units for this course
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT id, unit_number, title FROM course_units 
    WHERE course_id = ? 
    ORDER BY unit_number ASC
");
$stmt->execute([$course_id]);
$units = $stmt->fetchAll();

// Get completed units
$stmt = $db->prepare("
    SELECT unit_id FROM user_unit_progress 
    WHERE user_id = ? AND course_id = ? AND status = 'completed'
");
$stmt->execute([$user_id, $course_id]);
$completed = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Find first incomplete unit
$next_unit = null;
foreach ($units as $unit) {
    if (!in_array($unit['id'], $completed)) {
        $next_unit = $unit;
        break;
    }
}

if ($next_unit) {
    redirect('courses/unit-player.php?course_id=' . $course_id . '&unit_id=' . $next_unit['id']);
} else {
    // All units completed - go to final exam or course details
    redirect('courses/course-details.php?id=' . $course_id);
}
?>