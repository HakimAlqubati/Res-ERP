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

        <script>
            const video = document.getElementById('video');
            const captureButton = document.getElementById('captureButton');
            const resultMessage = document.getElementById('resultMessage');
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
                    // Display result message
                    resultMessage.textContent = data.message;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultMessage.textContent = 'An error occurred while processing the image.';
                });
            });
        </script>
    </div>
</x-filament::page>
