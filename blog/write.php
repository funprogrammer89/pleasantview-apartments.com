<?php
session_start();

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

// Replace your old define line with this:
$stored_passcode = trim(file_get_contents('p.txt'));
define('ADMIN_PASSCODE', $stored_passcode);
$draft_content = ""; 
$current_id = ""; 
$message = "";

// --- LOGIN LOGIC ---
if (isset($_POST['login_passcode'])) {
    if ($_POST['login_passcode'] === ADMIN_PASSCODE) {
        $_SESSION['authenticated'] = true;
    } else {
        $message = "Invalid passcode.";
    }
}

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: write.php");
    exit;
}

$is_admin = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// 3. FETCH RECENT POSTS
$recent_posts = [];
if ($is_admin) {
    $stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
    $recent_posts = $stmt_list->fetchAll();
}

// 4. ACTION: DELETE
if ($is_admin && isset($_POST['delete_post'])) {
    $id_to_delete = $_POST['post_to_load'] ?? null;
    if ($id_to_delete) {
        $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt_del->execute([$id_to_delete]);
        header("Location: write.php?msg=Post+Deleted");
        exit;
    }
}

// 5. ACTION: LOAD FOR EDIT
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

// 6. ACTION: SAVE/UPDATE
if ($is_admin && isset($_POST['submit_post'])) {
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
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blog Editor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; padding: 10px; }
        .preview-box { width: 100%; max-width: 400px; padding: 5px; font-size: 16px; } /* Added 16px here */
        fieldset { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; }
        
        /* Ensure all inputs and textareas are at least 16px to prevent iOS zoom */
        textarea, input[type="password"], input[type="text"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            font-size: 16px; 
            box-sizing: border-box; 
        }
        
        .btn-ai { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; width: auto; margin-bottom: 10px; }
        .btn-ai:hover { background: #218838; }
        .btn-save { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: auto; }
        
        /* Small adjustment for layout buttons */
        input[type="submit"] { width: auto; }
    </style>
</head>
<body>

    <?php if ($message || isset($_GET['msg'])): ?>
        <p style="color: blue; font-weight: bold;"><?php echo htmlspecialchars($_GET['msg'] ?? $message); ?></p>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <div style="text-align: center; margin-top: 50px;">
            <h2>Admin Login</h2>
            <form method="post">
                <input type="password" name="login_passcode" placeholder="Enter Passcode" required>
                <br><br>
                <input type="submit" value="Login">
            </form>
        </div>
    <?php else: ?>
        <p align="right"><a href="write.php?logout=1">Logout</a></p>

        <form method="post" action="write.php">
            <fieldset>
                <legend><b>Post Management</b></legend>
                <select name="post_to_load" class="preview-box">
                    <option value="">-- Select a post --</option>
                    <?php foreach ($recent_posts as $post): ?>
                        <option value="<?php echo $post['id']; ?>">
                            ID #<?php echo $post['id']; ?>: <?php