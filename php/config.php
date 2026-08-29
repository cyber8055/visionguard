<?php
// Dynamic Environment Configuration Loader
$envPath = __DIR__ . '/../data/env.json';

if (file_exists($envPath)) {
    $envData = json_decode(file_get_contents($envPath), true);
    
    if ($envData) {
        $aiKeys = [];
        
        foreach ($envData as $key => $value) {
            // Collect individual AI keys into the array
            if (strpos($key, 'NVIDIA_KEY_') === 0 || strpos($key, 'GEMINI_KEY_') === 0) {
                if (!empty(trim($value))) {
                    $aiKeys[] = trim($value);
                }
            }
            
            if (!defined($key)) {
                define($key, $value);
            }
        }
        
        // Expose the bundled array for legacy support
        if (!defined('AI_API_KEYS')) {
            define('AI_API_KEYS', $aiKeys);
        }
    }
} else {
    die("FATAL ERROR: Environment configuration (env.json) is missing. System halted for security.");
}
?>
