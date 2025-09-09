<?php

// Deck klasöründeki tüm alt klasörleri al
$folders = glob(__DIR__ . "/deck/*", GLOB_ONLYDIR);
sort($folders); // Sıralı gösterim
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kart Galeri</title>
    <style>
        body {
            margin: 0;
            background: #1e272e;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            width: 95%;
            max-width: 1200px;
            padding: 20px;
            box-sizing: border-box;
        }

        .card {
            position: relative;
            width: 100%;
            padding-top: 43.75%;
            overflow: hidden;
            border-radius: 15px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, box-shadow 0.3s, opacity 0.3s;
        }

        .card img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body>
    <div class="container">
        <?php foreach ($folders as $folder):
            $deck = basename($folder);
            $showcase = "deck/$deck/showcase.webp";
            ?>
                    <div class="card" onclick="openDeck('<?= $deck ?>')">
                        <img src="<?= $showcase ?>" alt="Deck <?= $deck ?>">
                    </div>
        <?php endforeach; ?>
    </div>

    <script>
        function openDeck(deckId) {
            window.location.href = `nefes.php?deck=${deckId}`;
        }
    </script>
</body>

</html>