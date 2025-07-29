<?php
$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "YUSUF_KENAN_AKGUN_DB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("\u274c Ba\u011flant\u0131 hatas\u0131: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

function readLines($filename) {
    $path = __DIR__ . "/" . $filename;
    return file_exists($path) ? array_filter(array_map("trim", file($path))) : [];
}

$firstNames   = readLines("firstnames.txt");
$lastNames    = readLines("lastnames.txt");
$countryLines = readLines("countries.txt");
$genres       = readLines("genres.txt");
$artistNames  = readLines("artist_names.txt");
$albumTitles  = readLines("album_titles.txt");
$songTitles   = readLines("song_titles.txt");

// COUNTRY
foreach ($countryLines as $line) {
    $parts = explode(",", $line);
    if (count($parts) === 2) {
        [$country_name, $country_code] = $parts;
        $stmt = $conn->prepare("INSERT INTO COUNTRY (country_name, country_code) VALUES (?, ?)");
        $stmt->bind_param("ss", $country_name, $country_code);
        $stmt->execute();
    }
}
echo "\ud83c\udf0d COUNTRY tablosu dolduruldu.<br>";

$countryIds = [];
$result = $conn->query("SELECT country_id FROM COUNTRY");
while ($row = $result->fetch_assoc()) {
    $countryIds[] = $row['country_id'];
}

// ARTISTS (100 adet)
for ($i = 1; $i <= 100; $i++) {
    $name = $artistNames[array_rand($artistNames)];
    $genre = $genres[array_rand($genres)];
    $date_joined = date("Y-m-d", strtotime("-" . rand(30, 2000) . " days"));
    $total_music = rand(5, 100);
    $total_albums = rand(0, 5);
    $listeners = rand(1000, 100000);
    $bio = "This is the bio of artist $name.";
    $country_id = $countryIds[array_rand($countryIds)];
    $image = "https://picsum.photos/seed/artist$i/120/120";

    $stmt = $conn->prepare("INSERT INTO ARTISTS (name, genre, date_joined, total_num_music, total_albums, listeners, bio, country_id, image)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiisis", $name, $genre, $date_joined, $total_music, $total_albums, $listeners, $bio, $country_id, $image);
    $stmt->execute();
}
echo "\ud83c\udfa4 ARTISTS tablosu dolduruldu.<br>";

// USERS (100 adet)
$usedUsernames = [];
for ($i = 1; $i <= 100; $i++) {
    do {
        $fname = strtolower($firstNames[array_rand($firstNames)]);
        $lname = strtolower($lastNames[array_rand($lastNames)]);
        $username = $fname . $lname . rand(1000, 9999);
    } while (in_array($username, $usedUsernames));
    $usedUsernames[] = $username;

    $name = ucfirst($fname) . " " . ucfirst($lname);
    $email = $username . "@mail.com";
    $password = password_hash("1234", PASSWORD_DEFAULT);
    $country_id = $countryIds[array_rand($countryIds)];
    $age = rand(18, 45);
    $today = date("Y-m-d");
    $now = date("Y-m-d H:i:s");
    $follower_num = rand(0, 5000);
    $subscription = rand(0, 1) ? "Free" : "Premium";
    $top_genre = $genres[array_rand($genres)];
    $liked = rand(10, 100);
    $most_played_artist = rand(1, 100);
    $image = "https://picsum.photos/seed/user$i/100/100";

    $stmt = $conn->prepare("INSERT INTO USERS (country_id, age, name, username, email, password, date_joined, last_login, follower_num, subscription_type, top_genre, num_songs_liked, most_played_artist, image)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssssisssis", $country_id, $age, $name, $username, $email, $password, $today, $now, $follower_num, $subscription, $top_genre, $liked, $most_played_artist, $image);
    $stmt->execute();
}
echo "\ud83d\udc64 USERS tablosu dolduruldu.<br>";

