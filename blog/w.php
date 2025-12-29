<?php
require_once 'db.php'; 

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

define('ADMIN_PASSCODE', '7747');
$draft_content = ""; 

// --- NEW: FETCH LAST 10 POSTS FOR DROPDOWN ---
$stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
$recent_posts = $stmt_list->fetchAll();

// --- NEW: LOAD SELECTED POST CONTENT ---
if (isset($_POST['load_post'])) {
    $selected_id = $_POST['post_to_load'] ?? null;
    if ($selected_id) {
        $stmt_load = $pdo->prepare("SELECT content FROM posts WHERE id = ?");
        $stmt_load->execute([$selected_id]);
        $loaded_post = $stmt_load->fetch();
        if ($loaded_post) {
            $draft_content = $loaded_post['content'];
        }
    }
}

// --- EXISTING: SAVE POST LOGIC ---
if (isset($_POST['submit_post'])) {
    $draft_content = $_POST['blog_content'] ?? '';

    if (isset($_POST['passcode']) && $_POST['passcode'] === ADMIN_PASSCODE) {
        if (!empty($draft_content)) {
            $sql = "INSERT INTO posts (content) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([$draft_content]);
                $message = "Post successfully published!";
                $draft_content = ""; 
            } catch (\PDOException $e) {
                $message = "Error publishing post: " . $e->getMessage();
            }
        } else {
            $message = "Error: Post content cannot be empty.";
        }
    } else {
        $message = "Error: Invalid passcode. Access denied.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Blog Posts</title>
</head>
<body>

<?php if (isset($message)) : ?>
    <p style="color: <?php echo (strpos($message, 'successfully') !== false) ? 'green' : 'red'; ?>; font-weight: bold;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<fieldset style="margin-bottom: 20px; padding: 15px;">
    <legend>Load Recent Entry</legend>
    <form method="post" action="w.php">
        <label for="post_to_load">Select a recent post:</label>
        <select name="post_to_load" id="post_to_load">
            <?php foreach ($recent_posts as $post): ?>
                <option value="<?php echo $post['id']; ?>">
                    ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr($post['content'], 0, 50)); ?>...
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" name="load_post" value="Load into Editor">
    </form>
</fieldset>

<hr>

<form method="post" action="w.php">
    <h2>Editor</h2>
    
    <label for="passcode">Enter Passcode:</label><br>
    <input type="password" id="passcode" name="passcode" required>
    <br><br>
    
    <label for="blog_content">Post Content:</label><br>
    <textarea id="blog_content" name="blog_content" rows="20" cols="80" required><?php echo htmlspecialchars($draft_content); ?></textarea>
    <br><br>
    
    <input type="submit" name="submit_post" value="Publish Post">
</form>

</body>
</html>