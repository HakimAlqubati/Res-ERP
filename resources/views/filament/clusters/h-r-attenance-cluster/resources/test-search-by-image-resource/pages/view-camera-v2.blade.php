<!DOCTYPE html>
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
            /* box-sizing: border-box; */
            font-family: 'Roboto', sans-serif;
        }


        #videoWrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 96px;
            overflow: hidden;
            border: 0.5px solid rgba(0, 60, 30, 0.7);
            /* إطار مناسب مع اللون الأخضر */
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
        }


        body {
            margin: 0;
            padding: 30px;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* background-color: #0b65ed; */
            /* background: linear-gradient(20deg, rgb(0, 100, 46), rgb(0, 255, 128)); */
            background: linear-gradient(135deg, rgba(0, 150, 70, 1), rgba(0, 100, 46, 0.8) 50%, rgba(0, 50, 30, 1));

            color: #ffffff;
        }

        canvas {
            position: absolute;
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
            color: #ffffff;
            font-size: 1.5em;
        }


        /* Loader Styles */
        #loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            text-align: center;
            z-index: 1000;
            /* Make sure loader is on top */
        }


        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #4caf50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            /* Only spinner rotates */
            margin-bottom: 10px;
            /* Space between spinner and text */
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }


        #loader p {
            margin: 0;
            padding: 8px 16px;
            color: #ffffff;
            font-size: 1em;
            font-weight: 500;
            background-color: rgba(0, 0, 0, 0.7);
            /* Semi-transparent dark background */
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            /* Shadow for depth */
        }



        #capturedImage,
        #video {
            border-bottom-left-radius: 66px;
            border-top-right-radius: 66px;
            margin: 3px;
            border-radius: 120px;
            overflow: hidden;
            /* border: 5px solid rgba(0, 60, 30, 0.7); */
            /* إطار مناسب مع اللون الأخضر */
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
        }


        #messageContainer {
            margin-top: 20px;
            text-align: center;
        }

        #time,
        #message {
            color: #ffffff;
            font-weight: bold;
            font-size: 1.1em;
        }

        #leftPanel {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        #logo {
            width: 190px;
            height: 150px;
            /* border-radius: 50%; */
            /* border: 2px solid #ffffff; */
            /* box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3); */
        }

        #description {
            font-size: 1.2em;
            font-weight: 500;
            color: #ffffff;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 8px 16px;
            border-radius: 8px;
            max-width: 431px;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            display: none;
        }

        #greeting {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2em;
            font-weight: bold;
            color: #ffffff;
            z-index: 1000;
            letter-spacing: 5px !important;
        }

        #greeting img {
            width: 100px;
            height: 100px;
        }

        /* إضافة تأثير النبض */
        #icon {
            width: 100px;
            height: 100px;
            animation: pulse 3s infinite;
            /* مدة النبض وتكراره */
            margin-right: 20px;
        }

        /* تأثير النبض */
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                /* تكبير طفيف */
                opacity: 0.8;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    {{-- <video id="video" width="720" height="560" autoplay muted></video> --}}

    <div id="leftPanel">
        <img id="logo" src="{{ asset('storage/logo/default-wb.png') }}" alt="Logo">
        <div id="description">Stand in front of the camera to register your attendance for work.</div>
    </div>

    <div id="greeting">
        <span id="greetingText"></span>
        <img id="icon" src="" alt="Icon">
    </div>
    <div id="videoWrapper">
        <video id="video" width="720" height="560" autoplay muted></video>
        <canvas id="overlayCanvas"></canvas>
    </div>
    <img id="capturedImage" />
    <div id="loader">
        <div class="spinner"></div> <!-- Spinner rotates -->
        <p>Wait for the employee's photo to be matched.</p> <!-- Static message -->
    </div>


    <p style="display: none" id="helloEmployee">Hello</p>
    <br>
    <div id="messageContainer">
        <p id="message"></p>
        <p id="time"></p>
        <p id="currentDate"></p>
        <p id="currentTime"></p>
    </div>
    <div id="uploadedImageContainer">
        <span class="icon">✔</span>
        <span id="successMessage"></span>
        <img id="uploadedImage" />
    </div>

    <script src="{{ asset('/js/faceapi.js') }}"></script>

    <script>
        let noFaceTimeout; // To keep track of the timer
        const reopenButton = document.createElement('button');
        // Style the button
        reopenButton.textContent = 'Reopen Camera';
        reopenButton.style.display = 'none';
        reopenButton.style.position = 'absolute';
        reopenButton.style.top = '50%';
        reopenButton.style.left = '50%';
        reopenButton.style.transform = 'translate(-50%, -50%)';
        reopenButton.style.padding = '10px 20px';
        reopenButton.style.fontSize = '1.2em';
        reopenButton.style.backgroundColor = '#4caf50';
        reopenButton.style.color = '#ffffff';
        reopenButton.style.border = 'none';
        reopenButton.style.borderRadius = '8px';
        reopenButton.style.cursor = 'pointer';
        reopenButton.style.boxShadow = '0px 4px 8px rgba(0, 0, 0, 0.2)';


        // Add an event listener to reopen the camera
        reopenButton.addEventListener('click', () => {
            reopenButton.style.display = 'none'; // Hide the button
            startVideo(); // Restart the camera
        });


        // Function to reset the timer for no face detection
        function resetNoFaceTimer() {
            clearTimeout(noFaceTimeout);
            noFaceTimeout = setTimeout(() => {
                stopVideo();
                reopenButton.style.display = 'block';
            }, 60000); // 1-minute timeout
        }
        const video = document.getElementById('video');
        const overlayCanvas = document.getElementById('overlayCanvas');
        const messageDiv = document.getElementById('message');
        const timeDiv = document.getElementById('time');
        const currentTime = document.getElementById('currentTime');
        const currentDate = document.getElementById('currentDate');
        const uploadedImageContainer = document.getElementById('uploadedImageContainer');
        const uploadedImage = document.getElementById('uploadedImage');
        const capturedImage = document.getElementById('capturedImage');

        // Initially hide the loader
        loader.style.display = 'none';

        let loaderActive = false;

        let blinkDetected = false;

        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceExpressionNet.loadFromUri('{{ asset('models') }}')
        ]).then(stopVideo);

        function startVideo() {
            navigator.mediaDevices.getUserMedia({
                    video: {}
                })
                .then((stream) => {
                    video.srcObject = stream;
                    resetNoFaceTimer(); // Start/reset the timer
                })
                .catch(err => console.error(err));
        }

        function stopVideo() {
            const stream = video.srcObject;
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            video.srcObject = null;
        }

        let startTime;

        async function captureFullFrame() {
            // Record the start time right before capturing
            startTime = Date.now();

            const captureCanvas = document.createElement('canvas');
            captureCanvas.width = video.videoWidth;
            captureCanvas.height = video.videoHeight;
            const ctx = captureCanvas.getContext('2d');
            ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
            const dataUrl = captureCanvas.toDataURL('image/png');

            // Display the captured image and hide the video
            capturedImage.src = dataUrl;
            capturedImage.style.display = 'block';
            video.style.display = 'none';

            // Get the full URL
            const fullUrl = window.location.href;

            // Split the URL into parts by '/'
            const urlParts = fullUrl.split('/');

            // Get the last two parameters
            const currentDate = urlParts[urlParts.length - 2]; // Second last part
            const currentTime = urlParts[urlParts.length - 1]; // Last part


            // await uploadImage(dataUrl, currentDate, currentTime);
        }

        async function uploadImage(dataUrl, date, time) {
            try {

                // Display loader and activate the loader flag
                loader.style.display = 'flex';
                loaderActive = true;

                const response = await fetch("{{ route('upload.captured.image') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        image: dataUrl,
                        date: date,
                        time: time
                    })
                });

                const result = await response.json();

                // Calculate elapsed time and format it
                const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(2); // in seconds


                if (result.status === 'success') {
                    console.log(result)
                    // Show the success message with the result from Rekognition
                    messageDiv.textContent = result.message;
                    currentDate.textContent = date;
                    currentTime.textContent = time;
                    // document.getElementById('helloEmployee').style.display = 'block'
                    timeDiv.textContent = `Time used :(${elapsedTime}) seconds`;
                    // timeDiv.textContent = result.message;
                } else {
                    // Show the error message
                    messageDiv.textContent = result.message;
                    // messageDiv.textContent = `${result.message} Task completed in ${elapsedTime} seconds.`;
                    timeDiv.textContent = `Time used :(${elapsedTime}) seconds`;
                    messageDiv.style.color = "red";
                }

                // Display the uploaded image
                uploadedImageContainer.style.display = 'flex';
                // uploadedImage.src = dataUrl;

                // Hide loader and deactivate loader flag
                loader.style.display = 'none';
                loaderActive = false;
                // Stop the camera after upload
                stopVideo();
            } catch (error) {
                console.error("Error uploading image:", error);
                messageDiv.textContent = "Error uploading image!";
                messageDiv.style.color = "red";
            } finally {
                // Hide the loader after uploading is complete
                loader.style.display = 'none';
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
                // Only process detections if the loader is not active
                // if (!loaderActive) {
                    const detections = await faceapi.detectAllFaces(video, new faceapi
                            .TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceExpressions();
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);


                    if (detections.length === 0) {
                        console.log('No face detected');
                        return;
                    }

                    const landmarks = detections[0].landmarks;

                    // Get ear landmarks
                    const {
                        leftEarPoints,
                        rightEarPoints
                    } = getEarLandmarks(landmarks);

                    // Log ear points for debugging
                    console.log("Left Ear Points:", leftEarPoints);
                    console.log("Right Ear Points:", rightEarPoints);

                    // Check if both ears are visible
                    if (areBothEarsVisible(leftEarPoints, rightEarPoints)) {
                        console.log("Both ears detected!");
                    } else {
                        console.log("One or both ears are not visible.");
                    }

                    // Example eye landmarks (these points would come from a face detection library)

                    const ctx = canvas.getContext('2d', {
                        willReadFrequently: true
                    });
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    // Draw detections only if loader is not active
                    if (!loaderActive) {
                        faceapi.draw.drawDetections(canvas, resizedDetections);
                        faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                        faceapi.draw.drawFaceExpressions(canvas, resizedDetections);
                    }


                    if (detections.length > 0) {
                        resetNoFaceTimer(); // Reset the timer when a face is detected
                    }

                    if (detections.length > 0 && !hasCaptured) {
                        hasCaptured = true;

                        // Wait for 5 seconds before capturing
                        setTimeout(() => {
                            // captureFullFrame();
                        }, 3000);
                    }
                // }
            }, 100);
        });


        // Function to validate if an ear is visible
        function isEarVisible(earPoints) {
            if (!earPoints || earPoints.length === 0) return false;

            // Calculate the average position of the ear points
            const averageX = earPoints.reduce((sum, point) => sum + point._x, 0) / earPoints.length;
            const averageY = earPoints.reduce((sum, point) => sum + point._y, 0) / earPoints.length;

            // Example thresholds (adjust based on your video dimensions)
            const isXValid = averageX > 50 && averageX < 600; // X range
            const isYValid = averageY > 50 && averageY < 400; // Y range

            return isXValid && isYValid;
        }

        // Main function to check both ears
        function areBothEarsVisible(leftEarPoints, rightEarPoints) {
            const leftEarVisible = isEarVisible(leftEarPoints);
            const rightEarVisible = isEarVisible(rightEarPoints);

            return leftEarVisible && rightEarVisible; // Both ears must be visible
        }
        // Function to get approximate ear landmarks
        function getEarLandmarks(landmarks) {
            // Approximation based on facial outline (can be adjusted)
            const leftEarPoints = landmarks.positions.slice(0, 4); // Adjust indices as needed
            const rightEarPoints = landmarks.positions.slice(12, 16); // Adjust indices as needed

            return {
                leftEarPoints,
                rightEarPoints
            };
        }
        // Function to check if both ears are visible
        function areEarsVisible(leftEarPoints, rightEarPoints) {
            const isLeftEarVisible = isEarVisible(leftEarPoints);
            const isRightEarVisible = isEarVisible(rightEarPoints);

            return isLeftEarVisible && isRightEarVisible;
        }
    </script>



    <script>
        // Laravel-passed current time in 24-hour format
        const currentHour = @json($currentTime);

        document.addEventListener("DOMContentLoaded", function() {
            const icon = document.getElementById("icon");
            const greetingText = document.getElementById("greetingText");

            if (currentHour >= 6 && currentHour < 18) {
                icon.src = 'storage/icons/sun.png'; // Add path to sun icon
                greetingText.textContent = "Good Morning";
            } else {
                icon.src = 'storage/icons/crescent-moon.png'; // Add path to moon icon
                greetingText.textContent = "Good Evening";
            }
        });


        // Function to calculate Eye Aspect Ratio (EAR)
        function calculateEAR(eyeLandmarks) {
            // Get key points for the eye
            const [p1, p2, p3, p4, p5, p6] = eyeLandmarks;

            // Vertical distances (top to bottom of eye)
            const vertical1 = Math.sqrt(Math.pow(p2.x - p6.x, 2) + Math.pow(p2.y - p6.y, 2)); // d2
            const vertical2 = Math.sqrt(Math.pow(p3.x - p5.x, 2) + Math.pow(p3.y - p5.y, 2)); // d4

            // Horizontal distance (left to right of eye)
            const horizontal = Math.sqrt(Math.pow(p1.x - p4.x, 2) + Math.pow(p1.y - p4.y, 2)); // d1

            // Calculate EAR
            const EAR = (vertical1 + vertical2) / (2 * horizontal);

            return EAR; // Return the Eye Aspect Ratio
        }

        // Function to detect blinking using EAR thresholds
        function isBlinking(leftEyeLandmarks, rightEyeLandmarks, threshold = 0.3) {

            // Calculate EAR for both eyes
            const leftEAR = calculateEAR(leftEyeLandmarks);
            const rightEAR = calculateEAR(rightEyeLandmarks);

            console.log(`Left EAR: ${leftEAR}, Right EAR: ${rightEAR}`); // Debugging EAR values

            // Check if both eyes' EAR are below the threshold
            if (leftEAR < threshold && rightEAR < threshold) {
                return true; // Blink detected
            }

            return false; // No blink detected
        }


        // function isBlinking(landmarks) {
        //     const leftEye = landmarks.getLeftEye();
        //     const rightEye = landmarks.getRightEye();

        //     const leftEyeHeight = Math.abs(leftEye[1].y - leftEye[5].y);
        //     const leftEyeWidth = Math.abs(leftEye[0].x - leftEye[3].x);

        //     const rightEyeHeight = Math.abs(rightEye[1].y - rightEye[5].y);
        //     const rightEyeWidth = Math.abs(rightEye[0].x - rightEye[3].x);

        //     const leftRatio = leftEyeHeight / leftEyeWidth;
        //     const rightRatio = rightEyeHeight / rightEyeWidth;

        //     console.log(`Left Ratio: ${leftRatio}, Right Ratio: ${rightRatio}`); // Debugging ratios

        //     return leftRatio < 0.3 && rightRatio < 0.3; // Adjusted threshold
        // }
    </script>
</body>

</html>
