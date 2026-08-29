<?php
header('Content-Type: application/json');
require_once __DIR__ . '/auth-helper.php';
require_once __DIR__ . '/../config.php';

$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$jobDescription = $input['job_description'] ?? 'General Work';
$permitTypes = (array)($input['permit_types'] ?? []);

$jsaNumber = 'JSA-' . date('Ymd') . '-' . rand(1000, 9999);

// Load and Pre-Filter Database to save massive AI tokens (Zero-Omission Real-Time Filter)
$dbContent = '{}';
$dbFile = __DIR__ . '/../../data/ptw_database.json';

if (file_exists($dbFile)) {
    $rawData = json_decode(file_get_contents($dbFile), true);
    if (is_array($rawData)) {
        // Build robust Zero-Omission keyword list
        $keywords = explode(' ', strtolower($jobDescription));
        foreach ($permitTypes as $pt) {
            $keywords = array_merge($keywords, explode(' ', strtolower($pt)));
        }
        $globalKeywords = [
            'hazard', 'risk', 'ppe', 'control', 'safety', 'jsa', 'must', 'shall', 
            'prohibited', 'precaution', 'requirement', 'emergency', 'isolation',
            'oisd', 'is ', 'factories act', 'nfpa', 'mandatory', 'rule',
            'lel', 'uel', 'pel', 'gas', 'chemical'
        ];
        $keywords = array_merge($keywords, $globalKeywords);
        $keywords = array_filter($keywords, function($w) { return strlen(trim($w)) > 2; });
        
        $filteredData = [];
        foreach ($rawData as $item) {
            $itemStr = strtolower(json_encode($item));
            
            // Exclude huge irrelevant architecture/UI specifications
            if (strpos($itemStr, 'design_tokens') !== false || strpos($itemStr, 'component_guidance') !== false || strpos($itemStr, 'engineering_architecture') !== false) {
                continue;
            }
            
            // Keep if it matches safety keywords or user job keywords
            $keep = false;
            foreach ($keywords as $kw) {
                if (strpos($itemStr, $kw) !== false) {
                    $keep = true;
                    break;
                }
            }
            if ($keep) {
                $filteredData[] = $item;
            }
        }
        
        // Convert to Plain Text to strip JSON syntax and save 30%+ tokens (Idea 1 implementation)
        $plainTextContext = "";
        array_walk_recursive($filteredData, function($value, $key) use (&$plainTextContext) {
            if (is_string($value) && !empty(trim($value))) {
                // Ignore long SVG or base64 strings if any exist
                if (strlen($value) < 1000) {
                    $plainTextContext .= ucfirst(str_replace('_', ' ', $key)) . ": " . trim($value) . "\n";
                }
            }
        });
        
        $dbContent = $plainTextContext;
    }
}

// ---------------------------------------------------------
// RAG Implementation: Load Past JSA Records for Inspiration (Targeted Category Search)
// ---------------------------------------------------------
$inspirationContent = "";
$recordsDirBase = __DIR__ . '/../jsa_records/';
$files = [];

// Only scan folders matching the selected permit types to drastically reduce load
$searchCategories = !empty($permitTypes) ? $permitTypes : ['General'];
foreach ($searchCategories as $cat) {
    $catFolderName = preg_replace('/[^a-zA-Z0-9]/', '_', $cat);
    $catDir = $recordsDirBase . $catFolderName . '/';
    if (is_dir($catDir)) {
        $files = array_merge($files, glob($catDir . '*.json'));
    }
}

if (!empty($files)) {
    $matchedRecords = 0;
    
    // Sort files by modified time (newest first) to get recent inspirations
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Build keywords for scanning old records within the category
    $searchKeywords = explode(' ', strtolower($jobDescription));
    foreach ($permitTypes as $pt) {
        $searchKeywords = array_merge($searchKeywords, explode(' ', strtolower($pt)));
    }
    $searchKeywords = array_filter($searchKeywords, function($w) { return strlen($w) > 3; });
    
    foreach ($files as $file) {
        if ($matchedRecords >= 3) break; // Limit to 3 past records to save tokens
        
        $recordJson = json_decode(file_get_contents($file), true);
        if (!$recordJson) continue;
        
        $recordText = strtolower(json_encode($recordJson));
        
        // Check if old JSA is relevant to current task
        $isRelevant = false;
        foreach ($searchKeywords as $kw) {
            if (strpos($recordText, $kw) !== false) {
                $isRelevant = true;
                break;
            }
        }
        
        if ($isRelevant && isset($recordJson['steps'])) {
            $inspirationContent .= "Past JSA (" . $recordJson['jsa_number'] . ") Job: " . $recordJson['job_description'] . "\n";
            foreach ($recordJson['steps'] as $step) {
                $inspirationContent .= "- Step: " . ($step['step_description'] ?? '') . " | Hazard Controls: " . ($step['controls'] ?? '') . "\n";
            }
            $inspirationContent .= "\n";
            $matchedRecords++;
        }
    }
}

