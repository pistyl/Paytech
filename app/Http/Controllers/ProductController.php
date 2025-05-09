<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function welcome()
    {
        // Produits simulés
        $products = [
            [
                'id' => 1,
                'name' => 'Téléphone Samsung Galaxy',
                'description' => 'Un smartphone puissant avec de belles fonctionnalités.',
                'price' => 150000,
                'image' => 'https://nova.sn/34321-medium_default/samsung-galaxy-z-flip-6-12-go-de-ram-memoire-256-go.jpg',
            ],
            [
                'id' => 2,
                'name' => 'Ordinateur portable Dell',
                'description' => 'Idéal pour le travail et les études.',
                'price' => 300000,
                'image' => 'https://syllart-shop.com/19933-large_default/ordinateur-portable-dell-latitude-intel-core-i5-6300u-ram-4go-disque-dur-500go-14-windows-10-famille-64bits.jpg',
            ],
            [
                'id' => 3,
                'name' => 'Casque Bluetooth JBL',
                'description' => 'Profitez d’un son immersif sans fil.',
                'price' => 50000,
                'image' => 'https://devitech-senegal.com/storage/casque-jbl-tune-510bt-800x800.jpg',
            ],
        ];

        return view('welcome', compact('products'));
    }
}
