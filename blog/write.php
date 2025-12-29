<?php
// 1. Error Reporting (Turn this off once the site is live)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Database Connection
require_once 'db.php'; 
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 
$current_id = ""; 
$message = "";

// 3. FETCH RECENT POSTS (For the dropdown menu)
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// 4. ACTION: DELETE (Requires Passcode)
if (isset($_POST['delete_post'])) {
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        $id_to_delete = $_POST['post_to_load'] ?? null;
        if ($id_to_delete) {
            $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt_del->execute([$id_to_delete]);
            // Redirect to prevent form resubmission
            header("Location: write.php?msg=Deleted+Successfully"); 
            exit;
        }
    } else {
        $message = "Error: Invalid passcode for deletion.";
    }
}

// 5. ACTION: LOAD FOR EDIT (No passcode needed to just view)
if (isset($_POST['load_post'])) {
    $selected_id = $_POST['post_to_load'] ?? null;
    if ($selected_id) {
        $stmt_load = $pdo->prepare("SELECT id, content FROM posts WHERE id = ?");
        $stmt_load->execute([$selected_id]);
        $loaded_post = $stmt_load->fetch();
        if ($loaded_post) {
            $draft_content = $loaded_post['content'];
            $current_id = $loaded_post['id'];
        }
    }
}

// 6. ACTION: SAVE/UPDATE (Requires Passcode)
if (isset($_POST['submit_post'])) {
    $draft_content = $_POST['blog_content'] ?? '';
    $post_id = $_POST['post_id'] ?? ''; 

    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        if (!empty($draft_content)) {
            if (!empty($post_id)) {
                // Logic: If ID exists, UPDATE
                $sql = "UPDATE posts SET content = ? WHERE id = ?";
                $params = [$draft_content, $post_id];
            } else {
                // Logic: If ID is empty, INSERT
                $sql = "INSERT INTO posts (content) VALUES (?)";
                $params = [$draft_content];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = "Success: Post saved.";
            $draft_content = ""; 
            $current_id = "";
        } else {
            $message = "Error: Content is empty.";
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
$display_msg = $_GET['msg'] ?? $message;
if ($display_msg): 
?>
    <p style="color: blue;"><b><?php echo htmlspecialchars($display_msg); ?></b></p>
<?php endif; ?>

<fieldset style="padding: 15px; margin-bottom: 20px;">
    <legend>Manage Existing Posts</legend>
    <form method="post">
        <select name="post_to_load">
            <option value="">-- Select Post --</option>
            <?php foreach ($recent_posts as $post): ?>
                <option value="<?php echo $post['id']; ?>">
                    ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr($post['content'], 0, 30)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" name="load_post" value="Load into Editor">
        <br><br>
        <label>Passcode to Delete:</label>
        <input type="password" name="passcode" style="width:80px;">
        <input type="submit" name="delete_post" value="Delete Selected" onclick="return confirm('Confirm Delete?');">
    </form>
</fieldset>

<form method="post">
    <h2><?php echo $current_id ? "Edit Post #$current_id" : "New Post"; ?></h2>
    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($current_id); ?>">
    
    <label>Admin Passcode:</label><br>
    <input type="password" name="passcode" required><br><br>
    
    <label>Content:</label><br>
    <textarea name="blog_content" rows="15" cols="70"><?php echo htmlspecialchars($draft_content); ?></textarea><br><br>
    
    <input type="submit" name="submit_post" value="Publish/Update">
    <?php if ($current_id): ?>
        <a href="write.php">Cancel Edit</a>
    <?php endif; ?>
</form>

</body>
</html>