// ALBUMS (200 adet)
$albumImages = [];
for ($i = 1; $i <= 200; $i++) {
    $titleBase = $albumTitles[array_rand($albumTitles)];
    $title = substr($titleBase . " #" . rand(1, 999), 0, 100);
    $artist_id = rand(1, 100);
    $release_date = date("Y-m-d", strtotime("-" . rand(1, 2000) . " days"));
    $genre = $genres[array_rand($genres)];
    $music_number = rand(5, 15);
    $image = "https://picsum.photos/seed/album$i/300/300";

    $stmt = $conn->prepare("INSERT INTO ALBUMS (artist_id, title, release_date, genre, music_number, image)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $artist_id, $title, $release_date, $genre, $music_number, $image);
    $stmt->execute();
    $albumImages[$i] = $image;
}
echo "\ud83d\udcc1 ALBUMS tablosu dolduruldu.<br>";

// SONGS (1000 adet)
for ($i = 1; $i <= 2000; $i++) {
    $title = substr($songTitles[array_rand($songTitles)] . " #" . rand(1, 999), 0, 100);
    $album_id = rand(1, 200);
    $duration = sprintf("%02d:%02d:%02d", 0, rand(1, 4), rand(0, 59));
    $genre = $genres[array_rand($genres)];
    $release_date = date("Y-m-d", strtotime("-" . rand(1, 2000) . " days"));
    $rank = rand(1, 2000);
    $image = $albumImages[$album_id];

    $stmt = $conn->prepare("INSERT INTO SONGS (album_id, title, duration, genre, release_date, `rank`, image)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssis", $album_id, $title, $duration, $genre, $release_date, $rank, $image);
    $stmt->execute();
}
echo "\ud83c\udfb5 SONGS tablosu dolduruldu.<br>";

// PLAYLISTS (500 adet)
$userIds = [];
$result = $conn->query("SELECT user_id FROM USERS");
while ($row = $result->fetch_assoc()) {
    $userIds[] = $row['user_id'];
}

for ($i = 1; $i <= 500; $i++) {
    $user_id = $userIds[array_rand($userIds)];
    $title = "Playlist #$i";
    $description = "This is playlist $i by user $user_id.";
    $date_created = date("Y-m-d", strtotime("-" . rand(1, 1000) . " days"));
    $image = "https://picsum.photos/seed/playlist$i/250/250";

    $stmt = $conn->prepare("INSERT INTO PLAYLISTS (user_id, title, description, date_created, image)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $title, $description, $date_created, $image);
    $stmt->execute();
}
echo "\ud83c\udfb6 PLAYLISTS tablosu dolduruldu.<br>";

// PLAYLIST_SONGS (500+ kayıt)
$playlistIds = [];
$songIds = [];

$result = $conn->query("SELECT playlist_id FROM PLAYLISTS");
while ($row = $result->fetch_assoc()) {
    $playlistIds[] = $row['playlist_id'];
}

$result = $conn->query("SELECT song_id FROM SONGS");
while ($row = $result->fetch_assoc()) {
    $songIds[] = $row['song_id'];
}

for ($i = 0; $i < 500; $i++) {
    $playlist_id = $playlistIds[array_rand($playlistIds)];
    $song_id = $songIds[array_rand($songIds)];
    $date_added = date("Y-m-d H:i:s", strtotime("-" . rand(1, 10000) . " seconds"));

    $stmt = $conn->prepare("INSERT INTO PLAYLIST_SONGS (playlist_id, song_id, date_added)
                            VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $playlist_id, $song_id, $date_added);
    $stmt->execute();
}
echo "\ud83d\udd39 PLAYLIST_SONGS tablosu dolduruldu.<br>";

// PLAY_HISTORY (her user için 20 kayıt)
for ($i = 0; $i < count($userIds); $i++) {
    for ($j = 0; $j < 20; $j++) {
        $user_id = $userIds[$i];
        $song_id = $songIds[array_rand($songIds)];
        $playtime = date("Y-m-d H:i:s", strtotime("-" . rand(1, 100000) . " seconds"));

        $stmt = $conn->prepare("INSERT INTO PLAY_HISTORY (user_id, song_id, playtime)
                                VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $song_id, $playtime);
        $stmt->execute();
    }
}
echo "\ud83d\udcfb PLAY_HISTORY tablosu dolduruldu.<br>";

$conn->close();
?>