<?php
/**
 * Documentation Organizer
 * 
 * Moves all .md documentation files to a /docs folder
 * Keeps the root directory clean
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = __DIR__;
$docsDir = $baseDir . '/docs';

// Create docs directory if it doesn't exist
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0755, true);
    echo "✓ Created /docs directory<br>";
}

// Scan for .md files in root
$files = scandir($baseDir);
$mdFiles = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $filePath = $baseDir . '/' . $file;
    
    // Only process .md files in root directory
    if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'md') {
        $mdFiles[] = $file;
    }
}

echo "<h1>📚 Documentation Organizer</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #17a2b8; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #007bff; color: white; }
    .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
</style>";

echo "<div class='container'>";
echo "<p class='info'>Found " . count($mdFiles) . " documentation files to organize</p>";

if (count($mdFiles) > 0) {
    echo "<table>";
    echo "<tr><th>File</th><th>Action</th></tr>";
    
    if (isset($_POST['organize'])) {
        $moved = 0;
        $failed = 0;
        
        foreach ($mdFiles as $file) {
            $source = $baseDir . '/' . $file;
            $destination = $docsDir . '/' . $file;
            
            if (rename($source, $destination)) {
                echo "<tr><td>$file</td><td class='success'>✓ Moved to /docs/</td></tr>";
                $moved++;
            } else {
                echo "<tr><td>$file</td><td class='error'>✗ Failed to move</td></tr>";
                $failed++;
            }
        }
        
        echo "</table>";
        echo "<p class='success'>✅ Moved $moved files to /docs/</p>";
        if ($failed > 0) {
            echo "<p class='error'>❌ Failed to move $failed files</p>";
        }
        
    } else {
        foreach ($mdFiles as $file) {
            echo "<tr><td>$file</td><td>Will be moved to /docs/</td></tr>";
        }
        echo "</table>";
        
        echo "<form method='POST'>";
        echo "<button type='submit' name='organize' class='btn'>📁 Move All to /docs/</button>";
        echo "</form>";
    }
} else {
    echo "<p class='success'>✅ All documentation files are already organized!</p>";
}

echo "</div>";
?>
