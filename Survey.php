<?php
session_start();

$host        = "localhost";
$db_name     = "language_learning";
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

$stmt = $conn ->prepare("SELECT User_ID 
                      FROM user_detail 
                      WHERE User_ID = ?");
$stmt -> bind_param("i", $user_id);
$stmt -> execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$exists) {
    header("Location: register.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $age = (int)$_POST['age'];
    $reason_learning = $_POST['reason_learning'];
    $how_user_know = $_POST['how_user_know'];

    $allowed_reasons = ['School', 'Travel', 'Work', 'Personal Interest', 'Other'];
    $allowed_sources = ['Social Media', 'Friend', 'Google', 'Advertisement', 'Other'];

    if($age <= 0){
        $error = "Please enter a valid age.";
    } elseif(!in_array($reason_learning, $allowed_reasons)){
        $error = "Please select a valid reason for learning.";
    } elseif(!in_array($how_user_know, $allowed_sources)){
        $error = "Please select a valid source for how you heard about us.";
    } else {
        $stmt = $conn->prepare("INSERT INTO user_survey (User_ID, Age, Reason_Learning, How_User_Know)
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $age, $reason_learning, $how_user_know);
        $stmt->execute();
        $stmt->close();

        header("Location: Choose_Language.php");
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
  <title>Survey</title>
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
      max-width: 500px;
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
 
    .question-block {
      margin-bottom: 24px;
    }
 
    .question-block label.question-label {
      display: block;
      font-size: 0.95rem;
      font-weight: bold;
      color: #333;
      margin-bottom: 10px;
    }
 
    /* Age input */
    .question-block input[type="number"] {
      width: 100px;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
      font-family: Georgia, serif;
    }
 
    .question-block input[type="number"]:focus {
      outline: none;
      border-color: #333;
    }
 
    /* Radio options */
    .radio-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
 
    .radio-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border: 1px solid #ccc;
      border-radius: 4px;
      cursor: pointer;
      background: #fff;
      transition: background 0.15s;
    }
 
    .radio-option:hover { background: #f0f0f0; }
 
    .radio-option input[type="radio"] {
      accent-color: #333;
      width: 15px;
      height: 15px;
      cursor: pointer;
    }
 
    .radio-option label {
      cursor: pointer;
      font-size: 0.9rem;
      color: #333;
    }
 
    /* Error */
    .error {
      color: #c0392b;
      font-size: 0.9rem;
      margin-bottom: 16px;
      font-weight: bold;
    }
 
    /* Submit */
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
  <h2>Quick Survey</h2>
  <p class="subtitle">Help us personalise your experience. Just 3 quick questions!</p>
 
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
 
  <form method="POST" action="survey.php">
 
    <!-- Q1: Age -->
    <div class="question-block">
      <label class="question-label">1. How old are you?</label>
      <input type="number"
             name="age"
             min="1"
             max="100"
             value="<?= isset($_POST['age']) ? (int)$_POST['age'] : '' ?>"
             placeholder="e.g. 22"
             required />
    </div>
 
    <!-- Q2: Reason for learning -->
    <div class="question-block">
      <label class="question-label">2. Why are you learning a new language?</label>
      <div class="radio-group">
        <?php
          $reasons = ['School', 'Travel', 'Work', 'Personal Interest', 'Other'];
          foreach ($reasons as $r):
            $checked = (isset($_POST['reason_learning']) && $_POST['reason_learning'] === $r) ? 'checked' : '';
        ?>
          <div class="radio-option" onclick="this.querySelector('input').checked=true">
            <input type="radio" name="reason_learning" value="<?= $r ?>" <?= $checked ?> required />
            <label><?= $r ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
 
    <!-- Q3: How they found us -->
    <div class="question-block">
      <label class="question-label">3. How did you hear about us?</label>
      <div class="radio-group">
        <?php
          $sources = ['Social Media', 'Friend', 'Google', 'Advertisement', 'Other'];
          foreach ($sources as $s):
            $checked = (isset($_POST['how_user_know']) && $_POST['how_user_know'] === $s) ? 'checked' : '';
        ?>
          <div class="radio-option" onclick="this.querySelector('input').checked=true">
            <input type="radio" name="how_user_know" value="<?= $s ?>" <?= $checked ?> required />
            <label><?= $s ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
 
    <button type="submit" id="submit-btn">Submit</button>
 
  </form>
</div>
 
</body>
</html>