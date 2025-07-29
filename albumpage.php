<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$uid = $_SESSION['uid'];
$uname = $_SESSION['name'] ?? "User"; // ✅ HATA DÜZELTİLDİ

if (!isset($_GET['album_id'])) {
    die("Album ID not provided.");
}

$album_id = intval($_GET['album_id']);

$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

// Albüm bilgisi + sanatçı adı
$stmt = $conn->prepare("SELECT AL.*, AR.name AS artist_name 
                        FROM ALBUMS AL
                        JOIN ARTISTS AR ON AL.artist_id = AR.artist_id
                        WHERE AL.album_id = ?");
$stmt->bind_param("i", $album_id);
$stmt->execute();
$album = $stmt->get_result()->fetch_assoc();
if (!$album) die("Album not found.");

// Şarkılar
$songs = $conn->query("
    SELECT * FROM SONGS
    WHERE album_id = $album_id
    ORDER BY title ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($album["title"]) ?> | Album</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #1e1e2f;
      color: #f0f0f0;
      margin: 0;
    }

    header {
      background: #2d2d3a;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      width: 100%;
      top: 0;
      left: 0;
      z-index: 100;
      border-bottom: 1px solid #444;
    }

    header h2 {
      margin: 0;
    }

    .back {
      color: #ffcc00;
      text-decoration: none;
      font-weight: bold;
    }

    .back:hover {
      text-decoration: underline;
    }

    .container {
      padding: 130px 40px 40px;
      max-width: 900px;
      margin: auto;
    }

    .album-info {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      margin-bottom: 40px;
    }

    .album-info img {
      width: 250px;
      height: 250px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    .album-details p {
      margin: 8px 0;
      color: #ccc;
    }

    h3 {
      color: #ffcc00;
      margin-bottom: 20px;
    }

    ul.songs {
      list-style: none;
      padding: 0;
    }

    ul.songs li {
      margin-bottom: 12px;
      background: #333;
      padding: 10px 15px;
      border-radius: 6px;
      transition: background 0.2s;
    }

    ul.songs li:hover {
      background: #444;
    }

    ul.songs a {
      text-decoration: none;
      color: #fff;
      display: flex;
      justify-content: space-between;
    }

    ul.songs a span {
      color: #aaa;
      font-size: 14px;
    }

    @media (max-width: 600px) {
      .container {
        padding: 120px 20px 40px;
      }

      .album-info {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body>

<header>
  <h2><?= htmlspecialchars($album["title"]) ?> 🎵</h2>
  <a class="back" href="homepage.php">&larr; Back to Homepage</a>
</header>

<div class="container">
  <div class="album-info">
    <img src="<?= htmlspecialchars($album["image"]) ?>" alt="album">
    <div class="album-details">
      <p><strong>Artist:</strong> <?= htmlspecialchars($album["artist_name"]) ?></p>
      <p><strong>Genre:</strong> <?= htmlspecialchars($album["genre"]) ?></p>
      <p><strong>Release Date:</strong> <?= htmlspecialchars($album["release_date"]) ?></p>
      <p><strong>Number of Songs:</strong> <?= htmlspecialchars($album["music_number"]) ?></p>
    </div>
  </div>

  <h3>Songs in this Album</h3>
  <ul class="songs">
    <?php while($s = $songs->fetch_assoc()): ?>
      <li>
        <a href="currentmusic.php?song_id=<?= $s['song_id'] ?>">
          <?= htmlspecialchars($s["title"]) ?>
          <span><?= htmlspecialchars($s["duration"]) ?></span>
        </a>
      </li>
    <?php endwhile; ?>
  </ul>
</div>

</body>
</html>
