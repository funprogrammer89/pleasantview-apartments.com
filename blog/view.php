<?php
// ------------------------------------
// 1. Database Connection Setup
// ------------------------------------
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
     die("Error: Could not connect to the database.");
}

// ------------------------------------
// 2. Fetch All Posts from Database (Newest First)
// ------------------------------------

// Select the ID, date, and content from the 'posts' table.
// ORDER BY post_date DESC ensures the newest posts are at the top.
$sql = "SELECT id, post_date, content FROM posts ORDER BY post_date DESC";
$stmt = $pdo->query($sql);

// Fetch all the results into an array
$posts = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Simple Blog</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .post-container { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .post-date { color: #888; font-size: 0.8em; margin-bottom: 5px; }
        .post-content { white-space: pre-wrap; margin-top: 10px; } /* preserves formatting/line breaks */
        h2 a { text-decoration: none; color: #333; }
        h2 a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <h1>The Latest Blog Entries</h1>

    <?php 
    // Check if any posts were found
    if (count($posts) > 0): 
    ?>
        
        <?php foreach ($posts as $post): 
            
            // Convert the 24-hour time to a user-friendly format
            $display_date = date('F j, Y \a\t g:i A', strtotime($post['post_date']));
            
            // Get the first 250 characters of the content for a preview
            $preview_text = substr($post['content'], 0, 250);
            
            // Add an ellipsis if the content was longer than 250 characters
            if (strlen($post['content']) > 250) {
                $preview_text .= '...';
            }
            
        ?>
        
            <div class="post-container">
                
                <p class="post-date">Published: <?php echo htmlspecialchars($display_date); ?></p>
                
                <h2>
                    <a href="view_post.php?id=<?php echo htmlspecialchars($post['id']); ?>">
                        <?php echo nl2br(htmlspecialchars($preview_text)); ?>
                    </a>
                </h2>
                
                <p>
                    <a href="view_post.php?id=<?php echo htmlspecialchars($post['id']); ?>">Read More</a>
                </p>
                
            </div>
            
        <?php endforeach; ?>

    <?php else: ?>
        <p>No blog posts found yet. Go to the admin page to create one!</p>
    <?php endif; ?>

</body>
</html>