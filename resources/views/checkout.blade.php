<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body { font-family: sans-serif; padding: 30px; }
        .product { border-bottom: 1px solid #ccc; padding: 10px 0; }
        .product:last-child { border: none; }
        .total { margin-top: 20px; font-size: 18px; }
        .pay-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #1e90ff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h1>Checkout</h1>

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if(count($cart) === 0)
        <p>Votre panier est vide.</p>
    @else
        @foreach ($cart as $product)
            <div class="product">
                <h2>{{ $product['name'] }}</h2>
                <p>{{ $product['description'] ?? '' }}</p>
                <p><strong>Prix :</strong> {{ number_format($product['price'], 0, ',', ' ') }} FCFA</p>
            </div>
        @endforeach

        <div class="total">
            <strong>Total :</strong>
            {{ number_format(collect($cart)->sum('price'), 0, ',', ' ') }} FCFA
        </div>

        <form method="POST" action="{{ route('checkout.pay') }}">
            @csrf
            <button class="pay-btn">Payer</button>
        </form>
    @endif

    <p><a href="/">← Retour à l'accueil</a></p>
</body>
</html>
