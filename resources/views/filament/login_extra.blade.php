<h3 id="slogan" style="opacity: 0;">Empowering Growth with
    <br>
    Enterprise-Level Solutions for All
</h3>

<style>
    body {
        background: rgb(34, 193, 195) !important;
        background: linear-gradient(0deg, rgba(34, 193, 195, 1) 0%, rgba(253, 187, 45, 1) 100%) !important;
    }

    @media screen and (min-width: 1024px) {
        main {
            position: absolute;
            right: 100px;
            animation: slideUp 1s ease-out; 
        }

        main:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: darkcyan;
            border-radius: 12px;
            z-index: -9;

            -webkit-transform: rotate(7deg);
            -moz-transform: rotate(7deg);
            -o-transform: rotate(7deg);
            -ms-transform: rotate(7deg);
            transform: rotate(7deg);
        }

        .fi-logo {
            position: fixed;
            left: 100px;
            font-size: 3em;
            color: cornsilk;
        }

        #slogan {
            position: fixed;
            left: 100px;
            margin-top: 50px;
            color: bisque;
            font-family: cursive;
            font-size: 2em;
            font-weight: bold;
            text-shadow: #3f6212 2px 2px 5px;
            opacity: 0; /* Initially hidden */
            transition: opacity 0.5s ease-in; 
        }
    }

    /* Animation for sliding up */
    @keyframes slideUp {
        0% {
            transform: translateY(100vh); /* Start off-screen at the bottom */
            opacity: 0;
        }
        100% {
            transform: translateY(0); /* End at its natural position */
            opacity: 1;
        }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Select the slogan element
        const slogan = document.getElementById("slogan");

        // Wait for the form animation to finish (1.5 seconds)
        setTimeout(() => {
            // Show the slogan by changing opacity
            slogan.style.opacity = 1;
        }, 1000); // Matches the animation duration
    });
</script>
