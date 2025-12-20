<?php
// 1. Pull in the database credentials
require_once 'blog/db.php'; 
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');

// Create a variable to hold the content so it doesn't disappear
$draft_content = ""; 

if (isset($_POST['submit_post'])) {
    // Store what you typed so we can show it again if there's an error
    $draft_content = $_POST['blog_content'] ?? '';

    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        if (!empty($draft_content)) {
            $sql = "INSERT INTO posts (content) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([$draft_content]);
                $message = "Post successfully published!";
                $draft_content = ""; // Clear the box ONLY if it was successful
            } catch (\PDOException $e) {
                $message = "Error publishing post: " . $e->getMessage();
            }
        } else {
            $message = "Error: Post content cannot be empty.";
        }
    } else {
        $message = "Error: Invalid passcode. Access denied.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Blog Post</title>
</head>
<body>

<?php if (isset($message)) : ?>
    <p style="color: <?php echo (strpos($message, 'successfully') !== false) ? 'green' : 'red'; ?>; font-weight: bold;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<form method="post" action="write2.php">
    <h2>New Blog Post</h2>
    
    <label for="passcode">Enter Passcode:</label><br>
    <input type="password" id="passcode" name="passcode" required>
    <br><br>
    
    <label for="blog_content">Post Content:</label><br>
    <textarea id="blog_content" name="blog_content" rows="20" cols="80" required><?php echo htmlspecialchars($draft_content); ?></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="Publish Post">
</form>

</body>
</html>