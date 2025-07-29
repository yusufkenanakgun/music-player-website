<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$uid = $_SESSION['uid'];
$uname = $_SESSION['name'] ?? "User";

$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

if (isset($_GET['song_id'])) {
    $song_id = intval($_GET['song_id']);

    $stmt = $conn->prepare("
        SELECT S.song_id, S.title, S.genre, S.duration, S.image, A.album_id, A.title AS album_title, AR.name AS artist_name
        FROM SONGS S
        JOIN ALBUMS A ON S.album_id = A.album_id
        JOIN ARTISTS AR ON A.artist_id = AR.artist_id
        WHERE S.song_id = ?
    ");
    $stmt->bind_param("i", $song_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $song = $result->fetch_assoc();

    if (!$song) {
        die("❌ Song not found.");
    }

    // Oynatılan şarkıyı session'a ve geçmişe kaydet
    $_SESSION['now_playing'] = [
        "song_id"   => $song["song_id"],
        "title"     => $song["title"],
        "image"     => $song["image"]
    ];

    // Eskiyi silip yenisini ekle
    $conn->query("DELETE FROM PLAY_HISTORY WHERE user_id = $uid AND song_id = {$song['song_id']}");
    $stmt2 = $conn->prepare("INSERT INTO PLAY_HISTORY (user_id, song_id, playtime) VALUES (?, ?, NOW())");
    $stmt2->bind_param("ii", $uid, $song["song_id"]);
    $stmt2->execute();
    $stmt2->close();
} else {
    $song = $_SESSION['now_playing'] ?? null;
    if (!$song) {
        die("No song selected.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Now Playing - <?= htmlspecialchars($song["title"]) ?></title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #1e1e2f;
      color: #f0f0f0;
      margin: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 60px 20px 80px;
    }

    .player {
      max-width: 480px;
      width: 100%;
      background: #2d2d3a;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.4);
      text-align: center;
    }

    .player img {
      width: 100%;
      height: 240px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .title {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .meta {
      color: #ccc;
      font-size: 14px;
      margin: 4px 0;
    }

    .meta a {
      color: #1db954;
      text-decoration: none;
    }

    .meta a:hover {
      text-decoration: underline;
    }

    .status {
      margin-top: 20px;
      font-size: 16px;
      font-weight: bold;
      color: #1db954;
      display: none;
    }

    .buttons {
      margin-top: 15px;
    }

    .play-btn {
      background: #1db954;
      border: none;
      padding: 12px 26px;
      font-size: 16px;
      color: white;
      border-radius: 6px;
      cursor: pointer;
    }

    .play-btn:hover {
      background: #14833b;
    }

    .nav {
      margin-top: 25px;
    }

    .nav a {
      color: #ffcc00;
      text-decoration: none;
      margin: 0 10px;
      font-size: 15px;
    }

    .nav a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="player">
    <img src="<?= htmlspecialchars($song["image"]) ?>" alt="Album Cover">
    <div class="title"><?= htmlspecialchars($song["title"]) ?></div>
    <div class="meta">Artist: <?= htmlspecialchars($song["artist_name"] ?? "Unknown") ?></div>
    <div class="meta">
      Album:
      <a href="albumpage.php?album_id=<?= $song["album_id"] ?>">
        <?= htmlspecialchars($song["album_title"] ?? "-") ?>
      </a>
    </div>
    <div class="meta">Genre: <?= htmlspecialchars($song["genre"]) ?></div>
    <div class="meta">Duration: <?= htmlspecialchars($song["duration"]) ?></div>

    <div class="buttons">
      <button class="play-btn" onclick="showPlayed()">▶ Play</button>
    </div>

    <div class="status" id="statusText">🎵 Played!</div>

    <div class="nav">
      <a href="homepage.php">&larr; Back to Homepage</a>
    </div>
  </div>

  <script>
    function showPlayed() {
      document.getElementById("statusText").style.display = "block";
    }
  </script>

</body>
</html>
