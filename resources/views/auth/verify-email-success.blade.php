<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified Successfully</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .lottie-container {
            height: 300px;
            width: 300px;
            margin: 0 auto;
            position: relative;
        }
        .lottie-fallback {
            display: none;
            width: 100%;
            height: 100%;
            background-color: #f3f4f6;
            border-radius: 8px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 fade-in">
        <div class="text-center">
            <div class="lottie-container">
                <lottie-player
                    id="success-animation"
                    src="{{ asset('animations/success-verified.json') }}"
                    background="transparent"
                    speed="1"
                    style="width: 300px; height: 300px;"
                    loop="false"
                    autoplay
                    @complete="handleAnimationComplete(event)"
                    @load="console.log('Lottie loaded successfully')"
                    @error="handleLottieError(event)">
                </lottie-player>
                <div class="lottie-fallback">
                    <svg class="w-16 h-16 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p>Animation loading... (Check console for errors)</p>
                </div>
            </div>
        </div>

        <div class="content opacity-0 transition-opacity duration-500">
            <div class="text-center space-y-4">
                <h1 class="text-3xl font-bold text-gray-900">Your Account Has Been Created</h1>
                <p class="text-lg text-green-600 font-medium">{{ $email ?? 'Your email' }} is now confirmed.</p>
                <p class="text-gray-600">You can now return to the mobile app to continue.</p>
            </div>

            <div class="text-center">
                <img src="{{ asset('images/delivered-email-illustration.png') }}" alt="Email Verified" class="mx-auto w-48 h-48 object-cover rounded-lg">
            </div>

            <div>
                <button onclick="closeTab()" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 ease-in-out transform hover:scale-105">
                    Close This Tab
                </button>
                <p class="text-center text-sm text-gray-500 mt-2">Or simply close this window and switch back to the app.</p>
            </div>
        </div>
    </div>

    <script>
        function handleAnimationComplete(event) {
            console.log('Animation completed successfully');
            event.target.style.opacity = 0;
            event.target.style.transition = 'opacity 0.5s';
            document.querySelector('.content').style.opacity = 1;
        }

        function handleLottieError(event) {
            console.error('Lottie Error:', event.detail);
            const player = document.getElementById('success-animation');
            const fallback = player.parentElement.querySelector('.lottie-fallback');
            fallback.style.display = 'flex';
            player.style.display = 'none';
            document.querySelector('.content').style.opacity = 1;
        }

        function closeTab() {
            if (window.close()) {
                window.close();
            } else {
                alert('Close this tab manually and return to the app.');
            }
        }

        window.addEventListener('load', () => {
            const player = document.getElementById('success-animation');
            if (!player) {
                console.error('Lottie player not found - script failed to load');
                document.querySelector('.content').style.opacity = 1;
            }
        });
    </script>
</body>
</html>