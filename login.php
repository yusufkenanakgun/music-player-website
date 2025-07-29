<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "YUSUF_KENAN_AKGUN_DB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("❌ Bağlantı hatası: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uname = trim($_POST["username"] ?? '');
    $pw    = trim($_POST["password"] ?? '');

    if ($uname === '' || $pw === '') {
        $error = "❌ Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password FROM USERS WHERE username = ?");
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($pw, $user["password"])) {
                $_SESSION["uid"]  = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                header("Location: homepage.php");
                exit;
            } else {
                $error = "❌ Şifre hatalı.";
            }
        } else {
            $error = "❌ Kullanıcı bulunamadı.";
        }
    }
}
?>

<!-- İsteğe bağlı HTML form sayfanın devamına eklenecekse -->
<?php if (isset($error)): ?>
  <p style="color:red; text-align:center;"><?= $error ?></p>
<?php endif; ?>