$prompt = "You are an expert Industrial Safety Officer. Generate a highly detailed, step-by-step Job Safety Analysis (JSA) matrix (minimum 10 to 15 steps) covering the entire workflow from pre-job alignment to site restoration.
CRITICAL: The steps MUST be in strict chronological sequence (from start to finish). Do not mix up the order of operations.
CRITICAL STANDARD PRIORITY: You must strictly prioritize OISD (Oil Industry Safety Directorate) and IS (Indian Standard) codes for all hazards and controls. Use NFPA only as a low-priority fallback.
The user's scope of work is provided below (it may be in Hindi, English, or Hinglish). You MUST deeply analyze all contextual hazards mentioned (e.g., weather, adjacent equipment, toxic gases).
Cross-reference the hazards and controls based on the primary Permit-to-Work database provided here:
<DATABASE>
" . $dbContent . "
</DATABASE>";

if (!empty($inspirationContent)) {
    $prompt .= "\n\nAdditionally, use the following past approved JSAs for INSPIRATION. Base your structure and hazard identification on these, but adapt them to the specific scope of work:\n<INSPIRATION>\n" . $inspirationContent . "\n</INSPIRATION>";
}

$prompt .= "

Scope of Work: " . $jobDescription . "
Permit Categories Selected: " . implode(', ', $permitTypes) . "

Generate the JSA returning ONLY valid JSON format exactly matching this schema. Do NOT include markdown blocks like ```json.
{
  \"steps\": [
    {
      \"step_description\": \"Detailed description of the step\",
      \"initial_risk\": \"Low\", \"Medium\", \"High\", or \"Extreme\" (choose one),
      \"controls\": \"Detailed control measures, cross-referenced from the database if applicable\",
      \"revised_risk\": \"Low\", \"Medium\", \"High\", or \"Extreme\" (choose one)
    }
  ]
}";

// Call AI APIs with Key Rotation (Supports both NVIDIA and Gemini)
$apiKeys = defined('AI_API_KEYS') ? AI_API_KEYS : (defined('GEMINI_API_KEYS') ? GEMINI_API_KEYS : []);
if (empty($apiKeys)) {
    echo json_encode(['success' => false, 'message' => 'AI API keys are not configured.']);
    exit;
}

$lastError = 'Unknown error';

foreach ($apiKeys as $index => $apiKey) {
    $isNvidia = strpos($apiKey, 'nvapi-') === 0;
    
    if ($isNvidia) {
        // Prepare NVIDIA API Payload (OpenAI Format)
        $url = 'https://integrate.api.nvidia.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
        $data = [
            'model' => 'meta/llama-3.1-70b-instruct',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.2
        ];
    } else {
        // Prepare Gemini API Payload
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=' . $apiKey;
        $headers = [
            'Content-Type: application/json'
        ];
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json'
            ]
        ];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $lastError = 'Curl Error: ' . $curlError;
        continue; // Try next key
    }
    
    $responseData = json_decode($response, true);
    
    // Check for API errors
    if ($httpCode >= 400 || isset($responseData['error'])) {
        if ($isNvidia) {
            $lastError = $responseData['detail'] ?? $responseData['message'] ?? "NVIDIA API HTTP $httpCode";
        } else {
            $lastError = $responseData['error']['message'] ?? "Gemini API HTTP $httpCode";
        }
        continue; // Try next key
    }
    
    // Parse success response based on provider
    $aiText = '';
    if ($isNvidia && isset($responseData['choices'][0]['message']['content'])) {
        $aiText = $responseData['choices'][0]['message']['content'];
    } elseif (!$isNvidia && isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiText = $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
    
    if (!empty($aiText)) {
        // Clean markdown backticks if NVIDIA returned them
        $aiText = preg_replace('/```json\s*(.*?)\s*```/is', '$1', $aiText);
        $aiText = preg_replace('/```\s*(.*?)\s*```/is', '$1', $aiText);
        
        $aiJson = json_decode($aiText, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($aiJson['steps'])) {
            // Minimalistic Categorized Save Logic
            $primaryCategory = !empty($permitTypes) ? preg_replace('/[^a-zA-Z0-9]/', '_', $permitTypes[0]) : 'General';
            $saveDir = __DIR__ . '/../jsa_records/' . $primaryCategory . '/';
            if (!is_dir($saveDir)) {
                mkdir($saveDir, 0755, true);
            }
            
            $recordData = [
                'jsa_number' => $jsaNumber,
                'job_description' => $jobDescription,
                'permit_types' => $permitTypes,
                'created_by' => $auth['user']['email'] ?? 'System',
                'created_at' => time(),
                'provider' => $isNvidia ? 'NVIDIA' : 'Gemini',
                'steps' => $aiJson['steps']
            ];
            
            $savePath = $saveDir . $jsaNumber . '.json';
            file_put_contents($savePath, json_encode($recordData, JSON_PRETTY_PRINT));
            
            // SUCCESS! Output and exit immediately
            echo json_encode([
                'success' => true,
                'jsa_number' => $jsaNumber,
                'steps' => $aiJson['steps'],
                'provider' => $isNvidia ? 'NVIDIA' : 'Gemini',
                'saved_path' => $savePath
            ]);
            exit;
        } else {
            $lastError = 'Invalid JSON structure returned by AI.';
        }
    } else {
        $lastError = 'Unexpected AI response format.';
    }
}

// If all keys failed, return the last error encountered
echo json_encode([
    'success' => false,
    'message' => 'All API keys exhausted or failed. Last error: ' . $lastError
]);


