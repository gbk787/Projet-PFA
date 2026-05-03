<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$age_mois = intval($data['age_mois'] ?? 0);
$bebe_nom = htmlspecialchars($data['bebe_nom'] ?? 'Bébé');
$parent_nom = htmlspecialchars($data['parent_nom'] ?? 'Parent');
$messages = $data['messages'] ?? [];

if (empty($messages)) {
    echo json_encode(['success' => false, 'message' => 'Aucun message']);
    exit;
}

// ============================================================
// VOTRE CLÉ API GROQ → https://console.groq.com
// ============================================================
$GROQ_API_KEY = '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';

// ============================================================
// PROMPT SYSTÈME
// ============================================================
$systemPrompt = "Tu es un assistant parental bienveillant et expert en santé et développement du nourrisson.
Tu aides {$parent_nom}, le parent de {$bebe_nom} qui a {$age_mois} mois.

Tu réponds à TOUTES les questions liées aux bébés et enfants :
- Pleurs excessifs, coliques, agitation
- Alimentation, allaitement, diversification alimentaire
- Sommeil, réveils nocturnes, siestes
- Fièvre, rhume, dents qui poussent, symptômes courants
- Développement moteur et cognitif
- Hygiène et soins du quotidien
- Bien-être émotionnel du bébé et du parent
- Questions de comportement

Règles :
1. Réponds TOUJOURS en français, ton chaleureux et rassurant.
2. Personnalise selon l'âge de {$bebe_nom} ({$age_mois} mois).
3. Si symptôme grave → recommande un médecin immédiatement.
4. Réponds de façon concise (max 150 mots).
5. Termine par un emoji et une phrase d'encouragement.";

// ============================================================
// CONSTRUCTION DES MESSAGES (format OpenAI compatible)
// ============================================================
$apiMessages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

foreach ($messages as $msg) {
    $role = $msg['role'] ?? '';
    $content = $msg['content'] ?? '';
    if (empty($content))
        continue;
    if (!in_array($role, ['user', 'assistant']))
        continue;
    $apiMessages[] = ['role' => $role, 'content' => $content];
}

// ============================================================
// PAYLOAD GROQ (compatible OpenAI)
// ============================================================
$payload = [
    'model' => 'llama-3.3-70b-versatile',  // modèle gratuit Groq
    'messages' => $apiMessages,
    'max_tokens' => 512,
    'temperature' => 0.7,
];

// ============================================================
// APPEL API GROQ
// ============================================================
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$GROQ_API_KEY}",
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ============================================================
// GESTION DES ERREURS
// ============================================================
if ($curlError) {
    echo json_encode(['success' => false, 'message' => "Erreur réseau : $curlError"]);
    exit;
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? "Erreur HTTP $httpCode";
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

$result = json_decode($response, true);
$reponse = $result['choices'][0]['message']['content'] ?? null;

if (!$reponse) {
    echo json_encode(['success' => false, 'message' => 'Réponse vide']);
    exit;
}

echo json_encode(['success' => true, 'reponse' => $reponse]);