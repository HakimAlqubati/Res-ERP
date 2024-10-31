<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Face Capture</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        /*
        body {
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #f4f6f8;
        } */

        #videoWrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        canvas {
            position: absolute;
        }

        #message {
            margin-top: 20px;
            color: #4caf50;
            font-size: 1.2em;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #uploadedImageContainer {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
            display: none;
            /* Hidden by default */
        }

        #uploadedImage {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 8px;
            border: 2px solid #4caf50;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .icon {
            color: #4caf50;
            font-size: 1.5em;
        }
    </style>
</head>

<body>

    <video id="video" width="720" height="560" autoplay muted></video>
    <div id="message"></div>
    <div id="uploadedImageContainer">
        <span class="icon">✔</span>
        <span id="successMessage">Image uploaded successfully!</span>
        <img id="uploadedImage" />
    </div>

    <script src="{{ asset('/js/faceapi.js') }}"></script>

    <script>
        const video = document.getElementById('video');
        const messageDiv = document.getElementById('message');
        const uploadedImageContainer = document.getElementById('uploadedImageContainer');
        const uploadedImage = document.getElementById('uploadedImage');

        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceExpressionNet.loadFromUri('{{ asset('models') }}')
        ]).then(startVideo);

        function startVideo() {
            navigator.mediaDevices.getUserMedia({
                    video: {}
                })
                .then(stream => video.srcObject = stream)
                .catch(err => console.error(err));
        }

        function stopVideo() {
            const stream = video.srcObject;
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            video.srcObject = null;
        }

        async function captureFullFrame() {
            const captureCanvas = document.createElement('canvas');
            captureCanvas.width = video.videoWidth;
            captureCanvas.height = video.videoHeight;
            const ctx = captureCanvas.getContext('2d');
            ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
            const dataUrl = captureCanvas.toDataURL('image/png');
            await uploadImage(dataUrl);
        }

        async function uploadImage(dataUrl) {
            try {
                const response = await fetch("{{ route('upload.captured.image') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        image: dataUrl
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Show the success message with the result from Rekognition
                    messageDiv.textContent = result.message;
                    messageDiv.style.color = "#4caf50";
                } else {
                    // Show the error message
                    messageDiv.textContent = result.message;
                    messageDiv.style.color = "red";
                }

                // Display the uploaded image
                uploadedImageContainer.style.display = 'flex';
                uploadedImage.src = dataUrl;

                // Stop the camera after upload
                stopVideo();
            } catch (error) {
                console.error("Error uploading image:", error);
                messageDiv.textContent = "Error uploading image!";
                messageDiv.style.color = "red";
            }
        }

        video.addEventListener('play', () => {
            const canvas = faceapi.createCanvasFromMedia(video);
            document.body.append(canvas);
            const displaySize = {
                width: video.width,
                height: video.height
            };
            faceapi.matchDimensions(canvas, displaySize);

            let hasCaptured = false;

            setInterval(async () => {
                const detections = await faceapi.detectAllFaces(video, new faceapi
                        .TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceExpressions();
                const resizedDetections = faceapi.resizeResults(detections, displaySize);

                const ctx = canvas.getContext('2d', {
                    willReadFrequently: true
                });
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                faceapi.draw.drawDetections(canvas, resizedDetections);
                faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                faceapi.draw.drawFaceExpressions(canvas, resizedDetections);

                if (detections.length > 0 && !hasCaptured) {
                    hasCaptured = true;

                    // Wait for 5 seconds before capturing
                    setTimeout(() => {
                        captureFullFrame();
                    }, 3000);
                }
            }, 100);
        });
    </script>
</body>

</html>
