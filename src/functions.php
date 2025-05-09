<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// File paths for storage (absolute paths for consistency)
define('TASKS_FILE', 'D:/Task Scheduler/tasks.txt');
define('SUBSCRIBERS_FILE', 'D:/Task Scheduler/subscribers.txt');
define('PENDING_SUBSCRIPTIONS_FILE', 'D:/Task Scheduler/pending_subscriptions.txt');

// Helper function to read a file into an array
function readFileToArray($filename) {
    if (!file_exists($filename)) {
        file_put_contents($filename, ""); // Create file if it doesn't exist
        return [];
    }
    $content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return $content ? $content : [];
}

// Helper function to write an array to a file
function writeArrayToFile($filename, $data) {
    file_put_contents($filename, implode("\n", $data) . "\n", LOCK_EX);
}

// Add a new task to the list
function addTask($task_name) {
    $tasks = readFileToArray(TASKS_FILE);
    foreach ($tasks as $task) {
        $task_data = json_decode($task, true);
        if ($task_data['name'] === $task_name && !$task_data['is_completed']) {
            return false;
        }
    }
    $task_id = count($tasks) + 1;
    $task = [
        'id' => $task_id,
        'name' => $task_name,
        'is_completed' => false
    ];
    $tasks[] = json_encode($task);
    writeArrayToFile(TASKS_FILE, $tasks);
    return true;
}

// Get all tasks from tasks.txt
function getAllTasks() {
    $tasks = readFileToArray(TASKS_FILE);
    $result = [];
    foreach ($tasks as $task) {
        $decoded = json_decode($task, true);
        if ($decoded) {
            $result[] = $decoded;
        }
    }
    return $result;
}

// Mark/unmark a task as complete
function markTaskAsCompleted($task_id, $is_completed) {
    $tasks = readFileToArray(TASKS_FILE);
    $updated = false;
    foreach ($tasks as &$task) {
        $task_data = json_decode($task, true);
        if ($task_data['id'] == $task_id) {
            $task_data['is_completed'] = (bool)$is_completed;
            $task = json_encode($task_data);
            $updated = true;
            break;
        }
    }
    if ($updated) {
        writeArrayToFile(TASKS_FILE, $tasks);
    }
    return $updated;
}

// Delete a task from the list
function deleteTask($task_id) {
    $tasks = readFileToArray(TASKS_FILE);
    $new_tasks = [];
    $deleted = false;
    foreach ($tasks as $task) {
        $task_data = json_decode($task, true);
        if ($task_data['id'] != $task_id) {
            $new_tasks[] = json_encode($task_data);
        } else {
            $deleted = true;
        }
    }
    if ($deleted) {
        writeArrayToFile(TASKS_FILE, $new_tasks);
    }
    return $deleted;
}

// Generate a 6-digit verification code
function generateVerificationCode() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Helper function to send email using mail() for Papercut
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@taskscheduler.com\r\n";
    $result = mail($to, $subject, $message, $headers);
    if (!$result) {
        error_log("Failed to send email to $to: " . print_r(error_get_last(), true));
    }
    return $result;
}

// Add email to pending subscriptions and send verification
function subscribeEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: $email");
        return false;
    }
    $subscribers = readFileToArray(SUBSCRIBERS_FILE);
    if (in_array($email, $subscribers)) {
        error_log("Email already subscribed: $email");
        return false;
    }
    $pending = readFileToArray(PENDING_SUBSCRIPTIONS_FILE);
    foreach ($pending as $entry) {
        $data = json_decode($entry, true);
        if ($data['email'] === $email) {
            error_log("Email already in pending subscriptions: $email");
            return false;
        }
    }
    $code = generateVerificationCode();
    $pending[] = json_encode(['email' => $email, 'code' => $code]);
    writeArrayToFile(PENDING_SUBSCRIPTIONS_FILE, $pending);

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:8000';
    $verification_link = "http://" . $host . "/index.php?email=" . urlencode($email) . "&code=" . $code;
    $subject = "Verify Your Subscription";
    $message = "
    <html>
    <body>
        <h2>Verify Your Subscription</h2>
        <p>Please click the link below to verify your email subscription:</p>
        <p><a href='$verification_link'>Verify Email</a></p>
        <p>Or use this code: $code</p>
    </body>
    </html>";
    return sendEmail($email, $subject, $message);
}

// Verify email subscription
function verifySubscription($email, $code) {
    $pending = readFileToArray(PENDING_SUBSCRIPTIONS_FILE);
    $new_pending = [];
    $verified = false;
    foreach ($pending as $entry) {
        $data = json_decode($entry, true);
        if ($data['email'] === $email && $data['code'] === $code) {
            $subscribers = readFileToArray(SUBSCRIBERS_FILE);
            $subscribers[] = $email;
            writeArrayToFile(SUBSCRIBERS_FILE, $subscribers);
            $verified = true;
        } else {
            $new_pending[] = $entry;
        }
    }
    writeArrayToFile(PENDING_SUBSCRIPTIONS_FILE, $new_pending);
    return $verified;
}

// Remove email from subscribers list
function unsubscribeEmail($email) {
    $subscribers = readFileToArray(SUBSCRIBERS_FILE);
    $new_subscribers = array_diff($subscribers, [$email]);
    writeArrayToFile(SUBSCRIBERS_FILE, $new_subscribers);
    return !in_array($email, $new_subscribers);
}

// Sends task reminders to all subscribers
function sendTaskReminders() {
    $subscribers = readFileToArray(SUBSCRIBERS_FILE);
    $tasks = getAllTasks();
    $pending_tasks = array_filter($tasks, function($task) {
        return !$task['is_completed'];
    });
    foreach ($subscribers as $email) {
        sendTaskEmail($email, $pending_tasks);
    }
}

// Sends a task reminder email to a subscriber with pending tasks
function sendTaskEmail($email, $pending_tasks) {
    if (empty($pending_tasks)) {
        return false;
    }
    $task_list = "<ul>";
    foreach ($pending_tasks as $task) {
        $task_list .= "<li>" . htmlspecialchars($task['name']) . "</li>";
    }
    $task_list .= "</ul>";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:8000';
    $unsubscribe_link = "http://" . $host . "/src/unsubscribe.php?email=" . urlencode($email);
    $subject = "Task Reminder";
    $message = "
    <html>
    <body>
        <h2>Your Pending Tasks</h2>
        <p>Here are your pending tasks:</p>
        $task_list
        <p><a href='$unsubscribe_link'>Unsubscribe from reminders</a></p>
    </body>
    </html>";
    return sendEmail($email, $subject, $message);
}
?>