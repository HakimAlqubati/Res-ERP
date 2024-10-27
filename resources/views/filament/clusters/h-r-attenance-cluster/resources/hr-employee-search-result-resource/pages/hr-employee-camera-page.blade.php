<x-filament::page>
    <div class="flex flex-col items-center justify-center space-y-4">
        <!-- Camera Preview -->
        <video id="video" autoplay playsinline class="rounded shadow-md"></video>

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
                <h2 style="color: black;" class="text-xl font-bold" id="modalTitle">Result</h2> <!-- Retained color style -->
                <p style="color: black;" id="modalMessage" class="mt-4"></p> <!-- Retained color style -->
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
            const captureButton = document.getElementById('captureButton');
            const resultMessage = document.getElementById('resultMessage');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultModal = document.getElementById('resultModal');
            const modalMessage = document.getElementById('modalMessage');
            const closeModal = document.getElementById('closeModal');
            const canvas = document.createElement('canvas');

            // Access camera
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => video.srcObject = stream)
                .catch(error => console.error('Camera access denied:', error));

            // Capture the image and send to server
            captureButton.addEventListener('click', () => {
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to base64
                const imageData = canvas.toDataURL('image/png');

                // Show loading spinner
                loadingSpinner.classList.remove('hidden');

                // Send image data to server
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
                    // Hide loading spinner
                    loadingSpinner.classList.add('hidden');

                    // Display result message
                    resultMessage.textContent = data.message;
                    // Show modal with details
                    modalMessage.textContent = data.message; // Replace with additional user details if needed
                    resultModal.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingSpinner.classList.add('hidden'); // Hide spinner on error
                    resultMessage.textContent = 'An error occurred while processing the image.';
                });
            });

            // Close modal
            closeModal.addEventListener('click', () => {
                resultModal.classList.add('hidden');
            });
        </script>
    </div>
</x-filament::page>
