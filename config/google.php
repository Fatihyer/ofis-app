<?php

return [

    'application_name' => env('GOOGLE_APPLICATION_NAME', 'YourAppName'),

    'credentials_path' => storage_path(env('GOOGLE_CREDENTIALS_PATH', 'app/google/ggolesheet-6727ca4dc3ab.json')),

    'token_path' => storage_path('app/google/token.json'),

    'scopes' => [
        \Google_Service_Drive::DRIVE_METADATA_READONLY,
        \Google_Service_Sheets::SPREADSHEETS,
      
    ],

];
