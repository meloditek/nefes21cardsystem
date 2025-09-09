<?php

require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// deck_id al
if (!isset($_GET['deck_id'])) {
    header("Location: admin.php");
    exit;
}
$deck_id = $_GET['deck_id'];

// --- Deck verilerini al ---
$stmt = $pdo->prepare("SELECT * FROM cards WHERE deck_id=?");
$stmt->execute([$deck_id]);
$deck = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$deck) {
    header("Location: admin.php");
    exit;
}

// --- Mevcut notları al ---
$notesStmt = $pdo->prepare("SELECT * FROM card_notes WHERE deck_id=? ORDER BY id ASC");
$notesStmt->execute([$deck_id]);
$notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Silme işlemi ---
if (isset($_POST['delete'])) {
    $dir = __DIR__ . "/deck/$deck_id/";
    if (is_dir($dir)) {
        array_map('unlink', glob("$dir/*.*"));
        rmdir($dir);
    }
    $stmt = $pdo->prepare("DELETE FROM cards WHERE deck_id=?");
    $stmt->execute([$deck_id]);
    header("Location: admin.php");
    exit;
}

// --- Güncelleme işlemi ---
if (isset($_POST['save'])) {
    $uploadDir = __DIR__ . "/deck/$deck_id/";
    $files = ['front', 'back', 'background', 'showcase'];
    foreach ($files as $f) {
        if (!empty($_FILES[$f]['tmp_name'])) {
            move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . "$f.webp");
            $deck[$f] = "deck/$deck_id/$f.webp"; // sadece değişen dosya güncellenir
        }
    }
    // Buton rengi de güncellensin
    $buttonColor = $_POST['button_color'] ?? '#10b981';

    $stmt = $pdo->prepare("UPDATE cards SET front=?, back=?, background=?, showcase=?, button_color=? WHERE deck_id=?");
    $stmt->execute([$deck['front'], $deck['back'], $deck['background'], $deck['showcase'], $buttonColor, $deck_id]);

    header("Location: edit_deck.php?deck_id=$deck_id");
    exit;
}

