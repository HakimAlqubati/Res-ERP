<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>التحقق من حيوية الوجه - Workbench ERP</title>
    <style>
        body {
            background: linear-gradient(135deg, #009646, #00332b 80%);
            color: #fff;
            font-family: 'Tajawal', 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .camera-container {
            position: relative;
            width: 400px;
            height: 320px;
            margin: 20px auto;
        }
        #video, #canvas {
            border-radius: 16px;
            position: absolute;
            top: 0; left: 0;
        }
        #info {
            margin: 16px;
            font-size: 18px;
            background: rgba(0,0,0,0.18);
            border-radius: 8px;
            padding: 8px 16px;
            min-height: 32px;
            text-align: center;
        }
        #startLiveness {
            background: #00b876;
            color: #fff;
            border: none;
            font-size: 19px;
            padding: 13px 36px;
            border-radius: 10px;
            margin: 24px 0 0 0;
            cursor: pointer;
            font-weight: bold;
            letter-spacing: 1.2px;
            display: none;
            transition: background .2s;
        }
        #startLiveness:active {
            background: #007a4f;
        }
    </style>
</head>
<body>
    <h2>التحقق من حيوية الوجه</h2>
    <div id="info">يرجى وضع وجهك داخل الإطار وانتظر حتى يظهر زر البدء</div>
    <div class="camera-container">
        <video id="video" width="400" height="320" autoplay muted></video>
        <canvas id="canvas" width="400" height="320"></canvas>
    </div>
    <button id="startLiveness">بدء التحقق من الحيوية</button>

    <!-- face-api.js من مسارك أو CDN أو public/js -->
    <script src="{{ asset('/js/faceapi.js') }}"></script>
    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const info = document.getElementById('info');
        const startBtn = document.getElementById('startLiveness');

        let faceReady = false;
        let timerShow = null;

        async function loadModels() {
            await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
        }

        async function startVideo() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
            } catch (e) {
                info.textContent = "تعذر فتح الكاميرا، يرجى التحقق من الصلاحيات.";
            }
        }

        function drawCenterOval(ctx, w, h) {
            ctx.save();
            ctx.strokeStyle = "#2ee6a1";
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.ellipse(w/2, h/2, 110, 145, 0, 0, Math.PI*2);
            ctx.stroke();
            ctx.restore();
        }

        video.addEventListener('play', () => {
            setInterval(async () => {
                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // رسم الإطار البيضاوي
                drawCenterOval(ctx, canvas.width, canvas.height);

                if (detections.length === 1) {
                    // مركز الوجه
                    const box = detections[0].detection.box;
                    const centerX = box.x + box.width / 2;
                    const centerY = box.y + box.height / 2;

                    // الإطار المركزي (نفس الإهليلج)
                    const ovalX = canvas.width / 2;
                    const ovalY = canvas.height / 2;
                    const ovalW = 110;
                    const ovalH = 145;

                    // تحقق أن مركز الوجه ضمن الإطار بدقة كافية
                    const dist = Math.sqrt(
                        Math.pow((centerX - ovalX) / ovalW, 2) +
                        Math.pow((centerY - ovalY) / ovalH, 2)
                    );

                   console.log('sdf',dist)
                    if (dist < 1.5) {
                        info.textContent = "تموضع جيد! انتظر لحظة حتى يظهر زر البدء...";
                        console.log('ttt',faceReady)
                        if (!faceReady) {
                            faceReady = true;
                            if (timerShow) clearTimeout(timerShow);
                            timerShow = setTimeout(() => {
                                startBtn.style.display = "inline-block";
                                info.textContent = "اضغط زر البدء للانتقال لاختبار الحيوية.";
                                console.log('sdfsdfsdf')
                            }, 500); // انتظار بسيط لثبات المستخدم
                        }
                    } else {
                        faceReady = false;
                        startBtn.style.display = "none";
                        info.textContent = "يرجى تقريب وجهك إلى مركز الإطار.";
                        if (timerShow) clearTimeout(timerShow);
                    }
                } else if (detections.length === 0) {
                    faceReady = false;
                    startBtn.style.display = "none";
                    info.textContent = "لم يتم العثور على وجه. يرجى ضبط الإضاءة أو تحريك الكاميرا.";
                    if (timerShow) clearTimeout(timerShow);
                } else if (detections.length > 1) {
                    faceReady = false;
                    startBtn.style.display = "none";
                    info.textContent = "يرجى التأكد من وجود وجه واحد فقط في الكاميرا.";
                    if (timerShow) clearTimeout(timerShow);
                }
            }, 300);
        });

        // التحميل والترتيب
        loadModels().then(startVideo);

        // عند الضغط على زر بدء التحقق
        startBtn.onclick = function () {
    document.querySelector('.camera-container').style.display = 'none';
    startBtn.style.display = "none";
    info.textContent = "جاري جلب sessionId ...";

    fetch('/api/aws/employee-liveness/start-session', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.sessionId) {
                // يمكنك عرض sessionId في الصفحة أو نسخه آليًا أو طباعته في الـ console
                info.textContent = "Session ID: " + data.sessionId;
                console.log("Session ID:", data.sessionId);

                // إذا أردت نسخه آليًا إلى الحافظة (اختياري)
                /*
                navigator.clipboard.writeText(data.sessionId).then(() => {
                    info.textContent += " (تم النسخ تلقائياً)";
                });
                */
            } else {
                info.textContent = "فشل في الحصول على sessionId. تحقق من الـ API.";
            }
        })
        .catch(() => {
            info.textContent = "تعذر بدء اختبار الحيوية. حاول لاحقاً.";
        });
};

    </script>
</body>
</html>
