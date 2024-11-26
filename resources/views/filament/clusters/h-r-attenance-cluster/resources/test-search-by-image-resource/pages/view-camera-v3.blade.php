<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Face Depth Detection</title>
    <style>
        * {
            font-family: 'Roboto', sans-serif;
        }

        body {
            margin: 0;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(135deg, rgba(0, 150, 70, 1), rgba(0, 100, 46, 0.8) 50%, rgba(0, 50, 30, 1));
            color: #ffffff;
        }

        video {
            border-radius: 20px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
            transform: scaleX(-1); /* Flip the video to correct mirroring */
        }

        canvas {
            position: absolute;
        }
    </style>
</head>

<body>
    <video id="video" width="720" height="560" autoplay muted></video>
    <canvas id="overlayCanvas" width="720" height="560"></canvas>

    <script src="{{ asset('/js/faceapi.js') }}"></script>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('overlayCanvas');
        let lastNosePosition = null;
        let depthDetected = false; // Variable to track if depth has been detected

        const startVideo = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: false
                });
                video.srcObject = stream;
                video.play();
            } catch (error) {
                console.error("Error accessing webcam: ", error);
                alert("Could not access your webcam. Please check browser permissions.");
            }
        };

        const loadModels = async () => {
            // Load models from your specified path
            await faceapi.nets.tinyFaceDetector.loadFromUri('{{ asset('models') }}');
            await faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset('models') }}');
        };

        const detectDepth = async () => {
            const displaySize = { width: video.width, height: video.height };
            faceapi.matchDimensions(canvas, displaySize);

            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks(); // Facial landmarks detection

            const resizedDetections = faceapi.resizeResults(detections, displaySize);

            // Clear previous drawings
            const context = canvas.getContext("2d");
            context.clearRect(0, 0, canvas.width, canvas.height);

            // Flip canvas horizontally for alignment with video
            context.save();
            context.scale(-1, 1);
            context.translate(-canvas.width, 0);

            // Draw detections and landmarks
            faceapi.draw.drawDetections(canvas, resizedDetections);
            faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);

            context.restore();

            // Detect head movement based on nose position (simple depth approximation)
            if (resizedDetections.length > 0) {
                const landmarks = resizedDetections[0].landmarks;
                const nose = landmarks.getNose()[0]; // Get nose position

                if (lastNosePosition !== null) {
                    const movement = Math.abs(nose.x - lastNosePosition.x) + Math.abs(nose.y - lastNosePosition.y);

                    if (movement > 5) {
                        depthDetected = true; // Indicates depth change or movement
                        console.log("Face depth detected, likely a real human face!");
                    } else {
                        depthDetected = false; // No significant movement
                    }
                }

                lastNosePosition = { x: nose.x, y: nose.y }; // Store current nose position
            }

            // Log message for depth detection
            if (!depthDetected) {
                console.log("Face appears to be flat, possibly a photo or screen.");
            }
        };

        // Start video and detection when models are loaded
        const initializeApp = async () => {
            await loadModels();
            await startVideo();

            video.addEventListener('play', () => {
                setInterval(detectDepth, 100); // Detect depth every 100 ms
            });
        };

        initializeApp();
    </script>
</body>

</html>
