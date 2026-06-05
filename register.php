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

// if(isset($_SESSION['User_ID'])){
// 	header("Location: MainPage.php");
// 	exit();
// }

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	$username = $_POST['username'];
	$password = $_POST['password'];
	$email = $_POST['email'];

	if(empty($username) || empty($password) || empty($email)){
		$error = "Please fill in all fields.";
	} 
	else{
		$stmt = $conn->prepare("SELECT User_ID 
								FROM User_Detail
								WHERE Username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$exists = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if($exists){
			$error = "Username already exists. Please choose another.";
		} else {
			$stmt = $conn->prepare("INSERT INTO User_Detail (Username, Password, Email) 
									VALUES (?, ?, ?)");
			$stmt->bind_param("sss", $username, $password, $email);
			$stmt->execute();
			$stmt->close();

			$_SESSION['User_ID'] = $conn->insert_id;
			$_SESSION['username'] = $username;

			header("Location: Survey.php");
			exit();
		}
	}
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
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
      margin-bottom: 24px;
    }
 
    .field {
      margin-bottom: 16px;
    }
 
    .field label {
      display: block;
      font-size: 0.9rem;
      font-weight: bold;
      color: #333;
      margin-bottom: 6px;
    }
 
    .field input {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 0.95rem;
      font-family: Georgia, serif;
    }
 
    .field input:focus {
      outline: none;
      border-color: #333;
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
      margin-top: 8px;
    }
 
    button:hover { background: #555; }
 
    .login-link {
      text-align: center;
      margin-top: 16px;
      font-size: 0.9rem;
      color: #888;
    }
 
    .login-link a {
      color: #333;
      text-decoration: underline;
    }
  </style>
</head>
<body>
 
<div class="container">
  <h2>Create Account</h2>
  <p class="subtitle">Sign up to start learning!</p>
 
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
 
  <form method="POST" action="register.php">
 
    <div class="field">
      <label>Username</label>
      <input type="text"
             name="username"
             value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
             placeholder="Enter your username"
             required />
    </div>
 
    <div class="field">
      <label>Email</label>
      <input type="email"
             name="email"
             value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
             placeholder="Enter your email"
             required />
    </div>
 
    <div class="field">
      <label>Password</label>
      <input type="password"
             name="password"
             placeholder="Enter your password"
             required />
    </div>
 
    <button type="submit">Register</button>
 
  </form>
 
  <div class="login-link">
    Already have an account? <a href="login.php">Log in</a>
  </div>
 
</div>
 
</body>
</html>
