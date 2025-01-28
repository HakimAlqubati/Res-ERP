<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Face Capture</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* background-color: #0b65ed; */
            /* background: linear-gradient(20deg, rgb(0, 100, 46), rgb(0, 255, 128)); */
            background: linear-gradient(135deg, rgb(7 54 29), rgb(4 54 27 / 80%) 50%, rgba(0, 50, 30, 1));

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
            transform: scaleX(-1);
            /* Flip the video horizontally */
            -webkit-transform: scaleX(-1);
            /* For older browsers */
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
            width: 90px;
            height: 50px;
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

        #icon {
            display: none;
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

    <button id="reopen" style="z-index: 10;">
        {{ 'Reopen Camera' }}
        <i class="fas fa-redo-alt"></i>
    </button>
    <button id="nextEmployee" style="z-index: 11;">{{ 'Next employee' }}
        <i class="fas fa-arrow-right"></i>

    </button>


    <script src="{{ asset('/js/faceapi.js') }}"></script>

    <script>
        let noFaceTimeout; // To keep track of the timer
        const reopenButton = document.getElementById('reopen');
        const nextEmployeeButton = document.getElementById('nextEmployee');
        // Style the button
        reopenButton.style.display = 'none';
        reopenButton.style.position = 'absolute';
        reopenButton.style.top = '15%';
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

        nextEmployeeButton.style.display = 'none';
        nextEmployeeButton.style.position = 'absolute';
        nextEmployeeButton.style.top = '15%';
        nextEmployeeButton.style.left = '50%';
        nextEmployeeButton.style.transform = 'translate(-50%, -50%)';
        nextEmployeeButton.style.padding = '10px 20px';
        nextEmployeeButton.style.fontSize = '1.2em';
        nextEmployeeButton.style.backgroundColor = '#4caf50';
        nextEmployeeButton.style.color = '#ffffff';
        nextEmployeeButton.style.border = 'none';
        nextEmployeeButton.style.borderRadius = '8px';
        nextEmployeeButton.style.cursor = 'pointer';
        nextEmployeeButton.style.boxShadow = '0px 4px 8px rgba(0, 0, 0, 0.2)';



        // Add an event listener to reopen the camera
        reopenButton.addEventListener('click', () => {
            reopenButton.style.display = 'none'; // Hide the button
            console.log('show up again')
            video.style.display = 'block';
            // startVideo(); // Restart the camera
        });
        // Add an event listener to reopen the camera
        nextEmployeeButton.addEventListener('click', () => {
            // nextEmployeeButton.style.display = 'none'; // Hide the button
            // document.getElementById('capturedImage').display = 'none';
            // startVideo(); // Restart the camera
            location.reload(true);
        });


        const timeoutWebCamValue = @json($timeoutWebCamValue);
        console.log('Timeout Value:', timeoutWebCamValue);
        // Function to reset the timer for no face detection
        function resetNoFaceTimer() {

            clearTimeout(noFaceTimeout);
            noFaceTimeout = setTimeout(() => {
                // Capture time from the database
                // await stopVideo();
                video.style.display = 'none';
                reopenButton.style.display = 'block';
            }, timeoutWebCamValue); //
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

        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('{{ asset('models') }}'),
            // faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset('models') }}'),
            // faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset('models') }}'),
            // faceapi.nets.faceExpressionNet.loadFromUri('{{ asset('models') }}')
        ]).then(startVideo);


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


            await uploadImage(dataUrl, currentDate, currentTime);
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
                    // currentDate.textContent = date;
                    // currentTime.textContent = time;
                    // document.getElementById('helloEmployee').style.display = 'block'
                    timeDiv.textContent = `Time used :(${elapsedTime}) seconds`;
                    document.getElementById('nextEmployee').style.display = 'block';
                    document.getElementById('reopen').style.display = 'none';
                    // timeDiv.textContent = result.message;
                } else {
                    // Show the error message
                    messageDiv.textContent = result.message;
                    // messageDiv.textContent = `${result.message} Task completed in ${elapsedTime} seconds.`;
                    timeDiv.textContent = `Time used :(${elapsedTime}) seconds`;
                    messageDiv.style.color = "white";
                    document.getElementById('nextEmployee').style.display = 'block';
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
                messageDiv.style.color = "white";
                document.getElementById('nextEmployee').style.display = 'block';
            } finally {
                // Hide the loader after uploading is complete
                loader.style.display = 'none';
            }
        }
        const webCamCaptureTime = @json($webCamCaptureTime);
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
                if (!loaderActive) {
                    const detections = await faceapi.detectAllFaces(video, new faceapi
                        .TinyFaceDetectorOptions());
                    // .withFaceLandmarks();
                    // .withFaceExpressions();
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);

                    const ctx = canvas.getContext('2d', {
                        willReadFrequently: true
                    });
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    // Draw detections only if loader is not active
                    if (!loaderActive) {
                        faceapi.draw.drawDetections(canvas, resizedDetections);
                        // faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                        // faceapi.draw.drawFaceExpressions(canvas, resizedDetections);
                    }
                    // Only capture the frame if it hasn't been captured yet and a face is detected
                    if (!hasCaptured) {
                        hasCaptured = true;

                        // Wait for a short delay before capturing to ensure stability
                        setTimeout(() => {
                            // Check again to confirm face is still detected before capturing
                            if (detections.length > 0) {
                                captureFullFrame();
                            } else {
                                hasCaptured = false; // Reset flag if no face detected
                                console.log('No face detected at the time of capture.');
                            }
                        }, webCamCaptureTime);
                    }
                }
            }, 500);
        });
    </script>



    <script>
        // Laravel-passed current time in 24-hour format
        const currentTimePassed = @json($currentTime);

        document.addEventListener("DOMContentLoaded", function() {
            const icon = document.getElementById("icon");
            const greetingText = document.getElementById("greetingText");
            const currentHour = parseInt(currentTimePassed.split(":")[0], 10);

            console.log('sd', currentHour)
            if (currentHour >= 0 && currentHour < 11) { // Morning (6:00 to 17:59)
                icon.src = 'storage/icons/sun.png'; // Add path to sun icon
                greetingText.textContent = "Good Morning";
            } else {
                icon.src = 'storage/icons/crescent-moon.png'; // Add path to moon icon
                greetingText.textContent = "Good Evening";
            }
        });
    </script>
</body>

</html>
