<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Received Notes Count</title>
        <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .count-display {
            font-size: 64px;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0;
        }
        .description {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }
        .filter-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .filter-section label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        .filter-section input[type="date"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Approved GRN Count</h1>
        
        <form method="GET" action="{{ route('reports.grn-count') }}" id="filter-form">
            <div class="filter-section">
                <label for="date">Select Date (up to):</label>
                <input type="date" id="date" name="date" value="{{ $selectedDate }}" onchange="document.getElementById('filter-form').submit();">
            </div>
        </form>

        <div class="count-display">
            {{ number_format($count) }}
        </div>
        <p class="description">
            Approved & Uninvoiced GRNs (until {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}).
        </p>
    </div>
</body>
</html>
