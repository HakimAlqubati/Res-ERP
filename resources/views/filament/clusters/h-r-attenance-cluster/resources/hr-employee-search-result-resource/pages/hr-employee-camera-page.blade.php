<x-filament::page>
    <div class="flex flex-col items-center justify-center space-y-4">
        <!-- Camera Preview -->
        <video id="video" autoplay playsinline class="rounded shadow-md" style="display: block;"></video>

        <canvas id="overlay" class="absolute rounded shadow-md"></canvas> <!-- Canvas for later use -->

        <!-- Capture Button -->
        <button id="captureButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Capture
        </button>

        <!-- Display Result -->
        <div id="resultMessage" class="text-green-500 mt-4"></div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="hidden">
            <div class="loader"></div> <!-- Spinner element -->
        </div>

        <!-- Modal -->
        <div id="resultModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
            <div class="bg-white rounded-lg p-6 w-96">
                <h2 style="color: black;" class="text-xl font-bold" id="modalTitle">Result</h2>
                <p style="color: black;" id="modalMessage" class="mt-4"></p>
                <button style="color: black;" id="closeModal" class="mt-4 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Close</button>
            </div>
        </div>

        <style>
            .loader {
                border: 8px solid #f3f3f3; /* Light gray */
                border-top: 8px solid #3498db; /* Blue */
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>

        <script>
            const video = document.getElementById('video');
            const canvas = document.getElementById('overlay');
            const captureButton = document.getElementById('captureButton');
            const resultMessage = document.getElementById('resultMessage');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultModal = document.getElementById('resultModal');
            const modalMessage = document.getElementById('modalMessage');
            const closeModal = document.getElementById('closeModal');

            // Start the video stream
            function startVideo() {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(stream => {
                        video.srcObject = stream;
                        video.play();
                    })
                    .catch(err => console.error('Camera access denied:', err));
            }

            // Start the video when the page loads
            startVideo();

            // Capture the image and send to server
            captureButton.addEventListener('click', () => {
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = canvas.toDataURL('image/png');
                loadingSpinner.classList.remove('hidden');
                video.style.display = 'none'; // Hide the video element

                fetch('/recognize-face', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ image: imageData }),
                })
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.classList.add('hidden');
                    resultMessage.textContent = data.message;
                    modalMessage.textContent = data.message; 
                    resultModal.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingSpinner.classList.add('hidden'); 
                    resultMessage.textContent = 'An error occurred while processing the image.';
                });
            });

            // Close modal
            closeModal.addEventListener('click', () => {
                resultModal.classList.add('hidden');
                video.style.display = 'block'; // Show the video element again when the modal is closed
            });
        </script>
    </div>
</x-filament::page>
