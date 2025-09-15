<?php
// School/admin/view_contact_queries.php

// --- PHPMailer Integration ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start the session
session_start();

// --- DEPENDENCIES ---
require_once "../config.php";
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mail_config.php';

// --- ACCESS CONTROL ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'principal'])) {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. You do not have permission to view this page.</p>";
    header("location: ./admin_dashboard.php");
    exit;
}

$pageTitle = "Contact Form Queries";

// --- HANDLE REPLY FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_reply'])) {
    $submission_id = trim($_POST['submission_id']);
    $reply_message = trim($_POST['reply_message']);
    $recipient_email = trim($_POST['recipient_email']);
    $recipient_name = trim($_POST['recipient_name']);
    $original_subject = trim($_POST['original_subject']);
    $replied_by = $_SESSION['name'] ?? 'Admin';

    if (empty($reply_message) || empty($submission_id) || empty($recipient_email)) {
        $_SESSION['operation_message'] = "<p class='text-red-600'>Error: Missing required fields to send reply.</p>";
    } else {
        // 1. Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($recipient_email, $recipient_name);
            $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

            $mail->isHTML(true);
           $mail->Subject = "Re: " . htmlspecialchars($original_subject);

$mail->Body = '
<div style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; border-radius: 8px; border: 1px solid #e1e4e8;">
    <p>Dear <strong>' . htmlspecialchars($recipient_name) . '</strong>,</p>

    <p>Thank you for reaching out to us. Below is our response regarding your query on "<strong>' . htmlspecialchars($original_subject) . '</strong>":</p>

    <div style="margin: 20px 0; padding: 15px; border-left: 5px solid #4a90e2; background-color: #eef4fb; color: #333;">
        ' . nl2br(htmlspecialchars($reply_message)) . '
    </div>

    <p>If you have any further questions, feel free to contact us again.</p>

    <p>Best regards,<br>
    <strong>The Team at Basic Public School</strong></p>

    <hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 13px; color: #999;">This is an team  message from the contact response system of Basic Public School.</p>
</div>';

$mail->AltBody = 
"Dear " . htmlspecialchars($recipient_name) . ",\n\n" .
"Thank you for contacting us. Here is our response regarding your query about '" . htmlspecialchars($original_subject) . "':\n\n" .
$reply_message . "\n\n" .
"Sincerely,\nThe Team at  Basic Public School";


            $mail->send();

            // 2. Save Reply to Database
            $sql_save_reply = "INSERT INTO contact_replies (submission_id, replied_by_staff_name, reply_message) VALUES (?, ?, ?)";
            if ($stmt_save = mysqli_prepare($link, $sql_save_reply)) {
                mysqli_stmt_bind_param($stmt_save, "iss", $submission_id, $replied_by, $reply_message);
                mysqli_stmt_execute($stmt_save);
                mysqli_stmt_close($stmt_save);
            }
            $_SESSION['operation_message'] = "<p class='text-green-600'>Reply sent successfully!</p>";

        } catch (Exception $e) {
            $_SESSION['operation_message'] = "<p class='text-red-600'>Reply could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
        }
    }
    header("location: ./view_contact_queries.php");
    exit();
}


// --- FETCH ALL QUERIES AND REPLIES ---
$queries = [];
$sql_fetch_queries = "SELECT cs.*, cr.reply_message, cr.replied_by_staff_name, cr.replied_at 
                      FROM contact_form_submissions cs 
                      LEFT JOIN contact_replies cr ON cs.id = cr.submission_id 
                      ORDER BY cs.submitted_at DESC";

if ($result = mysqli_query($link, $sql_fetch_queries)) {
    $queries = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $operation_message = "<p class='text-red-600'>Error fetching contact queries.</p>";
}

// Include the admin header
require_once "./admin_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">Contact Form Queries</h1>

    <?php if (isset($_SESSION['operation_message'])): ?>
        <div class="max-w-4xl mx-auto mb-6 p-4 rounded-md <?php echo strpos($_SESSION['operation_message'], 'success') !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo $_SESSION['operation_message']; unset($_SESSION['operation_message']); ?>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <?php if (empty($queries)): ?>
            <p class="text-center text-gray-500">No contact queries found.</p>
        <?php else: ?>
            <?php foreach ($queries as $query): ?>
                <div class="bg-white rounded-lg shadow-md" x-data="{ open: false }">
                    <div @click="open = !open" class="p-4 cursor-pointer flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($query['subject']); ?></p>
                            <p class="text-sm text-gray-600">From: <?php echo htmlspecialchars($query['name']); ?> (<?php echo htmlspecialchars($query['email']); ?>)</p>
                            <p class="text-xs text-gray-400">Received: <?php echo date('d-M-Y h:i A', strtotime($query['submitted_at'])); ?></p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <?php if ($query['reply_message']): ?>
                                <span class="px-3 py-1 text-xs font-bold text-green-800 bg-green-100 rounded-full">Replied</span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-bold text-yellow-800 bg-yellow-100 rounded-full">Pending</span>
                            <?php endif; ?>
                            <svg class="h-6 w-6 text-gray-500 transition-transform" :class="{ 'transform rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <div x-show="open" x-transition class="p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-700 mb-2">Original Message:</h4>
                        <div class="bg-gray-50 p-3 rounded-md text-gray-800 mb-6">
                            <?php echo nl2br(htmlspecialchars($query['message'])); ?>
                        </div>

                        <?php if ($query['reply_message']): ?>
                            <h4 class="font-semibold text-gray-700 mb-2">Your Reply:</h4>
                            <div class="bg-blue-50 p-3 rounded-md">
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($query['reply_message'])); ?></p>
                                <p class="text-xs text-gray-500 mt-2 text-right">Replied by <?php echo htmlspecialchars($query['replied_by_staff_name']); ?> on <?php echo date('d-M-Y h:i A', strtotime($query['replied_at'])); ?></p>
                            </div>
                        <?php else: ?>
                            <h4 class="font-semibold text-gray-700 mb-2">Send Reply:</h4>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="submission_id" value="<?php echo $query['id']; ?>">
                                <input type="hidden" name="recipient_email" value="<?php echo htmlspecialchars($query['email']); ?>">
                                <input type="hidden" name="recipient_name" value="<?php echo htmlspecialchars($query['name']); ?>">
                                <input type="hidden" name="original_subject" value="<?php echo htmlspecialchars($query['subject']); ?>">
                                <textarea name="reply_message" rows="5" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Type your reply here..." required></textarea>
                                <div class="text-right mt-4">
                                    <button type="submit" name="send_reply" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">Send Reply</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<?php
// Include the admin footer
require_once "./admin_footer.php";
?>
