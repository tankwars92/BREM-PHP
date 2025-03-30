<?php
session_start();

if (!isset($_SESSION['last_reset']) || time() - $_SESSION['last_reset'] > 180) {
    $_SESSION['console_output'] = [];
    $_SESSION['last_reset'] = time();
}

if (isset($_SESSION['registers'])) {
    $string = implode(', ', $_SESSION['registers']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save') {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="registers.txt"');
            echo implode(", ", $_SESSION['registers']);
            exit();
        } elseif ($_POST['action'] === 'reset') {
            $_SESSION['registers'] = array_fill(0, 162, 0);
        }
    }
}


date_default_timezone_set('UTC');

class Peripheral {
    public $identifier;
    public $text_scale;
    public $cursor_pos;
    public $size;
    public $buffer;
    public $mount_path;

    public function __construct($identifier) {
        $this->identifier = $identifier;
        $this->text_scale = 1.0;
        $this->cursor_pos = array(1, 1);
        if (strpos($identifier, "blue") !== false) {
            $this->size = array(51, 19);
        } elseif (strpos($identifier, "right") !== false) {
            $this->size = array(51, 19);
            $this->buffer = array();
            for ($i = 0; $i < 19; $i++) {
                $this->buffer[] = str_repeat(" ", 51);
            }
        } else {
            $this->size = array(51, 19);
        }
        if (strpos($identifier, "white") !== false) {
            $this->mount_path = getcwd() . DIRECTORY_SEPARATOR . "disk_white";
        } elseif (strpos($identifier, "green") !== false) {
            $this->mount_path = getcwd() . DIRECTORY_SEPARATOR . "disk_green";
        } elseif (strpos($identifier, "red") !== false) {
            $this->mount_path = getcwd() . DIRECTORY_SEPARATOR . "disk_red";
        } else {
            $this->mount_path = null;
        }
    }

    public function clear() {
        // pass
    }

    public function setTextScale($scale) {
        $this->text_scale = $scale;
    }

    public function getSize() {
        return $this->size;
    }

    public function setCursorPos($x, $y) {
        $this->cursor_pos = array($x, $y);
    }

    public function write($string) {
        // pass
    }

    public function scroll($n) {
        if (property_exists($this, "buffer")) {
            for ($j = 0; $j < $n; $j++) {
                array_shift($this->buffer);
                $this->buffer[] = str_repeat(" ", $this->size[0]);
            }
        }
    }

    public function getMountPath() {
        return $this->mount_path;
    }
}

class peripheral_ {
    public static function wrap($identifier) {
        return new Peripheral($identifier);
    }
}

class FSFile {
    public $file_obj;
    public $mode;

    public function __construct($file_obj, $mode) {
        $this->file_obj = $file_obj;
        $this->mode = $mode;
    }

    public function writeLine($line) {
        fwrite($this->file_obj, strval($line) . "\n");
    }

    public function readLine() {
        if (feof($this->file_obj)) {
            return null;
        }
        $line = fgets($this->file_obj);
        if ($line === "") {
            return null;
        }
        return rtrim($line, "\n");
    }

    public function close() {
        fclose($this->file_obj);
    }
}

function fs_open($filename, $mode) {
    try {
        $f = @fopen($filename, $mode);
        if ($f === false) {
            throw new Exception("Cannot open file");
        }
        return new FSFile($f, $mode);
    } catch (Exception $e) {
        return null;
    }
}

class fs {
    public static function open($filename, $mode) {
        return fs_open($filename, $mode);
    }
}

