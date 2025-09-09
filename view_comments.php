<?php
require_once 'db.php'; // db.php yolunu projenin dizinine göre ayarla

// note_id al
$note_id = isset($_GET['note_id']) ? intval($_GET['note_id']) : 0;
if ($note_id <= 0)
    die("Geçersiz not seçildi.");

// not bilgisi
$noteStmt = $pdo->prepare("SELECT * FROM card_notes WHERE id = ?");
$noteStmt->execute([$note_id]);
$note = $noteStmt->fetch(PDO::FETCH_ASSOC);
if (!$note)
    die("Not bulunamadı.");

// yorumları al
$commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE id=? ORDER BY created_at ASC");
$commentsStmt->execute([$note_id]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Güvenli HTML fonksiyonu (izin verilen etiketler)
function sanitizeHtml($html)
{
    return strip_tags($html, '<p><br><strong><em><ul><li><ol><b><i>');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($note['message']); ?> - Kart Yorumları</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
body {font-family:'Inter',sans-serif;background:#fff;color:#111;margin:0;padding:0;line-height:1.7;}
.container {max-width:900px;margin:40px auto;padding:20px;}
h1,h2,h3 {margin-bottom:15px;color:#111;}
h1 {font-size:1.6rem;display:flex;align-items:center;gap:8px;}
h2 {font-size:1.3rem;margin-top:30px;}
.note-box {background:#f3f4f6;padding:12px 15px;border-radius:8px;font-weight:600;font-size:1rem;margin-bottom:25px;}
.comment-card {background:#f9fafb;padding:15px 20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:15px;}
.comment-card p {margin:0;font-size:0.95rem;line-height:1.6;}
.back-btn {display:inline-block;margin-top:20px;text-decoration:none;background:#2563eb;color:white;padding:10px 16px;border-radius:6px;transition:0.3s;cursor:pointer;}
.back-btn:hover {background:#1e40af;}
</style>
</head>
<body>
<div class="container">
    <h1>🌱 <?php echo htmlspecialchars($note['message']); ?></h1>

    <h2>Kart Yorumu</h2>
    <?php if ($comments): ?>
                                <?php foreach ($comments as $c): ?>
                                                            <div class="comment-card">
                                                                <p><?php echo sanitizeHtml($c['comment_text']); ?></p>
                                                            </div>
                                <?php endforeach; ?>
    <?php else: ?>
                                <p>Henüz yorum eklenmemiş.</p>
    <?php endif; ?>

    <button class="back-btn" onclick="goBack('<?php echo $note['deck_id']; ?>')">← Geri</button>
</div>

<script>
function goBack(deckId) {
    window.location.replace("nefes.php?deck=" + deckId);
}
</script>
</body>
</html>
