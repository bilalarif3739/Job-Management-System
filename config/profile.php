<?php

return [

    // User Preferences
    'preferences' => [
        // When double click on message
        'click_reminder' => ['selection', 'reminder'],
        // When left click on message
        'left_click' => ['default_click', 'open_menu'],
    ],

    'avatar' => [
        'disk' => env('AVATAR_DISK', 'public'),
        'directory' => 'avatars',
        'max_kb' => 2048,
        'max_side' => 512,
        'min_dimension' => 64,
        'max_dimension' => 4096,
    ],

    'status' => [
        'text_max' => 100,
    ],

];

