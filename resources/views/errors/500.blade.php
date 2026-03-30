<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f9fafb;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .error-wrapper {
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 4rem 3rem;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
            text-align: center;
            border-top: 4px solid #0d7c66;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin: 0 auto 3rem;
            display: block;
            opacity: 0.95;
        }
        .icon {
            color: #0d7c66;
            width: 52px;
            height: 52px;
            margin: 0 auto 1.5rem;
            display: block;
            opacity: 0.85;
            stroke-width: 1.25;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 300;
            letter-spacing: -0.02em;
            color: #0d7c66;
            margin: 0;
            line-height: 1;
        }
        .error-divider {
            width: 48px;
            height: 2px;
            background-color: #e5e7eb;
            margin: 2rem auto;
            border-radius: 2px;
        }
        .error-title {
            font-size: 1.125rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #4b5563;
            margin: 0 0 1rem 0;
        }
        .error-desc {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 2.5rem;
            line-height: 1.5;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: #ffffff;
            color: #4b5563;
            padding: 0.75rem 1.75rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }
        .btn-home:hover {
            background-color: #f9fafb;
            border-color: #0d7c66;
            color: #0d7c66;
        }
        .btn-home svg {
            width: 1.125rem;
            height: 1.125rem;
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <img src="{{ asset('default.png') }}" alt="System Logo" class="logo">
        
        <!-- Warning Icon representing Server Error -->
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>

        <h1 class="error-code">500</h1>
        <div class="error-divider"></div>
        <h2 class="error-title">System Error</h2>
        <p class="error-desc">An unexpected error has occurred. If your session expired due to account changes, please log in again.</p>
        
        <!-- Action Button -->
        <a href="{{ url('/admin/login') }}" class="btn-home">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            Go to Login
        </a>
    </div>
</body>
</html>
