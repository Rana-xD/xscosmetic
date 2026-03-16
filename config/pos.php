<?php

$skipPrinter = env('POS_SKIP_PRINTER');

if ($skipPrinter === null) {
    $skipPrinter = env('APP_ENV', 'production') === 'local';
} else {
    $skipPrinter = filter_var($skipPrinter, FILTER_VALIDATE_BOOLEAN);
}

return [
    'skip_printer' => (bool) $skipPrinter,
];
