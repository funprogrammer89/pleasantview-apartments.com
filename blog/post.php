<?php

// 1. Include the Parsedown library
// Change the path if your Parsedown.php file is in a different location
require 'Parsedown.php';

// 2. Get the requested file name (slug) from the URL
$post_slug = $_GET['slug'] ?? 'default-post'; // Use a default if 'slug' is missing
$file_path = 'posts/' . $post_slug . '.md';

// 3. Basic error checking: Ensure the file exists
if (!file_exists($file_path)) {
    // Handle the "404 Not Found" case
    http_response_code(404);
    echo "<h1>Error 404: Post Not Found</h1>";
    exit;
}

// 4. Read the content of the .md file
$markdown_content = file_get_contents($file_path);

// 5. Initialize Parsedown and convert the content
$Parsedown = new Parsedown();
$html_content = $Parsedown->text($markdown_content);

// 6. Display the page layout and the converted content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ken Elliott's Blog post - <?= basename($file_path, '.md') ?></title>
    </head>
<body>
    <main>
        <?php echo $html_content; ?>
    </main>
</body>
</html>