<?php
require_once 'db.php'; 

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 
$current_id = ""; 

// --- 1. PRE-FETCH: Get recent posts for the dropdown list ---
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// --- 2. ACTION: DELETE A POST ---
if (isset($_POST['delete_post'])) {
    // Check passcode before deleting
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        $id_to_delete = $_POST['post_to_load'] ?? null;
        if ($id_to_delete) {
            $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            try {
                $stmt_del->execute([$id_to_delete]);
                $message = "Post ID #$id_to_delete deleted successfully.";
                // Refresh to clear the dropdown
                header("Location: write.php?msg=deleted"); 
                exit;
            } catch (\PDOException $e) {
                $message = "Error deleting post: " . $e->getMessage();
            }
        }
    } else {
        $message = "Error: Invalid passcode. Delete denied.";
    }
}

// --- 3. ACTION: LOAD POST INTO EDITOR ---
// Note: Passcode is usually not required just to VIEW/LOAD, 
// but it is required to SAVE the changes later.
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

// --- 4. ACTION: SAVE (INSERT OR UPDATE) ---
if (isset($_POST['submit_post'])) {
    $draft_content = $_POST['blog_content'] ?? '';
    $post_id = $_POST['post_id'] ?? ''; 

    // Check passcode before modifying the database
    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        if (!empty($draft_content)) {
            if (!empty($post_id)) {
                // UPDATE if an ID exists
                $sql = "UPDATE posts SET content
				
				
<!DOCTYPE html>
<html>
<head>
    <title>Blog Admin</title>
</head>
<body>

<?php if (isset($message) || isset($_GET['msg'])) : ?>
    <p style="color: blue; font-weight: bold;">
        <?php echo htmlspecialchars($_GET['msg'] ?? $message); ?>
    </p>
<?php endif; ?>

<fieldset style="margin-bottom: 20px; padding: 15px; border: 2px solid #ccc;">
    <legend>Modify or Delete Entries</legend>
    <form method="post" action="write.php">
        <label>Select Post:</label>
        <select name="post_to_load" required>
            <option value="">-- Choose --</option>
            <?php foreach ($recent_posts as $post): ?>
                <option value="<?php echo $post['id']; ?>">
                    ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr($post['content'], 0, 30)); ?>...
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="submit" name="load_post" value="Load for Editing">
        
        <hr>
        
        <label>Passcode to Delete:</label>
        <input type="password" name="passcode" style="width: 60px;">
        <input type="submit" name="delete_post" value="Permanently Remove" 
               style="background-color: #ffcccc;"
               onclick="return confirm('Really delete this?');">
    </form>
</fieldset>

<form method="post" action="write.php">
    <h2><?php echo $current_id ? "Modifying Post #$current_id" : "New Post"; ?></h2>
    
    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($current_id); ?>">

    <label>Admin Passcode:</label><br>
    <input type="password" name="passcode" required>
    <br><br>
    
    <label>Content:</label><br>
    <textarea name="blog_content" rows="15" cols="80" required><?php echo htmlspecialchars($draft_content); ?></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="Save Changes">
    
    <?php if ($current_id): ?>
        <a href="write.php">Cancel & Start New</a>
    <?php endif; ?>
</form>

</body>
</html>