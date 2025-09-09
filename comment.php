<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$note_id = isset($_GET['note_id']) ? intval($_GET['note_id']) : 0;
if ($note_id <= 0)
    die("Geçersiz not seçildi.");

$noteStmt = $pdo->prepare("SELECT * FROM card_notes WHERE id = ?");
$noteStmt->execute([$note_id]);
$note = $noteStmt->fetch(PDO::FETCH_ASSOC);
if (!$note)
    die("Not bulunamadı.");

// --- Yorum ekleme / güncelleme / silme ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Silme
    if (isset($_POST['delete_comment'])) {
        $deleteStmt = $pdo->prepare("DELETE FROM comments WHERE id=? AND note_id=?");
        $deleteStmt->execute([$_POST['delete_comment'], $note_id]);
    }

    // Yeni yorum ekleme (sadece 1 tane olacak)
    if (isset($_POST['new_comment']) && trim($_POST['new_comment']) !== '') {
        $comment_text = trim($_POST['new_comment']);
        // Önce bu nota ait yorum var mı?
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE note_id=?");
        $checkStmt->execute([$note_id]);
        $count = $checkStmt->fetchColumn();

        if ($count == 0) {
            $insertStmt = $pdo->prepare("INSERT INTO comments (note_id, comment_text) VALUES (?, ?)");
            $insertStmt->execute([$note_id, $comment_text]);
        }
    }

    // Yorum güncelleme
    if (isset($_POST['edit_comment']) && is_array($_POST['edit_comment'])) {
        foreach ($_POST['edit_comment'] as $comment_id => $text) {
            $text = trim($text);
            if ($text !== '') {
                $updateStmt = $pdo->prepare("UPDATE comments SET comment_text=? WHERE id=? AND note_id=?");
                $updateStmt->execute([$text, $comment_id, $note_id]);
            }
        }
    }

    header("Location: comment.php?note_id=$note_id");
    exit;
}

// mevcut yorumlar
$commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE id = ? ORDER BY created_at DESC");
$commentsStmt->execute([$note_id]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Kart Yorumu - <?php echo htmlspecialchars($note['message']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* {box-sizing: border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#1f272e;color:#e5e7eb;}
.container{max-width:900px;margin:40px auto;padding:20px;}
.note-box{background:#374151;color:white;padding:15px;border-radius:12px;font-size:1.1rem;font-weight:600;text-align:center;box-shadow:0 4px 10px rgba(0,0,0,0.2);margin-bottom:25px;}
.comment-list{display:flex;flex-direction:column;gap:20px;margin-bottom:30px;}
.comment-card{background:#1f2936;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.25);position:relative;}
.comment-card textarea{width:100%;height:120px;padding:12px;border-radius:10px;border:1px solid #4b5563;font-size:16px;resize:none;background:#f9fafb;color:#111;font-family:'Inter',sans-serif;}
.comment-card small{position:absolute;bottom:12px;right:16px;font-size:12px;color:#9ca3af;}
button{padding:8px 14px;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;transition:0.3s;}
button.update{background:#10b981;color:white;}
button.update:hover{background:#059669;}
button.add{background:#3b82f6;color:white;margin-top:10px;}
button.add:hover{background:#2563eb;}
button.delete{background:#ef4444;color:white;margin-top:10px;}
button.delete:hover{background:#dc2626;}
.back-btn{display:inline-block;margin-top:20px;text-decoration:none;background:#6b7280;color:white;padding:10px 16px;border-radius:8px;transition:0.3s;}
.back-btn:hover{background:#4b5563;}
textarea#new_comment{height:140px;font-size:16px;padding:14px;border-radius:10px;border:1px solid #4b5563;background:#f9fafb;color:#111;}
h3{margin-bottom:12px;color:#f3f4f6;}
</style>
</head>
<body>
<div class="container">
<h2 style="text-align:center;margin-bottom:25px;">Kart Yorumu</h2>
<div class="note-box"><?php echo htmlspecialchars($note['message']); ?></div>

<h3>Mevcut Yorum</h3>
<form method="post">
<div class="comment-list">
<?php foreach ($comments as $c): ?>
                <div class="comment-card">
                    <textarea name="edit_comment[<?php echo $c['id']; ?>]"><?php echo htmlspecialchars($c['comment_text']); ?></textarea>
                    <small><?php echo $c['created_at']; ?></small>
                    <button type="submit" name="delete_comment" value="<?php echo $c['id']; ?>" class="delete">Sil</button>
                </div>
<?php endforeach; ?>
</div>
<?php if ($comments): ?>
                <button type="submit" class="update">Yorumu Güncelle</button>
<?php endif; ?>
</form>

<?php if (!$comments): ?>
            <h3>Yeni Yorum Ekle</h3>
            <form method="post">
                <textarea id="new_comment" name="new_comment" placeholder="Yeni yorumunuzu buraya yazın..."></textarea>
                <button type="submit" class="add">Yorum Ekle</button>
            </form>
<?php endif; ?>

<a href="view_comments.php?note_id=<?php echo $note_id; ?>" class="back-btn">👁 Yorumları Görüntüle</a>
<a href="edit_deck.php?deck_id=<?php echo $note['deck_id']; ?>" class="back-btn">← Geri</a>
</div>
</body>
</html>
