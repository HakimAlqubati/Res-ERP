<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Face Liveness Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet"> --}}
    <style>
        body {
            margin: 0;
            background: linear-gradient(135deg, rgb(7 54 29), rgb(4 54 27 / 80%) 50%, rgba(0, 50, 30, 1));
            color: #fff;
            font-family: 'Roboto', Arial, sans-serif;
            min-height: 100vh;
        }
        #container {
            max-width: 500px;
            margin: 50px auto;
            background: #112418;
            border-radius: 18px;
            padding: 32px 18px 24px 18px;
            box-shadow: 0 2px 32px 0 #151d144a;
        }
        #aws-liveness-root {
            margin: 24px 0 0 0;
        }
        #result {
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
            min-height: 40px;
        }
        button {
            font-size: 1.2em;
            padding: 14px 28px;
            border: none;
            background: #18c26e;
            color: #fff;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px 0 #0000002a;
            transition: background 0.2s;
        }
        button:hover { background: #0aa956; }
        h2 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div id="container">
        <h2>Face Liveness Verification</h2>
        <button id="livenessBtn">ابدأ التحقق الحيوي</button>
        <div id="aws-liveness-root"></div>
        <div id="result"></div>
    </div>

    <!-- AWS Liveness SDK -->
    <script src="https://unpkg.com/amazon-rekognition-face-liveness-web@latest/dist/rekognition-face-liveness-web.umd.production.js"></script>
    <script src="https://assets.amazontrust.com/face-liveness-web-sdk/latest/faceLiveness.js"></script>

    <script>
        const apiBase = "{{ url('/api/liveness') }}";

        async function startLivenessSession() {
            const res = await fetch(apiBase + '/start', { method: 'POST' });
            const data = await res.json();
            if (!data.sessionId) { alert('خطأ في بدء الجلسة'); return; }
            return data.sessionId;
        }

        async function checkLivenessResult(sessionId) {
            const res = await fetch(apiBase + '/check', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ sessionId })
            });
            return await res.json();
        }

        document.getElementById('livenessBtn').onclick = async function () {
            console.log(33333333)
            document.getElementById('result').innerHTML = '';
            // 1. احصل على sessionId من الباك إند
            const sessionId = await startLivenessSession();
            if (!sessionId) return;

            // 2. شغل الـ Face Liveness Web SDK من أمازون
            window.RekognitionFaceLivenessWeb.open({
                region: 'us-east-1', // غيّر حسب منطقتك في AWS
                sessionId: sessionId,
                root: document.getElementById('aws-liveness-root'),
                onComplete: async function () {
                    // عند انتهاء التحدي الحيوي
                    const res = await checkLivenessResult(sessionId);
                    if(res.status === "SUCCEEDED" && res.confidence >= 95){
                        document.getElementById('result').innerHTML = '✅ التحقق الحيوي ناجح!<br>الثقة: ' + res.confidence;
                    }else{
                        document.getElementById('result').innerHTML = '❌ فشل التحقق الحيوي<br>' + (res.confidence ? ('درجة الثقة: ' + res.confidence) : '');
                    }
                },
                onError: function (error) {
                    document.getElementById('result').innerHTML = 'حدث خطأ: ' + error;
                }
            });
        };
    </script>
</body>
</html>
