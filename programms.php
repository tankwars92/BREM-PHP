<?php
$baseDir = 'programms';

if (!is_dir($baseDir)) {
    die("Папка 'programms' не найдена!");
}

$items = scandir($baseDir);

$files = [];
$dirs = [];
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = "$baseDir/$item";
    if (is_dir($path)) {
        $dirs[$item] = scandir($path);
    } else {
        $files[] = $item;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BREM Programms List</title>
    <style type="text/css">
      a { color: blue; text-decoration: none; }
      a:hover { color: blue; text-decoration: underline; }
      a:visited { color: blue; }
      a:active { color: blue; }
   </style>
</head>
<body background="GRAY-WEV.JPG">
<center>
| <a href="index.php">Simulator</a> | <a href="programms.php">Programms List</a> | <a href="about.php">About</a> | 
<br>
<hr width="80%">
</center>
    <b><i>Programms List:</i></b>
    <ul>
        <?php foreach ($files as $file): ?>
            <li><a href="index.php?filename=<?php echo urlencode($file); ?>"><?php echo htmlspecialchars($file); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php foreach ($dirs as $dir => $subItems): ?>
        <i><?php echo htmlspecialchars($dir); ?></i>
        <ul>
            <?php foreach ($subItems as $subItem): ?>
                <?php if ($subItem === '.' || $subItem === '..') continue; ?>
                <li><a href="index.php?filename=programms/<?php echo urlencode("$dir/$subItem"); ?>"><?php echo htmlspecialchars($subItem); ?></a> (<a href="programms/<?php echo "$dir/$subItem"; ?>">View</a>)</li>
            <?php endforeach; ?>
        </ul>
        <hr>
    <?php endforeach; ?>
    As we already mentioned in the "<a href='about.php'>About</a>" section, our simulator does not support an infinite loop system, like, for example, my desktop simulator in Python, and therefore, you will not be able to run some programs (they are excluded from the list above).
</body>
</html>
