$content = @'
<?php
require_once 'functions.php';

$message = '';
if (isset($_GET['email'])) {
    $email = urldecode($_GET['email']);
    if (unsubscribeEmail($email)) {
        $message = "You have been unsubscribed successfully.";
    } else {
        $message = "Failed to unsubscribe. Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #acb6e5);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #333;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .success {
            background-color: #c3e6cb;
            color: #155724;
        }

        .error {
            background-color: #f5c6cb;
            color: #721c24;
        }

        a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Unsubscribe</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <a href="/index.php">Back to Task Scheduler</a>
    </div>
</body>
</html>
'@
echo $content | Out-File -FilePath "D:\Task Scheduler\src\unsubscribe.php" -Encoding UTF8