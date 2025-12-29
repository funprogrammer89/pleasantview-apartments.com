<?php
// 1. Start the session to "remember" the passcode
session_start();

// 2. Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. Database Connection
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
// If the passcode is submitted, check it and save to session
if (isset($_POST['login_passcode'])) {
    if ($_POST['login_passcode'] === ADMIN_PASSCODE) {
        $_SESSION['authenticated'] = true;
    } else {
        $message = "Invalid passcode. Access denied.";
    }
}

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: write.php");
    exit;
}

// Helper variable to check if user is logged in
$is_admin = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// 4. FETCH RECENT POSTS (Only if logged in)
$recent_posts = [];
if ($is_admin) {
    $stmt_list = $pdo->query("SELECT id, content FROM posts ORDER BY id DESC LIMIT 10");
    $recent_posts = $stmt_list->fetchAll();
}

// 5. ACTION: DELETE (Uses Session instead of re-typing passcode)
if ($is_admin && isset($_POST['delete_post'])) {
    $id_to_delete = $_POST['post_to_load'] ?? null;
    if ($id_to_delete) {
        $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt_del->execute([$id_to_delete]);
        header("Location: write.php?msg=Post+Deleted");
        exit;
    }
}

// 6. ACTION: LOAD
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

// 7. ACTION: SAVE/UPDATE (Uses Session)
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
        .preview-box { width: 400px; }
        .login-box { text-align: center; margin-top: 50px; padding: 20px; border: 1px solid #ccc; display: inline-block; }
    </style>
</head>
<body>

    <?php if ($message || isset($_GET['msg'])): ?>
        <p style="color: blue; font-weight: bold;"><?php echo htmlspecialchars($_GET['msg'] ?? $message); ?></p>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <div class="login-box">
            <h2>Admin Login</h2>
            <form method="post">
                <input type="password" name="login_passcode" placeholder="Enter Passcode" required>
                <input type="submit" value="Login">
            </form>
        </div>
    <?php else: ?>
        <p align="right"><a href="write.php?logout=1">Logout</a></p>

        <form method="post" action="write.php">
            <fieldset style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
                <legend><b>Post Management</b></legend>
                
                <label>Select an Entry:</label><br>
                <select name="post_to_load" class="preview-box">
                    <option value="">-- Select Post --</option>
                    <?php foreach ($recent_posts as $post): ?>
                        <option value="<?php echo $post['id']; ?>">
                            ID #<?php echo $post['id']; ?>: 
                            <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 40)) . '...'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="load_post" value="Load Content">
                <input type="submit" name="delete_post" value="DELETE" onclick="return confirm('Delete?');" style="color:red;">
                
                <hr>
                
                <h3><?php echo $current_id ? "Modifying Post #$current_id" : "Create New Post"; ?></h3>
                <input type="hidden" name="post_id" value="<?php echo $current_id; ?>">
                <textarea name="blog_content" rows="15" cols="80"><?php echo htmlspecialchars($draft_content); ?></textarea>
                <br><br>
                <input type="submit" name="submit_post" value="Publish Changes">
                <?php if ($current_id): ?> 
                    | <a href="write.php">Cancel Edit</a> 
                <?php endif; ?>
				
				
				
				
				<button type="button" id="ai-btn" style="margin-bottom: 10px; padding: 8px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">
    ✨ Improve with AI
</button>
<span id="ai-loading" style="display:none; margin-left:10px; font-style: italic;">AI is working...</span>



            </fieldset>
        </form>
    <?php endif; ?>
	
	
<script>
document.getElementById('ai-btn').addEventListener('click', function() {
    const contentArea = document.querySelector('textarea[name="blog_content"]');
    const loadingText = document.getElementById('ai-loading');
    
    if (!contentArea.value.trim()) {
        alert("Please enter some text first!");
        return;
    }

    loadingText.style.display = 'inline';
    this.disabled = true;

    fetch('ai_proxy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text: contentArea.value })
    })
    .then(response => response.json())
    .then(data => {
        // Log the data to the browser console for debugging
        console.log("AI Response:", data);

        if (data.candidates && data.candidates[0].content.parts[0].text) {
            contentArea.value = data.candidates[0].content.parts[0].text.trim();
        } else if (data.error) {
            alert("AI Error: " + data.error);
        } else {
            alert("Unexpected response format from AI.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Connection Error: Check if ai_proxy.php exists in the same folder.");
    })
    .finally(() => {
        loadingText.style.display = 'none';
        this.disabled = false;
    });
});
</script>

</body>
</html>