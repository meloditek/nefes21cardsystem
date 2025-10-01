<?php


require_once 'db.php'; // db.php yolunu projenin dizinine göre ayarla


// URL'den deck id al
$deck = isset($_GET['deck']) ? $_GET['deck'] : '501001';

// Kart seti için buton rengini çek
$stmtDeck = $pdo->prepare("SELECT button_color FROM cards WHERE deck_id=? LIMIT 1");
$stmtDeck->execute([$deck]);
$deckInfo = $stmtDeck->fetch(PDO::FETCH_ASSOC);
$buttonColor = $deckInfo ? $deckInfo['button_color'] : '#10b981'; // varsayılan yeşil

// Deck notlarını çek (rastgele 9 tane)
$stmt = $pdo->prepare("SELECT id, message, color, font_size FROM card_notes WHERE deck_id=? ORDER BY RAND() LIMIT 9");
$stmt->execute([$deck]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kart Seçme Sistemi</title>
    <style>
        body {
            margin: 0;
            background: #1e272e;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            overflow-x: hidden;
        }

        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            pointer-events: none;
            z-index: -1;
        }

        .card-container {
    display: flex;
    justify-content: center;
    align-items: center;
    perspective: 3500px; /* 2000px'ten 3500px'e yükselttik (Daha geniş görüş alanı) */
    position: relative;
    width: 100%;
    /* Kartların üst üste yığılmasını önlemek için height değerini sabit bırakın */
    height: 500px; 
}
/* ... Satır 93 */
.card {
    width: 220px;
    height: 352px;
    transform-style: preserve-3d;
    transition: transform 0.6s ease, opacity 0.5s ease;
    cursor: pointer;
    opacity: 0.9; /* 0.7'den 0.9'a yükseltildi */
    margin: 0 -2px; /* -5px'ten -2px'e düşürüldü (Kartları biraz ayırdık) */
}

        .card-inner {
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 0.6s ease;
            position: relative;
        }

        .card.flipped .card-inner {
            transform: rotateY(180deg);
        }

        .card-front,
        .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 12px;
            overflow: hidden;
        }

        .card-front img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* cover yerine CONTAIN yaptık */
}

        .card-back {
            background: no-repeat center center/contain;
            transform: rotateY(180deg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .card-back p {
            font-weight: bold;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            flex: 1;
        }

        .comment-btn {
    padding: 8px 14px;
    margin-top: -15px;
    border: none;
    border-radius: 10px;
    background-color: <?php echo $buttonColor; ?>;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

        .comment-btn:hover {
            opacity: 0.85;
        }

        .shuffle-btn {
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 20px;
        }

        .shuffle-btn:hover {
            background: #0056b3;
        }

        @keyframes fadeOpenAnimation {
            0% {
                transform: rotateY(0deg) scale(0.9);
                opacity: 0.4;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                transform: rotateY(0deg) scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="background-overlay" style="background-image:url('deck/<?php echo $deck; ?>/background.webp');"></div>

    <div class="card-container" id="cardContainer"></div>
    <button class="shuffle-btn" onclick="shuffleCards()">Kartları Karıştır</button>

    <script>
        const deck = "<?php echo $deck; ?>";
        const notes = <?php echo json_encode($notes); ?>;
        const cardContainer = document.getElementById("cardContainer");
        let cards = [];

        function createCards() {
            cardContainer.innerHTML = "";
            cards = [];
            for (let i = 0; i < notes.length; i++) {
                const note = notes[i];

                const card = document.createElement("div");
                card.classList.add("card");

                const inner = document.createElement("div");
                inner.classList.add("card-inner");

                // Front
                const front = document.createElement("div");
                front.classList.add("card-front");
                const imgFront = document.createElement("img");
                imgFront.src = `deck/${deck}/back.webp`;
                front.appendChild(imgFront);

                // Back
                const back = document.createElement("div");
                back.classList.add("card-back");
                back.style.backgroundImage = `url('deck/${deck}/front.webp')`;

                const backP = document.createElement("p");
                backP.innerText = note.message;
                backP.style.color = note.color;
                backP.style.fontSize = note.font_size;
                backP.style.fontWeight = 'bold';
                backP.style.display = 'flex';
                backP.style.justifyContent = 'center';
                backP.style.alignItems = 'center';

                // Kartı Yorumla butonu
                const commentBtn = document.createElement("button");
                commentBtn.innerText = "Kartı Yorumla";
                commentBtn.className = "comment-btn";
                commentBtn.addEventListener("click", (e) => {
                    e.stopPropagation(); // kart açılmasını engelle
                    window.location.href = `view_comments.php?note_id=${note.id}`;
                });

                back.appendChild(backP);
                back.appendChild(commentBtn);

                inner.appendChild(front);
                inner.appendChild(back);
                card.appendChild(inner);

                card.addEventListener("click", () => {
                    card.classList.toggle("flipped");
                    moveToCenter(card);
                });

                cardContainer.appendChild(card);
                cards.push(card);
            }
            setInitialPositions();
        }

        function setInitialPositions() {
    const centerIndex = Math.floor(cards.length / 2);
    cards.forEach((card, i) => {
        const offset = i - centerIndex;
        if (offset < 0) {
            // DEĞİŞTİRİLDİ: -450px -> -250px, 55deg -> 40deg
            card.style.transform = `translateX(${offset * -10}px) translateZ(${Math.abs(offset) * -250}px) rotateY(40deg) scale(1)`;
            card.style.opacity = 0.9;
            card.style.zIndex = i;
        } else if (offset === 0) {
            // DEĞİŞTİRİLDİ: 160px -> 100px
            card.style.transform = `translateX(0px) translateZ(100px) rotateY(0deg) scale(1.15)`;
            card.style.opacity = 1;
            card.style.zIndex = 100;
        } else {
            // DEĞİŞTİRİLDİ: -450px -> -250px, -55deg -> -40deg
            card.style.transform = `translateX(${offset * -10}px) translateZ(${Math.abs(offset) * -250}px) rotateY(-40deg) scale(1)`;
            card.style.opacity = 0.9;
            card.style.zIndex = cards.length - i;
        }
    });
}

        function moveToCenter(selectedCard) {
            const centerIndex = cards.indexOf(selectedCard);
            cards.forEach((card, i) => {
                const offset = i - centerIndex;
                if (card === selectedCard) {
                    card.style.transform = `translateX(0px) translateZ(180px) rotateY(0deg) scale(1.25)`;
                    card.style.opacity = 1;
                    card.style.zIndex = 200;
                } else if (offset < 0) {
                    card.style.transform = `translateX(${offset * -10}px) translateZ(${Math.abs(offset) * -20}px) rotateY(50deg) scale(1)`;
                    card.style.opacity = 0.7;
                    card.style.zIndex = i;
                } else {
                    card.style.transform = `translateX(${offset * -10}px) translateZ(${Math.abs(offset) * -20}px) rotateY(-50deg) scale(1)`;
                    card.style.opacity = 0.7;
                    card.style.zIndex = cards.length - i;
                }
            });
        }

        function shuffleCards() {
    cards.forEach(card => {
        const back = card.querySelector(".card-back p");
        const randomNote = notes[Math.floor(Math.random() * notes.length)];
        back.innerText = randomNote.message;
        back.style.color = randomNote.color;
        back.style.fontSize = randomNote.font_size;
        back.style.fontWeight = 'bold';
        
        // ✅ Buton güncelle
        const btn = card.querySelector(".comment-btn");
        btn.onclick = (e) => {
            e.stopPropagation();
            window.location.href = `view_comments.php?note_id=${randomNote.id}`;
        };

        card.style.animation = "fadeOpenAnimation 0.5s ease";
        card.addEventListener("animationend", () => { card.style.animation = ""; }, { once: true });
    });
    setInitialPositions();
}


        createCards();
    </script>
</body>

</html>




