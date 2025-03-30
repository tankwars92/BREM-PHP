<!DOCTYPE html>
<html>
<head>
    <title>BREM Registers</title>
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 2000);
    </script>
    <style type="text/css"> <!--
    table.withborder {
      border-width: 1px;
      border-style: solid;
      border-color: black;
      border-collapse: collapse;
    }
    td.withborder {
      border-width: 1px;
      border-style: solid;
      border-color: black;
      border-collapse: collapse;
    }
    a:link {
      text-decoration: none;
      color: #2018A0;
    }
    a:visited {
      text-decoration: none;
      color: #2018A0;
    }
    a:active {
      text-decoration: none;
      color: #2018A0;
    }
    a:hover {
      text-decoration: none;
      color: #4030FF;
    }
    sub {
      font-size: 6pt;
    }
    sup {
      font-size: 6pt;
    }
    hr {
      height: 1px;
      color: black;
    }
    --></style>
</head>

<body bgcolor="#dfdfdf">
<?php
session_start();

$registers = $_SESSION['registers'];

function getColorByRegisterValue($value) {
  if (!isset($value)) return "#bfbfbf";
  
  $colors = [
      "#CCCCE1",
      "#FF99CC",
      "#CCFFCC", 
      "#CCCCFF", 
      "#FFFF99", 
      "#FF99CC",
      "#006D00",
      "#CCCCFF",
      "#FFCC99",
      "#FFCCCC",
      "#CCFFCC",
      "#CCCCFF",
      "#FFCC99",
      "#FF99CC", 
      "#CCFFCC",
      "#CCCCFF"
  ];
  
  $value = intval($value) % count($colors);
  return $colors[$value];
}
echo '<table id="opcodes" cellspacing="0" cellpadding="0" class="withborder" width="460">';

echo '<tr style="font-family: monospace; font-size: 6pt" align="center" bgcolor="#9f9f9f">';
echo '<td class="withborder"></td>';
for ($i = 0; $i <= 0xF; $i++) {
    echo sprintf('<td class="withborder"><b>x%X</b></td>', $i);
}
echo '</tr>';

$register_index = 0;
for ($row = 0; $row <= 0xF; $row++) {
    echo '<tr style="font-family: monospace; font-size: 6pt" align="center">';
    echo sprintf('<td class="withborder" bgcolor="#9f9f9f"><b>%Xx</b></td>', $row);
    
    for ($col = 0; $col <= 0xF; $col++) {
        $opcode = ($row << 4) | $col;
        $bgcolor = isset($_SESSION['registers'][$register_index]) 
            ? getColorByRegisterValue($_SESSION['registers'][$register_index]) 
            : "#bfbfbf";
        
        echo sprintf('<td class="withborder" bgcolor="%s">%02X</td>', 
            $bgcolor, 
            $opcode
        );
        
        $register_index++;
        if ($register_index >= 162) break;
    }
    echo '</tr>';
    
    if ($register_index >= 162) break;
}

echo '</table>';
?>
</body>
</html>