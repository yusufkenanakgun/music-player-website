<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$uid = $_SESSION['uid'];
$uname = $_SESSION['name'] ?? "User"; // ✅ düzeltildi

if (!isset($_GET["artist_id"])) {
    die("Artist ID not provided.");
}

$artist_id = intval($_GET["artist_id"]);

$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

// Sanatçı bilgisi + ülke
$stmt = $conn->prepare("SELECT A.*, C.country_name 
                        FROM ARTISTS A
                        JOIN COUNTRY C ON A.country_id = C.country_id
                        WHERE artist_id = ?");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$artist = $stmt->get_result()->fetch_assoc();
if (!$artist) die("Artist not found.");

// Son 5 albüm
$albums = $conn->query("
    SELECT * FROM ALBUMS
    WHERE artist_id = $artist_id
    ORDER BY release_date DESC
    LIMIT 5
");

// En çok dinlenen 5 şarkı
$songs = $conn->query("
    SELECT S.*
    FROM SONGS S
    JOIN ALBUMS A ON S.album_id = A.album_id
    WHERE A.artist_id = $artist_id
    ORDER BY S.rank ASC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($artist["name"]) ?> | Artist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

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
      border-bottom: 1px solid #444;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 999;
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
    }

    .artist-info {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      margin-bottom: 40px;
    }

    .artist-info img {
      width: 250px;
      height: 250px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    .artist-details p {
      margin: 6px 0;
      color: #ccc;
    }

    h3 {
      margin-top: 40px;
      color: #ffcc00;
    }

    .albums, .songs {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
    }

    .card, .albums a {
      background: #3a3a4a;
      padding: 12px;
      border-radius: 10px;
      text-align: center;
      transition: transform 0.2s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .card:hover, .albums a:hover {
      transform: scale(1.02);
    }

    .card img, .albums img {
      width: 100%;
      height: 140px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 8px;
    }

    .card h4, .albums h4 {
      margin: 8px 0 4px;
      color: #fff;
    }

    .card p, .albums p {
      margin: 0;
      color: #bbb;
      font-size: 14px;
    }

    small {
      color: #888;
    }

    @media (max-width: 600px) {
      .container {
        padding: 120px 20px 40px;
      }
    }
  </style>
</head>
<body>

<header>
  <h2><?= htmlspecialchars($artist["name"]) ?> 🎤</h2>
  <a class="back" href="homepage.php">&larr; Back to Homepage</a>
</header>

<div class="container">

  <div class="artist-info">
    <img src="<?= htmlspecialchars($artist["image"]) ?>" alt="artist">
    <div class="artist-details">
      <p><strong>Genre:</strong> <?= htmlspecialchars($artist["genre"]) ?></p>
      <p><strong>Country:</strong> <?= htmlspecialchars($artist["country_name"]) ?></p>
      <p><strong>Listeners:</strong> <?= number_format($artist["listeners"]) ?></p>
      <p><strong>Total Albums:</strong> <?= $artist["total_albums"] ?></p>
      <p><strong>Total Songs:</strong> <?= $artist["total_num_music"] ?></p>
      <p><strong>Joined:</strong> <?= $artist["date_joined"] ?></p>
      <p><strong>Bio:</strong><br><?= nl2br(htmlspecialchars($artist["bio"])) ?></p>
    </div>
  </div>

  <h3>Latest Albums</h3>
  <div class="albums">
    <?php while($a = $albums->fetch_assoc()): ?>
      <a href="albumpage.php?album_id=<?= $a['album_id'] ?>">
        <img src="<?= htmlspecialchars($a["image"]) ?>" alt="album">
        <h4><?= htmlspecialchars($a["title"]) ?></h4>
        <p><?= htmlspecialchars($a["release_date"]) ?> | <?= htmlspecialchars($a["genre"]) ?></p>
      </a>
    <?php endwhile; ?>
  </div>

  <h3>Top 5 Most Listened Songs</h3>
  <div class="songs">
    <?php while($s = $songs->fetch_assoc()): ?>
      <a class="card" href="currentmusic.php?song_id=<?= $s['song_id'] ?>">
        <img src="<?= htmlspecialchars($s["image"]) ?>" alt="song">
        <h4><?= htmlspecialchars($s["title"]) ?></h4>
        <p><?= htmlspecialchars($s["genre"]) ?> | <?= htmlspecialchars($s["duration"]) ?></p>
        <p><small>Rank: <?= $s["rank"] ?></small></p>
      </a>
    <?php endwhile; ?>
  </div>

</div>

</body>
</html>
