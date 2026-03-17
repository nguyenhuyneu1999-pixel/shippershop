<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors',1);

$d = db();
echo "Class: ".get_class($d)."\n";
echo "Methods: ".implode(', ',get_class_methods($d))."\n\n";

// Check db.php source
echo "=== db.php source ===\n";
echo file_get_contents(__DIR__.'/../includes/db.php');
