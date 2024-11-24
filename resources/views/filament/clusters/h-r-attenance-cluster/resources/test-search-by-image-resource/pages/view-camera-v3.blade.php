<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Face Capture</title>
    {{-- <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet"> --}}
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
    {{-- <div id="loader">
        <div class="spinner"></div> <!-- Spinner rotates -->
        <p>Wait for the employee's photo to be matched.</p> <!-- Static message -->
    </div> --}}


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
        const video = document.getElementById('video')


        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceLandmark68Net.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceRecognitionNet.loadFromUri('{{ asset('models') }}'),
            faceapi.nets.faceExpressionNet.loadFromUri('{{ asset('models') }}')
        ]).then(startVideo)

        function startVideo() {

            if (navigator.userAgent.match(/iPhone|iPad|Android/)) { ///iPhone|Android.+Mobile/
                console.log("Mobile");
                video.width = 400; //1080;

                navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: false
                    })
                    .then(localMediaStream => {
                        if ('srcObject' in video) {
                            video.srcObject = localMediaStream;
                        } else {
                            video.src = window.URL.createObjectURL(localMediaStream);
                        }
                        video.play();
                    })
                    .catch(err => {
                        console.error(`Not available!!!!`, err);
                    });

            } else {
                console.log("PC");
                navigator.getUserMedia({
                        video: {}
                    },
                    stream => video.srcObject = stream,
                    err => console.error(err)
                )
            }
            console.log("video:" + [video.width, video.height]);

            // let div = document.createElement('div')
            // div.innerText = 'video size:'+video.width+', '+video.height
            // console.log(div.innerText);
            // document.body.appendChild(div)
        }


        // Assuming `landmarkPositions` is already available
        const videoWidth = video.width;
        const videoHeight = video.height;



        function calculateEarVisibility(earPoint, faceBox, isLeft) {
            const {
                top,
                left,
                bottom,
                right
            } = faceBox;

            // Check if the ear is within the face bounding box
            const isEarVisible =
                earPoint.x >= left &&
                earPoint.x <= right &&
                earPoint.y >= top &&
                earPoint.y <= bottom;

            // If the ear is not visible, return 0% visibility
            if (!isEarVisible) {
                return 0;
            }

            // Calculate distance to frame edge (visibility approximation)
            const xDistance = isLeft ? earPoint.x : videoWidth - earPoint.x; // Distance to frame edge
            const maxDistance = videoWidth * 0.2; // Assume max visibility when within 20% of frame width

            // Calculate percentage visibility (clamp to 0–100%)
            return Math.min((xDistance / maxDistance) * 100, 100);
        }

        video.addEventListener('play', () => {

            var canvas_bg = document.createElement("canvas");
            canvas_bg.width = video.width;
            canvas_bg.height = video.height;
            document.body.append(canvas_bg)
            var ctx_bg = canvas_bg.getContext('2d');
            // ctx_bg.fillStyle = "rgb(0,0,0)";
            // ctx_bg.fillRect(0, 0, video.width, video.height/2);

            var canvas_face = document.createElement("canvas");
            canvas_face.width = video.width;
            canvas_face.height = video.height;
            var ctx_face = canvas_face.getContext('2d');

            const canvas = faceapi.createCanvasFromMedia(video)
            document.body.append(canvas)
            const displaySize = {
                width: video.width,
                height: video.height
            }
            faceapi.matchDimensions(canvas, displaySize)

            var t1 = performance.now();
            var irisC = [];
            let nowBlinking = false;
            let blinkCount = 0;

            setInterval(async () => {
                //const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceExpressions()
                const detections = await faceapi.detectAllFaces(video, new faceapi
                    .TinyFaceDetectorOptions()).withFaceLandmarks()
                const resizedDetections = faceapi.resizeResults(detections, displaySize)
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height)
                //faceapi.draw.drawDetections(canvas, resizedDetections)
                faceapi.draw.drawFaceLandmarks(canvas, resizedDetections)
                //faceapi.draw.drawFaceExpressions(canvas, resizedDetections)

                //console.log(resizedDetections);
                const landmarks = resizedDetections[0].landmarks;

                // Assuming you already have `landmarkPositions` from Face API

                //console.log(landmarks);
                const landmarkPositions = landmarks.positions;
                
                // Get ear positions
                const leftEar = landmarkPositions[0]; // Approximate left ear
                const rightEar = landmarkPositions[16]; // Approximate right ear
                
                const faceBox = detections[0].detection.box;
                
                const leftEarVisibility = calculateEarVisibility(leftEar, faceBox, true);
                const rightEarVisibility = calculateEarVisibility(rightEar, faceBox, false);

                const ctx2 = canvas.getContext('2d');
                ctx2.strokeStyle = "green";
                ctx2.lineWidth = 2;
                ctx2.strokeRect(faceBox.x, faceBox.y, faceBox.width, faceBox.height);


                // Log visibility results
                console.log(`Left Ear Visibility: ${leftEarVisibility.toFixed(2)}%`);
                console.log(`Right Ear Visibility: ${rightEarVisibility.toFixed(2)}%`);
                //--- Iric mark ---//
                ctx_bg.clearRect(0, 0, canvas_bg.width, canvas_bg.height)
                var x_ = landmarkPositions[38 - 1].x
                var y_ = landmarkPositions[38 - 1].y
                var w_ = landmarkPositions[39 - 1].x - landmarkPositions[38 - 1].x
                var h_ = landmarkPositions[42 - 1].y - landmarkPositions[38 - 1].y
                ctx_bg.fillStyle = "rgb(255,0,0)";
                // ctx_bg.fillRect(x_, y_, w_, h_)

                x_ = landmarkPositions[44 - 1].x
                y_ = landmarkPositions[44 - 1].y
                w_ = landmarkPositions[45 - 1].x - landmarkPositions[44 - 1].x
                h_ = landmarkPositions[48 - 1].y - landmarkPositions[44 - 1].y
                // ctx_bg.fillRect(x_, y_, w_, h_)


                //--- Iris value ---//
                ctx_face.clearRect(0, 0, canvas_face.width, canvas_face.height)
                ctx_face.drawImage(video, 0, 0, video.width, video.height);
                var frame = ctx_face.getImageData(0, 0, video.width, video.height);
                var p_ = Math.floor(x_ + w_ / 2) + Math.floor(y_ + h_ / 2) * video.width
                //console.log("eye_RGB:"+[frame.data[p_*4+0], frame.data[p_*4+1], frame.data[p_*4+2]]);
                var v_ = Math.floor((frame.data[p_ * 4 + 0] + frame.data[p_ * 4 + 1] + frame.data[p_ *
                    4 + 2]) / 3);
                console.log("irisC:" + v_);

                irisC.push(v_);
                if (irisC.length > 100) {
                    irisC.shift();
                } //

                let meanIrisC = irisC.reduce(function(sum, element) {
                    return sum + element;
                }, 0);
                meanIrisC = meanIrisC / irisC.length;
                let vThreshold = 1.5;

                let currentIrisC = irisC[irisC.length - 1];
                if (irisC.length == 100) {
                    if (nowBlinking == false) {
                        if (currentIrisC >= meanIrisC * vThreshold) {
                            nowBlinking = true;
                        } //
                    } //
                    else {
                        if (currentIrisC < meanIrisC * vThreshold) {
                            nowBlinking = false;
                            blinkCount += 1;

                        } //
                    } //

                } //





                var ctx = canvas.getContext('2d');
                var t2 = performance.now(); //ms
                ctx.font = "48px serif";
                ctx.fillText("FPS:" + Math.floor(1000.0 / (t2 - t1)), 10, 50);
                ctx
                    .fillText("Count:" + blinkCount, 10, 100);
                if (nowBlinking) {
                    ctx.fillText("Blinking", 10, 150);
                }

                t1 = t2;

            }, 33)

        })
    </script>
</body>

</html>
