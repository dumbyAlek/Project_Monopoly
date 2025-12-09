<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url("Assets/bg.jpg") no-repeat center center/cover;
            font-family: Arial, sans-serif;
        }

        .container {
            text-align: center;
            background: rgba(100, 177, 255, 0.54);
            padding: 40px 60px;
            border-radius: 20px;
            backdrop-filter: blur(3px);
        }

        .logo {
            width: 500px;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        button {
            display: block;
            width: 300px;
            margin: 10px auto;
            padding: 14px;
            font-size: 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .login-btn {
            background: #ffffff;
        }
        .signup-btn {
            background: #4caf50;
            color: white;
        }
        .guest-btn {
            background: rgba(88, 91, 88, 1);
            color: white;
        }

        button:hover {
            opacity: 0.8;
            color: rgba(207, 47, 236, 1);
        }
        .button-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .button-row button {
            width: 225px;
        }

    </style>
</head>
<body>

    <div class="container">
        <img src="Assets/Logo.png" alt="Logo" class="logo" />
        <div class="button-row">
            <button class="login-btn" onclick="location.href='Pages/LoginPage/LoginPage.php'">Login</button>
            <button class="signup-btn" onclick="location.href='Pages/SignUpPage/SignUpPage.php'">Sign Up</button>
        </div>
        <button class="guest-btn" onclick="location.href='Pages/HomePage/HomePage.php'">Play as a Guest</button>
    </div>

</body>
</html>
