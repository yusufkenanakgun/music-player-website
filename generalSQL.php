<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "mysql", "YUSUF_KENAN_AKGUN_DB");
$conn->set_charset("utf8mb4");

$output = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sql = trim($_POST["query"]);

    if (!preg_match("/^SELECT/i", $sql)) {
        $error = "❌ Only SELECT queries are allowed.";
    } else {
        if (!preg_match("/LIMIT/i", $sql)) {
            $sql .= " LIMIT 5";
        }

        $result = $conn->query($sql);
        if (!$result) {
            $error = "❌ SQL Error: " . $conn->error;
        } else {
            if ($result->num_rows > 0) {
                $output .= "<table><tr>";
                while ($field = $result->fetch_field()) {
                    $output .= "<th>" . htmlspecialchars($field->name) . "</th>";
                }
                $output .= "</tr>";
                while ($row = $result->fetch_assoc()) {
                    $output .= "<tr>";
                    foreach ($row as $val) {
                        $output .= "<td>" . htmlspecialchars($val) . "</td>";
                    }
                    $output .= "</tr>";
                }
                $output .= "</table>";
            } else {
                $output = "✅ Query successful, but no rows returned.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SQL Query Tester</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #1e1e2f;
      color: #f5f5f5;
      padding: 40px;
    }

    h1 {
      color: #00bcd4;
    }

    code {
      color: #80cbc4;
      background-color: #2c2c3c;
      padding: 3px 6px;
      border-radius: 4px;
    }

    form textarea {
      width: 100%;
      height: 120px;
      font-size: 14px;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #444;
      background-color: #2c2c3c;
      color: #f5f5f5;
      resize: vertical;
    }

    form button {
      margin-top: 10px;
      padding: 10px 24px;
      background: #00bcd4;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }

    form button:hover {
      background: #008c9e;
    }

    .output {
      margin-top: 30px;
      background: #2d2d3a;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.3);
    }

    .back {
      margin-top: 30px;
      display: inline-block;
      text-decoration: none;
      color: #00bcd4;
      font-weight: bold;
    }

    .back:hover {
      text-decoration: underline;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 15px;
    }

    th, td {
      border: 1px solid #444;
      padding: 8px;
      text-align: left;
    }

    th {
      background-color: #3a3a4a;
    }

    td {
      background-color: #2b2b3a;
    }

    pre {
      background: #2c2c3c;
      padding: 10px;
      border-radius: 6px;
      font-size: 14px;
      color: #c3e88d;
      overflow-x: auto;
    }

    .error {
      color: #ff6b6b;
      font-weight: bold;
    }

    .success {
      color: #a5d6a7;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <h1>🧪 SQL Query Tester</h1>
  <p><strong>Hint:</strong> Try queries like:</p>
  <ul>
    <li><code>SELECT name, genre FROM ARTISTS ORDER BY listeners DESC</code></li>
    <li><code>SELECT title, genre FROM SONGS WHERE genre = 'Pop'</code></li>
  </ul>

  <form method="POST">
    <label for="query"><strong>Write a SELECT query:</strong></label><br>
    <textarea name="query" required><?= isset($_POST['query']) ? htmlspecialchars($_POST['query']) : ''; ?></textarea><br>
    <button type="submit">Execute Query</button>
  </form>

  <div class="output">
    <?php
    if ($error) {
        echo "<p class='error'>$error</p>";
    } elseif ($output) {
        echo "<p class='success'><strong>Query:</strong></p><pre>" . htmlspecialchars($_POST["query"]) . "</pre>";
        echo $output;
    }
    ?>
  </div>

  <a class="back" href="homepage.php">&larr; Back to Homepage</a>

</body>
</html>
