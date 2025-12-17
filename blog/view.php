<?php
// 1. Include the Parsedown library
require_once 'Parsedown.php';

// 2. Initialize the Parser
$Parsedown = new Parsedown();

// 1. Pull in the database credentials
require_once 'db.php';

// 2. The connection logic stays here
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

// 3. Fetch the posts
$sql = "SELECT id, post_date, content FROM posts ORDER BY post_date DESC";
$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll();

// --- NEW: Define your color palette here ---
// You can add as many colors as you want. These are soft pastels.
$colors = [
    '#FFF4E6', // Light Orange
    '#E6F4FF', // Light Blue
    '#F0FFF4', // Mint Green
    '#FFF0F5', // Lavender Blush
    '#FFFFE0', // Light Yellow
    '#F2F2F2'  // Soft Gray
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ken Elliott's Blog</title>
<style>
    /* 1. This styles the empty "void" areas on the sides */
    html {
        background-color: #f0f2f5; /* A cool, modern light gray/blue */
        /* You could also try: #2c3e50 (Dark), #fdf6e3 (Cream), or #e8f5e9 (Mint) */
    }

    /* 2. This styles the centered blog area */
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        max-width: 800px; 
        margin: 0 auto;        /* Centers the blog */
        line-height: 1.6; 
        padding: 40px;         /* Space inside the lines */
        background-color: #ffffff; /* Keeps the post area white */
        
        /* The Column Lines you wanted */
        border-left: 1px solid #d1d1d1;
        border-right: 1px solid #d1d1d1;
        
        /* Optional: adds a slight shadow so the blog "lifts" off the background */
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
    }

    .post { 
        padding: 25px;
        margin-bottom: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .date { 
        color: #666; 
        font-size: 0.9em; 
        margin-bottom: 10px; 
        font-weight: bold;
    }

    img { 
        max-width: 100%; 
        height: auto; 
        display: block; 
        margin: 15px auto; 
        border-radius: 6px; 
    }
    
    hr.style-two {
        border: 0;
        height: 1px;
        background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0));
        margin-top: 20px;
    }
</style>
</head>
<body>

    <center><img src="banner.gif"></center>

    <?php foreach ($posts as $index => $post): ?>
        <?php 
            // Calculate which color to use
            // The % operator loops back to 0 when it runs out of colors
            $bg_color = $colors[$index % count($colors)];
        ?>

        <div class="post" style="background-color: <?php echo $bg_color; ?>;">
            
            <div class="date"><?php echo date('F j, Y \a\t g:i A', strtotime($post['post_date'])); ?></div>
            
            <div class="content">
                <?php 
                    $raw_markdown = $post['content'];
                    $html_content = $Parsedown->text($raw_markdown);
                    echo $html_content; 
                ?>
                
                <hr class="style-two">
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>