if (!isset($_SESSION['initialized'])) {
    $_SESSION['monitor'] = peripheral_::wrap("back:blue");
    $_SESSION['monitor']->clear();
    $_SESSION['monitor']->setTextScale(0.5);
    list($w, $monitorH) = $_SESSION['monitor']->getSize();
    $_SESSION['monitorH'] = $monitorH;
    $_SESSION['monitorY'] = 1;
    $_SESSION['count'] = 162;
    $_SESSION['i_graph'] = 1;
    $_SESSION['enableGraphMonitor'] = true;
    $count = $_SESSION['count'];
    $_SESSION['registers'] = array();
    $_SESSION['registers'][0] = null;
    for ($i = 1; $i <= $count; $i++) {
        $_SESSION['registers'][$i] = 0;
    }
    $_SESSION['diskSides'] = array("back:white", "back:green", "back:red");
    $_SESSION['mountPaths'] = array();
    $diskSides = $_SESSION['diskSides'];
    for ($i = 1; $i <= count($diskSides); $i++) {
        $_SESSION['mountPaths'][$i] = peripheral_::wrap($diskSides[$i - 1])->getMountPath();
    }
    $_SESSION['console_output'] = array();
    $_SESSION['initialized'] = true;
}

$monitor = &$_SESSION['monitor'];
$monitorY = &$_SESSION['monitorY'];
$monitorH = &$_SESSION['monitorH'];
$count = &$_SESSION['count'];
$i_graph = &$_SESSION['i_graph'];
$enableGraphMonitor = &$_SESSION['enableGraphMonitor'];
$registers = &$_SESSION['registers'];
$diskSides = &$_SESSION['diskSides'];
$mountPaths = &$_SESSION['mountPaths'];
$console_output = &$_SESSION['console_output'];

function outputLine($line) {
    global $console_output;
    $console_output[] = $line;
    //echo $line . "<br>";
}

function split_string($s, $delimiter) {
    $result = array();
    $temp = "";
    $i = 0;
    $s_len = strlen($s);
    $delim_len = strlen($delimiter);
    while ($i < $s_len) {
        if (substr($s, $i, $delim_len) === $delimiter) {
            $result[] = $temp;
            $temp = "";
            $i += $delim_len;
        } else {
            $temp .= $s[$i];
            $i++;
        }
    }
    if ($temp !== "") {
        $result[] = $temp;
    }
    return $result;
}

function writeRegistersToFiles() {
    global $mountPaths, $registers, $count;
    $files = array(
        array($mountPaths[1] . "/reg.txt", 1, 54),
        array($mountPaths[2] . "/reg.txt", 55, 108),
        array($mountPaths[3] . "/reg.txt", 109, 162)
    );
    
    foreach ($files as $fileInfo) {
        list($filename, $startIdx, $endIdx) = $fileInfo;
        $file = fs::open($filename, "w");
        if ($file) {
            for ($i = $startIdx; $i <= $endIdx; $i++) {
                $file->writeLine($registers[$i]);
            }
            $file->close();
        } else {
            outputLine("Error writing to " . $filename);
        }
    }
}

function drawGraph() {
    global $i_graph, $registers, $monitor;
    $graphMonitor = peripheral_::wrap("right");
    if (!$graphMonitor) {
        outputLine("Graph monitor not found");
        return;
    }

    $graphMonitor->setTextScale(0.5);
    list($_, $h) = $graphMonitor->getSize();

    $count_active = 0;
    for ($idx = 1; $idx < count($registers); $idx++) {
        if ($registers[$idx] > 0) {
            $count_active++;
        }
    }

    if ($i_graph == $h) {
        $graphMonitor->scroll(1);
        $i_graph = $i_graph - 1;
    }

    $graphMonitor->setCursorPos(1, $i_graph);
    $scaled_value = floor($count_active / $_);
    $line = str_repeat(" ", $scaled_value) . "|";
    $graphMonitor->write($line);

    $i_graph = $i_graph + 1;
}

function writeToMonitor($string) {
    global $monitor, $monitorY, $monitorH;
    if ($monitorY <= $monitorH) {
        $monitor->setCursorPos(1, $monitorY);
        $monitor->write($string);
        $monitorY = $monitorY + 1;
    } else {
        $monitorY = 1;
        $monitor->clear();
        $monitor->setCursorPos(1, $monitorY);
        $monitor->write($string);
        $monitorY = $monitorY + 1;
    }
}

