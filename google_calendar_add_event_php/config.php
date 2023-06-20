<?php
//Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'site');

//Google API configuration
//define('GOOGLE_CLIENT_ID', '584071650743-dsvk81d0s19mol3ve4ltnm2qu7fhpp82.apps.googleusercontent.com');
//define('GOOGLE_CLIENT_SECRET', 'vlj9t8DqW45Qebt1cvAA3Md9');
define('GOOGLE_CLIENT_ID', '384737990715-k6vtulue8bf7u1bfv17vecn7m5emvsaa.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_OAUTH_SCOPE','https://www.googleapis.com/auth/calendar');
define('REDIRECT_URI', 'http://localhost/google_calendar_add_event_php/google_calendar_event_sync.php');

// Google OAuth URL
$googleOauthURL = 'https://accounts.google.com/o/oauth2/auth?scope='.urlencode(GOOGLE_OAUTH_SCOPE).'&redirect_uri='.REDIRECT_URI.'&response_type=code&client_id='.GOOGLE_CLIENT_ID.'&access_type=online';

//$googleOauthURL = 'https://accounts.google.com/o/oauth2/auth?scope=https://www.googleapis.com/auth/calendar&redirect_uri=http://localhost/google_calendar_add_event_php/google_calendar_event_sync.php&response_type=code&client_id=384737990715-k6vtulue8bf7u1bfv17vecn7m5emvsaa.apps.googleusercontent.com&access_type=online'

//Start session
if(!session_id()) session_start();


$tokenPath = 'arquivoJson.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
    }

