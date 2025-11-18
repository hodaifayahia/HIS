<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Debugbar Settings
     |--------------------------------------------------------------------------
     |
     | Debugbar is enabled by default, when debug is set to true in app.php.
     | You can override the value by setting enable to true or false instead of null.
     |
     */

    'enabled' => env('DEBUGBAR_ENABLED', false),  // Force disabled in production
    'except' => [
        'telescope*',
        'horizon*',
    ],

    /*
     |--------------------------------------------------------------------------
     | Storage settings
     |--------------------------------------------------------------------------
     |
     | DebugBar stores data for session/ajax requests.
     | You can disable this, so the debugbar stores data in headers/session,
     | but this can cause problems with large data collectors.
     | By default, file storage (in the storage folder) is used. Redis and PDO
     | can also be used. For PDO, run the package migrations first.
     |
     */
    'storage' => [
        'enabled' => false,  // Disabled for performance
        'driver' => 'file', // redis, file, pdo, socket, custom
        'path' => storage_path('debugbar'), // For file driver
        'connection' => null,   // Leave null for default connection (Redis/PDO)
        'provider' => '', // Instance of StorageInterface for custom driver
        'hostname' => '127.0.0.1', // Hostname to use with the "socket" driver
        'port' => 2304, // Port to use with the "socket" driver
    ],

    'editor' => env('DEBUGBAR_EDITOR', 'phpstorm'),

    /*
     |--------------------------------------------------------------------------
     | Vendors
     |--------------------------------------------------------------------------
     |
     | Vendor files are included by default, but can be set to false.
     | This can also be set to 'js' or 'css', to only include javascript or css vendor files.
     | Vendor files are for css: font-awesome (including fonts) and highlight.js (css files)
     | and for js: jquery and highlight.js
     | So if you want syntax highlighting, set it to true.
     | jQuery is set to not conflict with existing jQuery scripts.
     |
     */

    'include_vendors' => true,

    /*
     |--------------------------------------------------------------------------
     | Capture Ajax Requests
     |--------------------------------------------------------------------------
     |
     | The Debugbar can capture Ajax requests and display them. If you don't want this (ie. because of errors),
     | you can use this option to disable sending the data through the headers.
     |
     | Optionally, you can also send ServerTiming headers on ajax requests for the Chrome DevTools.
     |
     | Note for your request to be identified as ajax requests they must either send the X-Requested-With header
     | or have application/json as the Accept header.
     */
    'capture_ajax' => false,  // Disabled for performance
    'add_ajax_timing' => false,  // Disabled for performance

    /*
     |--------------------------------------------------------------------------
     | Custom Error Handler for Deprecated warnings
     |--------------------------------------------------------------------------
     |
     | When enabled, the Debugbar shows deprecated warnings for Symfony components
     | in the Messages tab.
     |
     */
    'error_handler' => false,

    /*
     |--------------------------------------------------------------------------
     | Clockwork integration
     |--------------------------------------------------------------------------
     |
     | Enable Clockwork data collection.
     | Note: Requires the itsgoingd/clockwork package to be installed.
     |
     */
    'clockwork' => false,

    /*
     |--------------------------------------------------------------------------
     | DataCollectors
     |--------------------------------------------------------------------------
     |
     | Enable/disable DataCollectors
     |
     */

    'collectors' => [
        'phpinfo' => false,  // Disabled - not needed in production
        'messages' => false,  // Disabled for performance
        'time' => false,  // Disabled for performance
        'memory' => false,  // Disabled for performance
        'exceptions' => false,  // Disabled for performance
        'log' => false,  // Disabled for performance
        'db' => false,  // Disabled for performance
        'views' => false,  // Disabled for performance
        'route' => false,  // Disabled for performance
        'auth' => false,  // Disabled for performance
        'gate' => false,  // Disabled for performance
        'session' => false,  // Disabled for performance
        'symfony_request' => false,  // Disabled for performance
        'mail' => false,  // Disabled for performance
        'laravel' => false,  // Disabled for performance
        'events' => false,  // Disabled for performance
        'default_request' => false,  // Disabled for performance
        'logs' => false,  // Disabled for performance
        'files' => false,  // Disabled for performance
        'config' => false,  // Disabled for performance
        'cache' => false,  // Disabled for performance
        'models' => false,  // Disabled for performance
        'livewire' => false,  // Disabled for performance
    ],

    /*
     |--------------------------------------------------------------------------
     | Extra options
     |--------------------------------------------------------------------------
     |
     | Configure some DataCollectors
     |
     */

    'options' => [
        'auth' => [
            'show_name' => false,
        ],
        'db' => [
            'with_params' => false,
            'backtrace' => false,
            'backtrace_exclude_paths' => [],
            'timeline' => false,
            'duration_background' => false,
            'explain' => [
                'enabled' => false,
            ],
            'hints' => false,
            'show_copy' => false,
        ],
        'mail' => [
            'full_log' => false,
        ],
        'views' => [
            'timeline' => false,
            'data' => false,
        ],
        'route' => [
            'label' => false,
        ],
        'logs' => [
            'file' => null,
        ],
        'cache' => [
            'values' => false,
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Inject Debugbar in Response
     |--------------------------------------------------------------------------
     |
     | Usually, the debugbar is added just before </body>, by listening to the
     | Response after the App is done. If you disable this, you have to add them
     | in your template yourself. See http://phpdebugbar.com/docs/rendering.html
     |
     */

    'inject' => false,  // Don't inject in production

    /*
     |--------------------------------------------------------------------------
     | Route Prefix
     |--------------------------------------------------------------------------
     |
     | Sometimes you want to set route prefix to be used by Debugbar to load
     | its resources from. Usually the need comes from misconfigured web server or
     | from trying to overcome bugs like this: http://trac.nginx.org/nginx/ticket/97
     |
     */
    'route_prefix' => '_debugbar',

    /*
     |--------------------------------------------------------------------------
     | Route Domain
     |--------------------------------------------------------------------------
     |
     | By default DebugBar route served from the same domain that request served.
     | To override default domain, specify it as a non-empty value.
     */
    'route_domain' => null,

    /*
     |--------------------------------------------------------------------------
     | DebugBar route middleware
     |--------------------------------------------------------------------------
     |
     | Additional middleware to run on DebugBar routes.
     | Provide an array of middleware or null to allow all users
     |
     */
    'route_middleware' => [],

    /*
     |--------------------------------------------------------------------------
     | DebugBar theme
     |--------------------------------------------------------------------------
     |
     | Switches between light and dark theme. If set to auto it will respect system preferences
     | Possible values: auto, light, dark
     |
     */
    'theme' => env('DEBUGBAR_THEME', 'auto'),

    /*
     |--------------------------------------------------------------------------
     | Backtrace stack limit
     |--------------------------------------------------------------------------
     |
     | By default, the DebugBar limits the number of stack frames returned for all queries and logs.
     | If you need larger stacktraces, you can increase the limit.
     |
     */
    'debug_backtrace_limit' => 25,

];
