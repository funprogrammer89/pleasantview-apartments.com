<?php
 
// 1. Pull in the database credentials
require_once 'db.php'; 

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

// ------------------------------------
// 2. Handle Form Submission
// ------------------------------------
if (isset($_POST['submit_post'])) {
    // Basic check to ensure content is not empty
    if (!empty($_POST['blog_content'])) {
        
        // 3. Security (IMPORTANT): Clean the input
        $content = $_POST['blog_content'];
        
        // 4. Prepare the SQL Statement (Prevents SQL Injection)
        $sql = "INSERT INTO posts (content) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        
        // 5. Execute the statement
        try {
            $stmt->execute([$content]);
            $message = "Post successfully published!";
        } catch (\PDOException $e) {
            $message = "Error publishing post: " . $e->getMessage();
        }
        
    } else {
        $message = "Error: Post content cannot be empty.";
    }
}
?>

<?php if (isset($message)) : ?>
    <p style="color: green; font-weight: bold;"><?php echo $message; ?></p>
<?php endif; ?>

<form method="post" action="write.php">
    <h2>New Blog Post</h2>
    
    <textarea name="blog_content" rows="20" cols="80" required></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="Publish Post">
</form>