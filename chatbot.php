<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_message = $data['message'] ?? '';

if (empty($user_message)) {
    echo json_encode(['reply' => 'I didn\'t catch that. Can you repeat?']);
    exit;
}

// Your Gemini API Key
$api_key = trim('AIzaSyA9Y6Wf4iOBfbqNBivaqc4N7ftSySlyLgI'); 

// The SNEAKDUP "System Prompt"
$system_instruction = "You are the official AI Assistant for SNEAKDUP, a premium streetwear and sneaker store. 
Your tone is cool, helpful, and concise. 

STORE FACTS:
- Address: We are located along Dr. Arcadio Santos Ave near Olivarez College, Parañaque.

RULES:
1. You only answer questions related to sneakers, clothing, store hours, and our physical branches.
2. If a user asks something unrelated, politely refuse and say you can only help with SNEAKDUP inquiries.
3. Keep your answers relatively short (1-3 sentences).
User Message: ";

$full_prompt = $system_instruction . $user_message;

// THE FIX: Using the exact model name Google gave us in your screenshot!
$model_name = 'gemini-2.5-flash';
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_name . ':generateContent?key=' . $api_key;

$post_data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $full_prompt]
            ]
        ]
    ]
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['reply' => '⚠️ Server Error: ' . $curl_error]);
    exit;
}

$response_data = json_decode($response, true);

// If success, send the reply!
if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
    $ai_reply = $response_data['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['reply' => trim($ai_reply)]);
} else {
    $api_error = isset($response_data['error']['message']) ? $response_data['error']['message'] : 'Unknown API error';
    echo json_encode(['reply' => '⚠️ API Error: ' . $api_error]);
}
?>