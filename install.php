<?php
ob_start();
ini_set('max_execution_time', 300);

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "YUSUF_KENAN_AKGUN_DB";

// 1. Bağlantı
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// 2. DB zaten var mı kontrolü
$dbCheck = $conn->query("SHOW DATABASES LIKE '$dbname'");
if ($dbCheck && $dbCheck->num_rows > 0) {
    // Session açıp mesaj ver ve yönlendir
    session_start();
    $_SESSION['install_notice'] = "🟡 Database already exists.";
    header("Location: login.html");
    exit;
}

// 3. DB oluştur
$conn->query("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($dbname);

// 4. Tablolar
$conn->query("CREATE TABLE IF NOT EXISTS COUNTRY (
    country_id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100),
    country_code VARCHAR(10)
)");

$conn->query("CREATE TABLE IF NOT EXISTS USERS (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT,
    age INT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    date_joined DATE,
    last_login DATETIME,
    follower_num INT,
    subscription_type VARCHAR(100),
    top_genre VARCHAR(100),
    num_songs_liked INT,
    most_played_artist INT,
    image VARCHAR(255),
    FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS ARTISTS (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    genre VARCHAR(100),
    date_joined DATE,
    total_num_music INT,
    total_albums INT,
    listeners INT,
    bio TEXT,
    country_id INT,
    image VARCHAR(255),
    FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS ALBUMS (
    album_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT,
    title VARCHAR(100) NOT NULL,
    release_date DATE,
    genre VARCHAR(100),
    music_number INT,
    image VARCHAR(255),
    FOREIGN KEY (artist_id) REFERENCES ARTISTS(artist_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS SONGS (
    song_id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT,
    title VARCHAR(100) NOT NULL,
    duration TIME,
    genre VARCHAR(100),
    release_date DATE,
    `rank` INT,
    image VARCHAR(255),
    FOREIGN KEY (album_id) REFERENCES ALBUMS(album_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS PLAY_HISTORY (
    play_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    song_id INT,
    playtime DATETIME,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id),
    FOREIGN KEY (song_id) REFERENCES SONGS(song_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS PLAYLISTS (
    playlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    date_created DATE,
    image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS PLAYLIST_SONGS (
    playlistsong_id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT,
    song_id INT,
    date_added DATETIME,
    FOREIGN KEY (playlist_id) REFERENCES PLAYLISTS(playlist_id),
    FOREIGN KEY (song_id) REFERENCES SONGS(song_id)
)");

$conn->close();

// 5. Veri üretimi
include_once("generate_data.php");

// 6. login sayfasına yönlendir
header("Location: login.html");
exit;
ob_end_flush();
