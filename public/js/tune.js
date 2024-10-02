function playTone() {
    var audio = document.getElementById('notification-tone');
    audio.play();
}

// Example: Playing the tone when a button is clicked
document.getElementById('play-tone-btn').addEventListener('click', function () {
    playTone();
});

// Example: Playing the tone when a notification is received
document.addEventListener('notification-received', function () {
    playTone();
});
