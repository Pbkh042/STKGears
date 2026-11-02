<?php
declare(strict_types=1);

require_once __DIR__ . '/cloud-schema.php';

$products = [];
$dump = file_get_contents(__DIR__ . '/../sql/stkgear.sql');
foreach (split_sql_statements((string)$dump) as $statement) {
    if (!preg_match('/^INSERT INTO `products`\s*\(([^)]+)\)\s*VALUES\s*(.*)$/s', $statement, $match)) continue;
    $columns = array_map(static fn(string $name): string => trim($name, " `\r\n\t"), explode(',', $match[1]));
    $values = $match[2];
    $length = strlen($values);
    $offset = 0;
    while ($offset < $length) {
        while ($offset < $length && (ctype_space($values[$offset]) || $values[$offset] === ',')) $offset++;
        if ($offset >= $length) break;
        if ($values[$offset++] !== '(') throw new RuntimeException('Invalid catalog row.');
        $row = [];
        while (true) {
            while ($offset < $length && ctype_space($values[$offset])) $offset++;
            if (($values[$offset] ?? '') === "'") {
                $offset++;
                $value = '';
                $closed = false;
                while ($offset < $length) {
                    $char = $values[$offset++];
                    if ($char === '\\') {
                        $escaped = $values[$offset++] ?? '';
                        $value .= match ($escaped) {'0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'Z' => "\x1a", default => $escaped};
                    } elseif ($char === "'") {
                        if (($values[$offset] ?? '') === "'") { $value .= "'"; $offset++; }
                        else { $closed = true; break; }
                    } else $value .= $char;
                }
                if (!$closed) throw new RuntimeException('Unclosed catalog value.');
            } else {
                $start = $offset;
                while ($offset < $length && !in_array($values[$offset], [',', ')'], true)) $offset++;
                $literal = trim(substr($values, $start, $offset - $start));
                if ($literal === 'NULL') $value = null;
                elseif (preg_match('/^-?\d+$/', $literal)) $value = (int)$literal;
                else throw new RuntimeException('Unsupported catalog literal.');
            }
            $row[] = $value;
            while ($offset < $length && ctype_space($values[$offset])) $offset++;
            $delimiter = $values[$offset++] ?? '';
            if ($delimiter === ')') break;
            if ($delimiter !== ',') throw new RuntimeException('Invalid catalog separator.');
        }
        if (count($row) !== count($columns)) throw new RuntimeException('Catalog column mismatch.');
        $product = array_combine($columns, $row);
        $product['created_by'] = null;
        $products[] = $product;
    }
}
if (count($products) !== 13) throw new RuntimeException('Unexpected source catalog size.');
$output = __DIR__ . '/../firebase/catalog.json';
file_put_contents($output, json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);
echo 'Built ' . count($products) . ' product records; no accounts or orders included.' . PHP_EOL;
