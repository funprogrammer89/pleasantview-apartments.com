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
if (!file_exists('p.txt')) {
    die("Error: passcode file is missing.");
}
$stored_hash = trim(file_get_contents('p.txt'));

$draft_content = ""; 
$current_id = ""; 
$message = "";

// --- LOGIN LOGIC ---
if (isset($_POST['login_passcode'])) {
    if (password_verify($_POST['login_passcode'], $stored_hash)) {
        $_SESSION['authenticated'] = true;
        session_regenerate_id(true);
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

// 6. ACTION: AUTO-LOAD (Triggered by dropdown change)
// We check for 'post_to_load' without requiring a specific 'load_post' button click
if ($is_admin && isset($_POST['post_to_load']) && !isset($_POST['delete_post']) && !isset($_POST['submit_post'])) {
    $selected_id = $_POST['post_to_load'];
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
        header("Location: write.php?msg=Saved+Successfully");
        exit;
    } else {
        $message = "Post content cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blog Editor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; padding: 10px; color: #333; }
        fieldset { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; }
        textarea, input, select { 
            font-size: 16px; 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 10px;
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .btn-ai { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; width: auto; padding: 10px 15px; }
        .btn-save { background: #007bff; color: white; border: none; cursor: pointer; width: auto; padding: 10px 15px; }
        .preview-box { max-width: 400px; }
        .msg-banner { color: #004085; background-color: #cce5ff; border: 1px solid #b8daff; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <?php if ($message || isset($_GET['msg'])): ?>
        <div class="msg-banner">
            <?php echo htmlspecialchars($_GET['msg'] ?? $message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <div style="text-align: center; margin-top: 50px;">
            <h2>Admin Login</h2>
            <form method="post">
                <input type="password" name="login_passcode" placeholder="Enter Passcode" required style="max-width:300px;">
                <br>
                <input type="submit" value="Login" style="width: auto; padding: 10px 25px;">
            </form>
        </div>
    <?php else: ?>
        <p align="right"><a href="write.php?logout=1">Logout</a></p>

        <form method="post" id="editor-form">
            <fieldset>
                <legend><b>Post Management</b></legend>
                <select name="post_to_load" class="preview-box" onchange="this.form.submit()">
                    <option value="">-- Select to Load a Post --</option>
                    <?php foreach ($recent_posts as $post): ?>
                        <option value="<?php echo htmlspecialchars($post['id']); ?>" <?php echo ($current_id == $post['id']) ? 'selected' : ''; ?>>
                            ID #<?php echo htmlspecialchars($post['id']); ?>: 
                            <?php echo htmlspecialchars(substr(strip_tags($post['content'] ?? ''), 0, 50)) . '...'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br>
                <input type="submit" name="delete_post" value="Delete Selected" onclick="return confirm('Permanently delete?');" style="color:red; width:auto;">
                
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                <h3><?php echo $current_id ? "Editing Post #" . htmlspecialchars($current_id) : "Create New Post"; ?></h3>
                
                <button type="button" class="btn-ai" id="ai-copy-btn">✨ Copy for AI Improvement</button>
                <span id="copy-status" style="margin-left:10px; font-size: 0.9em; color: green; display: none;">Copied!</span>
                
                <p style="font-size: 0.8em; color: #666; margin-top: 5px;">
                    Copies text and includes a prompt. Then paste into Gemini.
                </p>

                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($current_id); ?>">
                <textarea name="blog_content" id="blog_content" rows="15"><?php echo htmlspecialchars($draft_content); ?></textarea>
                
                <input type="submit" name="submit_post" class="btn-save" value="Publish Changes">
                <?php if ($current_id): ?> 
                    | <a href="write.php">Cancel/New Post</a> 
                <?php endif; ?>
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">
                <b>Markdown Cheat sheet</b><br>
                <code>![img text](URL)</code>
            </fieldset>
        </form>
    <?php endif; ?>

    <script>
    document.getElementById('ai-copy-btn')?.addEventListener('click', function() {
        const content = document.getElementById('blog_content').value.trim();
        const status = document.getElementById('copy-status');
        if (!content) { alert("Please write something first!"); return; }

        const fullPrompt = "Please rewrite the following blog post to improve the flow, grammar, and professional tone. Keep the original meaning intact:\n\n" + content;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(fullPrompt).then(() => {
                status.style.display = 'inline';
                setTimeout(() => { status.style.display = 'none'; }, 2000);
                window.open('https://gemini.google.com/', '_blank');
            });
        } else {
            alert("Clipboard access failed. Please copy manually.");
        }
    });
    </script>
</body>
</html>