<?php
// 1. Include the Parsedown library
require_once 'Parsedown.php';

// 2. Initialize the Parser
$Parsedown = new Parsedown();

$host = 'sql213.infinityfree.com';
$db = 'epiz_33496197_blogs';
$user = 'epiz_33496197';
$pass = 'jJgY7jeQtt';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed.");
}

// 3. Fetch the posts
$sql = "SELECT id, post_date, content FROM posts ORDER BY post_date DESC";
$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ken Elliott's Blog</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: auto; line-height: 1.6; }
        .post { border-bottom: 1px solid #eee; padding: 20px 0; }
        .date { color: #666; font-size: 0.9em; }
        /* Style for the Markdown generated images */
        img { max-width: 100%; height: auto; display: block; margin: 10px 0; }
    </style>
</head>
<body>

    <h1>Ken Elliott's (Micro?) Blog</h1>

    <?php foreach ($posts as $post): ?>
        <div class="post">
            <div class="date"><?php echo date('F j, Y \a\t g:i A', strtotime($post['post_date'])); ?></div>
            
            <div class="content">
                <?php 
                    // THE MAGIC PART:
                    // 1. Get raw content from database
                    $raw_markdown = $post['content'];
                    
                    // 2. Turn Markdown into HTML
                    $html_content = $Parsedown->text($raw_markdown);
                    
                    // 3. Output the HTML directly (No htmlspecialchars here, or it will break the tags!)
                    echo $html_content; 
                ?>
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>