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