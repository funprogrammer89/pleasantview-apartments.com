<?php
// 1. Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Database Connection
require_once 'db.php'; 
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 
$current_id = ""; 
$message = "";

// 3. FETCH RECENT POSTS (Restoring 'content' to the SELECT so we can preview it)
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// 4. ACTION: LOAD
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

// 5. ACTION: DELETE
if (isset($_POST['delete_post'])) {
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        $id_to_delete = $_POST['post_to_load'] ?? null;
        if ($id_to_delete) {
            $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt_del->execute([$id_to_delete]);
            header("Location: write.php?msg=Post+Deleted");
            exit;
        }
    } else { $message = "Error: Invalid passcode for deletion."; }
}

// 6. ACTION: SAVE/UPDATE
if (isset($_POST['submit_post'])) {
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        $content = $_POST['blog_content'] ?? '';
        $pid = $_POST['post_id'] ?? '';
        if (!empty($content)) {
            $sql = !empty($pid) ? "UPDATE posts SET content = ? WHERE id = ?" : "INSERT INTO posts (content) VALUES (?)";
            $params = !empty($pid) ? [$content, $pid] : [$content];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header("Location: write.php?msg=Saved+Successfully");
            exit;
        }
    } else { $message = "Error: Invalid passcode for saving."; }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blog Editor</title>
    <style>
        .preview-box { width: 400px; } /* Keeps the dropdown from becoming too wide */
    </style>
</head>
<body>
    <?php if ($message || isset($_GET['msg'])): ?>
        <p style="color: blue; font-weight: bold;"><?php echo htmlspecialchars($_GET['msg'] ?? $message); ?></p>
    <?php endif; ?>

    <form method="post" action="write.php">
        <fieldset style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <legend><b>Post Management</b></legend>
            
            <label>1. Select an Entry to Load or Delete:</label><br>
            <select name="post_to_load" class="preview-box">
                <option value="">-- Select Post --</option>
                <?php foreach ($recent_posts as $post): ?>
                    <option value="<?php echo $post['id']; ?>">
                        ID #<?php echo $post['id']; ?>: 
                        <?php 
                            // Pull the first 40 characters for the preview string
                            $preview = strip_tags($post['content']); // Remove HTML tags if any
                            echo htmlspecialchars(substr($preview, 0, 40)) . '...'; 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="load_post" value="Load Content">
            <input type="submit" name="delete_post" value="DELETE SELECTED" onclick="return confirm('Permanently delete this entry?');" style="color:red;">
            
            <hr>
            
            <label>2. Admin Passcode (Required to Save/Delete):</label><br>
            <input type="password" name="passcode" required>
            
            <hr>
            
            <h3><?php echo $current_id ? "Modifying Post #$current_id" : "Create New Post"; ?></h3>
            <input type="hidden" name="post_id" value="<?php echo $current_id; ?>">
            <textarea name="blog_content" rows="15" cols="80" placeholder="Type content here..."><?php echo htmlspecialchars($draft_content); ?></textarea>
            <br><br>
            <input type="submit" name="submit_post" value="Submit Changes">
            <?php if ($current_id): ?> 
                | <a href="write.php">Discard Edit / Start New</a> 
            <?php endif; ?>
        </fieldset>
    </form>
</body>
</html>