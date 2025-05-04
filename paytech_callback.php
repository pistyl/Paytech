<?php
require_once 'config.php';

// Vérification de la signature
$received_hash = $_POST['hash'];
$expected_hash = hash('sha256', $_POST['ref_command'].$_POST['custom_field'].VOTRE_SECRET_API_PAYTECH);

if ($received_hash !== $expected_hash) {
    die("Signature invalide");
}

// Traitement du statut de paiement
$demande_id = $_POST['ref_command'];
$status = $_POST['status']; // 'success', 'cancel', 'failed'

if ($status === 'success') {
    // Mise à jour de la demande
    $sql = "UPDATE demandes SET statut = 'Paiement confirmé' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$demande_id]);
    
    // Envoyer un email de confirmation
    // ...
}

// Réponse obligatoire pour PayTech
echo "OK";