function executeCommand($command) {
    global $registers, $count, $enableGraphMonitor;
    $cmd = split_string($command, " ");
    if (count($cmd) == 0) {
        return null;
    }
    $op = $cmd[0];

    if ($op == "ADD") {
        $reg1 = null;
        $reg2 = null;
        $reg3 = null;
        if (count($cmd) >= 4) {
            try {
                $reg1 = intval($cmd[1]);
                $reg2 = intval($cmd[2]);
                $reg3 = intval($cmd[3]);
            } catch (Exception $e) {
                $reg1 = null;
                $reg2 = null;
                $reg3 = null;
            }
            if ($reg1 !== null && $reg2 !== null && $reg3 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count && $reg3 >= 1 && $reg3 <= $count) {
                $registers[$reg1] = $registers[$reg2] + $registers[$reg3];
                outputLine("ADD: " . strval($reg1) . " = " . strval($registers[$reg1]));
                writeToMonitor("ADD: " . strval($reg1) . " = " . strval($registers[$reg1]));
            } elseif (count($cmd) >= 3) {
                try {
                    $reg1 = intval($cmd[1]);
                    $reg2 = intval($cmd[2]);
                } catch (Exception $e) {
                    $reg1 = null;
                    $reg2 = null;
                }
                if ($reg1 !== null && $reg2 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count) {
                    $registers[$reg1] = $registers[$reg1] + $registers[$reg2];
                    outputLine("ADD: " . strval($reg1) . " = " . strval($registers[$reg1]));
                    writeToMonitor("ADD: " . strval($reg1) . " = " . strval($registers[$reg1]));
                } else {
                    outputLine("Error: Invalid register numbers in ADD");
                    writeToMonitor("Error: Invalid register numbers in ADD");
                }
            } else {
                outputLine("Error: Invalid register numbers in ADD");
                writeToMonitor("Error: Invalid register numbers in ADD");
            }
        }
    } elseif ($op == "SUB") {
        $reg1 = null;
        $reg2 = null;
        $reg3 = null;
        if (count($cmd) >= 4) {
            try {
                $reg1 = intval($cmd[1]);
                $reg2 = intval($cmd[2]);
                $reg3 = intval($cmd[3]);
            } catch (Exception $e) {
                $reg1 = null;
                $reg2 = null;
                $reg3 = null;
            }
            if ($reg1 !== null && $reg2 !== null && $reg3 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count && $reg3 >= 1 && $reg3 <= $count) {
                $registers[$reg1] = $registers[$reg2] - $registers[$reg3];
                outputLine("SUB: " . strval($reg1) . " = " . strval($registers[$reg1]));
                writeToMonitor("SUB: " . strval($reg1) . " = " . strval($registers[$reg1]));
            } elseif (count($cmd) >= 3) {
                try {
                    $reg1 = intval($cmd[1]);
                    $reg2 = intval($cmd[2]);
                } catch (Exception $e) {
                    $reg1 = null;
                    $reg2 = null;
                }
                if ($reg1 !== null && $reg2 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count) {
                    $registers[$reg1] = $registers[$reg1] - $registers[$reg2];
                    outputLine("SUB: " . strval($reg1) . " = " . strval($registers[$reg1]));
                    writeToMonitor("SUB: " . strval($reg1) . " = " . strval($registers[$reg1]));
                } else {
                    outputLine("Error: Invalid register numbers in SUB");
                    writeToMonitor("Error: Invalid register numbers in SUB");
                }
            } else {
                outputLine("Error: Invalid register numbers in SUB");
                writeToMonitor("Error: Invalid register numbers in SUB");
            }
        }
    } elseif ($op == "MUL") {
        $reg1 = null;
        $reg2 = null;
        $reg3 = null;
        if (count($cmd) >= 4) {
            try {
                $reg1 = intval($cmd[1]);
                $reg2 = intval($cmd[2]);
                $reg3 = intval($cmd[3]);
            } catch (Exception $e) {
                $reg1 = null;
                $reg2 = null;
                $reg3 = null;
            }
            if ($reg1 !== null && $reg2 !== null && $reg3 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count && $reg3 >= 1 && $reg3 <= $count) {
                $registers[$reg1] = $registers[$reg2] * $registers[$reg3];
                outputLine("MUL: " . strval($reg1) . " = " . strval($registers[$reg1]));
                writeToMonitor("MUL: " . strval($reg1) . " = " . strval($registers[$reg1]));
            } elseif (count($cmd) >= 3) {
                try {
                    $reg1 = intval($cmd[1]);
                    $reg2_val = intval($cmd[2]);
                } catch (Exception $e) {
                    $reg1 = null;
                    $reg2_val = null;
                }
                if ($reg1 !== null && $reg2_val !== null && $reg1 >= 1 && $reg1 <= $count) {
                    $registers[$reg1] = $registers[$reg1] * $reg2_val;
                    outputLine("MUL: " . strval($reg1) . " = " . strval($registers[$reg1]));
                    writeToMonitor("MUL: " . strval($reg1) . " = " . strval($registers[$reg1]));
                } else {
                    outputLine("Error: Invalid register numbers in MUL");
                    writeToMonitor("Error: Invalid register numbers in MUL");
                }
            } else {
                outputLine("Error: Invalid register numbers in MUL");
                writeToMonitor("Error: Invalid register numbers in MUL");
            }
        }
    } elseif ($op == "DIV") {
        $reg1 = null;
        $reg2 = null;
        $reg3 = null;
        if (count($cmd) >= 4) {
            try {
                $reg1 = intval($cmd[1]);
                $reg2 = intval($cmd[2]);
                $reg3 = intval($cmd[3]);
            } catch (Exception $e) {
                $reg1 = null;
                $reg2 = null;
                $reg3 = null;
            }
            if ($reg1 !== null && $reg2 !== null && $reg3 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count && $reg3 >= 1 && $reg3 <= $count) {
                $registers[$reg1] = $registers[$reg2] / $registers[$reg3];
                outputLine("DIV: " . strval($reg1) . " = " . strval($registers[$reg1]));
                writeToMonitor("DIV: " . strval($reg1) . " = " . strval($registers[$reg1]));
            } elseif (count($cmd) >= 3) {
                try {
                    $reg1 = intval($cmd[1]);
                    $reg2 = intval($cmd[2]);
                } catch (Exception $e) {
                    $reg1 = null;
                    $reg2 = null;
                }
                if ($reg1 !== null && $reg2 !== null && $reg1 >= 1 && $reg1 <= $count && $reg2 >= 1 && $reg2 <= $count) {
                    $registers[$reg1] = $registers[$reg1] / $registers[$reg2];
                    outputLine("DIV: " . strval($reg1) . " = " . strval($registers[$reg1]));
                    writeToMonitor("DIV: " . strval($reg1) . " = " . strval($registers[$reg1]));
                } else {
                    outputLine("Error: Invalid register numbers in DIV");
                    writeToMonitor("Error: Invalid register numbers in DIV");
                }
            } else {
                outputLine("Error: Invalid register numbers in DIV");
                writeToMonitor("Error: Invalid register numbers in DIV");
            }
        }
    } elseif ($op == "LOAD") {
        $reg1 = (count($cmd) >= 2) ? $cmd[1] : null;
        $value = (count($cmd) >= 3) ? $cmd[2] : null;
        
        $getValue = function($arg) use (&$registers, $count) {
            if (is_string($arg) && strlen($arg) > 0 && $arg[0] == "*") {
                try {
                    $pointerReg = intval(substr($arg, 1));
                } catch (Exception $e) {
                    $pointerReg = null;
                }
                if ($pointerReg !== null && $pointerReg >= 1 && $pointerReg <= $count) {
                    $targetReg = $registers[$pointerReg];
                    if ($targetReg !== null && $targetReg >= 1 && $targetReg <= $count) {
                        return $registers[$targetReg];
                    } else {
                        outputLine("Error: Invalid target register number in LOAD *");
                        writeToMonitor("Error: Invalid target register number in LOAD *");
                        return null;
                    }
                } else {
                    outputLine("Error: Invalid pointer register number in LOAD *");
                    writeToMonitor("Error: Invalid pointer register number in LOAD *");
                    return null;
                }
            } else {
                try {
                    return intval($arg);
                } catch (Exception $e) {
                    try {
                        return floatval($arg);
                    } catch (Exception $ex) {
                        return null;
                    }
                }
            }
        };

        $loadValue = $getValue($value);
        if ($loadValue === null) {
            return;
        }
        if (is_string($reg1) && strlen($reg1) > 0 && $reg1[0] == "*") {
            try {
                $pointerReg = intval(substr($reg1, 1));
            } catch (Exception $e) {
                $pointerReg = null;
            }
            if ($pointerReg !== null && $pointerReg >= 1 && $pointerReg <= $count) {
                $targetReg = $registers[$pointerReg];
                if ($targetReg !== null && $targetReg >= 1 && $targetReg <= $count) {
                    $registers[$targetReg] = $loadValue;
                    outputLine("LOAD: Register " . strval($targetReg) . " = " . strval($registers[$targetReg]));
                    writeToMonitor("LOAD: Register " . strval($targetReg) . " = " . strval($registers[$targetReg]));
                } else {
                    outputLine("Error: Invalid target register number in LOAD *");
                    writeToMonitor("Error: Invalid target register number in LOAD *");
                }
            } else {
                outputLine("Error: Invalid pointer register number in LOAD *");
                writeToMonitor("Error: Invalid pointer register number in LOAD *");
            }
        } else {
            try {
                $reg1_num = intval($reg1);
            } catch (Exception $e) {
                $reg1_num = null;
            }
            if ($reg1_num !== null && $reg1_num >= 1 && $reg1_num <= $count) {
                $registers[$reg1_num] = $loadValue;
                outputLine("LOAD: " . strval($reg1_num) . " = " . strval($registers[$reg1_num]));
                writeToMonitor("LOAD: " . strval($reg1_num) . " = " . strval($registers[$reg1_num]));
            } else {
                outputLine("Error: Invalid register number in LOAD");
                writeToMonitor("Error: Invalid register number in LOAD");
            }
        }
    } elseif ($op == "RAND") {
        $reg1 = (count($cmd) >= 2) ? $cmd[1] : null;
        if (is_string($reg1) && strlen($reg1) > 0 && $reg1[0] == "*") {
            try {
                $reg1_val = intval(substr($reg1, 1));
                $reg1 = $registers[$reg1_val];
            } catch (Exception $e) {
                $reg1 = null;
            }
        } else {
            try {
                $reg1 = intval($reg1);
            } catch (Exception $e) {
                $reg1 = null;
            }
        }

        if ($reg1 !== null) {
            if (count($cmd) >= 4) {
                try {
                    $low = intval($cmd[2]);
                    $high = intval($cmd[3]);
                    $registers[$reg1] = rand($low, $high);
                } catch (Exception $e) {
                    $registers[$reg1] = rand(0, 1);
                }
            } else {
                $registers[$reg1] = rand(0, 1);
            }
            outputLine("RAND: " . strval($reg1) . " = " . strval($registers[$reg1]));
            writeToMonitor("RAND: " . strval($reg1) . " = " . strval($registers[$reg1]));
        } else {
            outputLine("Error: Invalid register number in RAND");
            writeToMonitor("Error: Invalid register number in RAND");
        }
    } elseif ($op == "STORE") {
        try {
            $reg1 = intval($cmd[1]);
            $reg2 = intval($cmd[2]);
        } catch (Exception $e) {
            $reg1 = null;
            $reg2 = null;
        }
        if ($reg1 !== null && $reg2 !== null) {
            $registers[$reg1] = $registers[$reg2];
            outputLine("STORE: " . strval($reg1) . " = " . strval($registers[$reg1]));
            writeToMonitor("STORE: " . strval($reg1) . " = " . strval($registers[$reg1]));
        } else {
            outputLine("Error: Invalid register numbers in STORE");
            writeToMonitor("Error: Invalid register numbers in STORE");
        }
    } elseif ($op == "SLEEP") {
        try {
            $duration = floatval($cmd[1]) / 1000.0;
        } catch (Exception $e) {
            $duration = 0;
        }
        usleep($duration * 1000000);
        outputLine("SLEEP: " . strval($duration));
        writeToMonitor("SLEEP: " . strval($duration));
    } elseif ($op == "JUMP") {
        try {
            $target = intval($cmd[1]);
        } catch (Exception $e) {
            $target = null;
        }
        outputLine("JUMP: " . strval($target));
        writeToMonitor("JUMP: " . strval($target));
        return $target;
    } elseif ($op == "NOP") {
        outputLine("NOP: No Operation");
        writeToMonitor("NOP: No Operation");
    } elseif (substr($command, 0, 1) == ";") {
        outputLine(";: Comment Load");
        writeToMonitor(";: Comment Load");
    }
    writeRegistersToFiles();
    if ($enableGraphMonitor) {
        drawGraph();
    }
    return null;
}

