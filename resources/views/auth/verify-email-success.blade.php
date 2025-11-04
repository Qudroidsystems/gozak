<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified Successfully</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Lottie Player CDN -->
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
            height: 200px; /* Adjust based on your JSON animation size */
            width: 200px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 fade-in">
        <!-- Lottie Animation -->
        <div class="text-center">
            <lottie-player
                src="{{ asset('animations/success-verified.json') }}"
                background="transparent"
                speed="1"
                style="width: 200px; height: 200px;"
                loop="false"  <!-- Loop once for emphasis, or true for repeat -->
                autoplay
                @complete="this.style.opacity = 0; this.style.transition = 'opacity 0.5s'; document.querySelector('.content').style.opacity = 1;">
            </lottie-player>
        </div>

        <!-- Content (fades in after animation) -->
        <div class="content opacity-0 transition-opacity duration-500">
            <!-- Title and Email -->
            <div class="text-center space-y-4">
                <h1 class="text-3xl font-bold text-gray-900">Email Verified!</h1>
                <p class="text-lg text-green-600 font-medium">{{ $email ?? 'Your email' }} is now confirmed.</p>
                <p class="text-gray-600">You can now log in to your account. Redirecting shortly...</p>
            </div>

            <!-- Illustration Placeholder (optional, below animation) -->
            <div class="text-center">
                <img src="{{ asset('images/delivered-email-illustration.png') }}" alt="Email Verified" class="mx-auto w-48 h-48 object-cover rounded-lg">
            </div>

            <!-- Continue Button -->
            <div>
                <form id="redirectForm" action="{{ route('login') }}" method="GET" style="display: none;">
                    <input type="hidden" name="email" value="{{ $email ?? '' }}">
                </form>
                <button onclick="document.getElementById('redirectForm').submit();" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 ease-in-out transform hover:scale-105">
                    Continue to Login
                </button>
            </div>
        </div>

        <script>
            // Auto-redirect after 4 seconds (animation ~2s + buffer)
            setTimeout(function() {
                document.getElementById('redirectForm').submit();
            }, 4000);
        </script>
    </div>
</body>
</html>