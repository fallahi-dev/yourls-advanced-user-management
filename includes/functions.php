<?php
// Function to sanitize username
function yaum_sanitize_username($username) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);
}

function start_session(){
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Define array_insert function
function array_insert($array, $position, $insert_array) {
    $first_array = array_splice($array, 0, $position);
    $array = array_merge($first_array, $insert_array, $array);
    return $array;
}

?>
