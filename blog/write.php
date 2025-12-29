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

define('ADMIN_PASSCODE', '7747');
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
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; padding: 10px; }
        .preview-box { width: 100%; max-width: 400px; padding: 5px; }
        fieldset { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        .btn-ai { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-ai:hover { background: #218838; }
        .btn-save { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
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
                <input type="password" name="login_passcode" placeholder="Enter Passcode" required style="padding: 10px;">
                <input type="submit" value="Login" style="padding: 10px 20px; cursor: pointer;">
            </form>
        </div>
    <?php else: ?>
        <p align="right"><a href="write.php?logout=1">Logout</a></p>

        <form method="post" action="write.php">
            <fieldset>
                <legend><b>Post Management</b></legend>
                <select name="post_to_load" class="preview-box">
                    <option value="">-- Select a post to Edit or Delete --</option>
                    <?php foreach ($recent_posts as $post): ?>
                        <option value="<?php echo $post['id']; ?>">
                            ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 50)) . '...'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="load_post" value="Load">
                <input type="submit" name="delete_post" value="Delete" onclick="return confirm('Permanently delete?');" style="color:red;">
                
                <hr style="margin: 20px 0;">

                <h3><?php echo $current_id ? "Editing Post #$current_id" : "Create New Post"; ?></h3>
                
                <button type="button" class="btn-ai" id="ai-copy-btn">
                    ✨ Copy for AI Improvement
                </button>
                <span id="copy-status" style="margin-left:10px; font-size: 0.9em; color: green; display: none;">Copied!</span>
                
                <p style="font-size: 0.8em; color: #666; margin-top: 5px;">
                    Clicking this copies your text + a prompt. Then just paste into Gemini.
                </p>

                <input type="hidden" name="post_id" value="<?php echo $current_id; ?>">
                <textarea name="blog_content" id="blog_content" rows="15"><?php echo htmlspecialchars($draft_content); ?></textarea>
                
                <br><br>
                <input type="submit" name="submit_post" class="btn-save" value="Publish Changes">
                <?php if ($current_id): ?> 
                    | <a href="write.php">Cancel Edit</a> 
                <?php endif; ?>
            </fieldset>
        </form>
    <?php endif; ?>

    <script>
    document.getElementById('ai-copy-btn').addEventListener('click', function() {
        const content = document.getElementById('blog_content').value.trim();
        const status = document.getElementById('copy-status');
        
        if (!content) {
            alert("Please write something first!");
            return;
        }

        // Custom prompt that improves your writing
        const fullPrompt = "Please rewrite the following blog post to improve the flow, grammar, and professional tone. Keep the original meaning intact:\n\n" + content;

        // Copy to clipboard
        navigator.clipboard.writeText(fullPrompt).then(() => {
            status.style.display = 'inline';
            setTimeout(() => { status.style.display = 'none'; }, 2000);
            
            // Opens Gemini in a new tab for you
            window.open('https://gemini.google.com/', '_blank');
        });
    });
    </script>

</body>
</html>