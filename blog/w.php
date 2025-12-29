<?php
require_once 'db.php'; 

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 
$current_id = ""; // Track if we are editing an existing post

// 1. FETCH LAST 10 POSTS FOR DROPDOWN
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// 2. LOAD SELECTED POST CONTENT
if (isset($_POST['load_post'])) {
    $selected_id = $_POST['post_to_load'] ?? null;
    if ($selected_id) {
        $stmt_load = $pdo->prepare("SELECT id, content FROM posts WHERE id = ?");
        $stmt_load->execute([$selected_id]);
        $loaded_post = $stmt_load->fetch();
        if ($loaded_post) {
            $draft_content = $loaded_post['content'];
            $current_id = $loaded_post['id']; // Remember the ID
        }
    }
}

// 3. SAVE OR UPDATE POST LOGIC
if (isset($_POST['submit_post'])) {
    $draft_content = $_POST['blog_content'] ?? '';
    $post_id = $_POST['post_id'] ?? ''; // Get the ID from the hidden field

    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        if (!empty($draft_content)) {
            
            if (!empty($post_id)) {
                // UPDATE existing record
                $sql = "UPDATE posts SET content = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $params = [$draft_content, $post_id];
                $success_text = "Post ID #$post_id updated successfully!";
            } else {
                // INSERT new record
                $sql = "INSERT INTO posts (content) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $params = [$draft_content];
                $success_text = "New post successfully published!";
            }

            try {
                $stmt->execute($params);
                $message = $success_text;
                $draft_content = ""; 
                $current_id = ""; // Reset after success
            } catch (\PDOException $e) {
                $message = "Database error: " . $e->getMessage();
            }
        } else {
            $message = "Error: Post content cannot be empty.";
        }
    } else {
        $message = "Error: Invalid passcode.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit/Update Blog Posts</title>
</head>
<body>

<?php if (isset($message)) : ?>
    <p style="color: <?php echo (strpos($message, 'successfully') !== false) ? 'green' : 'red'; ?>; font-weight: bold;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<fieldset style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9;">
    <legend>Load Existing Entry to Modify</legend>
    <form method="post" action="w.php">
        <select name="post_to_load">
            <option value="">-- Select a post --</option>
            <?php foreach ($recent_posts as $post): ?>
                <option value="<?php echo $post['id']; ?>">
                    ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr($post['content'], 0, 40)); ?>...
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" name="load_post" value="Load into Editor">
    </form>
</fieldset>

<hr>

<form method="post" action="w.php">
    <h2>
        <?php echo $current_id ? "Editing Post #$current_id" : "Create New Post"; ?>
    </h2>
    
    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($current_id); ?>">

    <label for="passcode">Enter Passcode:</label><br>
    <input type="password" id="passcode" name="passcode" required>
    <br><br>
    
    <label for="blog_content">Post Content:</label><br>
    <textarea id="blog_content" name="blog_content" rows="20" cols="80" required><?php echo htmlspecialchars($draft_content); ?></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="<?php echo $current_id ? 'Update Existing Post' : 'Publish New Post'; ?>">
    
    <?php if ($current_id): ?>
        <a href="w.php">Cancel Edit / New Post</a>
    <?php endif; ?>
</form>

</body>
</html>