<?php

return [
    'skip_printer' => filter_var(env('POS_SKIP_PRINTER', false), FILTER_VALIDATE_BOOLEAN),
];
