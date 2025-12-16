<html>

<body>
<center><h1><center>Ken Elliott's Micro Blog</h1>
----------------------------------------------------<br><br><br>
<table width="75%" align='center' style="border-left: 1px solid #cdd0d4;border-right: 1px solid #cdd0d4;padding-left: 30px;padding-right: 30px;"><tr><td>
<?php

// Set the directory path
$dir = getcwd();
$dir2 = "/blogs/";
$dir3 = $dir.$dir2;
$colors = array("blue","purple","green","red","yellow","orange");
$colorCount = 0;
// Set the directory path
$dir = 'path/to/directory';

// Check if the directory exists and is accessible
if (!is_dir($dir3)) {
    die("The directory $dir does not exist or is not accessible.");
}

// Get all the text files in the directory
$files = glob("$dir3/*.txt");

// Sort the files by newest date
//usort($files, function($a, $b) {
  //  return filemtime($b) - filemtime($a);
//});


// Loop through all files in the directory
foreach ($files as $file) {
    // Check if the file is readable
    if (!is_readable($file)) {
        echo "The file $file is not readable.";
        continue;
    }
    // Open the text file
    $text = file_get_contents($file);
     // Display the text as HTML and add some ad hoc HTML tags and flair
    echo "</td></tr><tr><td>";
		//Set the size of your headers from your text file
	echo "<font size='6' color='$colors[$colorCount]'>";
		// remove the .txt from the display header
		$name = rtrim($file, ".txt");
		// remove the directory path from the display header
		$name = ltrim($name, "/var/www/html/blogs");
		echo $name;
		echo "</font></td><td align=left></td></tr><tr><td><br>";
		// get date and time of the text document and remove time with substring
		// echo "<script>x = document.lastModified;
		//document.write(x.substring(0,x.length-8));</script></td></tr><tr><td><br>";
        echo nl2br($text);
		// add break so there is spacing between entries
		echo "<br><br>";
		$colorCount++;
}


?>
</td></tr></table>
</body>
</html>
