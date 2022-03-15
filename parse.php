<?php
//----------------------------------------------------------------------------------------------------------------------
// IPP project 1 "Parser"
// author: Anton Medvedev (xmedve04)
//----------------------------------------------------------------------------------------------------------------------

const OK = 0; // navratova hodnota pri uspesnem dokonceni programu
//----------------------------------------------------------------------------------------------------------------------
// ERRORS
const ERR_SCRIPT_PARAMS = 10; // chybějící parametr skriptu (je-li třeba) nebo použití zakázané kombinace parametrů;
const ERR_OPEN_INPUT_FILE = 11; // chyba při otevírání vstupních souborů (např. neexistence, nedostatečné oprávnění);
const ERR_OPEN_OUTPUT_FILES = 12; // chyba při otevření výstupních souborů pro zápis (např. nedostatečné oprávnění,
// chyba při zápisu);
const ERR_HEADER = 21; // chybná nebo chybějící hlavička ve zdrojovém kódu zapsaném v IPPcode22;
const ERR_OPERATION_CODE = 22; // neznámý nebo chybný operační kód ve zdrojovém kódu zapsaném v IPPcode22;
const ERR_LEX_SYN = 23; // jiná lexikální nebo syntaktická chyba zdrojového kódu zapsaného v IPPcode22.
const ERR_INTERN = 99; // interní chyba (neovlivněná vstupními soubory či parametry příkazové řádky;
// např. chyba alokace paměti).
//----------------------------------------------------------------------------------------------------------------------

ini_set("display_errors", "stderr");
echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");


function validate_arguments($argc, $argv){
    if ($argc > 1) {
        if ($argc == 2 && $argv[1] == "--help") {
            echo("Usage: parse.php [options] <inputFile\n");
            exit(OK);
        } else {
            var_dump("Arguments error");
            exit(ERR_SCRIPT_PARAMS);
        }
    }
}

function remove_commentary($line) {
    if (str_contains($line, "#")) {
        $split_line_by_comment = explode("#", $line);
        $line = $split_line_by_comment[0];
        $line .= "\n";      // append "\n" at the end of line
    }
    return $line;
}

function format_line($line) {
    $line = remove_commentary($line);
    return trim($line);
}

function check_start_program($header_bool) {
    if ($header_bool == false) {
        var_dump("header .IPPcode22 does not exits in source file");
        exit(ERR_HEADER);
    }
}

function instruction_error() {
    var_dump("Instruction opcode error");
    exit(ERR_OPERATION_CODE);
}

function lexical_or_syntax_error() {
    var_dump("Lexical of Syntax error");
    exit(ERR_LEX_SYN);
}

function change_str_for_xml($str) {
    if (str_contains($str, "&")) {
        str_replace("&", "&amp;", $str);
    }
    if (str_contains($str, "<")) {
        str_replace("&", "&lt;", $str);
    }
    if (str_contains($str, ">")) {
        str_replace("&", "&gt;", $str);
    }
    return $str;
}

function instruction_with_0_args_control($line_elements_count) {
    if ($line_elements_count != 1) lexical_or_syntax_error();
}

function instruction_with_1_args_control($line_elements_count) {
    if ($line_elements_count != 2) lexical_or_syntax_error();
}

function instruction_with_2_args_control($line_elements_count) {
    if ($line_elements_count != 3) lexical_or_syntax_error();
}

function instruction_with_3_args_control($line_elements_count) {
    if ($line_elements_count != 4) lexical_or_syntax_error();
}

function instruction_without_args_to_xml($opcode, $instruction_order, $line_elements_count) {
    instruction_with_0_args_control($line_elements_count);
    echo("\t<instruction order=\"$instruction_order\" opcode=\"$opcode\">\n");
    echo("\t</instruction>\n");
}

function instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line) {
    echo("\t<instruction order=\"$instruction_order\" opcode=\"$opcode\">\n");
    for ($i = 1; $i <= ($line_elements_count - 1); $i += 1) {
        $arg_info = recognize_type_and_value($split_line[$i]);
        if ($arg_info["type"] == "string") {
            $arg_info["value"] = change_str_for_xml($arg_info["value"]);
        }
        echo("\t\t<arg".$i." type=\"".$arg_info["type"]."\">".$arg_info["value"]."</arg".$i.">\n");
    }
    echo("\t</instruction>\n");
}

function recognize_type_and_value($arg) {
    $info = ["type", "value"];
    if (str_contains($arg, "@")) {
        $split_arg = explode("@", $arg);
        $type = strtolower($split_arg[0]);
        if ($type == "gf" || $type == "lf" || $type == "tf") {
            $info["type"] = "var";
            $info["value"] = $arg;
        } else {
            $info["type"] = $type;
            $info["value"] = $split_arg[1];
        }
    } else {
        $lower_arg = strtolower($arg);
        if ($lower_arg == "int" || $lower_arg == "string" || $lower_arg == "bool") {
            $info["type"] = "type";
        } else {
            $info["type"] = "label";
        }
        $info["value"] = $lower_arg;
    }
    return $info;
}

