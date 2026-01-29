<?php
/**
 * API Response Helper Class
 * Provides consistent JSON response formatting
 */

class Response {
    
    /**
     * Send a success response
     * 
     * @param string $message Success message
     * @param array|null $data Data to return
     * @param int $code HTTP status code
     */
    public static function success($message, $data = null, $code = 200) {
        http_response_code($code);
        
        $response = [
            'status' => 'success',
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array|null $errors Additional errors
     */
    public static function error($message, $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a validation error response
     * 
     * @param array $errors Validation errors
     */
    public static function validationError($errors) {
        http_response_code(400);
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message Not found message
     */
    public static function notFound($message = 'Not found') {
        http_response_code(404);
        
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string $message Unauthorized message
     */
    public static function unauthorized($message = 'Unauthorized') {
        http_response_code(401);
        
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string $message Forbidden message
     */
    public static function forbidden($message = 'Forbidden') {
        http_response_code(403);
        
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a server error response
     * 
     * @param string $message Server error message
     */
    public static function serverError($message = 'Internal server error') {
        http_response_code(500);
        
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a created response (201)
     * 
     * @param string $message Created message
     * @param array|null $data Data to return
     */
    public static function created($message, $data = null) {
        http_response_code(201);
        
        $response = [
            'status' => 'success',
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a no content response (204)
     */
    public static function noContent() {
        http_response_code(204);
        exit;
    }
    
    /**
     * Send a custom JSON response
     * 
     * @param array $data Data to encode
     * @param int $code HTTP status code
     */
    public static function json($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}

