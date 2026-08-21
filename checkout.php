<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Payment | InnovaHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(-45deg, #0d1b2a, #1b263b, #415a77, #0d1b2a);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 22px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h2 {
            color: #00ffff;
            margin-bottom: 25px;
            font-weight: 600;
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
        }

        input[type="number"]:focus {
            border-color: #00ffff;
            box-shadow: 0 0 8px rgba(0, 255, 255, 0.2);
        }

        button {
            padding: 14px;
            background: #00ffff;
            color: #0d1b2a;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s ease;
            text-transform: uppercase;
        }

        button:hover {
            background: #fff;
            box-shadow: 0 0 20px #00ffff;
            transform: translateY(-2px);
        }

        ::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Pay Now</h2>

    <form action="payment_process.php" method="POST">
        <input type="number" name="amount" placeholder="Enter Amount (₹)" required>
        <button type="submit">Proceed to Payment</button>
    </form>
</div>

</body>
</html>