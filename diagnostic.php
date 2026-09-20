<?php
header('Content-Type: text/plain; charset=utf-8');
echo "PHP is working.\n";
echo "Project path: " . __DIR__ . "\n";
try {
    require __DIR__ . '/config/database.php';
    echo "Database connection: OK\n";
    echo "Users: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
    echo "Students: " . $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "Database connection: FAILED\n";
    echo $e->getMessage() . "\n";
}
