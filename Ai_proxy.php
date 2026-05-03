<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// ── Clé Groq (même que api.php) ──────────────────────────────
$api_key = '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';
$api_url = 'https://api.groq.com/openai/v1/chat/completions';

$input = json_decode(file_get_contents('php://input'), true);
$prompt = isset($input['prompt']) ? trim($input['prompt']) : '';
$max_tokens = isset($input['max_tokens']) ? (int) $input['max_tokens'] : 1000;

if (empty($prompt)) {
    echo json_encode(['error' => 'Prompt vide']);
    exit;
}

$payload = json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'max_tokens' => $max_tokens,
    'temperature' => 0.7,
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Tu es un assistant médical pédiatrique professionnel. Tu réponds toujours en français de manière clinique et concise.'
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ]
]);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($response === false || $curl_err) {
    echo json_encode(['error' => 'Erreur cURL : ' . $curl_err]);
    exit;
}

$data = json_decode($response, true);

if ($http_code !== 200) {
    $msg = $data['error']['message'] ?? "Code HTTP $http_code";
    echo json_encode(['error' => 'Erreur API : ' . $msg]);
    exit;
}

$text = $data['choices'][0]['message']['content'] ?? '';

if (empty($text)) {
    echo json_encode(['error' => 'Réponse vide : ' . substr($response, 0, 300)]);
    exit;
}

echo json_encode(['success' => true, 'text' => trim($text)]);
?>