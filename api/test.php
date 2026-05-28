<?php
/**
 * Simple API Test
 */
header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'message' => 'API is working!',
    'timestamp' => date('c')
]);
