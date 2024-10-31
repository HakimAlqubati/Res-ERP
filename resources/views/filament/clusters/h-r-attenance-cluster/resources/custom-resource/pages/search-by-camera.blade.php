<x-filament-panels::page>
    <!-- resources/views/filament/pages/search-by-camera.blade.php -->

    <h2>Capture Image and Search</h2>

    @if (session('status'))
        <div style="color: green;">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div style="color: red;">{{ $errors->first() }}</div>
    @endif
    <!-- Video Element for Camera -->
    <div>
        <video id="camera" autoplay playsinline></video>
        <button onclick="captureImage()">Capture Image</button>
    </div>

    <!-- Canvas for Captured Image -->
    <canvas id="canvas" style="display: none;"></canvas>

    <!-- Form to Submit Image Data -->
    <form id="imageForm" action="{{ route('filament.pages.search-by-camera.process') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="capturedImage" name="capturedImage">
        <button type="submit">Search Face</button>
    </form>

    <script>
        // Start the camera
        const video = document.getElementById('camera');
        const canvas = document.getElementById('canvas');
        const capturedImage = document.getElementById('capturedImage');

        navigator.mediaDevices.getUserMedia({
                video: true
            })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => console.error('Error accessing camera:', err));

        // Capture image from video
        function captureImage() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            capturedImage.value = canvas.toDataURL('image/jpeg'); // Base64 encode image
        }
    </script>
</x-filament-panels::page>