function loadProgram($filename) {
    $file = fs::open($filename, "r");
    if (!$file) {
        echo "Error opening file: " . $filename . "<br><br>";
        return null;
    }
    $program = array();
    while (true) {
        $line = $file->readLine();
        if ($line === null) {
            break;
        }
        $program[] = $line;
    }
    $file->close();
    return $program;
}

function checkCondition($reg, $op, $value) {
    global $registers;
    if ($op == ">") {
        return $registers[$reg] > $value;
    }
    if ($op == "<") {
        return $registers[$reg] < $value;
    }
    if ($op == "==") {
        return $registers[$reg] == $value;
    }
    if ($op == "!=") {
        return $registers[$reg] != $value;
    }
    if ($op == ">=") {
        return $registers[$reg] >= $value;
    }
    if ($op == "<=") {
        return $registers[$reg] <= $value;
    }
    if ($op == "~=") {
        return $registers[$reg] != $value;
    }
    return false;
}

function runProgram($program) {
    global $registers;
    $i_prog = 1;
    $loopStart = null;
    $loopReg1 = null;
    $loopReg2 = null;
    $loopOp = null;
    $loopValue = null;
    $loopType = null;

    while ($i_prog <= count($program)) {
        $command = $program[$i_prog - 1];
        $cmd = split_string($command, " ");
        $op = (count($cmd) > 0) ? $cmd[0] : "";

        if ($op == "LOOP") {
            try {
                $loopReg1 = intval($cmd[1]);
                $loopOp = $cmd[2];
                $loopValue = intval($cmd[3]);
                $loopType = "VALUE";
                $loopStart = $i_prog;
            } catch (Exception $e) {
                // pass
            }
        } elseif ($op == "LOOPI") {
            try {
                $loopReg1 = intval($cmd[1]);
                $loopOp = $cmd[2];
                $loopReg2 = intval($cmd[3]);
                $loopType = "REGISTER";
                $loopStart = $i_prog;
            } catch (Exception $e) {
                // pass
            }
        } elseif ($op == "END") {
            $conditionMet = false;
            if ($loopType == "VALUE") {
                $conditionMet = checkCondition($loopReg1, $loopOp, $loopValue);
            } elseif ($loopType == "REGISTER") {
                $reg1Value = $loopReg1;
                $reg2Value = $registers[$loopReg2];
                $conditionMet = checkCondition($reg1Value, $loopOp, $reg2Value);
            }
            if ($conditionMet) {
                $i_prog = $loopStart;
            } else {
                $loopStart = null;
            }
        } else {
            $jumpTarget = executeCommand($command);
            if ($jumpTarget) {
                $i_prog = $jumpTarget - 1;
            }
        }
        $i_prog = $i_prog + 1;
    }
}

