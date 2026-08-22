<?php

function sendBrevoEmailSmtp($toEmail, $subject, $htmlBody, $senderEmail = null, $senderName = null, $smtpUser = null, $smtpPass = null) {
    $senderEmail = $senderEmail ?: BREVO_SENDER_EMAIL;
    $senderName = $senderName ?: BREVO_SENDER_NAME;

    $payload = [
        'sender' => [
            'name' => $senderName,
            'email' => $senderEmail
        ],
        'to' => [
            ['email' => $toEmail, 'name' => 'VisionGuard Operations']
        ],
        'subject' => $subject,
        'htmlContent' => $htmlBody
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201 || $httpCode === 200) {
        return ['success' => true, 'message' => 'Email delivered via Brevo v3 REST API.'];
    } else {
        return ['success' => false, 'error' => "Brevo REST API error code {$httpCode}: " . $response];
    }
}
