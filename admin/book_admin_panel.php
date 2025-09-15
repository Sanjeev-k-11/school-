<?php
// book_admin_panel.php - Admin panel to manage the school book list.

session_start();
require_once "../config.php"; // Include your database connection file

// --- Basic Admin Authentication (Placeholder) ---
// In a real-world application, you would implement proper session-based authentication here.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // For this example, we assume a user is "logged in".
    // For a real app, uncomment the lines below to redirect to a login page.
    // header("Location: login.php");
    // exit;
    $_SESSION['admin_logged_in'] = true;
}

$message = '';

// --- HANDLE ADD BOOK FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_book'])) {
    $className = mysqli_real_escape_string($link, $_POST['class_name']);
    $subject = mysqli_real_escape_string($link, $_POST['subject']);
    $bookTitle = mysqli_real_escape_string($link, $_POST['book_title']);
    $publisher = mysqli_real_escape_string($link, $_POST['publisher']);

    if (!empty($className) && !empty($subject) && !empty($bookTitle) && !empty($publisher)) {
        $sql = "INSERT INTO school_books (class_name, subject, book_title, publisher) VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssss", $className, $subject, $bookTitle, $publisher);
            if (mysqli_stmt_execute($stmt)) {
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Book added successfully!</div>";
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Error: " . mysqli_error($link) . "</div>";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4'>Please fill in all required fields.</div>";
    }
}

// --- HANDLE DELETE BOOK REQUEST ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $bookId = (int)$_GET['id'];
    $sql = "DELETE FROM school_books WHERE id = ?";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $bookId);
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Book deleted successfully!</div>";
        } else {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Error: " . mysqli_error($link) . "</div>";
        }
        mysqli_stmt_close($stmt);
    }
    // Redirect to prevent re-submitting the delete action on refresh
    header("Location: book_admin_panel.php");
    exit;
}

// --- FETCH ALL BOOKS FOR DISPLAY ---
$book_data = [];
$sql = "SELECT id, class_name, subject, book_title, publisher FROM school_books ORDER BY class_name ASC, subject ASC";
$result = mysqli_query($link, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $book_data[$row['class_name']][] = $row;
    }
}

// --- HTML STRUCTURE ---
?>

<?php 
require_once "./admin_header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Book List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            body { background-color: #fff; }
            .no-print { display: none; }
            .container { max-width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 text-center mb-6 no-print">Admin Panel: Manage Book List</h1>
    <?php echo $message; ?>

    <!-- ADD NEW BOOK FORM -->
    <div class="bg-white p-8 rounded-lg shadow-lg mb-8 no-print">
        <h2 class="text-xl font-semibold mb-4 text-indigo-700">Add New Book</h2>
        <form method="POST" action="book_admin_panel.php" class="space-y-4">
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700">Class Name</label>
                <input type="text" id="class_name" name="class_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
            </div>
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                <input type="text" id="subject" name="subject" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
            </div>
            <div>
                <label for="book_title" class="block text-sm font-medium text-gray-700">Book Title</label>
                <input type="text" id="book_title" name="book_title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
            </div>
            <div>
                <label for="publisher" class="block text-sm font-medium text-gray-700">Publisher</label>
                <input type="text" id="publisher" name="publisher" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
            </div>
            <div class="text-right">
                <button type="submit" name="add_book" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">Add Book</button>
            </div>
        </form>
    </div>

    <!-- VIEW AND PRINT EXISTING BOOKS -->
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-4 no-print">
            <h2 class="text-xl font-semibold text-indigo-700">Existing Book List</h2>
            <button onclick="window.print()" class="px-6 py-2 bg-gray-500 text-white font-semibold rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">Print</button>
        </div>
        
        <div class="print-content">
            <?php if (empty($book_data)): ?>
                <p class="text-gray-500 text-center">No book entries found.</p>
            <?php else: ?>
                <?php foreach ($book_data as $class => $books): ?>
                    <div class="border rounded-lg shadow-sm mb-4 page-break-after">
                        <div class="bg-gray-100 p-4 font-bold text-lg text-gray-800 rounded-t-lg"><?php echo htmlspecialchars($class); ?></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-200">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Subject</th>
                                        <th scope="col" class="px-6 py-3">Book Title</th>
                                        <th scope="col" class="px-6 py-3">Publisher</th>
                                        <th scope="col" class="px-6 py-3 no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $index => $book): ?>
                                        <tr class="border-b <?php echo ($index % 2 === 0) ? 'bg-white' : 'bg-gray-100'; ?>">
                                            <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($book['subject']); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($book['book_title']); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($book['publisher']); ?></td>
                                            <td class="px-6 py-4 no-print">
                                                <a href="book_admin_panel.php?action=delete&id=<?php echo htmlspecialchars($book['id']); ?>" onclick="return confirm('Are you sure you want to delete this book?');" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 text-xs">Delete</a>
                                            </td>
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
</div>

</body>
</html>


<?php 
require_once "./admin_footer.php";
?>