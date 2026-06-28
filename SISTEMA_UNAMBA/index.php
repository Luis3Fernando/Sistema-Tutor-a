<?php
declare(strict_types=1);

// Redirige la raíz del proyecto al front controller real
header('Location: public/index.php?route=login');
exit;
