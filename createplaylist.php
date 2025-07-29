<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$uid = $_SESSION['uid'];
$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

$search = trim($_GET['search'] ?? '');
$songs = [];
$_SESSION['selectedSongs'] ??= [];

if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $sid = intval($_GET['add']);
    if (!in_array($sid, $_SESSION['selectedSongs'])) {
        $_SESSION['selectedSongs'][] = $sid;
    }
    header("Location: createplaylist.php?search=" . urlencode($search));
    exit;
}

if ($search !== '') {
    $escaped = $conn->real_escape_string(strtolower($search));
    $query = "
        SELECT S.song_id, S.title, S.genre, S.image, A.name AS artist_name
        FROM SONGS S
        JOIN ALBUMS AL ON S.album_id = AL.album_id
        JOIN ARTISTS A ON AL.artist_id = A.artist_id
        WHERE LOWER(S.title) LIKE '%$escaped%'
        ORDER BY S.title ASC
        LIMIT 100
    ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $songs[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
    if (!empty($_SESSION['selectedSongs'])) {
        $countRes = $conn->query("SELECT COUNT(*) AS total FROM PLAYLISTS WHERE user_id = $uid");
        $count = ($countRes->fetch_assoc()['total'] ?? 0) + 1;

        $title = "Playlist $count";
        $desc = "$title created by user $uid";
        $seed = rand(1, 9999);
        $image = "https://source.unsplash.com/300x300/?music&sig=$seed";
        $date = date("Y-m-d");

        $stmt = $conn->prepare("INSERT INTO PLAYLISTS (user_id, title, description, image, date_created) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $uid, $title, $desc, $image, $date);
        $stmt->execute();
        $playlist_id = $stmt->insert_id;
        $stmt->close();

        $ps = $conn->prepare("INSERT INTO PLAYLIST_SONGS (playlist_id, song_id, date_added) VALUES (?, ?, ?)");
        $date_added = date("Y-m-d H:i:s");
        foreach ($_SESSION['selectedSongs'] as $sid) {
            $ps->bind_param("iis", $playlist_id, $sid, $date_added);
            $ps->execute();
        }
        $ps->close();

        unset($_SESSION['selectedSongs']);
        header("Location: playlistpage.php?playlist_id=$playlist_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Create Playlist</title>
  <style>
    body { background-color: #1e1e2f; color: white; font-family: Arial, sans-serif; margin: 0; display: flex; flex-direction: column; height: 100vh; }
    header { background: #2d2d3a; padding: 20px; font-size: 22px; font-weight: bold; display: flex; justify-content: space-between; }
    .container { display: flex; flex: 1; }
    .left, .right { padding: 20px; height: calc(100vh - 80px); overflow-y: auto; }
    .left { width: 50%; background: #2a2a3a; }
    .right { width: 50%; background: #1f1f2a; border-left: 1px solid #444; display: flex; flex-direction: column; justify-content: space-between; }
    form.search-form { display: flex; gap: 10px; margin-bottom: 20px; }
    input[type="text"] { flex: 1; padding: 10px; border-radius: 6px; background: #444; color: white; border: none; }
    button, .add-btn { background: #1db954; padding: 8px 12px; border-radius: 6px; border: none; color: white; font-weight: bold; cursor: pointer; text-decoration: none; }
    .song-card { background: #3a3a4a; padding: 12px; border-radius: 8px; margin-bottom: 12px; display: flex; align-items: center; gap: 12px; }
    .song-card img { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; }
    .song-card label { flex-grow: 1; cursor: pointer; }
    .selected-item { background: #333; padding: 10px; margin-bottom: 8px; border-radius: 6px; }
    .create-btn { width: 100%; padding: 14px; margin-top: 20px; }
    a.home-link { color: #ffcc00; font-size: 16px; text-decoration: none; }
  </style>
</head>
<body>
  <header>
    <div>Create a Playlist Automatically</div>
    <a href="homepage.php" class="home-link">&larr; Return to Homepage</a>
  </header>
  <div class="container">
    <div class="left">
      <form class="search-form" method="GET">
        <input type="text" name="search" placeholder="Search for songs..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
      </form>
      <?php foreach ($songs as $song): ?>
        <div class="song-card">
          <img src="<?= htmlspecialchars($song['image']) ?>" alt="song">
          <label onclick="location.href='currentmusic.php?song_id=<?= $song['song_id'] ?>'">
            <strong><?= htmlspecialchars($song['title']) ?></strong><br>
            <small><?= htmlspecialchars($song['genre']) ?> | <?= htmlspecialchars($song['artist_name']) ?></small>
          </label>
          <a href="?search=<?= urlencode($search) ?>&add=<?= $song['song_id'] ?>" class="add-btn">Add</a>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="right">
      <form method="POST" style="display: flex; flex-direction: column; flex: 1;">
        <div style="flex-grow: 1;">
          <?php foreach ($_SESSION['selectedSongs'] as $sid): 
            $res = $conn->query("SELECT title FROM SONGS WHERE song_id = $sid");
            if ($row = $res->fetch_assoc()): ?>
              <div class="selected-item"><?= htmlspecialchars($row['title']) ?></div>
              <input type="hidden" name="songs[]" value="<?= $sid ?>">
          <?php endif; endforeach; ?>
        </div>
        <button type="submit" name="create_playlist" class="create-btn">✅ Create Playlist</button>
      </form>
    </div>
  </div>
</body>
</html>
