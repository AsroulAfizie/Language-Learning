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

if(!isset($_SESSION['points'])) $_SESSION['points'] = 0;
if(!isset($_SESSION['answered_ids'])) $_SESSION['answered_ids'] = [];

$user_id = $_SESSION['User_ID'] ;

$stmt = $conn->prepare("SELECT Language, Current_Level 
                        FROM current_progress 
                        WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_assoc();
$stmt->close();

$language = $progress['Language'];
$level    = $progress['Current_Level'] ;

// Initialize 
$feedback       = '';
$feedback_class = '';
$submitted      = false;
$selected_id    = null;
$correct_id     = null;
$user_answer    = '';
$answers        = [];
$question       = null;
$quiz_done      = false;
$coins          = $_SESSION['points']; // Assuming 1 points = 1 coin

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id'])) {
    $submitted   = true;
    $question_id = (int) $_POST['question_id'];
    


// Retrieve Question From Database

    $stmt = $conn->prepare("SELECT Question_ID, Question_Text, Question_Type, Language, Level 
                            FROM Question 
                            WHERE Question_ID = ?");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $question = $stmt->get_result()->fetch_assoc();
    $stmt->close();

// Retrieve Answers From Database

    $stmt = $conn->prepare("SELECT Answer_ID, answer_option, Is_Correct 
                            FROM Answer 
                            WHERE Question_ID = ?");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
        if ($row['Is_Correct'] == 1) $correct_id = $row['Answer_ID'];
    }
    $stmt->close();

    if($question['Question_Type'] === 'MCQ') {
      $selected_id = (int) $_POST['answer_id'];
        if ($selected_id == $correct_id) {
            $_SESSION['points'] += 100;
            $feedback = "Correct! +100 points.";
            $feedback_class = "correct";
        } else {
            $feedback = "Wrong! The correct answer was highlighted.";
            $feedback_class = "wrong";
        }
    }
    elseif($question['Question_Type'] === 'FITB') {
        $user_answer = trim($_POST['user_input']);
        $correct_answer = '';
        foreach ($answers as $ans) {
        if ($ans['Is_Correct'] == 1) {
            $correct_answer = trim($ans['answer_option']);
            break;
          }
      }
        if (strtolower($user_answer) === strtolower($correct_answer)) {
            $_SESSION['points'] += 100;
            $feedback = "Correct! +100 points.";
            $feedback_class = "correct";
        } else {
            $feedback = "Wrong! The correct answer was: " . htmlspecialchars($correct_answer);
            $feedback_class = "wrong";
        }
    }
    if(!in_array($question_id, $_SESSION['answered_ids'])) {
        $_SESSION['answered_ids'][] = $question_id;
    }

}


$stmt = $conn->prepare("SELECT COUNT(*) as total 
                        FROM Question 
                        WHERE Language = ? AND Level = ?");
$stmt->bind_param("si", $language, $level);
$stmt->execute();
$total_questions = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if(!$submitted) {
    $excluded = $_SESSION['answered_ids'];


    if(!empty($excluded)) {
        $placeholders = implode(',', array_fill(0, count($excluded), '?'));
        $types = str_repeat('i', count($excluded));
        $sql = "SELECT Question_ID , Question_Text, Question_Type, Language, Level 
                FROM Question 
                WHERE Language = ? AND Level = ? 
                AND Question_ID NOT IN ($placeholders)
                ORDER BY RAND() LIMIT 1";
        $stmt = $conn->prepare($sql);
        $bind_params = array_merge([$language, $level], $excluded);
        $types = 'si' . $types;
        $stmt->bind_param($types, ...$bind_params);
    } else {
        $sql = "SELECT Question_ID , Question_Text, Question_Type, Language, Level 
                FROM Question 
                WHERE Language = ? AND Level = ? 
                ORDER BY RAND() LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $language, $level);
    }
    $stmt->execute();
    $question = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$question) {
      $quiz_done = true;

      $coins = $_SESSION['points']; // Total coins earned in this level

      //Update User's Progress to next level
      $stmt = $conn->prepare("UPDATE Current_Progress 
                              SET Current_Level = Current_Level + 1, Coins = Coins + ?
                              WHERE User_ID = ?");
      $stmt->bind_param("ii", $coins, $user_id);
      $stmt->execute();
      $stmt->close(); 

      $_SESSION['answered_ids'] = []; // Reset answered questions for next level
      $_SESSION['points'] = 0; // Reset points for next level
    }


    if($question){
        if($question['Question_Type'] === 'MCQ') {
          $stmt = $conn->prepare("SELECT Answer_ID, answer_option, Is_Correct 
                                  FROM Answer 
                                  WHERE Question_ID = ?");
          $stmt->bind_param("i", $question['Question_ID']);
          $stmt->execute();
          $result = $stmt->get_result();
          while($row = $result->fetch_assoc()) {
              $answers[] = $row;
          }
          $stmt->close();
        }
    }    
}

$points = $quiz_done ? $coins : $_SESSION['points']; // Show total coins on end screen, current points otherwise
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Question</title>
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
      max-width: 600px;
      width: 100%;
    }
 
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }
 
    .header h2 {
      font-size: 1rem;
      font-weight: normal;
      color: #333;
    }
 
    .points-box {
      font-size: 1rem;
      font-weight: bold;
      color: #333;
    }
 
    .question-text {
      font-size: 1.15rem;
      color: #222;
      margin-bottom: 22px;
      line-height: 1.5;
    }
 
    .options {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 24px;
    }
 
    .option {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border: 1px solid #ccc;
      border-radius: 4px;
      cursor: pointer;
      background: #fff;
    }
 
    .option input[type="radio"] {
      accent-color: #333;
      width: 16px;
      height: 16px;
    }
 
    .option label {
      cursor: pointer;
      font-size: 0.95rem;
      color: #333;
    }
 
    /* Feedback highlight states */
    .option.correct { background: #d4edda; border-color: #5cb85c; }
    .option.wrong   { background: #f8d7da; border-color: #d9534f; }
 
    .feedback {
      font-size: 0.95rem;
      font-weight: bold;
      margin-bottom: 16px;
      min-height: 20px;
    }
 
    .feedback.correct { color: #3a7d44; }
    .feedback.wrong   { color: #c0392b; }
 
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
 
    button:hover { background: #555; }
 
    .no-question {
      text-align: center;
      color: #888;
      font-size: 1rem;
    }
 
    /* End screen */
    .end-screen {
      text-align: center;
      padding: 20px 0;
    }
 
    .end-screen h2 {
      font-size: 1.4rem;
      margin-bottom: 10px;
      color: #222;
    }
 
    .end-screen p {
      color: #555;
      margin-bottom: 10px;
    }
 
    .end-screen .final-points {
      font-size: 2.5rem;
      font-weight: bold;
      color: #333;
      margin: 16px 0;
    }
  </style>
</head>
<body>
 
<div class="container">
 
  <?php if ($quiz_done): ?>
    <!-- ── End Screen ── -->
    <div class="end-screen">
      <h2>Quiz Complete!</h2>
      <p><?= htmlspecialchars($language) ?> — <?= htmlspecialchars($level) ?></p>
      <p>You answered all <?= $total_questions ?> questions.</p>
      <div class="final-points"><?= $points ?> pts</div>
      <a href="MainPage.php"><button type="button">Back to Dashboard</button></a>
      <a href="Question.php"><button type="button">Next Level</button></a>
    </div>
 
  <?php elseif ($question): ?>
 
    <div class="header">
      <h2><?= htmlspecialchars($question['Language']) ?> — <?= htmlspecialchars($question['Level']) ?></h2>
      <div class="points-box">Points: <?= $points ?></div>
    </div>
 
    <div class="question-text">
      <?= htmlspecialchars($question['Question_Text']) ?>
    </div>
 
     <?php if ($submitted): ?>
      <!-- ── POST view: show feedback + result ── -->
 
      <div class="feedback <?= $feedback_class ?>">
        <?= $feedback ?>
      </div>
 
      <?php if ($question['Question_Type'] === 'MCQ'): ?>
        <!-- MCQ: highlight correct/wrong options -->
        <div class="options">
          <?php foreach ($answers as $ans): ?>
            <?php
              $cls = '';
              if ($ans['Is_Correct'] == 1)                $cls = 'correct';
              elseif ($ans['Answer_ID'] == $selected_id)  $cls = 'wrong';
            ?>
            <div class="option <?= $cls ?>">
              <input type="radio" <?= ($ans['Answer_ID'] == $selected_id) ? 'checked' : '' ?> disabled />
              <label><?= htmlspecialchars($ans['answer_option']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
 
      <?php elseif ($question['Question_Type'] === 'FITB'): ?>
        <!-- FITB: show what user typed, highlighted -->
        <div class="fitb-wrap">
          <input type="text"
                 value="<?= htmlspecialchars($user_answer) ?>"
                 class="<?= $feedback_class ?>"
                 disabled />
        </div>
 
      <?php endif; ?>
 
      <!-- Next Question reloads the page (GET) -->
      <form method="GET" action="question.php">
        <button type="submit">Next Question</button>
      </form>
 
    <?php else: ?>
      <!-- ── GET: show fresh question with submit form ── -->
 
      <form method="POST" action="question.php">
        <input type="hidden" name="question_id" value="<?= $question['Question_ID'] ?>"/>

        <?php if ($question['Question_Type'] === 'MCQ'): ?>
          <div class="options">
            <?php foreach ($answers as $ans): ?>
              <div class="option" onclick="this.querySelector('input').checked=true">
                <input type="radio"
                      name="answer_id"
                      value="<?= $ans['Answer_ID'] ?>"
                      required />
              <label><?= htmlspecialchars($ans['answer_option']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
        <?php elseif ($question['Question_Type'] === 'FITB'): ?>
          <div class="question-block">
            <input type="text" style="width: 300px; height: 50px; padding: 10px"
                   name="user_input"
                   placeholder="Type your answer here"
                   required />
          </div>
        <?php endif; ?>
        <button type="submit">Submit</button>
      </form>
 

 
  <?php endif; ?>
  <?php else: ?>
    <div class="no-question">No questions available for this language and level.</div>
  <?php endif; ?>
 
</div>
 
</body>
</html>