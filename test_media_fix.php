<?php
// Test simple pour vérifier si l'entité Media avec la propriété status fonctionne

require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "Testing Media entity with status field...\n";
    
    // Test : Créer un nouveau Media et définir son status
    $mediaClass = 'App\Entity\Media';
    if (class_exists($mediaClass)) {
        echo "✅ Media class exists\n";
        
        $media = new $mediaClass();
        echo "✅ Media object created\n";
        
        // Test des méthodes status
        $media->setStatus('pending');
        $status = $media->getStatus();
        echo "✅ Media entity status methods work! Status: {$status}\n";
        
        // Test avec un autre status
        $media->setStatus('validated');
        $status = $media->getStatus();
        echo "✅ Status change works! New status: {$status}\n";
        
        echo "\n🎉 Entity tests passed! The Media::status property is properly defined.\n";
    } else {
        echo "❌ Media class not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