function checkRegisters() {
    global $registers;
    while (true) {
        $count_active = 0;
        for ($i = 1; $i < count($registers); $i++) {
            if ($registers[$i] >= 1) {
                $count_active++;
            }
        }
        usleep(0);
        break;
    }
}

if (isset($_GET['filename'])) {
    $filename = $_GET['filename'];
    $program = loadProgram($filename);
    if ($program) {
        runProgram($program);
    }
}

if (isset($_POST['command'])) {
    $command_input = $_POST['command'];
    executeCommand($command_input);
    writeRegistersToFiles();
    usleep(0);
    $_SESSION['monitor'] = $monitor;
    $_SESSION['monitorY'] = $monitorY;
    $_SESSION['monitorH'] = $monitorH;
    $_SESSION['count'] = $count;
    $_SESSION['i_graph'] = $i_graph;
    $_SESSION['enableGraphMonitor'] = $enableGraphMonitor;
    $_SESSION['registers'] = $registers;
    $_SESSION['diskSides'] = $diskSides;
    $_SESSION['mountPaths'] = $mountPaths;
    $_SESSION['console_output'] = $console_output;
}

$registers = $_SESSION['registers'];
$maxHeight = 20;

$activeCount = array_sum(array_map(fn($v) => $v > 0 ? 1 : 0, $registers));
$graphHeight = round(($activeCount / count($registers)) * $maxHeight);

