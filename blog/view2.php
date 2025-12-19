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

// ------------------------------------
// 3. PAGINATION SETUP
// ------------------------------------
$posts_per_page = 10;

// Get the current page from the URL, default to 1
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Calculate the offset for the SQL query
$offset = ($current_page - 1) * $posts_per_page;

// Get the total number of posts
$count_sql = "SELECT COUNT(*) FROM posts";
$total_posts = $pdo->query($count_sql)->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_posts / $posts_per_page);

// 4. Fetch only the posts for the current page
$sql = "SELECT id, post_date, content FROM posts ORDER BY post_date DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $posts_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
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
    /* 1. The Background Layer */
    html {
        /* background-attachment: fixed ensures the gradient doesn't stretch 
           weirdly as you scroll through many posts */
        background: linear-gradient(135deg, #243949 0%, #517fa4 100%) fixed;
        min-height: 100vh;
    }
    /* 2. The "Floating" Card Layer */
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        max-width: 800px; 
        
        /* UPDATED: 40px margin at top/bottom creates the "floating" gap */
        margin: 40px auto; 
        
        line-height: 1.6; 
        padding: 40px; 
        background-color: #ffffff; 
        
        /* UPDATED: Rounded corners make it look like a physical sheet of paper */
        border-radius: 15px;
        /* The Column Lines */
        border-left: 1px solid #d1d1d1;
        border-right: 1px solid #d1d1d1;
        
        /* UPDATED: A deeper, softer shadow creates the illusion of height/depth */
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }
    /* Keep your existing post styles */
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
    
    /* NEW: Pagination Styles */
    .pagination {
        text-align: center;
        margin-top: 40px;
        padding: 20px 0;
    }
    
    .pagination a, .pagination span {
        display: inline-block;
        padding: 8px 12px;
        margin: 0 4px;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
        background-color: #f0f0f0;
        transition: background-color 0.3s;
    }
    
    .pagination a:hover {
        background-color: #517fa4;
        color: white;
    }
    
    .pagination .current {
        background-color: #243949;
        color: white;
        font-weight: bold;
    }
    
    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
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
            
            <div class="date">
    <?php 
        $date = new DateTime($post['post_date']);
        // Keeping your 3-hour correction
        $date->modify('+3 hours'); 
        // Set the timezone to New York so PHP knows if it's currently EST or EDT
        $date->setTimezone(new DateTimeZone('America/New_York'));
        // 'T' will now show "EST" because it is December
        echo $date->format('F j, Y \a\t g:i A T'); 
    ?>
</div>    
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
    
    <!-- PAGINATION LINKS -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <!-- Previous Button -->
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>">&laquo; Previous</a>
        <?php else: ?>
            <span class="disabled">&laquo; Previous</span>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $current_page): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <!-- Next Button -->
        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>