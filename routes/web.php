<?php

use App\Http\Controllers\PaytechController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'welcome'])->name('welcome');
Route::post('/add-to-cart', function (Request $request) {
    $productId = $request->input('product_id');

    $products = [
        1 => ['id' => 1, 'name' => 'Téléphone', 'price' => 100000],
        2 => ['id' => 2, 'name' => 'Ordinateur', 'price' => 350000],
        3 => ['id' => 3, 'name' => 'Casque audio', 'price' => 25000],
    ];

    $product = $products[$productId] ?? null;

    if (!$product) {
        return redirect('/')->with('error', 'Produit introuvable.');
    }

    $cart = session()->get('cart', []);
    $cart[$productId] = $product;
    session()->put('cart', $cart);

    return redirect('/')->with('success', 'Produit ajouté au panier.');
})->name('add.to.cart');

Route::get('/checkout', function () {
    $cart = session()->get('cart', []);
    return view('checkout', compact('cart'));
})->name('checkout');

Route::post('/checkout/pay', function () {
    // Simulation du paiement
    session()->forget('cart'); // Vide le panier après paiement
    return redirect('/')->with('success', 'Paiement effectué avec succès !');
})->name('checkout.pay');


    //Paiement
    Route::post('/abonnement/paiement', [PaytechController::class, 'paiement'])->name('paiment.abonnement');
