<?php
// upload_announcement_admin.php - Admin page to manage admission announcements.
session_start();

// Use Composer's autoloader.
require __DIR__ . '/../vendor/autoload.php';

// This file should contain the function `uploadToCloudinary($file, $folder)`
require_once "./cloudinary_upload_handler.php";

// Make sure to update the path to your config.php file if it's incorrect.
require_once __DIR__ . "/../config.php";



// Check if the user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Store the message in session before redirecting
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Only Admins can manage staff records.</p>"; // Updated message
    // Path from 'admin/' up to 'School/' is '../'
    header("location: ../login.php"); // Redirect to login if not logged in or not admin
    exit;
}
// A basic HTML template to display the page
function render_page($link, $message = "", $is_success = true) {
    // Fetch current data to pre-fill the form
    $current_settings = [];
    $settings_result = mysqli_query($link, "SELECT * FROM admissions_settings");
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }

    $current_dates_string = '';
    $dates_result = mysqli_query($link, "SELECT * FROM admission_dates ORDER BY display_order ASC");
    while ($row = mysqli_fetch_assoc($dates_result)) {
        $current_dates_string .= htmlspecialchars($row['event_name']) . ': ' . date("Y-m-d", strtotime($row['event_date'])) . "\n";
    }

    $current_docs_string = '';
    $documents_result = mysqli_query($link, "SELECT * FROM required_documents ORDER BY display_order ASC");
    while ($row = mysqli_fetch_assoc($documents_result)) {
        $current_docs_string .= htmlspecialchars($row['document_name']) . "\n";
    }

    // Include the header file 
?>

<?php 
require_once "./admin_header.php";
?>
    <div class="bg-white p-8 rounded-lg shadow-xl max-w-2xl w-full mx-auto my-8">
        <h1 class="text-3xl font-bold text-center text-teal-600 mb-6">Manage Admission Announcement</h1>

        <?php if (!empty($message)): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $is_success ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="upload_announcement_admin.php" method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="page_title" class="block text-gray-700 font-semibold mb-2">Page Title</label>
                <input type="text" id="page_title" name="page_title" required
                       value="<?php echo htmlspecialchars($current_settings['page_title'] ?? ''); ?>"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label for="page_subtitle" class="block text-gray-700 font-semibold mb-2">Page Subtitle</label>
                <input type="text" id="page_subtitle" name="page_subtitle"
                       value="<?php echo htmlspecialchars($current_settings['page_subtitle'] ?? ''); ?>"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
                <label for="eligibility_text" class="block text-gray-700 font-semibold mb-2">Eligibility Criteria</label>
                <textarea id="eligibility_text" name="eligibility_text" rows="4"
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"><?php echo htmlspecialchars($current_settings['eligibility_text'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="announcement_photo" class="block text-gray-700 font-semibold mb-2">Announcement Photo (JPG, PNG)</label>
                <input type="file" id="announcement_photo" name="announcement_photo" accept=".jpg, .jpeg, .png"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
            </div>
             
            <div>
                <label for="important_dates" class="block text-gray-700 font-semibold mb-2">Important Dates (Event: YYYY-MM-DD, one per line)</label>
                <textarea id="important_dates" name="important_dates" rows="4"
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"><?php echo htmlspecialchars($current_dates_string); ?></textarea>
            </div>
            <div>
                <label for="required_documents" class="block text-gray-700 font-semibold mb-2">Required Documents (One per line)</label>
                <textarea id="required_documents" name="required_documents" rows="4"
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"><?php echo htmlspecialchars($current_docs_string); ?></textarea>
            </div>
            
            <button type="submit" name="update_announcement"
                    class="w-full  bg-teal-200 hover:bg-teal-200 bg-red-300 text-black font-bold py-3 px-6 rounded-lg shadow-md transition duration-300">
                Update Announcement
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="admin.php" class="text-teal-600 hover:underline font-semibold">Go back to Admin Dashboard</a>
        </div>
    </div>
<?php
    // Include the footer file
    require_once "./admin_footer.php";
}

// --- MAIN LOGIC ---

