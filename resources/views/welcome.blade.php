<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Produits - Boutique</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- PayTech CSS and JS -->
    <link rel="stylesheet" href="https://paytech.sn/cdn/paytech.min.css">
    <script src="https://paytech.sn/cdn/paytech.min.js"></script>

</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Nos produits</h1>
        <div class="row">
            @foreach ($products as $product)
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <img src="{{ $product['image'] }}" class="card-img-top" alt="{{ $product['name'] }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product['name'] }}</h5>
                        <p class="card-text">{{ $product['description'] }}</p>
                        <p class="card-text"><strong>{{ number_format($product['price'], 0, ',', ' ') }} FCFA</strong></p>
                        <button class="buy" onclick="buy(this)" data-item-id={{$product['id']}} class="btn btn-primary btn-sm">Acheter</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <script>
        //or directly use payment/token url if already fetched
          function buy() {
            (new PayTech({ })).withOption({
                    tokenUrl           :   'https://paytech.sn/payment/checkout/405gzpplwambq1e',

                    prensentationMode   :   PayTech.OPEN_IN_POPUP,

                }).send();
          }

        </script>

        <script>

        //or use sdk to fetch payment url
            function buy(btn) {
                (new PayTech({
                    some_post_data_1          :   2, //will be sent to paiement.php page
                    some_post_data_3          :   4,
                })).withOption({
                    requestTokenUrl           :   {{route('paiment.abonnement')}},
                    method              :   'POST',
                    headers             :   {
                        "Accept"          :    "text/html"
                    },
                    prensentationMode   :   PayTech.OPEN_IN_POPUP,
                    willGetToken        :   function () {

                    },
                    didGetToken         : function (token, redirectUrl) {

                    },
                    didReceiveError: function (error) {

                    },
                    didReceiveNonSuccessResponse: function (jsonResponse) {

                    }
                }).send();

                //.send params are optional
            }
        </script>
</body>
</html>