// --- Not kaydetme işlemi ---
if (isset($_POST['save_notes'])) {
    // Silinen notlar
    $delete_ids = $_POST['delete_note'] ?? [];
    if (!empty($delete_ids)) {
        $in = implode(',', array_map('intval', $delete_ids));
        $pdo->exec("DELETE FROM card_notes WHERE id IN ($in)");
    }

    // Mevcut notları güncelle
    if (!empty($_POST['note_id'])) {
        foreach ($_POST['note_id'] as $index => $nid) {
            $msg = $_POST['message'][$index];
            $color = $_POST['color'][$index];
            $size = $_POST['font_size'][$index];
            $stmt = $pdo->prepare("UPDATE card_notes SET message=?, color=?, font_size=? WHERE id=?");
            $stmt->execute([$msg, $color, $size, $nid]);
        }
    }

    // Yeni not ekleme
    if (!empty($_POST['new_message'])) {
        foreach ($_POST['new_message'] as $index => $msg) {
            if (trim($msg) === '')
                continue;
            $color = $_POST['new_color'][$index] ?? '#ffffff';
            $size = $_POST['new_font_size'][$index] ?? '20px';
            $stmt = $pdo->prepare("INSERT INTO card_notes (deck_id, message, color, font_size) VALUES (?,?,?,?)");
            $stmt->execute([$deck_id, $msg, $color, $size]);
        }
    }

    header("Location: edit_deck.php?deck_id=$deck_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Deck Düzenle: <?php echo $deck_id; ?></title>
    <style>
        body {
            background: #1e272e;
            color: #ecf0f1;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            align-items: start;
        }
        .card-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .card-box img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            background: #111;
            padding: 5px;
        }
        input[type=file], input[type=text], input[type=color] {
            padding: 8px;
            border-radius: 6px;
            border: none;
            background: #34495e;
            color: #fff;
            cursor: pointer;
            width: 100%;
        }
        button {
            padding: 10px 14px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button.save { background: #27ae60; color: white; }
        button.save:hover { background: #2ecc71; }
        button.delete { background: #e74c3c; color: white; }
        button.delete:hover { background: #c0392b; }
        button.back { background: #3498db; color: white; margin-bottom: 10px; }
        button.back:hover { background: #2980b9; }
        .actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #34495e;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background: #2c3e50;
        }
        .comment-btn {
            background: #f39c12;
            color: white;
            font-size: 12px;
        }
        .comment-btn:hover {
            background: #d68910;
        }
    </style>
    <script>
        let changed = false;
        function markChange() { changed = true; }
        window.addEventListener("beforeunload", function (e) {
            if (changed) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll("button.save").forEach(btn => {
                btn.addEventListener("click", () => { changed = false; });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Deck Düzenle: <?php echo $deck_id; ?></h2>

        <div class="actions">
            <button type="button" class="back" onclick="window.location.href='admin.php'">⬅ Geri Dön</button>
        </div>

        <!-- Deck görselleri -->
<form method="POST" enctype="multipart/form-data">
    <div class="grid">
        <?php foreach (['front' => 'Front', 'back' => 'Back', 'background' => 'Background', 'showcase' => 'Showcase'] as $f => $label): ?>
                            <div class="card-box">
                                <label><?php echo $label; ?></label>
                                <img src="<?php echo $deck[$f]; ?>" alt="<?php echo $label; ?>">
                                <input type="file" name="<?php echo $f; ?>" accept="image/webp" onchange="markChange()">
                            </div>
        <?php endforeach; ?>
    </div>

    <!-- Yeni: Buton Rengi -->
    <div style="margin-top:20px;">
        <label for="button_color">Kartı Yorumla Buton Rengi:</label>
        <input type="color" id="button_color" name="button_color"
               value="<?php echo htmlspecialchars($deck['button_color'] ?? '#10b981'); ?>"
               onchange="markChange()">
    </div>

    <div class="actions">
        <button type="submit" name="save" class="save">Görselleri Kaydet</button>
        <button type="submit" name="delete" class="delete" onclick="return confirm('Deck tamamen silinecek. Emin misiniz?')">Decki Sil</button>
    </div>
</form>

        <!-- Deck Notları -->
        <h3>Deck Notları</h3>
        <form method="POST">
            <table>
                <tr>
                    <th>Mesaj</th>
                    <th>Renk</th>
                    <th>Boyut</th>
                    <th>Sil</th>
                    <th>Yorum</th>
                </tr>
                <?php foreach ($notes as $n): ?>
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="note_id[]" value="<?php echo $n['id']; ?>">
                                                    <input type="text" name="message[]" value="<?php echo htmlspecialchars($n['message']); ?>" onchange="markChange()">
                                                </td>
                                                <td><input type="color" name="color[]" value="<?php echo $n['color']; ?>" onchange="markChange()"></td>
                                                <td><input type="text" name="font_size[]" value="<?php echo $n['font_size']; ?>" style="width:60px;" onchange="markChange()"></td>
                                                <td><input type="checkbox" name="delete_note[]" value="<?php echo $n['id']; ?>" onchange="markChange()"></td>
                                                <td>
                                                    <button type="button" class="comment-btn" onclick="window.location.href='comment.php?note_id=<?php echo $n['id']; ?>'">
                                                        Kartı Yorumla
                                                    </button>
                                                </td>
                                            </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="new_message[]" placeholder="Yeni not" onchange="markChange()"></td>
                    <td><input type="color" name="new_color[]" value="#ffffff" onchange="markChange()"></td>
                    <td><input type="text" name="new_font_size[]" value="20px" style="width:60px;" onchange="markChange()"></td>
                    <td>—</td>
                    <td>—</td>
                </tr>
            </table>
            <button type="submit" name="save_notes" class="save" style="margin-top:10px;">Notları Kaydet</button>
        </form>
    </div>
</body>
</html>
