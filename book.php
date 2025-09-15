<?php
// book.php - Page to display school book lists

session_start();
require_once "./config.php"; // Include your database connection file

// Define school details
$schoolName = "Basic Public School";
$schoolAddress = "Baliya chowk, Raiyam, Madhubani, Bihar, 847237";
$schoolEmail = "vc74295@gmail.com";
$schoolPhone = "+91 88777 80197";

// --- FETCH ALL BOOKS FOR DISPLAY ---
$book_data = [];
// Assuming a table named `school_books` with columns: class_name, subject, book_title, publisher
$sql = "SELECT class_name, subject, book_title, publisher FROM school_books ORDER BY class_name ASC, subject ASC";
$result = mysqli_query($link, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Group the results by class name for easy display
        $book_data[$row['class_name']][] = $row;
    }
}

// --- Include Header (assuming a file exists) ---
include 'header.php';
?>
<title>Book List - <?php echo htmlspecialchars($schoolName); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; }
</style>

<div class="main-content bg-white">
    <section id="book-list" class="py-20 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800">Book List for Session <?php echo date('Y'); ?>-<?php echo date('Y') + 1; ?></h2>
                <p class="text-lg text-gray-600 mt-2">Required textbooks and materials for each class.</p>
            </div>

            <div class="max-w-5xl mx-auto space-y-8">
                <?php if (empty($book_data)): ?>
                    <p class="text-gray-500 text-center">No book lists found at this time.</p>
                <?php else: ?>
                    <?php foreach ($book_data as $class => $books): ?>
                        <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                            <h3 class="text-2xl font-semibold mb-4 border-b pb-2 text-indigo-700"><?php echo htmlspecialchars($class); ?></h3>
                            <div class="overflow-x-auto shadow-md rounded-lg">
                                <table class="w-full text-sm text-left text-gray-700">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-200">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Subject</th>
                                            <th scope="col" class="px-6 py-3">Book Title</th>
                                            <th scope="col" class="px-6 py-3">Publisher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($books as $index => $book): ?>
                                            <tr class="border-b <?php echo ($index % 2 === 0) ? 'bg-white' : 'bg-gray-100'; ?>">
                                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($book['subject']); ?></td>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars($book['book_title']); ?></td>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars($book['publisher']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php
// --- Include Footer (assuming a file exists) ---
include 'footer.php';
?>
