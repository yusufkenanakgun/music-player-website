<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$uid = $_SESSION['uid'];
$uname = $_SESSION['name'] ?? 'User';

$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

// Kullanıcı bilgisi
$stmt = $conn->prepare("SELECT country_id, image FROM USERS WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$country_id = $user["country_id"] ?? 1;
$userImage = $user["image"] ?? "https://source.unsplash.com/100x100/?person&sig=$uid";

// Arama parametreleri
$playlistSearch = trim($_GET['playlist_search'] ?? '');
$songSearch     = trim($_GET['song_search'] ?? '');
$artistSearch   = trim($_GET['artist_search'] ?? '');
$historySearch  = trim($_GET['history_search'] ?? '');

// 🎵 Playlist arama (başlığa göre)
if ($playlistSearch !== '') {
    $stmt = $conn->prepare("
        SELECT playlist_id, title, description, image, date_created
        FROM PLAYLISTS
        WHERE user_id = ? AND LOWER(title) LIKE ?
    ");
    $searchParam = "%" . strtolower($playlistSearch) . "%";
    $stmt->bind_param("is", $uid, $searchParam);
    $stmt->execute();
    $playlists = $stmt->get_result();
} else {
    $playlists = $conn->query("
        SELECT playlist_id, title, description, image, date_created
        FROM PLAYLISTS
        WHERE user_id = $uid
    ");
}

// 🎶 Song arama (tüm şarkılar içinden)
if ($songSearch !== '') {
    $stmt = $conn->prepare("
        SELECT S.song_id, S.title, S.genre, S.image, A.name AS artist_name
        FROM SONGS S
        JOIN ALBUMS AL ON S.album_id = AL.album_id
        JOIN ARTISTS A ON AL.artist_id = A.artist_id
        WHERE LOWER(S.title) LIKE ?
        ORDER BY S.rank ASC
        LIMIT 100
    ");
    $searchParam = "%" . strtolower($songSearch) . "%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $topSongs = $stmt->get_result();
} else {
    $topSongs = $conn->query("
        SELECT S.song_id, S.title, S.genre, S.image, A.name AS artist_name
        FROM SONGS S
        JOIN ALBUMS AL ON S.album_id = AL.album_id
        JOIN ARTISTS A ON AL.artist_id = A.artist_id
        ORDER BY S.rank ASC
        LIMIT 100
    ");
}

// 👨‍🎤 Artist arama (tüm sanatçılar)
if ($artistSearch !== '') {
    $stmt = $conn->prepare("
        SELECT artist_id, name, genre, image
        FROM ARTISTS
        WHERE LOWER(name) LIKE ?
        ORDER BY listeners DESC
        LIMIT 100
    ");
    $searchParam = "%" . strtolower($artistSearch) . "%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $artists = $stmt->get_result();
} else {
    $artists = $conn->query("
        SELECT artist_id, name, genre, image
        FROM ARTISTS
        ORDER BY listeners DESC
        LIMIT 100
    ");
}

// 🕓 History arama (kendi dinleme geçmişi)
if ($historySearch !== '') {
    $stmt = $conn->prepare("
        SELECT 
            S.song_id, S.title, S.genre, S.image, MAX(PH.playtime) AS playtime, A.name AS artist_name
        FROM PLAY_HISTORY PH
        JOIN SONGS S ON PH.song_id = S.song_id
        JOIN ALBUMS AL ON S.album_id = AL.album_id
        JOIN ARTISTS A ON AL.artist_id = A.artist_id
        WHERE PH.user_id = ? AND LOWER(S.title) LIKE ?
        GROUP BY S.song_id, S.title, S.genre, S.image, A.name
        ORDER BY playtime DESC
        LIMIT 10
    ");
    $searchParam = "%" . strtolower($historySearch) . "%";
    $stmt->bind_param("is", $uid, $searchParam);
    $stmt->execute();
    $songs = $stmt->get_result();
} else {
    $songStmt = $conn->prepare("
        SELECT 
            S.song_id, S.title, S.genre, S.image, MAX(PH.playtime) AS playtime, A.name AS artist_name
        FROM PLAY_HISTORY PH
        JOIN SONGS S ON PH.song_id = S.song_id
        JOIN ALBUMS AL ON S.album_id = AL.album_id
        JOIN ARTISTS A ON AL.artist_id = A.artist_id
        WHERE PH.user_id = ?
        GROUP BY S.song_id, S.title, S.genre, S.image, A.name
        ORDER BY playtime DESC
        LIMIT 10
    ");
    $songStmt->bind_param("i", $uid);
    $songStmt->execute();
    $songs = $songStmt->get_result();
}

// 🔔 Arama sonucu yoksa mesaj göstermek için bayrak
$notFound = isset($_GET['notfound']) ? true : false;

// 👉 Arayüz yükleniyor
include("homepage.html");
