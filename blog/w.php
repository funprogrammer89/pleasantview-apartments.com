<?php
require_once 'db.php'; 

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 
$current_id = ""; // If this is empty, we are in "New Post" mode. If it has a number, we are in "Edit" mode.

// --- 1. REFRESH DROPDOWN LIST ---
// We do this every time the page loads so the dropdown is always up to date.
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// --- 2. ACTION: DELETE A POST ---
if (isset($_POST['delete_post'])) {
    $id_to_delete = $_POST['post_to_load'] ?? null;
    if ($id_to_delete) {
        $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        try {
            $stmt_del->execute([$id_to_delete]);
            $message = "Post ID #$id_to_delete has been deleted.";
            // Refresh the list again so the deleted post disappears from the dropdown immediately
            header("Location: write.php?msg=deleted"); 
            exit;
        } catch (\PDOException $e) {
            $message = "Error deleting post: " . $e->getMessage();
        }
    }
}

// --- 3. ACTION: LOAD POST INTO EDITOR ---
if (isset($_POST['load_post'])) {
    $selected_id = $_POST['post_to_load'] ?? null;
    if ($selected_id) {
        $stmt_load = $pdo->prepare("SELECT id, content FROM posts WHERE id = ?");
        $stmt_load->execute([$selected_id]);
        $loaded_post = $stmt_load->fetch();
        if ($loaded_post) {
            $draft_content = $loaded_post['content']; // Put text in the box
            $current_id = $loaded_post['id'];         // Store ID in our tracker
        }
    }
}

// --- 4. ACTION: SAVE (INSERT OR UPDATE) ---
if (isset($_POST['submit_post'])) {
    $draft_content = $_POST['blog_content'] ?? '';
    $post_id = $_POST['post_id'] ?? ''; // Retrieved from the hidden input field

    // Security Check
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        if (!empty($draft_content)) {
            
            if (!empty($post_id)) {
                // UPDATE MODE: The hidden ID field was filled
                $sql = "UPDATE posts SET content = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $params = [$draft_content, $post_id];
                $success_text = "Post ID #$post_id updated successfully!";
            } else {
                // INSERT MODE: The hidden ID field was empty
                $sql = "INSERT INTO posts (content) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $params = [$draft_content];
                $success_text = "New post successfully published!";
            }

            try {
                $stmt->execute($params);
                $message = $success_text;
                $draft_content = ""; // Clear the editor
                $current_id = "";    // Reset back to "New Post" mode
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
    <title>Blog Management</title>
</head>
<body>

<?php 
// Display success or error messages
if (isset($message) || isset($_GET['msg'])) : 
    $display_msg = $_GET['msg'] ?? $message;
?>
    <p style="color: blue; font-weight: bold;">
        <?php echo htmlspecialchars($display_msg); ?>
    </p>
<?php endif; ?>

<fieldset style="margin-bottom: 20px; padding: 15px; background-color: #f4f4f4;">
    <legend>Manage Recent Entries</legend>
    <form method="post" action="write.php">
        <label>Select Post:</label>
        <select name="post_to_load" required>
            <option value="">-- Choose a post --</option>
            <?php foreach ($recent_posts as $post): ?>
                <option value="<?php echo $post['id']; ?>">
                    ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr($post['content'], 0, 40)); ?>...
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="submit" name="load_post" value="Load into Editor">
        
        <input type="submit" name="delete_post" value="Remove Post" 
               style="color: red;" 
               onclick="return confirm('Are you sure you want to permanently delete this post?');">
    </form>
</fieldset>

<hr>

<form method="post" action="write.php">
    <h2>
        <?php echo $current_id ? "Modifying Post #$current_id" : "Write New Post"; ?>
    </h2>
    
    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($current_id); ?>">

    <label for="passcode">Admin Passcode:</label><br>
    <input type="password" id="passcode" name="passcode" required>
    <br><br>
    
    <label for="blog_content">Content:</label><br>
    <textarea id="blog_content" name="blog_content" rows="15" cols="80" required><?php echo htmlspecialchars($draft_content); ?></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="<?php echo $current_id ? 'Save Changes' : 'Publish New Post'; ?>">
    
    <?php if ($current_id): ?>
        <br><br>
        <a href="write.php">Stop Editing & Start New Post</a>
    <?php endif; ?>
</form>

</body>
</html>