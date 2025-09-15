<?php
// admission_announcement.php - This page displays the admission announcements to the public.

session_start();
// This assumes your config.php establishes a mysqli connection named $link.
require_once "./config.php"; 

// --- School Details (can be moved to a settings table in the database) ---
$schoolName = "Basic Public School";
$schoolAddress = "Baliya chowk, Raiyam, Madhubani, Bihar, 847237";
$schoolEmail = "vc74295@gmail.com";
$schoolPhone = "+91 88777 80197";

// --- FETCH DATA FROM DATABASE ---

// 1. Fetch general settings from the 'admissions_settings' table.
// This is used for the page title, subtitle, and eligibility text.
$settings_result = mysqli_query($link, "SELECT * FROM admissions_settings");
$settings = [];
if ($settings_result) {
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// 2. Fetch all important dates from the 'admission_dates' table.
// Results are ordered for proper display.
$dates_result = mysqli_query($link, "SELECT * FROM admission_dates ORDER BY display_order ASC");
$important_dates = [];
if ($dates_result) {
    while ($row = mysqli_fetch_assoc($dates_result)) {
        $important_dates[] = $row;
    }
}

// 3. Fetch all required documents from the 'required_documents' table.
// These are also ordered for correct display.
$documents_result = mysqli_query($link, "SELECT * FROM required_documents ORDER BY display_order ASC");
$required_documents = [];
if ($documents_result) {
    while ($row = mysqli_fetch_assoc($documents_result)) {
        $required_documents[] = $row;
    }
}

// 4. Fetch the latest uploaded photo URL from the 'admissions_details' table.
// This query has been updated to only fetch the photo_url.
$announcement_result = mysqli_query($link, "SELECT photo_url FROM admissions_details ORDER BY id DESC LIMIT 1");
$latest_photo_url = null;
if ($announcement_result && mysqli_num_rows($announcement_result) > 0) {
    $announcement_row = mysqli_fetch_assoc($announcement_result);
    $latest_photo_url = $announcement_row['photo_url'];
}

// --- Include Header (assumed to contain the starting HTML tags) ---
include 'header.php';
?>
<title>Admissions - <?php echo htmlspecialchars($schoolName); ?></title>

<div class="main-content bg-white">
    <section id="admissions" class="py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($settings['page_title'] ?? 'Admissions Information'); ?>
                </h2>
                <p class="text-lg text-gray-600 mt-2">
                    <?php echo htmlspecialchars($settings['page_subtitle'] ?? 'Find details about our admission process below.'); ?>
                </p>
            </div>

            <div class="max-w-4xl mx-auto grid md:grid-cols-2 gap-8">
                <div class="bg-gray-100 p-8 rounded-lg shadow-md">
                    <h3 class="text-2xl font-semibold mb-4 text-teal-600">Important Dates</h3>
                    <ul class="space-y-3 text-gray-700">
                        <?php if (empty($important_dates)): ?>
                            <li>Details will be announced soon.</li>
                        <?php else: ?>
                            <?php foreach ($important_dates as $date): ?>
                                <li class="flex justify-between">
                                    <span><?php echo htmlspecialchars($date['event_name']); ?>:</span> 
                                    <strong><?php echo date("F j, Y", strtotime($date['event_date'])); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="bg-gray-100 p-8 rounded-lg shadow-md">
                    <h3 class="text-2xl font-semibold mb-4 text-teal-600">Eligibility Criteria</h3>
                    <p class="text-gray-700">
                        <?php echo nl2br(htmlspecialchars($settings['eligibility_text'] ?? 'Please contact the office for eligibility details.')); ?>
                    </p>
                </div>
            </div>

            <div class="max-w-4xl mx-auto mt-10 bg-white p-8 rounded-lg shadow-lg border">
                <h3 class="text-2xl font-semibold mb-4 text-center text-teal-600">Required Documents</h3>
                <ul class="list-disc list-inside space-y-2 text-gray-700 mx-auto max-w-md">
                    <?php if (empty($required_documents)): ?>
                        <li>Please contact the office for the required documents list.</li>
                    <?php else: ?>
                        <?php foreach ($required_documents as $doc): ?>
                            <li><?php echo htmlspecialchars($doc['document_name']); ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="text-center mt-12">
                <?php if ($latest_photo_url): ?>
                    <button onclick="openModal('<?php echo htmlspecialchars($latest_photo_url); ?>')" class="inline-block bg-teal-600 hover:bg-teal-700 text-black bg-red-300 font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                        View Admission Announcement
                    </button>
                <?php else: ?>
                    <p class="text-gray-500">No announcement photo available at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="relative max-w-5xl mx-auto p-4">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-white text-3xl">&times;</button>
        <div class="flex flex-col items-center">
            <img id="fullImage" src="" alt="Full-screen admission announcement" class="max-w-full max-h-screen rounded-lg shadow-xl">
<a id="downloadButton" href="" download="admission_announcement.jpg" class="mt-4 bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Download Image
            </a>
        </div>
    </div>
</div>

<script>
    // JavaScript to handle the modal
   function openModal(imageUrl) {
    document.getElementById('fullImage').src = imageUrl;

    // Extract a filename from URL or use a default
    const filename = imageUrl.split('/').pop().split('?')[0] || 'admission_announcement.jpg';

    const downloadButton = document.getElementById('downloadButton');
    downloadButton.href = imageUrl;
    downloadButton.setAttribute('download', filename);

    document.getElementById('imageModal').classList.remove('hidden');
}

</script>

<?php
// --- Include Footer (assumed to contain the closing HTML tags) ---
include 'footer.php';
?>