<?php
 
// 1. Pull in the database credentials
require_once 'db.php'; 
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

// ------------------------------------
// 2. Set your passcode here
// ------------------------------------
// IMPORTANT: Change this to your own secure passcode
define('ADMIN_PASSCODE', '7747');

// ------------------------------------
// 3. Handle Form Submission
// ------------------------------------
if (isset($_POST['submit_post'])) {
    
    // Check if passcode matches
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        
        // Basic check to ensure content is not empty
        if (!empty($_POST['blog_content'])) {
            
            // 4. Security (IMPORTANT): Clean the input
            $content = $_POST['blog_content'];
            
            // 5. Prepare the SQL Statement (Prevents SQL Injection)
            $sql = "INSERT INTO posts (content) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            
            // 6. Execute the statement
            try {
                $stmt->execute([$content]);
                $message = "Post successfully published!";
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

<form method="post" action="write.php">
    <h2>New Blog Post</h2>
    
    <label for="passcode">Enter Passcode:</label><br>
    <input type="password" id="passcode" name="passcode" required>
    <br><br>
    
    <label for="blog_content">Post Content:</label><br>
    <textarea id="blog_content" name="blog_content" rows="20" cols="80" required></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="Publish Post">
</form>

</body>
</html>