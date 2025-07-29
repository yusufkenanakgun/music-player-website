<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$uid = $_SESSION['uid'];
$uname = $_SESSION['name'] ?? "User";

if (!isset($_GET["playlist_id"])) {
    die("Playlist ID not provided.");
}

$playlist_id = intval($_GET["playlist_id"]);

$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

// Şarkı ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_songs'])) {
    $selectedSongs = $_POST['songs'] ?? [];
    $ps = $conn->prepare("INSERT INTO PLAYLIST_SONGS (playlist_id, song_id, date_added) VALUES (?, ?, ?)");

    foreach ($selectedSongs as $sid) {
        $date_added = date("Y-m-d H:i:s");
        $ps->bind_param("iis", $playlist_id, $sid, $date_added);
        $ps->execute();
    }

    $ps->close();
    header("Location: playlistpage.php?playlist_id=$playlist_id");
    exit;
}

// Playlist bilgisi
$stmt = $conn->prepare("SELECT P.title, P.description, P.date_created, U.name as owner, P.user_id
                        FROM PLAYLISTS P JOIN USERS U ON P.user_id = U.user_id
                        WHERE P.playlist_id = ?");
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
if (!$info) die("Playlist not found.");

// Playlist sırası
$order = 1;
$orderQuery = $conn->query("SELECT playlist_id FROM PLAYLISTS WHERE user_id = {$info['user_id']} ORDER BY date_created ASC");
while ($row = $orderQuery->fetch_assoc()) {
    if ($row['playlist_id'] == $playlist_id) break;
    $order++;
}

// Mevcut şarkılar
$songListQuery = $conn->query("
    SELECT S.song_id, S.title, S.genre, S.duration, S.image, C.country_name
    FROM PLAYLIST_SONGS PS
    JOIN SONGS S ON PS.song_id = S.song_id
    JOIN ALBUMS A ON S.album_id = A.album_id
    JOIN ARTISTS AR ON A.artist_id = AR.artist_id
    JOIN COUNTRY C ON AR.country_id = C.country_id
    WHERE PS.playlist_id = $playlist_id
    ORDER BY PS.date_added DESC
");

// Arama
$search = trim($_GET['search'] ?? '');
$allSongs = [];
if ($search !== '') {
    $searchParam = "%" . strtolower($search) . "%";
    $stmt = $conn->prepare("
        SELECT S.song_id, S.title, S.genre, S.image, A.name AS artist_name
        FROM SONGS S
        JOIN ALBUMS AL ON S.album_id = AL.album_id
        JOIN ARTISTS A ON AL.artist_id = A.artist_id
        WHERE LOWER(S.title) LIKE ?
        LIMIT 50
    ");
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $allSongs = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Playlist <?= $order ?> | <?= htmlspecialchars($info["owner"]) ?></title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #1e1e2f;
      color: white;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }
    header {
      background-color: #2d2d3a;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .back {
      color: #ffcc00;
      text-decoration: none;
      font-weight: bold;
    }
    .main {
      display: grid;
      grid-template-columns: 65% 35%;
      height: calc(100vh - 80px);
    }
    .left, .right {
      padding: 20px;
      overflow-y: auto;
    }
    h2 { color: #ffcc00; }
    .playlist-info p { margin: 5px 0; }
    .song-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
    }
    .song {
      background: #3a3a4a;
      padding: 4px;
      border-radius: 6px;
      text-align: center;
      font-size: 11px;
    }
    .song img {
      width: 100%;
      height: 60px;
      object-fit: cover;
      border-radius: 6px;
    }
    form input[type="text"] {
      width: 75%;
      padding: 14px;
      border: none;
      border-radius: 6px;
      background: #444;
      color: white;
      margin-right: 10px;
      font-size: 16px;
    }
    form button {
      padding: 14px;
      border: none;
      border-radius: 6px;
      background: #007bff;
      color: white;
      cursor: pointer;
      font-size: 16px;
    }
    .search-results {
      max-height: 450px;
      overflow-y: auto;
    }
    .search-results label {
      display: flex;
      align-items: center;
      background: #3a3a4a;
      margin-bottom: 14px;
      padding: 14px;
      border-radius: 6px;
      gap: 10px;
    }
    .search-results img {
      width: 50px;
      height: 50px;
      border-radius: 6px;
    }
    .search-results strong, .search-results small {
      display: block;
    }
    a.song-link {
      text-decoration: none;
      color: inherit;
    }
  </style>
</head>
<body>
<header>
  <h1>Playlist <?= $order ?> 🎶</h1>
  <a class="back" href="homepage.php">&larr; Back to Homepage</a>
</header>

<div class="main">
  <div class="left">
    <div class="playlist-info">
      <p><strong>Created by:</strong> <?= htmlspecialchars($info["owner"]) ?> | <strong>Date:</strong> <?= htmlspecialchars($info["date_created"]) ?></p>
      <p><em><?= htmlspecialchars($info["title"]) ?> - This is playlist <?= $order ?> by <?= htmlspecialchars($info["owner"]) ?>.</em></p>
    </div>
    <h2>Songs in this Playlist</h2>
    <div class="song-grid">
      <?php while($song = $songListQuery->fetch_assoc()): ?>
        <a class="song-link" href="currentmusic.php?song_id=<?= $song["song_id"] ?>">
          <div class="song">
            <img src="<?= $song["image"] ?>" alt="song">
            <p>
              <strong><?= htmlspecialchars($song["title"]) ?></strong><br>
              <?= $song["genre"] ?> | <?= $song["duration"] ?><br>
              <small><?= $song["country_name"] ?></small>
            </p>
          </div>
        </a>
      <?php endwhile; ?>
    </div>
  </div>

  <div class="right">
    <h2>Add Songs</h2>
    <form method="GET">
      <input type="hidden" name="playlist_id" value="<?= $playlist_id ?>">
      <input type="text" name="search" placeholder="Search songs..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
    </form>

    <form method="POST">
      <input type="hidden" name="playlist_id" value="<?= $playlist_id ?>">
      <?php if ($search !== '' && $allSongs->num_rows > 0): ?>
        <button type="submit" name="add_songs" style="margin: 16px 0;">Add to Playlist ✅</button>
      <?php endif; ?>
      <div class="search-results">
        <?php if ($search !== '' && $allSongs->num_rows > 0): ?>
          <?php while($s = $allSongs->fetch_assoc()): ?>
            <label>
              <input type="checkbox" name="songs[]" value="<?= $s['song_id'] ?>">
              <img src="<?= $s['image'] ?>" alt="song">
              <div>
                <strong><?= htmlspecialchars($s['title']) ?></strong>
                <small><?= $s['genre'] ?> | <?= $s['artist_name'] ?></small>
              </div>
            </label>
          <?php endwhile; ?>
        <?php elseif ($search !== ''): ?>
          <p>No matching songs found.</p>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
</body>
</html>
