<?php
session_start();

$register_count = 162;

$values = isset($_SESSION['registers']) ? $_SESSION['registers'] : array_fill(0, $register_count, 0);

$registers = array_map(fn($i) => "Р-" . $i, range(0, $register_count - 1));

function disassemble($registers, $values) {
    $output = "<div class=\"disassembler\"><pre>Disassembler Output:\n";
    foreach ($registers as $i => $reg) {
        $output .= sprintf("%-5s = 0x%04X (%d)\n", $reg, $values[$i], $values[$i]);
    }
    $output .= "</pre></div>";
    return $output;
}

function memory_dump($values) {
    $output = "<pre class=\"memory-dump\">\n";
    for ($i = 0; $i < count($values); $i += 8) {
        $chunk = array_slice($values, $i, 8);
        $hex = implode(" ", array_map(fn($b) => sprintf("%04X", $b), $chunk));
        // Преобразуем 16-битное число в символ, если это допустимый ASCII-символ
        $ascii = implode("", array_map(fn($b) => ($b >= 32 && $b <= 126) ? chr($b) : ".", $chunk));
        $output .= sprintf("%04X: %-39s | %-8s\n", $i, str_pad($hex, 39, " "), $ascii);
    }
    $output .= "</pre>";
    return $output;
}

echo "<html><head><style>
    body { background-color: black; color: white; font-family: monospace; }
    .disassembler { height: 300px; overflow-y: scroll; border: 1px solid gray; padding: 10px; }
    pre { margin: 0; }
    .memory-dump { white-space: pre-wrap; }
</style></head><body>";
echo disassemble($registers, $values);
echo memory_dump($values);
echo "</body></html>";
