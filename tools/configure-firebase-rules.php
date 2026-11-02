<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../core/firebase.php';
$rules = json_decode((string)file_get_contents(__DIR__ . '/../firebase/database.rules.json'), true, 512, JSON_THROW_ON_ERROR);
firebase_database()->request('.settings/rules', 'PUT', $rules);
echo "Firebase rules applied: public access denied; session expiry indexed.\n";
