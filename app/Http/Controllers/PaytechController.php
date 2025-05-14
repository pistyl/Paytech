<?php

namespace App\Http\Controllers;
use App\Http\Controllers\PayTech;

use Illuminate\Http\Request;

class PaytechController extends Controller
{
    public function paiement(Request $request){
        $product =
            [
                'id' => 1,
                'name' => 'Téléphone Samsung Galaxy',
                'description' => 'Un smartphone puissant avec de belles fonctionnalités.',
                'price' => 150000,
                'image' => 'https://nova.sn/34321-medium_default/samsung-galaxy-z-flip-6-12-go-de-ram-memoire-256-go.jpg',
            ];
        $product['currency'] = "XOF";

        $base_url  = "https://www.example.com/";
        $api_key = env("API_KEY");
        $api_secret = env("API_SECRET");
        $jsonResponse = (new PayTech($api_key, $api_secret))->setQuery([
                'item_name' => $product['name'],
                'item_price' => $product['price'],
                'command_name' => "Paiement produit  de {$product['name']}",
            ])->setCustomeField([
                'item_id' => $product['id'],
            'time_command' => time(),
            ])
                ->setTestMode(false)
                ->setCurrency("XOF")
                ->setRefCommand(uniqid())
                ->setNotificationUrl([
                    'ipn_url' => $base_url.'/paiement-ipn', //only https
                    'success_url' => $base_url.'/boutiques?state=success&id='.$product['id'],
                    'cancel_url' =>  $base_url.'/boutiques?state=cancel&id='.$product['id']
                ])->send();

        return $jsonResponse;
        // die($jsonResponse);
    }
}