function var_control($arg) {
    return preg_match("/(GF|LF|TF)@[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*/", $arg);
}

function int_control($arg) {
    return preg_match("/(int)@\-?[0-9]+/", $arg);
}

function string_control($arg) {
    return preg_match("/(string)@([a-zA-Z0-9]|(\\0(00|01|02|03|04|05
|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|35|92)))*/", $arg);
}

function bool_control($arg) {
    return preg_match("/(bool)@(true|false)/", $arg);
}

function nil_control($arg) {
    return preg_match("/(nil)@(nil)/", $arg);
}

function symb_control($arg) {
    return var_control($arg) || int_control($arg) || string_control($arg) || bool_control($arg) || nil_control($arg);
}

function label_control($arg) {
    return preg_match("/[a-zA-Z_\-$&%*!?][a-zA-Z0-9_\-$&%*!?]*/", $arg);
}

function type_control($arg) {
    return preg_match("/(string|int|bool)/", $arg);
}

function analyze_instructions($line, $instruction_order) {
    $line_without_multiple_whitespaces = preg_replace("/\s+/", " ", $line);
    $split_line = explode(" ", $line_without_multiple_whitespaces);
    $opcode = strtoupper($split_line[0]);
    $line_elements_count = count($split_line);
    switch ($opcode) {
        case "TYPE":
        case "MOVE":
            instruction_with_2_args_control($line_elements_count);
            if (var_control($split_line[1]) && symb_control($split_line[2])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "RETURN":
        case "BREAK":
            instruction_without_args_to_xml($opcode, $instruction_order, $line_elements_count);
            break;
        case "POPS":
        case "DEFVAR":
            instruction_with_1_args_control($line_elements_count);
            if (var_control($split_line[1])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "LABEL":
        case "JUMP":
        case "CALL":
            instruction_with_1_args_control($line_elements_count);
            if (label_control($split_line[1])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "WRITE":
        case "PUSHS":
            instruction_with_1_args_control($line_elements_count);
            if (symb_control($split_line[1])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "SUB":
        case "MUL":
        case "IDIV":
        case "ADD":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && int_control($split_line[2]) && int_control($split_line[3])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "GT":
        case "EQ":
        case "LT":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && symb_control($split_line[2]) && symb_control($split_line[3])) {
                $symb1_info = recognize_type_and_value($split_line[2]);
                $symb2_info = recognize_type_and_value($split_line[3]);
                if ($symb1_info["type"] != $symb2_info["type"]) lexical_or_syntax_error();
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "OR":
        case "AND":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && bool_control($split_line[2]) && bool_control($split_line[3])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "NOT":
            instruction_with_2_args_control($line_elements_count);
            if (var_control($split_line[1]) && bool_control($split_line[2])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "INT2CHAR":
            instruction_with_2_args_control($line_elements_count);
            if (var_control($split_line[1]) && int_control($split_line[2])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "GETCHAR":
        case "STRI2INT":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && string_control($split_line[2]) && int_control($split_line[3])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "READ":
            instruction_with_2_args_control($line_elements_count);
            if (var_control($split_line[1]) && type_control($split_line[2])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "CONCAT":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && string_control($split_line[2]) && string_control($split_line[3])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "STRLEN":
            instruction_with_2_args_control($line_elements_count);
            if (var_control($split_line[1]) && string_control($split_line[2])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "SETCHAR":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && int_control($split_line[2]) && string_control($split_line[3])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "JUMPIFNEQ":
        case "JUMPIFEQ":
            instruction_with_3_args_control($line_elements_count);
            if (var_control($split_line[1]) && symb_control($split_line[2]) && symb_control($split_line[3])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        case "DPRINT":
        case "EXIT":
            instruction_with_1_args_control($line_elements_count);
            if (int_control($split_line[1])) {
                instruction_to_xml($opcode, $instruction_order, $line_elements_count, $split_line);
            } else {
                lexical_or_syntax_error();
            }
            break;
        default:
            instruction_error();
    }
}


validate_arguments($argc, $argv);

$header_bool = false;
$instruction_order = 1;

while ($line = fgets(STDIN)) {
    $line = format_line($line);    // odstraneni komentaru
    if ($line == "") continue;   // odstraneni praznych radku
    if ($line == ".IPPcode22") {
        $header_bool = true;
        continue;
    }
    if ($header_bool) {
        if($instruction_order == 1) echo("<program language=\"IPPcode22\">\n");
        analyze_instructions($line, $instruction_order);
        $instruction_order++;
    }
}

check_start_program($header_bool);

echo("</program>\n");
exit(OK);
?>