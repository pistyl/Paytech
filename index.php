<?php
require_once 'config.php'; // Inclut la connexion PDO
require_once 'PayTech.php'; // Inclut la classe PayTech


// Constantes pour PayTech
define('PAYTECH_API_KEY', 'ce802ebe545215b0a539d2aef95079e11968dbb0d7f42b518f2af42fcb882aca');
define('PAYTECH_API_SECRET', '5dce42c6bbf7813c32c9c6f9f06d8041b39b915741844c2d25432ad2622cb6c3');
define('PAYTECH_IPN_URL', 'process_paytech.php');
define('PAYTECH_SUCCESS_URL', 'https://votresite.com?paiement=success');
define('PAYTECH_CANCEL_URL', 'https://votresite.com?paiement=cancel');

$tarifs = [
    'Carte d\'identité' => 5000,
    'Certificat de résidence' => 3000,
    'Extrait de naissance' => 2000,
    'Copie Litérale' => 2500
];

// [Le reste de votre code existant...]

// Modification de la partie traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['modifier']) && !isset($_POST['supprimer'])) {
    // Nettoyage des données
    $nom = htmlspecialchars($_POST['nom']);
    $date_naissance = $_POST['date_naissance'];
    $telephone = $_POST['telephone'];
    $type_demande = htmlspecialchars($_POST['type_demande']);
    $date_demande = $_POST['date_demande'];
    $numero_registre = ($type_demande === 'Extrait de naissance') ? $_POST['numero_registre'] : null;
    
    // Validation
    $errors = [];
    
    // Vérifier que le type de demande existe dans les tarifs
    if (!array_key_exists($type_demande, $tarifs)) {
        $errors[] = "Type de demande invalide.";
    }
    
    if ($type_demande === 'Extrait de naissance' && !preg_match('/^\d{3}\/\d{4}$/', $numero_registre)) {
        $errors[] = "Le numéro de registre doit être au format 123/2023.";
    }
    
    
    if (empty($errors)) {
        // Détermination du montant
        $montant = $tarifs[$type_demande] ?? 0;
      
        
        try {
            $pdo->beginTransaction();
            
            // Insertion de la demande
            $sql = "INSERT INTO demandes (nom, date_naissance, telephone, type_demande, date_demande, numero_registre, statut, montant)
                    VALUES (?, ?, ?, ?, ?, ?, 'En attente de paiement', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom, $date_naissance, $telephone, $type_demande, $date_demande, $numero_registre, $montant]);
            
            $demande_id = $pdo->lastInsertId();
            
            // Préparation requête PayTech
            $paytech_data = [
                'item_name' => $type_demande,
                'item_price' => $montant,
                'command_name' => "Demande #$demande_id",
                'ref_command' => $demande_id,
                'currency' => 'XOF',
                'ipn_url' => PAYTECH_IPN_URL,
                'success_url' => PAYTECH_SUCCESS_URL,
                'cancel_url' => PAYTECH_CANCEL_URL,
                'custom_field' => json_encode(['demande_id' => $demande_id])
            ];
            
            // Génération du hash de sécurité
            $paytech_data['hash'] = hash('sha256', PAYTECH_API_KEY . PAYTECH_API_SECRET . time());
            
            // Envoi à l'API PayTech
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.paytech.sn/payment/request');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge($paytech_data, ['api_key' => PAYTECH_API_KEY])));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($result['success'] == 1) {
                $pdo->commit();
                header("Location: " . $result['redirect_url']);
                exit;
            } else {
                throw new Exception("Erreur PayTech: " . $result['message']);
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Erreur lors du traitement: " . $e->getMessage();
        }
    }
}

// Gestion des retours PayTech
if (isset($_GET['paiement'])) {
    if ($_GET['paiement'] === 'success') {
        $success_message = "Paiement effectué avec succès! Votre demande est en cours de traitement.";
    } elseif ($_GET['paiement'] === 'cancel') {
        $error_message = "Paiement annulé. Vous pouvez réessayer.";
    }
}

// Modification des demandes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier'])) {
    $id = $_POST['id'];
    $statut = $_POST['statut'];

    $sql = "UPDATE demandes SET statut = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$statut, $id]);
    $success_message = "Statut mis à jour !";
}

// Suppression des demandes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supprimer'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM demandes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $success_message = "Demande supprimée !";
}

