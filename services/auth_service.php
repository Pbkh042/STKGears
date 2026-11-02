<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/' . (data_backend() === 'firebase' ? 'firebase' : 'mysql') . '/auth_service.php';