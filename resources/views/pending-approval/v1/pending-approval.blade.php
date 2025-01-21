<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Face Capture</title>
    <!-- Google Fonts - Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Global Styles */
        * {
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        /* Watermark Styles */
        body {
            position: relative;
            margin: 0;
            padding: 30px;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, rgba(0, 150, 70, 1), rgba(0, 100, 46, 0.8) 50%, rgba(0, 50, 30, 1));
            color: #ffffff;
            overflow: hidden;
            /* Ensure the watermark doesn't cause scrollbars */
        }

        /* Pseudo-element for the watermark */
        body::before {
            content: "NLT";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8vw;
            color: rgba(255, 255, 255, 0.1);
            pointer-events: none;
            /* Allow clicks to pass through */
            z-index: -1;
            /* Place behind other content */
        }

        /* Container for the alert */
        .alert-container {
            position: relative;
            z-index: 1;
            /* Ensure the alert is above the watermark */
            max-width: 600px;
            width: 100%;
            padding: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Alert Styles */
        .alert {
            border: none;
            background: transparent;
        }

        .alert-heading {
            color: #ffffff;
        }

        .alert-link {
            color: #ffeb3b;
            text-decoration: none;
        }

        .alert-link:hover {
            text-decoration: underline;
        }

        /* Optional: Responsive Font Size */
        @media (max-width: 768px) {
            body::before {
                font-size: 12vw;
            }
        }
    </style>
</head>

<body>

    <div class="alert-container">
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">Request Pending Approval</h4>
            <p>Your request for access is still pending approval. Please contact system admin</p>
            <hr>
            <p class="mb-0">

                <a href="{{ route('workbench_webcam_check') }}" class="alert-link">Refresh</a>.
            </p>
        </div>
    </div>

</body>

</html>
