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
                    autoplay>
                </lottie-player>
                <div class="lottie-fallback" id="lottie-fallback">
                    <svg class="w-16 h-16 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Animation could not be loaded</p>
                </div>
            </div>
        </div>

        <div class="content opacity-0 transition-opacity duration-500" id="content">
            <div class="text-center space-y-4 mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Your Account Has Been Created</h1>
                <p class="text-lg text-green-600 font-medium">{{ $email ?? 'Your email' }} is now confirmed.</p>
                <p class="text-gray-600">You can now return to the mobile app to continue.</p>
            </div>

            <div class="text-center mb-6">
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
        function showContent() {
            const content = document.getElementById('content');
            const player = document.getElementById('success-animation');
            
            if (player) {
                player.style.opacity = '0';
                player.style.transition = 'opacity 0.5s';
            }
            
            setTimeout(() => {
                content.style.opacity = '1';
            }, 500);
        }

        function handleLottieError() {
            console.error('Lottie animation failed to load');
            const player = document.getElementById('success-animation');
            const fallback = document.getElementById('lottie-fallback');
            
            if (player) player.style.display = 'none';
            if (fallback) fallback.style.display = 'flex';
            
            showContent();
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
                console.error('Lottie player not found');
                showContent();
                return;
            }

            // Listen for animation complete
            player.addEventListener('complete', () => {
                console.log('Animation completed successfully');
                showContent();
            });

            // Listen for errors
            player.addEventListener('error', handleLottieError);

            // Fallback timeout in case animation doesn't trigger complete event
            setTimeout(() => {
                const content = document.getElementById('content');
                if (content.style.opacity === '0' || content.style.opacity === '') {
                    console.log('Animation timeout - showing content anyway');
                    showContent();
                }
            }, 5000);
        });
    </script>
</body>
</html>