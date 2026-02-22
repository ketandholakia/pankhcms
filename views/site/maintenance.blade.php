<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Maintenance</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f7f7f7;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .box {
            width: 100%;
            max-width: 640px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px 32px;
            text-align: center;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 2rem;
        }

        p {
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.6;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🚧 Site Under Maintenance</h1>
        <p>{{ $message ?? 'Scheduled maintenance in progress.' }}</p>
    </div>
</body>
</html>