if (isset($_POST['update_announcement'])) {
    // Sanitize and collect form data
    $page_title = mysqli_real_escape_string($link, trim($_POST['page_title']));
    $page_subtitle = mysqli_real_escape_string($link, trim($_POST['page_subtitle']));
    $eligibility_text = mysqli_real_escape_string($link, trim($_POST['eligibility_text']));

    $photoUrl = null;
    $pdfUrl = null;
    $hasPhoto = isset($_FILES['announcement_photo']) && $_FILES['announcement_photo']['error'] === UPLOAD_ERR_OK;
    $hasPdf = isset($_FILES['admission_pdf']) && $_FILES['admission_pdf']['error'] === UPLOAD_ERR_OK;

    // Fetch existing URLs from the database to avoid overwriting them if no new file is uploaded
    $announcement_result = mysqli_query($link, "SELECT photo_url, pdf_url FROM admissions_details ORDER BY id DESC LIMIT 1");
    if ($announcement_result && mysqli_num_rows($announcement_result) > 0) {
        $announcement_row = mysqli_fetch_assoc($announcement_result);
        $photoUrl = $announcement_row['photo_url'];
        $pdfUrl = $announcement_row['pdf_url'];
    }

    if ($hasPhoto) {
        $uploadResult = uploadToCloudinary($_FILES['announcement_photo'], 'announcements');
        if (is_array($uploadResult) && isset($uploadResult['secure_url'])) {
            $photoUrl = mysqli_real_escape_string($link, $uploadResult['secure_url']);
        } else {
            render_page($link, "Photo upload failed: " . ($uploadResult['error'] ?? 'Unknown error.'), false);
            mysqli_close($link);
            exit();
        }
    }

    if ($hasPdf) {
        $uploadResult = uploadToCloudinary($_FILES['admission_pdf'], 'admissions_pdfs');
        if (is_array($uploadResult) && isset($uploadResult['secure_url'])) {
            $pdfUrl = mysqli_real_escape_string($link, $uploadResult['secure_url']);
        } else {
            render_page($link, "PDF upload failed: " . ($uploadResult['error'] ?? 'Unknown error.'), false);
            mysqli_close($link);
            exit();
        }
    }

    // Update admissions_settings table
    $settings_to_update = [
        'page_title' => $page_title,
        'page_subtitle' => $page_subtitle,
        'eligibility_text' => $eligibility_text,
    ];
    
    foreach ($settings_to_update as $key => $value) {
        $sql = "INSERT INTO `admissions_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = ?";
        $stmt = mysqli_prepare($link, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Update admissions_details table (or insert a new record if none exists)
$sql_details = "INSERT INTO `admissions_details` (id, photo_url) VALUES (1, ?) 
                ON DUPLICATE KEY UPDATE photo_url = VALUES(photo_url)";
    $stmt_details = mysqli_prepare($link, $sql_details);
    $stmt_details = mysqli_prepare($link, $sql_details);
if ($stmt_details) {
    mysqli_stmt_bind_param($stmt_details, "s", $photoUrl);
    mysqli_stmt_execute($stmt_details);
    mysqli_stmt_close($stmt_details);
}

    
    // Process important dates
    mysqli_query($link, "TRUNCATE TABLE `admission_dates`");
    $dates_array = explode("\n", trim($_POST['important_dates']));
    $display_order = 1;
    $sql_dates = "INSERT INTO `admission_dates` (`event_name`, `event_date`, `display_order`) VALUES (?, ?, ?)";
    $stmt_dates = mysqli_prepare($link, $sql_dates);
    if ($stmt_dates) {
        foreach ($dates_array as $date_line) {
            $parts = explode(':', $date_line, 2);
            if (count($parts) === 2) {
                $event_name = trim($parts[0]);
                $event_date = date("Y-m-d", strtotime(trim($parts[1])));
                mysqli_stmt_bind_param($stmt_dates, "ssi", $event_name, $event_date, $display_order);
                mysqli_stmt_execute($stmt_dates);
                $display_order++;
            }
        }
        mysqli_stmt_close($stmt_dates);
    }
    
    // Process required documents
    mysqli_query($link, "TRUNCATE TABLE `required_documents`");
    $docs_array = explode("\n", trim($_POST['required_documents']));
    $display_order = 1;
    $sql_docs = "INSERT INTO `required_documents` (`document_name`, `display_order`) VALUES (?, ?)";
    $stmt_docs = mysqli_prepare($link, $sql_docs);
    if ($stmt_docs) {
        foreach ($docs_array as $doc_name) {
            $doc_name = trim($doc_name);
            if (!empty($doc_name)) {
                mysqli_stmt_bind_param($stmt_docs, "si", $doc_name, $display_order);
                mysqli_stmt_execute($stmt_docs);
                $display_order++;
            }
        }
        mysqli_stmt_close($stmt_docs);
    }

    $message = "Admission announcement details updated successfully!";
    render_page($link, $message, true);

} else {
    // Initial page load, render the form
    render_page($link);
}

mysqli_close($link);
?>
