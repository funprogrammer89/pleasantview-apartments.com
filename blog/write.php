<?php
session_start();

// 1. Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Database Connection
if (!file_exists('db.php')) {
    die("Error: db.php is missing.");
}
require_once 'db.php'; 

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 3. Passcode Logic (Using Hashing)
if (!file_exists('passcode.txt')) {
    die("Error: passcode.txt is missing.");
}
$stored_hash = trim(file_get_contents('passcode.txt'));

$draft_content = ""; 
$current_id = ""; 
$message = "";

// --- LOGIN LOGIC ---
if (isset($_POST['login_passcode'])) {
    // Note: This now checks against a HASHED password in the file
    if (password_verify($_POST['login_passcode'], $stored_hash)) {
        $_SESSION['authenticated'] = true;
        session_regenerate_id(true); // Security best practice
    } else {
        $message = "Invalid passcode.";
    }
}

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: write.php");
    exit;
}

$is_admin = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// 4. FETCH RECENT POSTS
$recent_posts = [];
if ($is_admin) {
    $stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
    $recent_posts = $stmt_list->fetchAll();
}

// 5. ACTION: DELETE
if ($is_admin && isset($_POST['delete_post'])) {
    $id_to_delete = $_POST['post_to_load'] ?? null;
    if (!empty($id_to_delete)) {
        $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt_del->execute([$id_to_delete]);
        header("Location: write.php?msg=Post+Deleted");
        exit;
    }
}

// 6. ACTION: LOAD FOR EDIT
if ($is_admin && isset($_POST['load_post'])) {
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

// 7. ACTION: SAVE/UPDATE
if ($is_admin && isset($_POST['submit_post'])) {
    $content = trim($_POST['blog_content'] ?? '');
    $pid = $_POST['post_id'] ?? '';
    
    if (!empty($content)) {
        if (!empty($pid)) {
            $sql = "UPDATE posts SET content = ? WHERE id = ?";
            $params = [$content, $pid];
        } else {
            $sql = "INSERT INTO posts (content) VALUES (?)";
            $params = [$content];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header("Location: