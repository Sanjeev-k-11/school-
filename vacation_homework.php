<?php
// vacation_homework.php - Page to display vacation homework

session_start();
require_once "./config.php"; // Database connection

// Define school details (can be moved to a central config file)
$schoolName = "Basic Public School";
$schoolAddress = "Baliya chowk, Raiyam, Madhubani, Bihar, 847237";
$schoolEmail = "vc74295@gmail.com";
$schoolPhone = "+91 88777 80197";

// --- FETCH DATA FROM DATABASE ---
$homework_data = [];
$sql = "SELECT class_name, subject_name, image_url FROM vacation_homework ORDER BY class_name ASC, display_order ASC";
$result = mysqli_query($link, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Group the results by class name
        $homework_data[$row['class_name']][] = [
            'subject' => $row['subject_name'],
            'image_url' => $row['image_url']
        ];
    }
}

// --- Include Header ---
include 'header.php';
?>
<title>Vacation Homework - <?php echo htmlspecialchars($schoolName); ?></title>

<div class="main-content bg-white">
    <section id="homework" class="py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800">Vacation Homework</h2>
                <p class="text-lg text-gray-600 mt-2">Find and download the homework for your class.</p>
            </div>

            <div class="max-w-4xl mx-auto space-y-4">
                <?php if (empty($homework_data)): ?>
                    <div class="text-center text-gray-500 p-8 border rounded-lg shadow-sm">
                        No homework assignments available at this time.
                    </div>
                <?php else: ?>
                    <?php foreach ($homework_data as $class => $assignments): ?>
                        <div class="border rounded-lg shadow-sm">
                            <button class="w-full flex justify-between items-center p-5 font-semibold text-left bg-gray-100 hover:bg-gray-200" onclick="toggleAccordion(this)">
                                <span><?php echo htmlspecialchars($class); ?></span>
                                <svg class="w-6 h-6 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="accordion-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                                <ul class="p-5 space-y-3 bg-white">
                                    <?php foreach ($assignments as $assignment): ?>
                                        <li class="flex justify-between items-center">
                                            <span><?php echo htmlspecialchars($assignment['subject']); ?></span>
                                            <a href="<?php echo htmlspecialchars($assignment['image_url']); ?>" class="text-blue-600 hover:underline" download>Download Image</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script>
    function toggleAccordion(element) {
        const content = element.nextElementSibling;
        const svg = element.querySelector('svg');
        if (content.style.maxHeight) {
            content.style.maxHeight = null;
            svg.style.transform = 'rotate(0deg)';
        } else {
            document.querySelectorAll('.accordion-content').forEach(item => {
                item.style.maxHeight = null;
                item.previousElementSibling.querySelector('svg').style.transform = 'rotate(0deg)';
            });
            content.style.maxHeight = content.scrollHeight + "px";
            svg.style.transform = 'rotate(180deg)';
        }
    }
</script>

<?php
// --- Include Footer ---
include 'footer.php';
?>