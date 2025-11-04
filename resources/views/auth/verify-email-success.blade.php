<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified Successfully</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 fade-in">
        <div class="text-center">
            <div class="mb-6">
                <svg class="w-24 h-24 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="content">
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
        function closeTab() {
            if (window.close()) {
                window.close();
            } else {
                alert('Close this tab manually and return to the app.');
            }
        }
    </script>
</body>
</html>