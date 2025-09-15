<?php
// School/student/view_my_results.php

// Start the session
session_start();

// Include the database configuration
require_once "../config.php";

// Check if the user is logged in and is a student
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'student') {
    $_SESSION['operation_message'] = "<p class='text-red-600'>Access denied. Please log in as a student to view this page.</p>";
    header("location: ../login.php");
    exit;
}

// Get the logged-in student's ID from the session
$student_id = $_SESSION['id'];
$pageTitle = "My Exam Results"; // Set the page title

// Initialize variables
$student_results_raw = [];
$student_results_grouped = [];
$error_message = '';

// Fetch exam results from the database
if ($link) {
    // SQL to fetch all exam results for the specific student
    $sql = "SELECT academic_year, exam_name, subject_name, marks_obtained, max_marks 
            FROM student_exam_results 
            WHERE student_id = ? 
            ORDER BY academic_year DESC, exam_name ASC, subject_name ASC";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $student_results_raw = mysqli_fetch_all($result, MYSQLI_ASSOC);

            // --- Group the results by Academic Year and Exam Name ---
            foreach ($student_results_raw as $row) {
                $year = $row['academic_year'];
                $exam = $row['exam_name'];
                $marks = (float)($row['marks_obtained'] ?? 0);
                $max_marks = (float)($row['max_marks'] ?? 100); // Default to 100 if not set

                // Initialize the array for the year if it doesn't exist
                if (!isset($student_results_grouped[$year])) {
                    $student_results_grouped[$year] = [];
                }

                // Initialize the array for the exam within the year if it doesn't exist
                if (!isset($student_results_grouped[$year][$exam])) {
                    $student_results_grouped[$year][$exam] = [
                        'subjects' => [],
                        'total_marks_obtained' => 0,
                        'total_max_marks' => 0,
                        'percentage' => 0
                    ];
                }

                // Add subject details to the exam
                $student_results_grouped[$year][$exam]['subjects'][] = [
                    'subject_name' => $row['subject_name'],
                    'marks_obtained' => $marks,
                    'max_marks' => $max_marks
                ];

                // Update totals for the exam
                $student_results_grouped[$year][$exam]['total_marks_obtained'] += $marks;
                $student_results_grouped[$year][$exam]['total_max_marks'] += $max_marks;
            }

            // --- Calculate Percentage for each exam ---
            foreach ($student_results_grouped as $year => &$exams_in_year) {
                foreach ($exams_in_year as $exam_name => &$exam_data) {
                    if ($exam_data['total_max_marks'] > 0) {
                        $exam_data['percentage'] = ($exam_data['total_marks_obtained'] / $exam_data['total_max_marks']) * 100;
                    }
                }
            }
            unset($exam_data); // Unset reference
            unset($exams_in_year); // Unset reference

        } else {
            $error_message = "Error fetching your exam results.";
            error_log("Results view execute error for student ID $student_id: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Error preparing the results query.";
        error_log("Results view prepare error: " . mysqli_error($link));
    }
    mysqli_close($link);
} else {
    $error_message = "Database connection failed. Please try again later.";
}

// Include the student header
require_once "./student_header.php";
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">My Exam Results</h1>
        <a href="./student_dashboard.php" class="text-sm text-indigo-600 hover:underline font-medium">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">An Error Occurred</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php elseif (empty($student_results_grouped)): ?>
        <div class="text-center bg-white p-12 rounded-2xl shadow-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No Exam Results Found</h3>
            <p class="mt-1 text-sm text-gray-500">Your exam results have not been published yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="space-y-12">
            <?php foreach ($student_results_grouped as $year => $exams_in_year): ?>
                <div class="bg-white p-6 rounded-2xl shadow-lg">
                    <h2 class="text-xl font-bold text-gray-800 border-b-2 border-indigo-500 pb-3 mb-6">
                        Academic Year: <?php echo htmlspecialchars($year); ?>
                    </h2>
                    <div class="space-y-8">
                        <?php foreach ($exams_in_year as $exam_name => $exam_data): ?>
                            <div class="border border-gray-200 rounded-lg p-5">
                                <h3 class="text-lg font-semibold text-indigo-700"><?php echo htmlspecialchars($exam_name); ?></h3>
                                <div class="overflow-x-auto mt-4">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Marks Obtained</th>
                                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Max Marks</th>
                                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($exam_data['subjects'] as $subject): 
                                                // Assuming passing marks are 33%
                                                $is_passed = ($subject['marks_obtained'] / $subject['max_marks']) >= 0.33;
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center"><?php echo htmlspecialchars($subject['marks_obtained']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center"><?php echo htmlspecialchars($subject['max_marks']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php if ($is_passed): ?>
                                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Pass</span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Fail</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="bg-gray-100">
                                            <tr>
                                                <td class="px-6 py-3 text-right font-bold text-sm text-gray-800">Total:</td>
                                                <td class="px-6 py-3 text-center font-bold text-sm text-gray-800"><?php echo htmlspecialchars($exam_data['total_marks_obtained']); ?></td>
                                                <td class="px-6 py-3 text-center font-bold text-sm text-gray-800"><?php echo htmlspecialchars($exam_data['total_max_marks']); ?></td>
                                                <td class="px-6 py-3 text-center font-bold text-sm text-gray-800">
                                                    <?php echo number_format($exam_data['percentage'], 2); ?>%
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the footer
require_once "./student_footer.php";
?>
