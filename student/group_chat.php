<?php
// Start the session
session_start();

// Adjust path to config.php based on directory structure
require_once "../config.php"; // Path to config.php relative to School/

// --- ACCESS CONTROL ---
$allowed_roles = ['teacher', 'principal', 'staff', 'student'];
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['operation_message'] = "Access denied. Please log in to access the chat.";
    header("location: ./login.php");
    exit;
}

$current_user_id = $_SESSION['id'] ?? null;
$current_user_name = $_SESSION['name'] ?? 'Unknown User';
$current_user_role = $_SESSION['role'] ?? 'unknown';
$sender_type = ($current_user_role === 'student') ? 'student' : 'staff';

// --- AJAX Request Handler ---
// This block handles all AJAX requests
if (isset($_REQUEST['action'])) { // Use $_REQUEST to handle both GET and POST requests
    header('Content-Type: application/json');

    switch ($_REQUEST['action']) { // Use $_REQUEST here too
        case 'get_groups':
            $groups = [];
            if ($current_user_id !== null && $link !== false) {
                $sql = "SELECT gc.group_id, gc.group_name, gc.messaging_mode,
                               COUNT(gcmess.message_id) AS unread_count
                        FROM group_chats gc
                        JOIN group_chat_members gcm_current_user ON gc.group_id = gcm_current_user.group_id
                                                                    AND gcm_current_user.member_ref_id = ?
                                                                    AND gcm_current_user.member_type = ?
                        LEFT JOIN group_chat_read_status grs ON gc.group_id = grs.group_id
                                                                AND grs.member_ref_id = gcm_current_user.member_ref_id
                                                                AND grs.member_type = gcm_current_user.member_type
                        LEFT JOIN group_chat_messages gcmess ON gc.group_id = gcmess.group_id
                                                                AND gcmess.message_id > COALESCE(grs.last_read_message_id, 0)
                        WHERE gcm_current_user.member_ref_id = ? AND gcm_current_user.member_type = ?
                        GROUP BY gc.group_id, gc.group_name, gc.messaging_mode
                        ORDER BY gc.created_at DESC";

                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "isis", $current_user_id, $sender_type, $current_user_id, $sender_type);
                    if (mysqli_stmt_execute($stmt)) {
                        $result = mysqli_stmt_get_result($stmt);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $groups[] = $row;
                        }
                    } else {
                        error_log("Failed to execute get_groups query: " . mysqli_error($link));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("Failed to prepare get_groups query: " . mysqli_error($link));
                }
            }
            echo json_encode(['success' => true, 'groups' => $groups]);
            exit;

        case 'get_messages':
            $groupId = $_GET['group_id'] ?? null;
            $lastMessageId = $_GET['last_message_id'] ?? 0;
            $messages = [];
            if ($groupId !== null && $current_user_id !== null && $link !== false) {
                // Security check: Verify user is a member
                $check_member_sql = "SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND member_ref_id = ? AND member_type = ?";
                if ($stmt_check = mysqli_prepare($link, $check_member_sql)) {
                    mysqli_stmt_bind_param($stmt_check, "iis", $groupId, $current_user_id, $sender_type);
                    mysqli_stmt_execute($stmt_check);
                    mysqli_stmt_bind_result($stmt_check, $is_member);
                    mysqli_stmt_fetch($stmt_check);
                    mysqli_stmt_close($stmt_check);

                    if ($is_member > 0) {
                        $sql = "SELECT gcm.message_id, gcm.sender_type, gcm.sender_ref_id, gcm.message, gcm.sent_at,
                                COALESCE(s.staff_name, st.full_name) AS sender_name,
                                COALESCE(s.role, 'student') AS sender_role
                                FROM group_chat_messages gcm
                                LEFT JOIN staff s ON gcm.sender_type = 'staff' AND gcm.sender_ref_id = s.staff_id
                                LEFT JOIN students st ON gcm.sender_type = 'student' AND gcm.sender_ref_id = st.user_id
                                WHERE gcm.group_id = ? AND gcm.message_id > ?
                                ORDER BY gcm.sent_at ASC";
                        if ($stmt = mysqli_prepare($link, $sql)) {
                            mysqli_stmt_bind_param($stmt, "ii", $groupId, $lastMessageId);
                            if (mysqli_stmt_execute($stmt)) {
                                $result = mysqli_stmt_get_result($stmt);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $row['sent_at_formatted'] = date('M d, H:i', strtotime($row['sent_at']));
                                    $messages[] = $row;
                                }
                            } else {
                                error_log("Failed to execute get_messages query: " . mysqli_error($link));
                            }
                            mysqli_stmt_close($stmt);
                        } else {
                             error_log("Failed to prepare get_messages query: " . mysqli_error($link));
                        }

                        // Update last_read_message_id for the current user in this group
                        $max_message_id = 0;
                        $sql_max_id = "SELECT MAX(message_id) FROM group_chat_messages WHERE group_id = ?";
                        if ($stmt_max_id = mysqli_prepare($link, $sql_max_id)) {
                            mysqli_stmt_bind_param($stmt_max_id, "i", $groupId);
                            mysqli_stmt_execute($stmt_max_id);
                            mysqli_stmt_bind_result($stmt_max_id, $max_message_id_val);
                            mysqli_stmt_fetch($stmt_max_id);
                            mysqli_stmt_close($stmt_max_id);
                            if ($max_message_id_val !== null) {
                                $max_message_id = $max_message_id_val;
                            }
                        }

                        if ($max_message_id > 0) {
                            $sql_update_read_status = "INSERT INTO group_chat_read_status (group_id, member_ref_id, member_type, last_read_message_id)
                                                        VALUES (?, ?, ?, ?)
                                                        ON DUPLICATE KEY UPDATE last_read_message_id = VALUES(last_read_message_id)";
                            if ($stmt_update_read = mysqli_prepare($link, $sql_update_read_status)) {
                                mysqli_stmt_bind_param($stmt_update_read, "iisi", $groupId, $current_user_id, $sender_type, $max_message_id);
                                mysqli_stmt_execute($stmt_update_read);
                                mysqli_stmt_close($stmt_update_read);
                            } else {
                                error_log("Failed to prepare update read status query: " . mysqli_error($link));
                            }
                        }

                    } else {
                        echo json_encode(['success' => false, 'error' => 'You are not a member of this group.']);
                        exit;
                    }
                }
            }
            echo json_encode(['success' => true, 'messages' => $messages]);
            exit;

        case 'send_message':
            $groupId = $_POST['group_id'] ?? null;
            $message = trim($_POST['message'] ?? '');
            if ($groupId !== null && !empty($message) && $current_user_id !== null && $link !== false) {
                // Check for announcements_only mode
                $group_mode = 'open_chat';
                $mode_sql = "SELECT messaging_mode FROM group_chats WHERE group_id = ?";
                if ($stmt_mode = mysqli_prepare($link, $mode_sql)) {
                    mysqli_stmt_bind_param($stmt_mode, "i", $groupId);
                    mysqli_stmt_execute($stmt_mode);
                    mysqli_stmt_bind_result($stmt_mode, $group_mode);
                    mysqli_stmt_fetch($stmt_mode);
                    mysqli_stmt_close($stmt_mode);
                }

                if ($group_mode === 'announcements_only' && $sender_type === 'student') {
                    echo json_encode(['success' => false, 'error' => 'You cannot send messages in an announcements-only group.']);
                    exit;
                }

                // Security check: Verify user is a member
                $check_member_sql = "SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND member_ref_id = ? AND member_type = ?";
                if ($stmt_check = mysqli_prepare($link, $check_member_sql)) {
                    mysqli_stmt_bind_param($stmt_check, "iis", $groupId, $current_user_id, $sender_type);
                    mysqli_stmt_execute($stmt_check);
                    mysqli_stmt_bind_result($stmt_check, $is_member);
                    mysqli_stmt_fetch($stmt_check);
                    mysqli_stmt_close($stmt_check);

                    if ($is_member > 0) {
                        $sql = "INSERT INTO group_chat_messages (group_id, sender_type, sender_ref_id, message) VALUES (?, ?, ?, ?)";
                        if ($stmt = mysqli_prepare($link, $sql)) {
                            mysqli_stmt_bind_param($stmt, "isis", $groupId, $sender_type, $current_user_id, $message);
                            if (mysqli_stmt_execute($stmt)) {
                                $newMessageId = mysqli_insert_id($link);

                                // Update sender's last_read_message_id
                                $sql_update_read_status = "INSERT INTO group_chat_read_status (group_id, member_ref_id, member_type, last_read_message_id)
                                                            VALUES (?, ?, ?, ?)
                                                            ON DUPLICATE KEY UPDATE last_read_message_id = VALUES(last_read_message_id)";
                                if ($stmt_update_read = mysqli_prepare($link, $sql_update_read_status)) {
                                    mysqli_stmt_bind_param($stmt_update_read, "iisi", $groupId, $current_user_id, $sender_type, $newMessageId);
                                    mysqli_stmt_execute($stmt_update_read);
                                    mysqli_stmt_close($stmt_update_read);
                                } else {
                                    error_log("Failed to prepare update sender read status query: " . mysqli_error($link));
                                }

                                echo json_encode(['success' => true, 'message_id' => $newMessageId]);
                            } else {
                                error_log("Failed to insert message: " . mysqli_error($link));
                                echo json_encode(['success' => false, 'error' => 'Failed to send message.']);
                            }
                            mysqli_stmt_close($stmt);
                        } else {
                             error_log("Failed to prepare send_message query: " . mysqli_error($link));
                             echo json_encode(['success' => false, 'error' => 'Internal server error.']);
                        }
                    } else {
                         echo json_encode(['success' => false, 'error' => 'You are not authorized to send messages to this group.']);
                    }
                } else {
                    error_log("Failed to prepare member check query: " . mysqli_error($link));
                    echo json_encode(['success' => false, 'error' => 'Internal server error.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid group ID or empty message.']);
            }
            exit;

        case 'create_group':
            if (!in_array($current_user_role, ['principal', 'teacher', 'staff'])) {
                echo json_encode(['success' => false, 'error' => 'You do not have permission to create chat groups.']);
                exit;
            }
            $groupName = trim($_POST['group_name'] ?? '');
            $messagingMode = $_POST['messaging_mode'] ?? 'open_chat';
            if (!in_array($messagingMode, ['open_chat', 'announcements_only'])) {
                $messagingMode = 'open_chat';
            }
            $memberIds = json_decode($_POST['members'] ?? '[]', true);
            if (empty($groupName) || !is_array($memberIds)) {
                echo json_encode(['success' => false, 'error' => 'Invalid group name or members data.']);
                exit;
            }
            if ($link !== false) {
                mysqli_begin_transaction($link);
                try {
                    $sql_group = "INSERT INTO group_chats (group_name, messaging_mode, created_by_role) VALUES (?, ?, ?)";
                    $stmt_group = mysqli_prepare($link, $sql_group);
                    mysqli_stmt_bind_param($stmt_group, "sss", $groupName, $messagingMode, $current_user_role);
                    mysqli_stmt_execute($stmt_group);
                    $newGroupId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt_group);

                    $sql_member = "INSERT INTO group_chat_members (group_id, member_type, member_ref_id) VALUES (?, ?, ?)";
                    $stmt_member = mysqli_prepare($link, $sql_member);
                    $sql_read_status = "INSERT INTO group_chat_read_status (group_id, member_ref_id, member_type, last_read_message_id) VALUES (?, ?, ?, 0)";
                    $stmt_read_status = mysqli_prepare($link, $sql_read_status);

                    // Add current user to the group
                    mysqli_stmt_bind_param($stmt_member, "isi", $newGroupId, $sender_type, $current_user_id);
                    mysqli_stmt_execute($stmt_member);
                    mysqli_stmt_bind_param($stmt_read_status, "iis", $newGroupId, $current_user_id, $sender_type);
                    mysqli_stmt_execute($stmt_read_status);

                    // Add other selected members
                    foreach ($memberIds as $member) {
                        $member_id = $member['id'] ?? null;
                        $member_type = $member['type'] ?? null;
                        // Ensure we don't add the current user twice if they are included in the memberIds list
                        if ($member_id !== null && in_array($member_type, ['staff', 'student']) && !($member_id == $current_user_id && $member_type == $sender_type)) {
                            mysqli_stmt_bind_param($stmt_member, "isi", $newGroupId, $member_type, $member_id);
                            mysqli_stmt_execute($stmt_member);
                            mysqli_stmt_bind_param($stmt_read_status, "iis", $newGroupId, $member_id, $member_type);
                            mysqli_stmt_execute($stmt_read_status);
                        }
                    }
                    mysqli_stmt_close($stmt_member);
                    mysqli_stmt_close($stmt_read_status);
                    mysqli_commit($link);
                    echo json_encode(['success' => true, 'group_id' => $newGroupId, 'group_name' => $groupName]);
                } catch (Exception $e) {
                    mysqli_rollback($link);
                    error_log("Group creation failed: " . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'An error occurred during group creation. Please try again.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Database connection error.']);
            }
            exit;

        case 'get_users_for_group_creation':
            if (!in_array($current_user_role, ['principal', 'teacher', 'staff'])) {
                echo json_encode(['success' => false, 'error' => 'Permission denied to fetch user list.']);
                exit;
            }
            $response_data = ['staff' => [], 'students' => [], 'classes' => []];
            if ($link !== false) {
                $sql_staff = "SELECT staff_id AS id, staff_name AS name, role FROM staff ORDER BY staff_name";
                if ($stmt_staff = mysqli_prepare($link, $sql_staff)) {
                    mysqli_stmt_execute($stmt_staff);
                    $result_staff = mysqli_stmt_get_result($stmt_staff);
                    while ($row = mysqli_fetch_assoc($result_staff)) {
                        $response_data['staff'][] = ['id' => $row['id'], 'name' => $row['name'] . ' (Staff - ' . ucfirst($row['role']) . ')', 'type' => 'staff'];
                    }
                    mysqli_stmt_close($stmt_staff);
                }
                $sql_students = "SELECT user_id AS id, full_name AS name, current_class FROM students ORDER BY full_name";
                if ($stmt_students = mysqli_prepare($link, $sql_students)) {
                    mysqli_stmt_execute($stmt_students);
                    $result_students = mysqli_stmt_get_result($stmt_students);
                    while ($row = mysqli_fetch_assoc($result_students)) {
                        $response_data['students'][] = ['id' => $row['id'], 'name' => $row['name'] . ' (Student - Class ' . $row['current_class'] . ')', 'type' => 'student'];
                    }
                    mysqli_stmt_close($stmt_students);
                }
                $sql_classes = "SELECT DISTINCT current_class FROM students WHERE current_class IS NOT NULL AND current_class != '' ORDER BY current_class ASC";
                if ($result_classes = mysqli_query($link, $sql_classes)) {
                    while ($row = mysqli_fetch_assoc($result_classes)) {
                        $response_data['classes'][] = $row['current_class'];
                    }
                }
                echo json_encode(['success' => true, 'data' => $response_data]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database connection error.']);
            }
            exit;

        case 'get_total_unread_count':
            $total_unread = 0;
            if ($current_user_id !== null && $link !== false) {
                // This query calculates the sum of unread messages across all groups for the current user
                $sql = "SELECT COALESCE(SUM(unread_counts_per_group.unread_count), 0) AS total_unread_messages
                        FROM (
                            SELECT gc.group_id,
                                   COUNT(gcmess.message_id) AS unread_count
                            FROM group_chats gc
                            JOIN group_chat_members gcm_current_user ON gc.group_id = gcm_current_user.group_id
                                                                        AND gcm_current_user.member_ref_id = ?
                                                                        AND gcm_current_user.member_type = ?
                            LEFT JOIN group_chat_read_status grs ON gc.group_id = grs.group_id
                                                                    AND grs.member_ref_id = gcm_current_user.member_ref_id
                                                                    AND grs.member_type = gcm_current_user.member_type
                            LEFT JOIN group_chat_messages gcmess ON gc.group_id = gcmess.group_id
                                                                    AND gcmess.message_id > COALESCE(grs.last_read_message_id, 0)
                            WHERE gcm_current_user.member_ref_id = ? AND gcm_current_user.member_type = ?
                            GROUP BY gc.group_id
                        ) AS unread_counts_per_group";

                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "isis", $current_user_id, $sender_type, $current_user_id, $sender_type);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_bind_result($stmt, $total_unread_messages);
                        mysqli_stmt_fetch($stmt);
                        $total_unread = $total_unread_messages;
                    } else {
                        error_log("Failed to execute get_total_unread_count query: " . mysqli_error($link));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("Failed to prepare get_total_unread_count query: " . mysqli_error($link));
                }
            }
            echo json_encode(['success' => true, 'total_unread' => $total_unread]);
            exit;
        
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Chat | <?php echo htmlspecialchars(ucfirst($current_user_name)); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Styles */
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #333; }
        
        /* Custom Scrollbar for Webkit Browsers */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f0f2f5; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Layout Container */
        .chat-layout-wrapper {
            padding-top: 4rem; /* Adjust for fixed navbar height */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-container {
            flex-grow: 1;
            display: flex;
            max-width: 1400px; /* Wider for more comfortable chat */
            margin: 1.5rem auto; /* More margin */
            background-color: #fff;
            border-radius: 1rem; /* More rounded */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08), 0 3px 6px rgba(0, 0, 0, 0.04); /* Deeper shadow */
            overflow: hidden;
            height: calc(100vh - 4rem - 3rem); /* Full height minus navbar and margin */
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .chat-layout-wrapper { padding-top: 3.5rem; }
            .chat-container {
                flex-direction: column;
                height: calc(100vh - 3.5rem - 1rem);
                margin: 0.5rem;
                border-radius: 0.75rem;
            }
            .group-list {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                max-height: 40vh; /* Allow more space for chat */
            }
            .chat-window {
                width: 100%;
                flex-grow: 1;
                overflow: hidden; /* Crucial for internal scrolling */
            }
            .chat-window.hidden-on-mobile { display: none; }
            .group-list.hidden-on-mobile { display: none; }
        }

        /* Group List Section */
        .group-list {
            width: 350px; /* Slightly wider */
            border-right: 1px solid #e5e7eb;
            background-color: #fcfcfc;
            padding: 1.5rem;
            overflow-y: auto;
            flex-shrink: 0;
        }
        .group-item {
            padding: 0.9rem 1.2rem; /* More padding */
            border-radius: 0.625rem; /* More rounded */
            cursor: pointer;
            margin-bottom: 0.6rem;
            transition: all 0.2s ease-in-out;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden; /* Ensure text truncates nicely */
        }
        .group-item:hover {
            background-color: #edf2f7; /* Lighter hover */
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .group-item.active {
            background-color: #e0f2f7; /* Light blue active */
            font-weight: 600;
            color: #0e7490; /* Darker blue text */
            box-shadow: 0 2px 6px rgba(14, 116, 144, 0.1);
            transform: translateY(0); /* No transform on active */
        }
        .group-item.active::before { /* Left border for active item */
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #0e7490;
            border-radius: 0.625rem 0 0 0.625rem;
        }

        .group-mode-indicator {
            font-size: 0.7rem;
            color: #6b7280;
            background-color: #e5e7eb;
            padding: 0.1rem 0.6rem;
            border-radius: 9999px;
            margin-left: 0.5rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .group-item.active .group-mode-indicator { background-color: #a7d9ed; color: #0e7490; }

        .unread-badge {
            background-color: #ef4444; color: white; font-size: 0.75rem; font-weight: 700;
            padding: 0.15rem 0.6rem; border-radius: 9999px; margin-left: 0.6rem;
            min-width: 24px; text-align: center; display: inline-block;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2); white-space: nowrap;
        }
        .group-item.active .unread-badge { background-color: #dc2626; }

        /* Chat Window Section */
        .chat-window { flex-grow: 1; display: flex; flex-direction: column; }
        .chat-header {
            padding: 1.25rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background-color: #fff;
            font-weight: 700;
            font-size: 1.25rem; /* Larger font */
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03); /* Subtle shadow for header */
        }
        .messages {
            flex-grow: 1;
            padding: 1.5rem 2rem; /* More padding */
            overflow-y: auto;
            background-color: #f9fafc; /* Lighter background for messages */
            display: flex;
            flex-direction: column;
            gap: 1rem; /* Space between messages */
        }
        .message-bubble {
            max-width: 75%; /* Slightly wider messages */
            padding: 0.8rem 1.2rem;
            border-radius: 1.2rem; /* More rounded bubbles */
            line-height: 1.5;
            word-wrap: break-word;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Subtle shadow */
            position: relative; /* For the triangle effect */
        }

        /* Bubble triangle (tail) */
        .message-bubble::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border: 10px solid transparent;
            bottom: 0;
        }

        .message-bubble.sent {
            background-color: #dbeafe; /* Light blue for sent */
            color: #1e40af; /* Darker blue text */
            margin-left: auto;
            border-bottom-right-radius: 0.4rem; /* Sharpen one corner */
        }
        .message-bubble.sent::after {
            border-left-color: #dbeafe;
            border-bottom-color: #dbeafe;
            right: -8px; /* Position the tail */
        }

        .message-bubble.received {
            background-color: #e2e8f0; /* Light gray for received */
            color: #4a5568; /* Darker gray text */
            margin-right: auto;
            border-bottom-left-radius: 0.4rem; /* Sharpen one corner */
        }
        .message-bubble.received::after {
            border-right-color: #e2e8f0;
            border-bottom-color: #e2e8f0;
            left: -8px; /* Position the tail */
        }

        .message-sender {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            color: #4b5563; /* Darker grey for sender name */
            opacity: 0.9;
            text-shadow: 0 0 1px rgba(0,0,0,0.05);
        }
        .message-bubble.sent .message-sender { display: none; } /* Hide sender for own messages */

        .message-timestamp {
            font-size: 0.68rem; /* Slightly larger timestamp */
            color: #6b7280;
            margin-top: 0.5rem; /* More space */
            text-align: right;
            opacity: 0.8;
        }

        .chat-input-area {
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
            background-color: #fff;
            display: flex;
            gap: 1rem; /* More space between input and button */
            align-items: flex-end;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.03); /* Subtle shadow for input area */
        }
        .chat-input-area textarea {
            flex-grow: 1;
            padding: 0.8rem 1.2rem; /* More padding */
            border: 1px solid #d1d5db;
            border-radius: 0.75rem; /* More rounded */
            resize: none;
            min-height: 48px; /* Taller input */
            max-height: 120px;
            font-size: 1rem;
            line-height: 1.5;
            overflow-y: auto;
            background-color: #fff;
            transition: all 0.2s ease-in-out;
        }
        .chat-input-area textarea:focus {
            outline: none;
            border-color: #3b82f6; /* Blue border on focus */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .chat-input-area button {
            background-color: #3b82f6; /* Blue send button */
            color: white;
            padding: 0.8rem 1.5rem; /* More padding */
            border-radius: 0.75rem; /* More rounded */
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, transform 0.1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem; /* Space between icon and text */
        }
        .chat-input-area button:hover:not(:disabled) { background-color: #2563eb; transform: translateY(-1px); }
        .chat-input-area button:disabled { opacity: 0.6; cursor: not-allowed; }

        /* General Buttons */
        .btn-create-group, .btn-back-to-chats {
            background-color: #10b981; /* Green for create group */
            color: white;
            padding: 0.8rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, transform 0.1s ease-in-out;
            width: 100%;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
        }
        .btn-create-group:hover, .btn-back-to-chats:hover { background-color: #059669; transform: translateY(-1px); }
        .btn-back-to-chats {
            width: auto; margin-top: 0; padding: 0.6rem 1rem; font-size: 0.9rem;
            background-color: #6b7280; /* Gray for back button */
            box-shadow: none;
        }
        .btn-back-to-chats:hover { background-color: #4b5563; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); /* Darker overlay */
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px); /* Blur effect */
        }
        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 2.5rem; /* More padding */
            width: 95%;
            max-width: 550px; /* Slightly wider */
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), 0 5px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            transform: translateY(-20px); /* Initial slight animation */
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            opacity: 0;
        }
        .modal.open .modal-content { transform: translateY(0); opacity: 1; }

        .close-button {
            color: #9ca3af; /* Lighter close button */
            font-size: 32px; /* Larger */
            font-weight: bold;
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
        }
        .close-button:hover, .close-button:focus { color: #333; }

        .form-control { margin-bottom: 1.25rem; }
        .form-control label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }
        .form-control input[type="text"],
        .form-control select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.625rem;
            font-size: 1rem;
            transition: all 0.2s ease-in-out;
        }
        .form-control input[type="text"]:focus,
        .form-control select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .form-control select[multiple] { min-height: 180px; } /* Taller multi-select */

        .btn-modal-submit {
            background-color: #3b82f6; /* Blue submit button */
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, transform 0.1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.2);
        }
        .btn-modal-submit:hover:not(:disabled) { background-color: #2563eb; transform: translateY(-1px); }
        .btn-modal-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Utility/State Styles */
        .loading-text { text-align: center; color: #6b7280; font-style: italic; margin-top: 2rem; font-size: 0.95rem; }
        .empty-state { text-align: center; color: #6b7280; margin-top: 2rem; font-size: 1.1rem; padding: 1rem; }
        .empty-state i { display: block; margin-bottom: 0.8rem; font-size: 2.5rem; color: #9ca3af; }

        /* Toast Message (displayMessage) */
        .toast-message {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: white;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease-out;
        }
        .toast-message.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast-message.success { background-color: #10b981; }
        .toast-message.error { background-color: #ef4444; }
    </style>
</head>
<body>

    <?php
    // Include your navigation bar. Make sure it's positioned correctly (e.g., fixed-top)
    // IMPORTANT: Make sure your staff_navbar.php contains an element with id="chat-unread-nav-badge"
    // e.g., <a href="/path/to/group_chat.php" class="flex items-center">Chat <span id="chat-unread-nav-badge" class="unread-badge ml-2" style="display: none;"></span></a>
    $navbar_path = "./student_header.php"; // Assuming this is the correct path for all roles
    if (file_exists($navbar_path)) {
        require_once $navbar_path;
    }
    ?>
    <div class="chat-layout-wrapper">
        <div class="chat-container">
            <!-- Group List Section -->
            <div id="groupListSection" class="group-list">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Chats</h2>
                <?php if (in_array($current_user_role, ['principal', 'teacher', 'staff'])): ?>
                <button id="createGroupBtn" class="btn-create-group">
                    <i class="fas fa-plus mr-2"></i> Create New Group
                </button>
                <?php endif; ?>
                <div id="groupList" class="mt-6">
                    <p class="loading-text"><i class="fas fa-spinner fa-spin mr-2"></i> Loading groups...</p>
                </div>
            </div>

            <!-- Chat Window Section -->
            <div id="chatWindowSection" class="chat-window hidden-on-mobile">
                <div id="chatHeader" class="chat-header">
                    <button id="backToChatsBtn" class="btn-back-to-chats md:hidden mr-4"><i class="fas fa-arrow-left"></i> Back</button>
                    <span id="chatGroupName">Select a group to start chatting</span>
                </div>
                <div id="messagesContainer" class="messages">
                    <p class="empty-state">
                        <i class="fas fa-comments"></i>
                        Select a group from the left to start chatting.
                    </p>
                </div>
                <div class="chat-input-area">
                    <textarea id="messageInput" placeholder="Type your message..." rows="1" oninput="autoExpand(this)" disabled></textarea>
                    <button id="sendMessageBtn" disabled><i class="fas fa-paper-plane"></i> Send</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeModalBtn">&times;</span>
            <h2 class="text-2xl font-bold mb-5 text-gray-800">Create New Chat Group</h2>
            <form id="createGroupForm">
                <div class="form-control">
                    <label for="newGroupName">Group Name</label>
                    <input type="text" id="newGroupName" name="group_name" required placeholder="e.g., Grade 10 English Class">
                </div>
                <div class="form-control">
                    <label class="flex items-center cursor-pointer text-gray-700">
                        <input type="checkbox" id="announcementsOnlyMode" name="messaging_mode" value="announcements_only" class="mr-2 h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span>Announcements-Only Mode (only staff can send messages)</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>Filter Members By:</label>
                    <div id="memberFilterContainer" class="flex flex-wrap gap-4 mt-2">
                        <label class="inline-flex items-center"><input type="radio" name="member_filter" value="all" checked class="mr-2 h-4 w-4 text-indigo-600"> All</label>
                        <label class="inline-flex items-center"><input type="radio" name="member_filter" value="staff" class="mr-2 h-4 w-4 text-indigo-600"> Staff Only</label>
                        <label class="inline-flex items-center"><input type="radio" name="member_filter" value="student" class="mr-2 h-4 w-4 text-indigo-600"> Students Only</label>
                    </div>
                </div>
                <div class="form-control">
                    <label for="classFilterSelect">Quick-Select Students by Class</label>
                    <select id="classFilterSelect" class="block w-full py-2 px-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">-- Select a Class to Filter --</option>
                    </select>
                </div>
                <div class="form-control">
                    <label for="newGroupMembers">Add Members</label>
                    <select id="newGroupMembers" name="members[]" multiple required class="form-control">
                        <option value="">Loading users...</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd (Windows/Mac) to select multiple members.</p>
                </div>
                <button type="submit" class="btn-modal-submit mt-4"><i class="fas fa-users-plus mr-2"></i> Create Group</button>
            </form>
        </div>
    </div>

    <script>
        // DOM elements
        const groupListDiv = document.getElementById('groupList');
        const chatHeader = document.getElementById('chatHeader');
        const chatGroupNameSpan = document.getElementById('chatGroupName');
        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const createGroupBtn = document.getElementById('createGroupBtn');
        const createGroupModal = document.getElementById('createGroupModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const createGroupForm = document.getElementById('createGroupForm');
        const newGroupNameInput = document.getElementById('newGroupName');
        const newGroupMembersSelect = document.getElementById('newGroupMembers');
        const classFilterSelect = document.getElementById('classFilterSelect');
        const memberFilterContainer = document.getElementById('memberFilterContainer');
        const announcementsOnlyModeCheckbox = document.getElementById('announcementsOnlyMode');
        const groupListSection = document.getElementById('groupListSection');
        const chatWindowSection = document.getElementById('chatWindowSection');
        const backToChatsBtn = document.getElementById('backToChatsBtn');

        // State variables
        let selectedGroupId = null;
        let pollingInterval = null;
        let totalUnreadPollingInterval = null;
        let lastMessageId = 0;
        let groupDetails = {};
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        const currentSenderType = <?php echo json_encode($sender_type); ?>;
        const currentUserRole = <?php echo json_encode($current_user_role); ?>;
        let allUsersForCreation = { staff: [], students: [] };

        // --- Helper Functions ---
        function escapeHtml(text) {
          if (typeof text !== 'string') return text;
          var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
          return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        function autoExpand(field) {
            field.style.height = 'inherit';
            const computed = window.getComputedStyle(field);
            const height = parseInt(computed.getPropertyValue('border-top-width'), 10) +
                           parseInt(computed.getPropertyValue('padding-top'), 10) +
                           field.scrollHeight +
                           parseInt(computed.getPropertyValue('padding-bottom'), 10) +
                           parseInt(computed.getPropertyValue('border-bottom-width'), 10);
            field.style.height = (height < 120 ? height : 120) + 'px'; // Max 120px height
        }
        function scrollToBottom(force = false) {
            // Only scroll if already near bottom or if forced (e.g., after sending message)
            const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 100; // 100px buffer
            if (force || isScrolledToBottom) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        function displayMessage(msg, type = 'success') {
            const existingToast = document.querySelector('.toast-message');
            if (existingToast) existingToast.remove(); // Remove old toast if exists

            const toastDiv = document.createElement('div');
            toastDiv.className = `toast-message ${type}`;
            toastDiv.textContent = msg;
            document.body.appendChild(toastDiv);

            setTimeout(() => {
                toastDiv.classList.add('show');
            }, 50); // Small delay to trigger animation

            setTimeout(() => {
                toastDiv.classList.remove('show');
                toastDiv.addEventListener('transitionend', () => toastDiv.remove(), { once: true });
            }, 3000);
        }

        // --- Function to update total unread count in navbar ---
        async function updateTotalUnreadCount() {
            try {
                const response = await fetch('group_chat.php?action=get_total_unread_count');
                const data = await response.json();
                if (data.success) {
                    const badge = document.getElementById('chat-unread-nav-badge');
                    if (badge) {
                        if (data.total_unread > 0) {
                            badge.textContent = data.total_unread;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error("Could not fetch total unread count:", error);
            }
        }

        // --- Core Chat Functions ---

        async function loadGroups() {
            groupListDiv.innerHTML = '<p class="loading-text"><i class="fas fa-spinner fa-spin mr-2"></i> Loading groups...</p>';
            try {
                const response = await fetch('group_chat.php?action=get_groups');
                const data = await response.json();
                if (data.success) {
                    groupListDiv.innerHTML = '';
                    groupDetails = {}; // Clear previous details
                    if (data.groups.length > 0) {
                        data.groups.forEach(group => {
                            groupDetails[group.group_id] = { name: escapeHtml(group.group_name), mode: group.messaging_mode, unread_count: group.unread_count };
                            const groupItem = document.createElement('div');
                            groupItem.className = 'group-item';
                            groupItem.dataset.groupId = group.group_id;
                            groupItem.dataset.groupMode = group.messaging_mode;
                            groupItem.onclick = () => selectGroup(group.group_id);

                            let groupNameHtml = `<div class="flex-grow truncate pr-2"><span>${escapeHtml(group.group_name)}</span></div>`;
                            let indicatorsHtml = '';
                            if (group.messaging_mode === 'announcements_only') {
                                indicatorsHtml += `<span class="group-mode-indicator flex-shrink-0"><i class="fas fa-bullhorn"></i> Announce</span>`;
                            }
                            if (group.unread_count > 0) {
                                indicatorsHtml += `<span class="unread-badge flex-shrink-0">${group.unread_count}</span>`;
                            }
                            
                            groupItem.innerHTML = `${groupNameHtml}<div class="flex items-center gap-2">${indicatorsHtml}</div>`;
                            groupListDiv.appendChild(groupItem);
                        });
                        // Select the first group if no group is selected or selected group is gone
                        if (selectedGroupId === null || !groupDetails[selectedGroupId]) {
                           selectGroup(data.groups[0].group_id, true);
                        } else {
                            // If selected group still exists, just update its active state and badge
                            const activeItem = document.querySelector(`.group-item[data-group-id="${selectedGroupId}"]`);
                            if (activeItem) {
                                activeItem.classList.add('active');
                                const currentUnreadBadge = activeItem.querySelector('.unread-badge');
                                if (currentUnreadBadge) currentUnreadBadge.remove(); // Remove badge for active chat
                            }
                        }
                    } else {
                        groupListDiv.innerHTML = `<p class="empty-state"><i class="fas fa-users"></i> No chat groups found.<br>Click "Create New Group" to start one!</p>`;
                        chatGroupNameSpan.textContent = 'No chat groups available.';
                        messagesContainer.innerHTML = `<p class="empty-state"><i class="fas fa-comments"></i>No chat groups available.</p>`;
                        messageInput.disabled = true;
                        sendMessageBtn.disabled = true;
                        if (pollingInterval) clearInterval(pollingInterval);
                        selectedGroupId = null;
                    }
                    updateTotalUnreadCount();
                } else {
                    displayMessage('Failed to load chat groups: ' + (data.error || 'Unknown error.'), 'error');
                }
            } catch (error) {
                console.error('Error fetching groups:', error);
                displayMessage('Network error fetching groups.', 'error');
            }
        }

        async function selectGroup(groupId, forceReload = false) {
            if (selectedGroupId === groupId && !forceReload) {
                // If already selected, just switch view on mobile if needed
                if (window.innerWidth <= 768) {
                    groupListSection.classList.add('hidden-on-mobile');
                    chatWindowSection.classList.remove('hidden-on-mobile');
                }
                return;
            }

            const groupInfo = groupDetails[groupId];
            if (!groupInfo) {
                console.warn('Attempted to select a group that does not exist in groupDetails:', groupId);
                return;
            }

            selectedGroupId = groupId;
            chatGroupNameSpan.textContent = groupInfo.name;
            messagesContainer.innerHTML = '<p class="loading-text"><i class="fas fa-spinner fa-spin mr-2"></i> Loading messages...</p>';

            if (currentUserRole === 'student' && groupInfo.mode === 'announcements_only') {
                messageInput.placeholder = 'This is an announcements-only group. Only staff can send messages.';
                messageInput.disabled = true;
                sendMessageBtn.disabled = true;
            } else {
                messageInput.placeholder = 'Type your message...';
                messageInput.disabled = false;
                sendMessageBtn.disabled = false;
            }
            
            lastMessageId = 0; // Reset lastMessageId for new group
            
            // Update active class on group items
            document.querySelectorAll('.group-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.groupId == groupId) item.classList.add('active');
            });
            // Remove unread badge from the newly selected active group
            const activeItem = document.querySelector(`.group-item[data-group-id="${selectedGroupId}"]`);
            if (activeItem) {
                const selectedGroupBadge = activeItem.querySelector('.unread-badge');
                if (selectedGroupBadge) selectedGroupBadge.remove();
            }

            // Mobile view switch
            if (window.innerWidth <= 768) {
                groupListSection.classList.add('hidden-on-mobile');
                chatWindowSection.classList.remove('hidden-on-mobile');
            }

            if (pollingInterval) clearInterval(pollingInterval); // Stop previous polling
            await loadMessages(true); // Load messages for the new group
            await loadGroups(); // Reload groups to update unread counts (especially for the group just read)

            // Start polling for new messages in this group
            pollingInterval = setInterval(() => loadMessages(false), 3000); 
        }

        function displayMessageInChat(msg) {
            const placeholder = messagesContainer.querySelector('.empty-state');
            if (placeholder) placeholder.remove();

            const isSent = (msg.sender_ref_id == currentUserId && msg.sender_type == currentSenderType);
            const bubbleClass = isSent ? 'sent' : 'received';
            const senderName = isSent ? 'You' : escapeHtml(msg.sender_name);
            const senderRole = msg.sender_role ? ` (${escapeHtml(ucfirst(msg.sender_role))})` : '';

            const messageDiv = document.createElement('div');
            messageDiv.className = `message-bubble ${bubbleClass}`;
            
            // Sender name only for received messages
            if (!isSent) {
                const senderDiv = document.createElement('div');
                senderDiv.className = 'message-sender';
                senderDiv.textContent = `${senderName}${senderRole}`;
                messageDiv.appendChild(senderDiv);
            }

            const messageContent = document.createElement('p');
            messageContent.innerHTML = escapeHtml(msg.message); // Use innerHTML for line breaks if message contains them
            messageDiv.appendChild(messageContent);

            const timestampDiv = document.createElement('div');
            timestampDiv.className = 'message-timestamp';
            timestampDiv.textContent = msg.sent_at_formatted;
            messageDiv.appendChild(timestampDiv);
            
            messagesContainer.appendChild(messageDiv);
        }

        function ucfirst(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

        async function loadMessages(initialLoad = true) {
            if (!selectedGroupId) return;
            try {
                const response = await fetch(`group_chat.php?action=get_messages&group_id=${selectedGroupId}&last_message_id=${lastMessageId}`);
                const data = await response.json();
                if (data.success) {
                    if (initialLoad) messagesContainer.innerHTML = ''; // Clear for first load
                    
                    if (data.messages.length === 0 && initialLoad) {
                        messagesContainer.innerHTML = `<p class="empty-state"><i class="fas fa-inbox"></i>No messages in this group yet.<br>Be the first to say something!</p>`;
                    } else if (data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            if (msg.message_id > lastMessageId) lastMessageId = msg.message_id;
                            displayMessageInChat(msg);
                        });
                        scrollToBottom(); // Scroll on new messages
                    }
                } else {
                    displayMessage('Failed to load messages: ' + (data.error || 'Unknown error.'), 'error');
                    if (pollingInterval) clearInterval(pollingInterval);
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
                displayMessage('Network error fetching messages.', 'error');
                if (pollingInterval) clearInterval(pollingInterval);
            }
        }

        sendMessageBtn.addEventListener('click', async () => {
            const message = messageInput.value.trim();
            if (message === '' || !selectedGroupId) return;

            sendMessageBtn.disabled = true;
            sendMessageBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            // Optimistic UI update: Display message immediately
            const tempMessage = {
                sender_ref_id: currentUserId, sender_type: currentSenderType, message: message,
                sent_at: new Date().toISOString(),
                sent_at_formatted: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).replace(',', '')
            };
            displayMessageInChat(tempMessage);
            scrollToBottom(true); // Force scroll for new message
            
            messageInput.value = ''; // Clear input
            autoExpand(messageInput); // Reset textarea height

            try {
                const formData = new FormData();
                formData.append('group_id', selectedGroupId);
                formData.append('message', message);
                formData.append('action', 'send_message');

                const response = await fetch('group_chat.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    // Message sent, refresh group list to update unread counts
                    await loadGroups(); 
                    // No need to load messages, as polling will pick it up or it's already optimistically displayed
                } else { 
                    displayMessage('Error sending message: ' + (data.error || 'Unknown error.'), 'error'); 
                    // Consider removing the optimistically added message if send fails.
                    // For simplicity, we'll just show an error toast for now.
                }
            } catch (error) { 
                console.error('Network error sending message:', error);
                displayMessage('Network error sending message.', 'error');
            }
            finally { 
                sendMessageBtn.disabled = false; 
                sendMessageBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                messageInput.focus();
            }
        });

        messageInput.addEventListener('keypress', (e) => { 
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendMessageBtn.click(); 
            } 
        });

        // Create Group Modal Handlers
        if (createGroupBtn) { 
            createGroupBtn.addEventListener('click', async () => { 
                createGroupModal.classList.add('open'); 
                createGroupModal.style.display = 'flex'; 
                await loadUsersForGroupCreation(); 
            }); 
        }
        closeModalBtn.addEventListener('click', () => { 
            createGroupModal.classList.remove('open');
            createGroupModal.addEventListener('transitionend', () => {
                if (!createGroupModal.classList.contains('open')) {
                    createGroupModal.style.display = 'none';
                }
            }, { once: true });
        });
        window.addEventListener('click', (event) => { 
            if (event.target == createGroupModal) {
                createGroupModal.classList.remove('open');
                createGroupModal.addEventListener('transitionend', () => {
                    if (!createGroupModal.classList.contains('open')) {
                        createGroupModal.style.display = 'none';
                    }
                }, { once: true });
            }
        });

        // Mobile "Back to Chats" button
        if (backToChatsBtn) { 
            backToChatsBtn.addEventListener('click', () => { 
                groupListSection.classList.remove('hidden-on-mobile'); 
                chatWindowSection.classList.add('hidden-on-mobile'); 
            }); 
        }

        // Load users for group creation modal
        async function loadUsersForGroupCreation() {
             newGroupMembersSelect.innerHTML = '<option value="">Loading users...</option>'; 
             classFilterSelect.innerHTML = '<option value="">-- Select a Class to Filter --</option>';
             try {
                const response = await fetch('group_chat.php?action=get_users_for_group_creation');
                const result = await response.json();
                newGroupMembersSelect.innerHTML = '';
                if (result.success) {
                    allUsersForCreation.staff = result.data.staff; 
                    allUsersForCreation.students = result.data.students;
                    if (result.data.classes.length > 0) { 
                        result.data.classes.forEach(className => { 
                            const option = document.createElement('option'); 
                            option.value = escapeHtml(className); 
                            option.textContent = `Class ${escapeHtml(className)}`; 
                            classFilterSelect.appendChild(option); 
                        }); 
                    }
                    filterAndDisplayMembers('all'); // Default to showing all
                } else { 
                    newGroupMembersSelect.innerHTML = `<option value="">Error: ${escapeHtml(result.error)}</option>`; 
                }
            } catch (error) { 
                newGroupMembersSelect.innerHTML = `<option value="">Network error.</option>`; 
                console.error('Error loading users for group creation:', error);
            }
        }

        // Filter members for group creation
        function filterAndDisplayMembers(filterType) {
            newGroupMembersSelect.innerHTML = ''; 
            let usersToDisplay = [];
            if (filterType === 'all') { 
                usersToDisplay = [...allUsersForCreation.staff, ...allUsersForCreation.students]; 
            } else if (filterType === 'staff') { 
                usersToDisplay = allUsersForCreation.staff; 
            } else if (filterType === 'student') { 
                usersToDisplay = allUsersForCreation.students; 
            }
            usersToDisplay.sort((a, b) => a.name.localeCompare(b.name));

            // Add the current user to the list, but make them pre-selected and unchangeable.
            // A simpler approach for now is to just ensure they are implicitly added server-side.
            // For UI, we'll exclude them from the selectable list to avoid duplication.

            usersToDisplay.forEach(user => {
                if (user.id == currentUserId && user.type == currentSenderType) { 
                    return; // Skip current user from selectable list
                }
                const option = document.createElement('option'); 
                option.value = JSON.stringify({id: user.id, type: user.type}); 
                option.textContent = escapeHtml(user.name); 
                newGroupMembersSelect.appendChild(option);
            });
            if (usersToDisplay.length === 0) { 
                newGroupMembersSelect.innerHTML = '<option value="">No users found for this filter.</option>'; 
            }
        }

        // Handle group creation form submission
        createGroupForm.addEventListener('submit', async (e) => {
            e.preventDefault(); 
            const groupName = newGroupNameInput.value.trim(); 
            const selectedOptions = Array.from(newGroupMembersSelect.selectedOptions); 
            const memberIds = selectedOptions.map(option => JSON.parse(option.value)); 
            const messagingMode = announcementsOnlyModeCheckbox.checked ? 'announcements_only' : 'open_chat';

            if (!groupName || memberIds.length === 0) { 
                displayMessage('Group name and at least one member are required.', 'error'); 
                return; 
            }

            const submitBtn = createGroupForm.querySelector('button[type="submit"]'); 
            submitBtn.disabled = true; 
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';

            try {
                const formData = new FormData(); 
                formData.append('action', 'create_group'); 
                formData.append('group_name', groupName); 
                formData.append('members', JSON.stringify(memberIds)); 
                formData.append('messaging_mode', messagingMode);

                const response = await fetch('group_chat.php', { method: 'POST', body: formData }); 
                const data = await response.json();

                if (data.success) {
                    displayMessage(`Group "${escapeHtml(groupName)}" created successfully!`, 'success'); 
                    createGroupModal.classList.remove('open');
                    createGroupModal.addEventListener('transitionend', () => createGroupModal.style.display = 'none', { once: true });
                    createGroupForm.reset(); // Reset form fields
                    
                    await loadGroups(); // Reload groups to show the new one
                    selectGroup(data.group_id, true); // Select the newly created group
                } else { 
                    displayMessage('Error creating group: ' + (data.error || 'Unknown error.'), 'error'); 
                }
            } catch (error) { 
                displayMessage('Network error creating group.', 'error'); 
                console.error('Error creating group:', error);
            }
            finally { 
                submitBtn.disabled = false; 
                submitBtn.innerHTML = '<i class="fas fa-users-plus mr-2"></i> Create Group'; 
            }
        });

        // Quick-select students by class
        classFilterSelect.addEventListener('change', (e) => {
            const selectedClass = e.target.value;
            // Deselect all existing students first
            Array.from(newGroupMembersSelect.options).forEach(option => { 
                try {
                    const memberData = JSON.parse(option.value); 
                    if (memberData && memberData.type === 'student') { 
                        option.selected = false; 
                    } 
                } catch (e) { /* ignore invalid JSON values */ }
            });

            // Select students matching the chosen class
            if (selectedClass) { 
                Array.from(newGroupMembersSelect.options).forEach(option => { 
                    if (option.textContent.includes(`(Student - Class ${selectedClass})`)) { 
                        option.selected = true; 
                    } 
                }); 
            }
        });

        // Member filter (All/Staff/Student) radio buttons
        memberFilterContainer.addEventListener('change', e => { 
            const filter = e.target.value; 
            if (!filter) return; 
            filterAndDisplayMembers(filter); 
            classFilterSelect.value = ""; // Clear class filter when main filter changes
        });
        
        // --- Initial Load ---
        document.addEventListener('DOMContentLoaded', () => {
            loadGroups();
            updateTotalUnreadCount(); // Initial check for navbar badge
            totalUnreadPollingInterval = setInterval(updateTotalUnreadCount, 15000); // Check every 15 seconds
        });
        
        // Ensure messages scroll to bottom when container size changes
        const resizeObserver = new ResizeObserver(entries => { 
            for (let entry of entries) { 
                if (entry.target === messagesContainer) { 
                    scrollToBottom(); 
                } 
            } 
        });
        resizeObserver.observe(messagesContainer);

        // Handle window resize for responsive layout
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                groupListSection.classList.remove('hidden-on-mobile'); 
                chatWindowSection.classList.remove('hidden-on-mobile');
            } else {
                // On mobile, show chat window if a group is selected, otherwise show group list
                if (selectedGroupId) { 
                    groupListSection.classList.add('hidden-on-mobile'); 
                    chatWindowSection.classList.remove('hidden-on-mobile'); 
                } else { 
                    groupListSection.classList.remove('hidden-on-mobile'); 
                    chatWindowSection.classList.add('hidden-on-mobile'); 
                }
            }
        });
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($link) && is_object($link) && mysqli_ping($link)) {
     mysqli_close($link);
}
?>


<?php 
require_once "./student_footer.php";
?>
