<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- Silme işlemi ---
if (isset($_GET['delete'])) {
    $deck_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM cards WHERE deck_id=?");
    $stmt->execute([$deck_id]);
    $dir = __DIR__ . "/deck/$deck_id/";
    if (is_dir($dir)) {
        array_map('unlink', glob("$dir/*.*"));
        rmdir($dir);
    }
    header("Location: admin.php");
    exit;
}

// --- Yeni deck ekleme ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $folders = glob(__DIR__ . "/deck/*", GLOB_ONLYDIR);
    $maxDeck = 501000;
    foreach ($folders as $folder) {
        $name = basename($folder);
        if (is_numeric($name) && $name > $maxDeck)
            $maxDeck = (int) $name;
    }
    $newDeck = $maxDeck + 1;
    $uploadDir = __DIR__ . "/deck/$newDeck/";
    mkdir($uploadDir, 0777, true);

    $files = ['front', 'back', 'background', 'showcase'];
    foreach ($files as $f) {
        move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . "$f.webp");
    }

    $stmt = $pdo->prepare("INSERT INTO cards (deck_id, front, back, background, showcase) VALUES (?,?,?,?,?)");
    $stmt->execute([
        $newDeck,
        "deck/$newDeck/front.webp",
        "deck/$newDeck/back.webp",
        "deck/$newDeck/background.webp",
        "deck/$newDeck/showcase.webp"
    ]);
    header("Location: admin.php");
    exit;
}

// --- Tüm deckleri çek ve eksik veritabanı kayıtlarını ekle ---
$dbCards = $pdo->query("SELECT * FROM cards")->fetchAll(PDO::FETCH_ASSOC);
$dbDecks = [];
foreach ($dbCards as $c) {
    $dbDecks[$c['deck_id']] = $c;
}

// deck klasörlerini tara
$folders = glob(__DIR__ . "/deck/*", GLOB_ONLYDIR);
$allDecks = [];
foreach ($folders as $f) {
    $deck_id = basename($f);
    if (!is_numeric($deck_id))
        continue;

    if (isset($dbDecks[$deck_id])) {
        $allDecks[$deck_id] = $dbDecks[$deck_id];
    } else {
        // veritabanında yoksa otomatik ekle
        $front = "deck/$deck_id/front.webp";
        $back = "deck/$deck_id/back.webp";
        $background = "deck/$deck_id/background.webp";
        $showcase = "deck/$deck_id/showcase.webp";

        $stmt = $pdo->prepare("INSERT INTO cards (deck_id, front, back, background, showcase) VALUES (?,?,?,?,?)");
        $stmt->execute([$deck_id, $front, $back, $background, $showcase]);

        $allDecks[$deck_id] = [
            'deck_id' => $deck_id,
            'front' => $front,
            'back' => $back,
            'background' => $background,
            'showcase' => $showcase
        ];
    }
}

// deckleri sırala
ksort($allDecks);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        body {
            background: #1e272e;
            color: #ecf0f1;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        h2 {
            margin-bottom: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        /* Oturumu kapat butonu */
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Yeni deck form */
        form.add-deck {
            background: #2c3e50;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .add-deck label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .add-deck input[type=file] {
            padding: 10px;
            border-radius: 5px;
            border: none;
            background: #34495e;
            color: #fff;
            cursor: pointer;
            width: 100%;
        }

        .add-deck button {
            grid-column: span 2;
            background: #27ae60;
            color: white;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .add-deck button:hover {
            background: #2ecc71;
        }

        /* Deck grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .card-block {
            background: #2c3e50;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            position: relative;
            transition: transform 0.3s;
        }

        .card-block:hover {
            transform: translateY(-5px);
        }

        .card-block img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        button.edit,
        button.delete {
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            width: 48%;
            margin: 2px 1%;
        }

        button.edit {
            background: #3498db;
            color: white;
        }

        button.edit:hover {
            background: #2980b9;
        }

        button.delete {
            background: #e74c3c;
            color: white;
        }

        button.delete:hover {
            background: #c0392b;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Oturumu Kapat Butonu -->
        <button class="logout-btn" onclick="window.location.href='logout.php'">Oturumu Kapat</button>

        <h2>Yeni Deck Ekle</h2>
        <form method="POST" enctype="multipart/form-data" class="add-deck">
            <?php foreach (['front' => 'Front', 'back' => 'Back', 'background' => 'Background', 'showcase' => 'Showcase'] as $f => $label): ?>
                    <div>
                        <label><?php echo $label; ?> Dosya</label>
                        <input type="file" name="<?php echo $f; ?>" accept="image/webp" required>
                    </div>
            <?php endforeach; ?>
            <button type="submit" name="add">Deck Ekle</button>
        </form>

        <h2>Mevcut Deckler</h2>
        <div class="grid">
            <?php foreach ($allDecks as $c): ?>
                    <div class="card-block">
                        <img src="<?php echo $c['showcase']; ?>" alt="Deck <?php echo $c['deck_id']; ?>">
                        <button class="edit"
                            onclick="window.location.href='edit_deck.php?deck_id=<?php echo $c['deck_id']; ?>'">Düzenle</button>
                        <button class="delete"
                            onclick="if(confirm('Decki tamamen silmek istediğine emin misin?')){ window.location.href='?delete=<?php echo $c['deck_id']; ?>'; }">Sil</button>
                    </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>
