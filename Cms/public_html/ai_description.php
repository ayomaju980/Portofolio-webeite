<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak diizinkan']);
    exit;
}

$action = $_POST['action'] ?? '';
$text   = $_POST['text'] ?? '';

$apiKey = 'AIzaSyCg95DmXVDFEts-yS3LE0D1xbkau1HNL5k'; // Ganti dengan API Key Gemini-mu

if ($action === 'generate') {
    $prompt = "Berdasarkan poin-poin berikut, tulis deskripsi portofolio kreatif, profesional, jelas, dan ringkas (maksimal 150 kata) dalam bahasa Indonesia:\n\n" . $text;
} elseif ($action === 'clean') {
    $prompt = "Rapikan teks berikut agar lebih singkat, jelas, dan profesional (bahasa Indonesia):\n\n" . $text;
} else {
    echo json_encode(['error' => 'Aksi tidak valid']);
    exit;
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$apiKey}";
$payload
