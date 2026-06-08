<?php
// events/details.php
// Event Details and RSVP

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$event_id) {
    redirect('events/');
}

$db = Database::getConnection();

// Get event details
$stmt = $db->prepare("
    SELECT e.*, u.full_name as organizer_name
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    redirect('events/');
}

// Handle RSVP
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $user_id = getCurrentUserId();
    
    // Check if already RSVP'd
    $stmt = $db->prepare("SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO event_rsvps (event_id, user_id) VALUES (?, ?)");
        if ($stmt->execute([$event_id, $user_id])) {
            $message = '<div class="success">You have successfully RSVP\'d for this event!</div>';
            createNotification($user_id, 'Event RSVP Confirmed', 'You are registered for: ' . $event['title'], 'event');
        } else {
            $message = '<div class="error">Failed to RSVP. Please try again.</div>';
        }
    } else {
        $message = '<div class="info">You have already RSVP\'d for this event.</div>';
    }
}

// Check if user has RSVP'd
$has_rsvpd = false;
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, getCurrentUserId()]);
    $has_rsvpd = $stmt->fetch() !== false;
}

$page_title = $event['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo htmlspecialchars($event['title']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .event-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .event-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .event-header {
            margin-bottom: 25px;
        }
        
        .event-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .event-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item i {
            font-size: 20px;
            color: var(--orange);
        }
        
        .event-description {
            line-height: 1.7;
            margin: 25px 0;
        }
        
        .btn-rsvp {
            background: var(--orange);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .btn-rsvp:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info {
            background: #17a2b8;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--orange);
            text-decoration: none;
        }
        
        .add-calendar {
            display: inline-block;
            margin-left: 15px;
            color: var(--blue);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="event-container">
        <a href="<?php echo SITE_URL; ?>events/" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
        
        <div class="event-card">
            <div class="event-header">
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                <?php if ($event['is_virtual']): ?>
                    <span style="background: #17a2b8; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">
                        <i class="fas fa-video"></i> Virtual Event
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="event-info">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F d, Y', strtotime($event['event_date'])); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($event['location'] ?: 'Online Event'); ?></span>
                </div>
                <?php if ($event['is_virtual'] && $event['virtual_link']): ?>
                    <div class="info-item">
                        <i class="fas fa-link"></i>
                        <a href="<?php echo $event['virtual_link']; ?>" target="_blank">Join Link</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="event-description">
                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
            </div>
            
            <?php echo $message; ?>
            
            <?php if (isLoggedIn()): ?>
                <?php if (!$has_rsvpd): ?>
                    <form method="POST">
                        <button type="submit" class="btn-rsvp">
                            <i class="fas fa-check-circle"></i> RSVP for this Event
                        </button>
                        <a href="#" class="add-calendar" onclick="alert('Add to Google Calendar feature coming soon')">
                            <i class="fas fa-calendar-plus"></i> Add to Calendar
                        </a>
                    </form>
                <?php else: ?>
                    <div style="background: #d4edda; padding: 15px; border-radius: 8px;">
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                        You are registered for this event. We'll send you reminders.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px;">
                    <p>Please login to RSVP for this event.</p>
                    <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn-primary">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>