<?php

/**
 * Bootstrap file to initialize the database connection
 * Include this file in your public/index.php before handling requests
 */

require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize database connection
Database::initialize();

// Test database connection
if (!Database::testConnection()) {
    die('Database connection failed. Please check your configuration.');
}
