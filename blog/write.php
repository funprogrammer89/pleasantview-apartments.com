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

// 3. Passcode Logic
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
    $id_to_delete = $_POST['post_id'] ?? null;
    if (!empty($id_to_delete)) {
        $stmt_del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt_del->execute([$id_to_delete]);
        header("Location: write.php?msg=Post+Deleted");
        exit;
    }
}

// 6. ACTION: AUTO-LOAD
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
        header("Location: write.php?msg=Saved+Successfully&saved_id=" . ($pid ?: $pdo->lastInsertId()));
        exit;
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
        fieldset { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; position: relative; }
        textarea, input, select { font-size: 16px; width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-ai { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; width: auto; padding: 10px 15px; border-radius: 4px; }
        .btn-save { background: #007bff; color: white; border: none; cursor: pointer; width: auto; padding: 10px 15px; border-radius: 4px; }
        .btn-del { background: #dc3545; color: white; border: none; cursor: pointer; width: auto; padding: 10px 15px; border-radius: 4px; margin-left: 10px; }
        .btn-clear { background: #6c757d; color: white; border: none; cursor: pointer; width: auto; padding: 10px 15px; border-radius: 4px; }
        .msg-banner { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        #autosave-status { font-size: 0.8em; color: #888; float: right; height: 1.2em; }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <?php if ($message || isset($_GET['msg'])): ?>
        <div class="msg-banner"><?php echo htmlspecialchars($_GET['msg'] ?? $message); ?></div>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <div style="text-align: center; margin-top: 50px;">
            <h2>Admin Login</h2>
            <p>Your session may have expired. Please log in to continue.</p>
            <form method="post">
                <input type="password" name="login_passcode" placeholder="Enter Passcode" required style="max-width:300px;"><br>
                <input type="submit" value="Login" style="width: auto; padding: 10px 25px;">
            </form>
        </div>
    <?php else: ?>
        <p align="right"><a href="write.php?logout=1">Logout</a></p>

        <form method="post" id="editor-form">
            <fieldset>
                <legend><b>Post Management</b></legend>
                <select name="post_to_load" id="post_selector" onchange="this.form.submit()" style="max-width: 400px;">
                    <option value="">-- Select to Load a Post --</option>
                    <?php foreach ($recent_posts as $post): ?>
                        <option value="<?php echo $post['id']; ?>" <?php echo ($current_id == $post['id']) ? 'selected' : ''; ?>>
                            ID #<?php echo $post['id']; ?>: <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 50)); ?>...
                        </option>
                    <?php endforeach; ?>
                </select>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                <span id="autosave-status"></span>
                <h3 id="editor-title"><?php echo $current_id ? "Editing Post #" . htmlspecialchars($current_id) : "Create New Post"; ?></h3>
                
                <button type="button" class="btn-ai" id="ai-copy-btn">✨ Copy for AI</button><br><br>
                <span id="copy-status" style="margin-left:10px; font-size: 0.9em; color: green; display: none;">Copied!</span>

                <input type="hidden" name="post_id" id="post_id" value="<?php echo htmlspecialchars($current_id); ?>">
                <textarea name="blog_content" id="blog_content" rows="15" placeholder="Start writing..."><?php echo htmlspecialchars($draft_content); ?></textarea>
                
                <div style="margin-top: 10px;">
                    <input type="submit" name="submit_post" class="btn-save" value="Publish Changes">
                    
                    <button type="button" id="clear-btn" class="btn-clear <?php echo $current_id ? 'hidden' : ''; ?>" onclick="confirmClear()">Clear Editor</button>

                    <span id="admin-actions" class="<?php echo $current_id ? '' : 'hidden'; ?>">
                        <input type="submit" name="delete_post" value="Delete Post" class="btn-del" onclick="return confirm('Permanently delete this post?');">
                        | <a href="write.php">Cancel Edit</a> 
                    </span>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">
                <b>Markdown Cheat sheet</b><br>
                <code>![img text](URL)</code>
            </fieldset>
        </form>
    <?php endif; ?>

    <script>
    const textarea = document.getElementById('blog_content');
    const status = document.getElementById('autosave-status');
    const postIdInput = document.getElementById('post_id');
    const postSelector = document.getElementById('post_selector');
    const editorTitle = document.getElementById('editor-title');
    const adminActions = document.getElementById('admin-actions');
    const clearBtn = document.getElementById('clear-btn');

    let saveTimeout = null;

    // 1. Load Auto-Saved Draft
    window.addEventListener('load', () => {
        const currentId = postIdInput?.value || 'new';
        const savedData = localStorage.getItem('draft_' + currentId);
        
        if (savedData && (!textarea.value || textarea.value.trim() === "")) {
            textarea.value = savedData;
            status.innerText = 'Restored unsaved draft.';
            setTimeout(() => { status.innerText = ''; }, 3000);
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'Saved Successfully') {
            const savedId = urlParams.get('saved_id') || 'new';
            localStorage.removeItem('draft_' + savedId);
            localStorage.removeItem('draft_new'); 
            status.innerText = 'Save confirmed.';
            setTimeout(() => { status.innerText = ''; }, 3000);
        }
    });

    // 2. Optimized Auto-Save Logic (Debounced)
    textarea?.addEventListener('input', () => {
        // Clear the existing timer so the message doesn't flicker while typing
        clearTimeout(saveTimeout);

        // Save data immediately in background
        const currentId = postIdInput?.value || 'new';
        localStorage.setItem('draft_' + currentId, textarea.value);

        // Show "saved" message only after user stops typing for 1000ms
        saveTimeout = setTimeout(() => {
            status.innerText = 'Draft saved locally';
            // Hide the message again after 2 seconds
            setTimeout(() => { 
                if(status.innerText === 'Draft saved locally') status.innerText = ''; 
            }, 2000);
        }, 1000);
    });

    function confirmClear() {
        if (confirm("Clear editor? This will also delete your local auto-save draft.")) {
            const currentId = postIdInput?.value || 'new';
            localStorage.removeItem('draft_' + currentId);
            textarea.value = "";
            if (postSelector) postSelector.selectedIndex = 0;
            if (postIdInput) postIdInput.value = "";
            if (editorTitle) editorTitle.innerText = "Create New Post";
            if (adminActions) adminActions.classList.add('hidden');
            if (clearBtn) clearBtn.classList.remove('hidden');
            status.innerText = 'Editor reset.';
            setTimeout(() => { status.innerText = ''; }, 2000);
        }
    }

    // 3. AI Copy Tool
    document.getElementById('ai-copy-btn')?.addEventListener('click', function() {
        const content = textarea.value.trim();
        if (!content) { alert("Please write something first!"); return; }
        const fullPrompt = "Please rewrite the following blog post to improve the flow, grammar, and professional tone:\n\n" + content;
        navigator.clipboard.writeText(fullPrompt).then(() => {
            document.getElementById('copy-status').style.display = 'inline';
            setTimeout(() => { document.getElementById('copy-status').style.display = 'none'; }, 2000);
            window.open('https://gemini.google.com/', '_blank');
        });
    });
    </script>
</body>
</html>