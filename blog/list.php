<html>

<body>
<center><h1><center>Ken Elliott's Micro Blog</h1>

<?php
// 1. Define the directory you want to list
// Ensure this folder exists in the same place as your script
$directory = 'posts/'; 

// Check if the directory actually exists to prevent errors
if (is_dir($directory)) {
    
    // 2. Scan the directory and get all file names
    // scandir() returns an array of files
    $files = scandir($directory);

    // 3. Remove the hidden '.' and '..' folders from the list
    $files = array_diff($files, array('.', '..'));

    // 5. Loop through each file and create a link
    foreach ($files as $file) {
        // Create the URL-safe link path
        $link = $directory . rawurlencode($file);
        
        // Sanitize the filename for display (prevents code injection)
        $displayName = htmlspecialchars($file);
		
		$clean_name = pathinfo($link, PATHINFO_FILENAME);
		
		if ($clean_name === "s"){
			continue;
		}
		

        echo "<a href='post.php?slug=$clean_name'>$clean_name</a><br><br>";
    }

} else {
    echo "Directory not found.";
}
?>
</center></body></html>