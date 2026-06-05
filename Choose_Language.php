<?php
session_start();

$host       = "localhost";
$db_name    = "language_learning";
$db_username = "root";
$db_password = "";

$conn = mysqli_connect($host, $db_username, $db_password, $db_name);
if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}

// if(!isset($_SESSION['User_ID'])){
//     header("Location: register.php");
//     exit();
// }

$user_id = $_SESSION['User_ID'];

$stmt = $conn->prepare("SELECT Language 
                        FROM Current_Progress 
                        WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$exist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if($exist){
    header("Location: MainPage.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $language = $_POST['language'];

    $allowed_languages = ['Malay', 'Chinese'];

    if(!in_array($language, $allowed_languages)){
        $error = "Please select a valid language.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Current_Progress (User_ID, Language, Current_Level, LastlogIn)
                                 VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("is", $user_id, $language);
        $stmt->execute();
        $stmt->close();

        header("Location: MainPage.html");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Choose Language</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
 
    body {
      font-family: Georgia, serif;
      background: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
 
    .container {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 6px;
      padding: 30px;
      max-width: 420px;
      width: 100%;
    }
 
    h2 {
      font-size: 1.3rem;
      margin-bottom: 6px;
      color: #222;
    }
 
    .subtitle {
      font-size: 0.9rem;
      color: #888;
      margin-bottom: 28px;
    }
 
    .radio-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 24px;
    }
 
    .radio-option {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border: 1px solid #ccc;
      border-radius: 4px;
      cursor: pointer;
      background: #fff;
      transition: background 0.15s;
    }
 
    .radio-option:hover { background: #f0f0f0; }
 
    .radio-option input[type="radio"] {
      accent-color: #333;
      width: 16px;
      height: 16px;
      cursor: pointer;
    }
 
    .radio-option label {
      cursor: pointer;
      font-size: 1rem;
      color: #333;
    }
 
    .error {
      color: #c0392b;
      font-size: 0.9rem;
      font-weight: bold;
      margin-bottom: 16px;
    }
 
    button {
      width: 100%;
      padding: 12px;
      background: #333;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
    }
 
    button:hover    { background: #555; }
    button:disabled { background: #aaa; cursor: not-allowed; }
  </style>
</head>
<body>
 
<div class="container">
  <h2>Choose Your Language</h2>
  <p class="subtitle">Pick the language you want to learn. You can only choose one.</p>
 
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
 
  <form method="POST" action="choose_language.php">
 
    <div class="radio-group">
      <?php
        $languages = ['Malay', 'Japanese'];
        foreach ($languages as $lang):
          $checked = (isset($_POST['language']) && $_POST['language'] === $lang) ? 'checked' : '';
      ?>
        <div class="radio-option" onclick="this.querySelector('input').checked=true; enableSubmit();">
          <input type="radio"
                 name="language"
                 value="<?= $lang ?>"
                 <?= $checked ?>
                 required />
          <label><?= $lang ?></label>
        </div>
      <?php endforeach; ?>
    </div>
 
    <button type="submit" id="submit-btn" disabled>Confirm</button>
 
  </form>
</div>
 
<script>
  function enableSubmit() {
    document.getElementById('submit-btn').disabled = false;
  }
</script>
 
</body>
</html>
