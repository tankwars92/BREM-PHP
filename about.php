<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>BREM simulator</title>
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
<b><i>About Simulator</i></b>
<br>
This simulator is based on the BREM machine processor (162 registers in size, which can hold data up to 800 bytes in size and only numbers). This simulator, compared to the real BREM machine, as well as my desktop simulator in Python:
<ul>
<li><b>No support for infinite loops:</b> due to some limitations of PHP, we cannot perform infinite loops as such, so although their support is not disabled in the interpreter itself, it will not work.</li>
<li><b>A small error in dividing between registers:</b> for some reason, maybe even a problem in the program of my interpreter itself, but there may be a small error in dividing registers, this can be seen in the example of several rather complex programs (for example, those in the war_physics folder), the results between the desktop simulator in Python and this simulator will be slightly different.</li>
</ul>
In any case, I think this simulator is quite suitable for working with programs, even if not the most complex ones (in fact, the infinite loops I mentioned above are used in only a few programs). Enjoy!
</ul>
</body>
</html>