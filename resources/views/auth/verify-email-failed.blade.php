<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md text-center max-w-md w-full">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Verification Failed</h1>
        <p class="text-gray-600 mb-6">{{ $message ?? 'Something went wrong.' }}</p>
        <a href="{{ route('login') }}" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Back to Login</a>
    </div>
</body>
</html>