<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db.php'; 

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     // Force PDO to show errors if something fails
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 
$current_id = ""; 
$message = "";

// 1. Fetch the last 10 entries
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// 2. ACTION: LOAD (Pulls data into the editor)
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

// 3. ACTION: DELETE
if (isset($_POST['delete_post'])) {
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        $id_to_delete = $_POST['post_to_load'] ?? null;
        if ($id_to_delete) {
            try {
                $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt_del->execute([$id_to_delete]);
                
                // Verify if a row was actually removed
                if ($stmt_del->rowCount() > 0) {
                    header("Location: write.php?msg=Post+Deleted");
                    exit;
                } else {
                    $message = "Error: Post ID #$id_to_delete not found in database.";
                }
            } catch (\PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
            }
        } else {
            $message = "Error: No post selected to delete.";
        }
    } else {
        $message = "Error: Invalid passcode for deletion.";
    }
}

// 4. ACTION: SAVE/UPDATE
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
    } else {
        $message = "Error: Invalid passcode for saving.";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <?php if ($message || isset($_GET['msg'])): ?>
        <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($_GET['msg'] ?? $message); ?></p>
    <?php endif; ?>

    <form method="post" action="write.php">
        <fieldset style="background: #f0f0f0; padding: 20px;">
            <legend><b>Post Management</b></legend>
            
            <label>1. Select an Entry:</label>
            <select name="post_to_load">
                <option value="">-- Choose a post --</option>
                <?php foreach ($recent_posts as $post): ?>
                    <option value="<?php echo $post['id']; ?>">ID #<?php echo $post['id']; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="load_post" value="Load Content">
            <input type="submit" name="delete_post" value="DELETE THIS ID" onclick="return confirm('Permanently delete?');" style="color:red;">
            
            <hr>
            
            <label>2. Admin Passcode (Required to Delete or Save):</label><br>
            <input type="password" name="passcode" required>
            
            <hr>
            
            <h3><?php echo $current_id ? "Editing Post #$current_id" : "Create New Post"; ?></h3>
            <input type="hidden" name="post_id" value="<?php echo $current_id; ?>">
            <textarea name="blog_content" rows="15" cols="80" placeholder="Type here..."><?php echo htmlspecialchars($draft_content); ?></textarea>
            <br><br>
            <input type="submit" name="submit_post" value="Save / Publish">
            <?php if ($current_id): ?> <a href="write.php">Cancel</a> <?php endif; ?>
        </fieldset>
    </form>
</body>
</html>