if (!isset($_SESSION['graph_data'])) {
    $_SESSION['graph_data'] = array_fill(0, 50, $graphHeight);
}

array_shift($_SESSION['graph_data']);
$_SESSION['graph_data'][] = $graphHeight;

function renderHtmlGraph($data, $height) {
    $output = '<table class="graph"><tbody>';
    for ($y = $height; $y >= 0; $y--) {
        $output .= '<tr>';
        foreach ($data as $value) {
            $output .= '<td' . ($value == $y ? ' class="filled"' : '') . '></td>';
        }
        $output .= '</tr>';
    }
    $output .= '</tbody></table>';
    return $output;
}

?>
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
      .graph { border-collapse: collapse; border: 2px solid black; }
      .graph td { width: 6px; height: 6px; border: 1px solid black; padding: 0; }
      .graph .filled { background-color: black; }
   </style>
</head>
<body background="GRAY-WEV.JPG">
<center>
| <a href="index.php">Simulator</a> | <a href="programms.php">Programms List</a> | <a href="about.php">About</a> | 
<br>
<hr width="80%">
</center>
  <b><i>Command Input</i></b>
  <form method="post">
    <label for="command">Enter Command:</label>
    <input type="text" id="command" name="command" autofocus>
    <input type="submit" value="Submit">
  </form>
  <br>
  <b><i>Console Output</i></b>
  <div style="background-color:#FFFFFF; padding:10px; border:1px solid #000000; height:300px; overflow:auto;">
    <pre>
<?php
if (!empty($console_output)) {
    foreach ($console_output as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
}
?>
    </pre>
  </div>
  <br>
  <b><i>Load Program File</i></b>
  <form method="get">
    <label for="filename">Filename:</label>
    <input type="text" id="filename" name="filename">
    <input type="submit" value="Load and Run Program">
  </form>
  <br>
  <b><i>Memory Operation</i></b>
  <form method="post" action="index.php">
        <input type='hidden' name='action' id='action' value=''>
        <button type='submit' onclick="document.getElementById('action').value='save'">Save DZU</button>
        <button type='submit' onclick="document.getElementById('action').value='reset'">Reset DZU</button>
        <button onclick="window.open('visualer.php', 'Visualer', 'width=500,height=150,resizable=yes,scrollbars=yes; return false;')">Visualer</button>
        <button onclick="window.open('disassembler.php', 'Disassembler', 'width=470,height=665,resizable=yes,scrollbars=yes; return false;')">Disassembler</button>
  </form>
  <br>
  <b><i>Registers Graph</i></b>
  <?php echo renderHtmlGraph($_SESSION['graph_data'], $maxHeight); ?>
</body>
</html>