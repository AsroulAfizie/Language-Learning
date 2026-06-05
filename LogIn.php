<?php
session_start();
$host = "localhost";
$db_name = "language_learning";
$db_username  = "root";
$db_password = "";
 
$conn = mysqli_connect($host, $db_username, $db_password, $db_name);
 
 //Database Not Connnected

 if(!$conn){
	die("Connection failed: ".mysqli_connect_error());
}
 
// if(!isset($_SESSION['User_ID'])) {
// 	header("Location: MainPage.html");
// 	exit();
// }

// if(isset($_SESSION['Admin_ID'])){
// 	header("Location: MainPage.php");
// 	exit();
// }

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	$role = $_POST['role'];
	$username = $_POST['username'];
	$password = $_POST['password'];

	$allowed_roles = ['Admin', 'Player'];

	if(empty($username) || empty($password)){
		$error = "Please fill in all fields.";
	} elseif(!in_array($role, $allowed_roles)){
		$error = "Please select a valid role.";
	} else {
		if($role === 'Player'){
			$stmt = $conn->prepare("SELECT User_ID, Username, password
									FROM User_Detail
									WHERE Username = ?");
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$row = $stmt->get_result()->fetch_assoc();
			$stmt->close();

			if($row && $password === $row['password']){
				$_SESSION['User_ID'] = $row['User_ID'];
				$_SESSION['username'] = $row['Username'];

				$stmt = $conn->prepare("UPDATE Current_Progress
										SET LastLogIn = NOW()
										WHERE User_ID = ?");
				$stmt->bind_param("i", $row['User_ID']);
				$stmt->execute();
				$stmt->close();

				header("Location: MainPage.php");
				exit();
			} else {
				$error = "Invalid Username or Password.";
			}
		}elseif($role === 'Admin'){
			$stmt = $conn->prepare("SELECT Admin_ID, Username, password
									FROM Admin_Detail
									WHERE Username = ?");
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$row = $stmt->get_result()->fetch_assoc();
			$stmt->close();

			if($row && $password === $row['password']){
				$_SESSION['Admin_ID'] = $row['Admin_ID'];
				$_SESSION['username'] = $row['Username'];

				$stmt = $conn->prepare("UPDATE Admin_Detail
										SET LastLogIn = NOW()
										WHERE Admin_ID = ?");
				$stmt->bind_param("i", $row['Admin_ID']);
				$stmt->execute();
				$stmt->close();

				header("Location: Admin_Dashboard.php");
				exit();
			} else {
				$error = "Invalid Username or Password.";
			}
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
  <title>Login</title>
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
 
    /* Role selector */
    .role-group {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
 
    .role-option {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      cursor: pointer;
      background: #fff;
      transition: background 0.15s;
    }
 
    .role-option:hover { background: #f0f0f0; }
 
    .role-option input[type="radio"] {
      accent-color: #333;
      width: 15px;
      height: 15px;
      cursor: pointer;
    }
 
    .role-option label {
      cursor: pointer;
      font-size: 0.95rem;
      color: #333;
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
 
    .register-link {
      text-align: center;
      margin-top: 16px;
      font-size: 0.9rem;
      color: #888;
    }
 
    .register-link a {
      color: #333;
      text-decoration: underline;
    }
  </style>
</head>
<body>
 
<div class="container">
  <h2>Welcome Back</h2>
  <p class="subtitle">Log in to continue.</p>
 
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
 
  <form method="POST" action="login.php">
 
    <!-- Role selector -->
    <div class="role-group">
      <div class="role-option" onclick="this.querySelector('input').checked=true">
        <input type="radio"
               name="role"
               value="Player"
               <?= (!isset($_POST['role']) || $_POST['role'] === 'Player') ? 'checked' : '' ?>
               required />
        <label>Player</label>
      </div>
      <div class="role-option" onclick="this.querySelector('input').checked=true">
        <input type="radio"
               name="role"
               value="Admin"
               <?= (isset($_POST['role']) && $_POST['role'] === 'Admin') ? 'checked' : '' ?> />
        <label>Admin</label>
      </div>
    </div>
 
    <div class="field">
      <label>Username</label>
      <input type="text"
             name="username"
             value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
             placeholder="Enter your username"
             required />
    </div>
 
    <div class="field">
      <label>Password</label>
      <input type="password"
             name="password"
             placeholder="Enter your password"
             required />
    </div>
 
    <button type="submit">Log In</button>
 
  </form>
 
  <div class="register-link">
    Don't have an account? <a href="register.php">Register</a>
  </div>
 
</div>
 
</body>
</html>