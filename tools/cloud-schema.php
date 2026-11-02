<?php
declare(strict_types=1);

// Read the trusted SQL dump without splitting semicolons inside SQL strings.
function split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';
        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
            } elseif ($char === $quote) {
                if ($next === $quote) {
                    $buffer .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }
        if (($char === '-' && $next === '-' && ctype_space($sql[$i + 2] ?? ' ')) || $char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }
        if ($char === '/' && $next === '*') {
            $end = strpos($sql, '*/', $i + 2);
            if ($end === false) {
                throw new RuntimeException('Unterminated SQL comment.');
            }
            $i = $end + 1;
            $buffer .= ' ';
            continue;
        }
        if (in_array($char, ["'", '"', '`'], true)) {
            $quote = $char;
        }
        if ($char === ';') {
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }
            $buffer = '';
        } else {
            $buffer .= $char;
        }
    }
    if ($quote !== null) {
        throw new RuntimeException('Unterminated SQL string.');
    }
    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }
    return $statements;
}

function cloud_schema_statements(): array
{
    $dump = file_get_contents(__DIR__ . '/../sql/stkgear.sql');
    $sessions = file_get_contents(__DIR__ . '/../sql/sessions.sql');
    if ($dump === false || $sessions === false) {
        throw new RuntimeException('Cannot read database schema.');
    }
    $result = [];
    foreach (split_sql_statements($dump) as $statement) {
        if (preg_match('/^(CREATE\s+TABLE|ALTER\s+TABLE)\b/i', $statement)) {
            $result[] = $statement;
        } elseif (preg_match('/^INSERT\s+INTO\s+`?products`?\s*\(/i', $statement)) {
            $result[] = $statement;
            // Do not copy legacy accounts, password hashes or customer orders.
            $result[] = 'UPDATE products SET created_by = NULL';
        }
    }
    return array_merge($result, split_sql_statements($sessions));
}
