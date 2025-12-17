<?php

require_once __DIR__.'/../vendor/autoload.php';

use Villaflor\Connection\Adapter\Curl;
use Villaflor\Connection\Auth\None;

// Example 1: Upload a single file from disk
echo "=== Example 1: Upload File from Disk ===\n";

$client = new Curl(new None, 'https://postman-echo.com');

// Create a test file
$testFile = __DIR__.'/test-upload.txt';
file_put_contents($testFile, "This is a test file for upload\nLine 2\nLine 3");

try {
    $response = $client->post('/post', [
        'description' => 'My test file',
        'file' => $testFile,
    ]);

    $body = json_decode($response->getBody()->getContents());
    echo 'Upload Status: '.$response->getStatusCode()."\n";
    echo 'Files received: '.json_encode($body->files)."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
} finally {
    // Clean up test file
    if (file_exists($testFile)) {
        unlink($testFile);
    }
}

// Example 2: Upload raw file content (not from disk)
echo "=== Example 2: Upload Raw File Content ===\n";

$client = new Curl(new None, 'https://postman-echo.com');

// Upload content that's generated in memory
$csvContent = "name,email,age\n";
$csvContent .= "John Doe,john@example.com,30\n";
$csvContent .= "Jane Smith,jane@example.com,25\n";

try {
    $response = $client->post('/post', [
        'report_type' => 'users',
        'data.csv' => [
            'name' => 'data.csv',
            'contents' => $csvContent,
            'mime_type' => 'text/csv',
        ],
    ]);

    echo 'Upload Status: '.$response->getStatusCode()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 3: Upload multiple files
echo "=== Example 3: Upload Multiple Files ===\n";

$client = new Curl(new None, 'https://postman-echo.com');

// Create multiple test files
$file1 = __DIR__.'/document1.txt';
$file2 = __DIR__.'/document2.txt';
file_put_contents($file1, 'Content of document 1');
file_put_contents($file2, 'Content of document 2');

try {
    $response = $client->post('/post', [
        'title' => 'Multiple Documents',
        'file1' => $file1,
        'file2' => $file2,
        'metadata' => json_encode(['uploaded_at' => date('Y-m-d H:i:s')]),
    ]);

    $body = json_decode($response->getBody()->getContents());
    echo 'Upload Status: '.$response->getStatusCode()."\n";
    echo 'Files received: '.count((array) $body->files)."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
} finally {
    // Clean up test files
    if (file_exists($file1)) {
        unlink($file1);
    }
    if (file_exists($file2)) {
        unlink($file2);
    }
}

// Example 4: Upload with custom MIME type
echo "=== Example 4: Upload with Custom MIME Type ===\n";

$client = new Curl(new None, 'https://postman-echo.com');

$jsonData = json_encode(['id' => 123, 'name' => 'Test Product'], JSON_PRETTY_PRINT);

try {
    $response = $client->post('/post', [
        'product_id' => '123',
        'data.json' => [
            'name' => 'product-data.json',
            'contents' => $jsonData,
            'mime_type' => 'application/json',
        ],
    ]);

    echo 'Upload Status: '.$response->getStatusCode()."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Example 5: Mixed form data and files
echo "=== Example 5: Mixed Form Data and Files ===\n";

$client = new Curl(new None, 'https://postman-echo.com');

$imageFile = __DIR__.'/test-image.txt';
file_put_contents($imageFile, 'Simulated image binary data...');

try {
    $response = $client->post('/post', [
        // Regular form fields
        'user_id' => '456',
        'caption' => 'My awesome photo',
        'tags' => 'nature,landscape,sunset',

        // File field
        'image' => $imageFile,

        // Additional metadata as JSON
        'metadata' => json_encode([
            'camera' => 'iPhone 12',
            'location' => 'San Francisco',
            'timestamp' => time(),
        ]),
    ]);

    $body = json_decode($response->getBody()->getContents());
    echo 'Upload Status: '.$response->getStatusCode()."\n";
    echo 'Form fields: '.implode(', ', array_keys((array) $body->form))."\n";
    echo 'Files uploaded: '.count((array) $body->files)."\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
} finally {
    if (file_exists($imageFile)) {
        unlink($imageFile);
    }
}

// Example 6: Handling upload errors
echo "=== Example 6: Error Handling ===\n";

$client = new Curl(new None, 'https://postman-echo.com');

try {
    // Try to upload a non-existent file
    $response = $client->post('/post', [
        'file' => '/path/to/nonexistent/file.txt',
    ]);

    echo "This won't be reached if file doesn't exist\n";
} catch (Exception $e) {
    echo "Caught expected error: File doesn't exist\n";
}

// Upload with invalid raw content structure (missing required fields)
try {
    $response = $client->post('/post', [
        'invalid_file' => [
            // Missing 'name' and 'contents'
            'mime_type' => 'text/plain',
        ],
    ]);

    echo "Upload successful despite invalid structure (field was skipped)\n\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n\n";
}

echo "File upload examples complete!\n";
