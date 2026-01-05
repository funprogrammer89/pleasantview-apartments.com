<?php
session_start();

/* ---------------- ERROR REPORTING ---------------- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---------------- DATABASE ---------------- */
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

/* ---------------- PASSCODE ---------------- */
if (!file_exists('p.txt')) {
    die("Error: passcode file is missing.");
}
$stored_hash = trim(file_get_contents('p.txt'));

$draft_content = "";
$current_id = "";
$message = "";

/* ---------------- LOGIN ---------------- */
if (isset($_POST['login_passcode'])) {
    if (password_verify($_POST['login_passcode'], $stored_hash)) {
        $_SESSION['authenticated'] = true;
        session_regenerate_id(true);
    } else {
        $message = "Invalid passcode.";
    }
}

/* ---------------- LOGOUT ---------------- */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: write.php");
    exit;
}

$is_admin = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

/* ---------------- FETCH POSTS ---------------- */
$recent_posts = [];
if ($is_admin) {
    $recent_posts = $pdo->query(
        "SELECT id, content FROM posts ORDER BY id DESC LIMIT 10"
    )->fetchAll();
}

/* ---------------- DELETE ---------------- */
if ($is_admin && isset($_POST['delete_post'])) {
    $id = $_POST['post_id'] ?? '';
    if ($id) {
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
        header("Location: write.php?msg=Post+Deleted");
        exit;
    }
}

/* ---------------- LOAD ---------------- */
if ($is_admin && isset($_POST['post_to_load']) && !isset($_POST['submit_post'])) {
    $id = $_POST['post_to_load'];
    $stmt = $pdo->prepare("SELECT id, content FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $current_id = $row['id'];
        $draft_content = $row['content'];
    }
}

/* ---------------- SAVE ---------------- */
if ($is_admin && isset($_POST['submit_post'])) {
    $content = trim($_POST['blog_content'] ?? '');
    $pid = $_POST['post_id'] ?? '';

    if ($content) {
        if ($pid) {
            $pdo->prepare("UPDATE posts SET content = ? WHERE id = ?")
                ->execute([$content, $pid]);
        } else {
            $pdo->prepare("INSERT INTO posts (content) VALUES (?)")
                ->execute([$content]);
            $pid = $pdo->lastInsertId();
        }
        header("Location: write.php?msg=Saved+Successfully&saved_id=$pid");
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
body {
    font-family: sans-serif;
    max-width: 800px;
    margin: 20px auto;
    color: #333;
}
fieldset {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}
textarea, input, select {
    width: 100%;
    font-size: 16px;
    padding: 10px;
    margin-bottom: 10px;
}
.btn-save { background:#007bff;color:#fff;border:0;padding:10px 15px;border-radius:4px; }
.btn-del  { background:#dc3545;color:#fff;border:0;padding:10px 15px;border-radius:4px; }
.btn-ai   { background:#28a745;color:#fff;border:0;padding:10px 15px;border-radius:4px; }

#autosave-status {
    float: right;
    font-size: 0.8em;
    color: #666;
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.4s ease;
}
#autosave-status.visible { opacity: 1; }

.save-dot {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
}
.msg-banner {
    background:#d4edda;
    padding:10px;
    border-radius:4px;
    margin-bottom:20px;
}
</style>
</head>

<body>

<?php if ($message || isset($_GET['msg'])): ?>
<div class="msg-banner"><?= htmlspecialchars($_GET['msg'] ?? $message) ?></div>
<?php endif; ?>

<?php if (!$is_admin): ?>
<h2>Admin Login</h2>
<form method="post">
    <input type="password" name="login_passcode" required>
    <input type="submit" value="Login">
</form>

<?php else: ?>

<p align="right"><a href="?logout=1">Logout</a></p>

<form method="post">
<fieldset>

<select name="post_to_load" onchange="this.form.submit()">
<option value="">-- Load Post --</option>
<?php foreach ($recent_posts as $p): ?>
<option value="<?= $p['id'] ?>" <?= $current_id == $p['id'] ? 'selected' : '' ?>>
ID #<?= $p['id'] ?>: <?= htmlspecialchars(substr(strip_tags($p['content']),0,50)) ?>…
</option>
<?php endforeach; ?>
</select>

<span id="autosave-status"></span>
<h3><?= $current_id ? "Editing Post #$current_id" : "New Post" ?></h3>

<button type="button" class="btn-ai" id="ai-copy-btn">✨ Copy for AI</button>

<input type="hidden" name="post_id" id="post_id" value="<?= htmlspecialchars($current_id) ?>">
<textarea id="blog_content" name="blog_content" rows="15"><?= htmlspecialchars($draft_content) ?></textarea>

<input type="submit" name="submit_post" class="btn-save" value="Publish">

<?php if ($current_id): ?>
<input type="submit" name="delete_post" class="btn-del" value="Delete"
       onclick="return confirm('Delete this post?');">
<?php endif; ?>

</fieldset>
</form>
<?php endif; ?>

<script>
const textarea = document.getElementById('blog_content');
const status = document.getElementById('autosave-status');
const postId = document.getElementById('post_id');

let autosaveTimer = null;
let hideTimer = null;

function showStatus(text) {
    status.innerHTML = `<span class="save-dot"></span>${text}`;
    status.classList.add('visible');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => status.classList.remove('visible'), 2000);
}

// Restore draft
window.addEventListener('load', () => {
    const id = postId?.value || 'new';
    const saved = localStorage.getItem('draft_' + id);
    if (saved && (!textarea.value || textarea.value.trim() === '')) {
        textarea.value = saved;
        showStatus('Draft restored');
    }

    const p = new URLSearchParams(location.search);
    if (p.get('msg') === 'Saved Successfully') {
        localStorage.removeItem('draft_' + (p.get('saved_id') || 'new'));
    }
});

// Autosave (debounced UI)
textarea?.addEventListener('input', () => {
    const id = postId?.value || 'new';
    localStorage.setItem('draft_' + id, textarea.value);

    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
        showStatus('Draft saved');
    }, 600);
});

// AI copy helper
document.getElementById('ai-copy-btn')?.addEventListener('click', () => {
    if (!textarea.value.trim()) return alert('Write something first.');
    const text = "Please rewrite the following blog post to improve the flow and tone:\n\n" + textarea.value;
    navigator.clipboard.writeText(text).then(() => {
        window.open('https://gemini.google.com/', '_blank');
    });
});
</script>

</body>
</html>
