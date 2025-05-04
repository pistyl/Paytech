<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['paytech_request'])) {
    header('Location: index.php');
    exit;
}

$paytech_data = $_SESSION['paytech_request'];
unset($_SESSION['paytech_request']);

// Configuration PayTech
$api_key = "VOTRE_CLE_API_PAYTECH";
$api_secret = "VOTRE_SECRET_API_PAYTECH";
$endpoint = "https://api.paytech.sn/payment/request";

// Préparation des données
$post_data = array_merge($paytech_data, [
    'api_key' => $api_key,
    'hash' => hash('sha256', $api_key.$api_secret.time())
]);

// Envoi de la requête à PayTech
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['success'] == 1) {
    // Redirection vers la page de paiement PayTech
    header("Location: " . $result['redirect_url']);
} else {
    // Gestion des erreurs
    $_SESSION['error_message'] = "Erreur lors de la création du paiement: " . $result['message'];
    header('Location: index.php');
}
exit;