// Récupérer les demandes pour le calendrier
$sql = "SELECT * FROM demandes";
$demandes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des demandes</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.10.1/main.min.css">
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Formulaire de demande -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-bold mb-4">Faire une demande administrative</h2>

            <?php if (isset($success_message)) : ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)) : ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom :</label>
                        <input type="text" name="nom" id="nom" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="date_naissance" class="block text-sm font-medium text-gray-700">Date de naissance :</label>
                        <input type="date" name="date_naissance" id="date_naissance" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="telephone" class="block text-sm font-medium text-gray-700">Numéro de téléphone :</label>
                        <input type="text" name="telephone" id="telephone" value="+221" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="type_demande" class="block text-sm font-medium text-gray-700">Type de demande :</label>
                        <select name="type_demande" id="type_demande"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="Carte d'identité">Carte d'identité (5 000 XOF)</option>
                            <option value="Certificat de résidence">Certificat de résidence (3 000 XOF)</option>
                            <option value="Extrait de naissance">Extrait de naissance (2 000 XOF)</option>
                            <option value="Copie Litérale">Copie Litérale (2 500 XOF)</option>
                            <?php foreach ($tarifs as $type => $prix): ?>
        <option value="<?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($type) ?> (<?= number_format($prix, 0, ',', ' ') ?> XOF)
        </option>
    <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="registre_annee" style="display: none;">
                    <label for="numero_registre" class="block text-sm font-medium text-gray-700">Numéro registre / Année d'enregistrement :</label>
                    <input type="text" name="numero_registre" id="numero_registre"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Exemple : 123/2023">
                </div>
                
                <div>
                    <label for="date_demande" class="block text-sm font-medium text-gray-700">Date de demande :</label>
                    <input type="date" name="date_demande" id="date_demande" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <input type="submit" value="Payer et Soumettre"
                           class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                </div>
            </form>

            <script>
                // Gestion de l'affichage du champ registre
                document.getElementById('type_demande').addEventListener('change', function() {
                    var registreAnneeDiv = document.getElementById('registre_annee');
                    if (this.value === 'Extrait de naissance') {
                        registreAnneeDiv.style.display = 'block';
                    } else {
                        registreAnneeDiv.style.display = 'none';
                    }
                });

                // Formatage du numéro de registre
                document.getElementById('numero_registre').addEventListener('input', function(e) {
                    var input = e.target;
                    var value = input.value.replace(/\D/g, '');
                    if (value.length > 4) {
                        value = value.slice(0, 3) + '/' + value.slice(3, 7);
                    }
                    input.value = value;
                });
            </script>
        </div>

        <!-- Calendrier et Liste des demandes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Calendrier des demandes</h3>
                <div id="calendar"></div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Dernières demandes</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($demandes as $demande): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $demande['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($demande['nom']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($demande['type_demande']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $demande['statut'] === 'Paiement confirmé' ? 'bg-green-100 text-green-800' : 
                                           ($demande['statut'] === 'En attente de paiement' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                        <?= $demande['statut'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="id" value="<?= $demande['id'] ?>">
                                        <select name="statut" onchange="this.form.submit()" 
                                                class="text-sm border rounded p-1 focus:outline-none focus:ring">
                                            <option value="En attente de paiement" <?= $demande['statut'] === 'En attente de paiement' ? 'selected' : '' ?>>En attente</option>
                                            <option value="Paiement confirmé" <?= $demande['statut'] === 'Paiement confirmé' ? 'selected' : '' ?>>Confirmé</option>
                                            <option value="Terminé" <?= $demande['statut'] === 'Terminé' ? 'selected' : '' ?>>Terminé</option>
                                        </select>
                                        <input type="hidden" name="modifier" value="1">
                                    </form>
                                    
                                    <form method="post" class="inline ml-2">
                                        <input type="hidden" name="id" value="<?= $demande['id'] ?>">
                                        <button type="submit" name="supprimer" value="1" 
                                                class="text-red-600 hover:text-red-900 text-sm"
                                                onclick="return confirm('Supprimer cette demande?')">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.10.1/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php foreach ($demandes as $demande) { ?>
                        {
                            title: "<?= addslashes($demande['type_demande']) ?> - <?= addslashes($demande['nom']) ?>",
                            start: "<?= $demande['date_demande'] ?>",
                            color: "<?= $demande['statut'] === 'Paiement confirmé' ? '#10B981' : 
                                     ($demande['statut'] === 'En attente de paiement' ? '#F59E0B' : '#6B7280') ?>"
                        },
                    <?php } ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>