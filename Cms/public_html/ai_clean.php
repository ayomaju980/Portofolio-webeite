<?php
header('Content-Type: application/json');

// --- Ambil input JSON ---
$input = json_decode(file_get_contents('php://input'), true);
$text  = $input['text'] ?? '';

if (trim($text) === '') {
    echo json_encode(['success' => false, 'error' => 'Deskripsi kosong.']);
    exit;
}

// === API Key Gemini ===
// Dapatkan dari: https://aistudio.google.com/app/apikey
$GEMINI_API_KEY = 'AIzaSyCg95DmXVDFEts-yS3LE0D1xbkau1HNL5k';

// === Panggil Gemini API ===
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$GEMINI_API_KEY}";

$payload = [
    'contents' => [[
        'role'  => 'user',
        'parts' => [[
            'text' => "Rapikan dan perbaiki tata bahasa teks berikut tanpa mengubah makna. Gunakan bahasa Indonesia yang jelas, ringkas, dan profesional:\n\n" . $text
        ]]
    ]]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);

// Cek error dari Gemini
if (isset($result['error'])) {
    echo json_encode(['success' => false, 'error' => $result['error']['message']]);
    exit;
}

// Ambil hasil
$cleaned = $result['candidates'][0]['content']['parts'][0]['text'] ?? $text;
echo json_encode(['success' => true, 'cleaned' => trim($cleaned)]);
