<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\App;

$app = new App();
$app->run();
