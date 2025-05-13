<?php
require_once 'src/functions.php';

$message = '';
if (isset($_GET['email']) && isset($_GET['code'])) {
    $email = urldecode($_GET['email']);
    $code = $_GET['code'];
    if (verifySubscription($email, $code)) {
        $message = "Email verified successfully! You will now receive task reminders.";
    } else {
        $message = "Invalid verification code or email.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $task_name = trim($_POST['task_name']);
        if (addTask($task_name)) {
            header("Location: /index.php");
            exit;
        } else {
            $message = "Task already exists or invalid.";
        }
    } elseif (isset($_POST['subscribe'])) {
        $email = trim($_POST['email']);
        if (subscribeEmail($email)) {
            $message = "Verification email sent! Please check your inbox (or Papercut at http://localhost:5000).";
        } else {
            $message = "Email already subscribed, pending verification, or invalid.";
        }
    } elseif (isset($_POST['mark_complete'])) {
        $task_id = $_POST['task_id'];
        if (markTaskAsCompleted($task_id, true)) {
            header("Location: /index.php");
            exit;
        } else {
            $message = "Failed to mark task as complete.";
        }
    } elseif (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];
        if (deleteTask($task_id)) {
            header("Location: /index.php");
            exit;
        } else {
            $message = "Failed to delete task.";
        }
    }
}

$tasks = getAllTasks();
$subscribers = readFileToArray(SUBSCRIBERS_FILE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Scheduler</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #acb6e5);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="text"], input[type="email"] {
            padding: 10px;
            width: calc(100% - 22px);
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            padding: 10px;
            background: #f9f9f9;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background-color: #c3e6cb;
            color: #155724;
        }

        .error {
            background-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Task Scheduler</h1>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h2>Add Task</h2>
        <form method="post" class="form-group">
            <input type="text" name="task_name" placeholder="Enter task" required>
            <button type="submit" name="add_task">Add Task</button>
        </form>

        <h2>Subscribe for Reminders</h2>
        <form method="post" class="form-group">
            <input type="email" name="email" placeholder="Enter email" required>
            <button type="submit" name="subscribe">Subscribe</button>
        </form>
        <h2>Tasks</h2>
        <ul>
            <?php foreach ($tasks as $task): ?>
                <li>
                    <span style="<?php echo $task['is_completed'] ? 'text-decoration: line-through;' : ''; ?>">
                        <?php echo htmlspecialchars($task['name']); ?>
                    </span>
                    <div>
                        <?php if (!$task['is_completed']): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="mark_complete">Mark Complete</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" name="delete_task">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <h2>Subscribers</h2>
        <ul>
            <?php foreach ($subscribers as $subscriber): ?>
                <li><?php echo htmlspecialchars($subscriber); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
