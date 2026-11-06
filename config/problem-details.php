<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | Framework exceptions are converted into Problem Details responses only
    | for requests matching this path prefix. The prefix is matched both
    | exactly (for example, "/api") and with subpaths ("/api/v1/users").
    |
    | Set it to an empty string to convert framework exceptions for every
    | request.
    |
    */

    'api_prefix' => 'api',

];
