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


        #videoWrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 20px;
            overflow: hidden;
            border: 5px solid rgba(0, 60, 30, 0.7);
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
            margin: 20px;
            border-radius: 20px;
            overflow: hidden;
            border: 5px solid rgba(0, 60, 30, 0.7);
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
        }

        #greeting img {
            width: 30px;
            height: 30px;
        }

        /* إضافة تأثير النبض */
        #icon {
            width: 50px;
            height: 50px;
            animation: pulse 1.5s infinite;
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
    </div>
    <div id="uploadedImageContainer">
        <span class="icon">✔</span>
        <span id="successMessage"></span>
        <img id="uploadedImage" />
    </div>

    <script src="{{ asset('/js/faceapi.js') }}"></script>

    <script>
        const video = document.getElementById('video');
        const overlayCanvas = document.getElementById('overlayCanvas');
        const messageDiv = document.getElementById('message');
        const timeDiv = document.getElementById('time');
        const uploadedImageContainer = document.getElementById('uploadedImageContainer');
        const uploadedImage = document.getElementById('uploadedImage');
        const capturedImage = document.getElementById('capturedImage');

        // Initially hide the loader
        loader.style.display = 'none';

        let loaderActive = false;

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


            await uploadImage(dataUrl);
        }

        async function uploadImage(dataUrl) {
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
                        image: dataUrl
                    })
                });

                const result = await response.json();

                // Calculate elapsed time and format it
                const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(2); // in seconds


                if (result.status === 'success') {
                    console.log(result)
                    // Show the success message with the result from Rekognition
                    messageDiv.textContent = result.message;
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
                if (!loaderActive) {
                    const detections = await faceapi.detectAllFaces(video, new faceapi
                            .TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceExpressions();
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);

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
                    if (detections.length > 0 && !hasCaptured) {
                        hasCaptured = true;

                        // Wait for 5 seconds before capturing
                        setTimeout(() => {
                            captureFullFrame();
                        }, 1000);
                    }
                }
            }, 100);
        });
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
    </script>
</body>

</html>
