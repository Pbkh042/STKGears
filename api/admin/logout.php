<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';

require_post();
$_SESSION = [];
session_destroy();

json_ok();




