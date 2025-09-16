<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SWMA Dashboard Selector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="/styles/output.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .dashboard-card:hover { box-shadow: 0 4px 24px rgba(0,0,0,0.08); transform: translateY(-2px) scale(1.03); }
    </style>
</head>
<body class="bg-gradient-to-br from-red-100 via-slate-100 to-purple-100 min-h-screen flex items-center justify-center">
    <div class="max-w-xl w-full mx-auto p-8 bg-white rounded-2xl shadow-lg">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Welcome to SWMA System</h1>
        <p class="text-center text-gray-500 mb-8">Select a dashboard to continue:</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="/admin" class="dashboard-card group flex flex-col items-center p-6 bg-red-50 rounded-xl transition duration-200 hover:bg-red-100 cursor-pointer">
                <i class="fas fa-user-shield text-4xl text-red-600 mb-3 group-hover:scale-110 transition"></i>
                <span class="text-lg font-semibold text-gray-800 mb-1">Admin Dashboard</span>
                <span class="text-sm text-gray-500">Manage all system records</span>
            </a>
            <a href="/agent" class="dashboard-card group flex flex-col items-center p-6 bg-purple-50 rounded-xl transition duration-200 hover:bg-purple-100 cursor-pointer">
                <i class="fas fa-user-tie text-4xl text-purple-600 mb-3 group-hover:scale-110 transition"></i>
                <span class="text-lg font-semibold text-gray-800 mb-1">Agent Dashboard</span>
                <span class="text-sm text-gray-500">Report and track issues</span>
            </a>
            <a href="/officer" class="dashboard-card group flex flex-col items-center p-6 bg-blue-50 rounded-xl transition duration-200 hover:bg-blue-100 cursor-pointer">
                <i class="fas fa-user-cog text-4xl text-blue-600 mb-3 group-hover:scale-110 transition"></i>
                <span class="text-lg font-semibold text-gray-800 mb-1">Officer Dashboard</span>
                <span class="text-sm text-gray-500">Review and resolve cases</span>
            </a>
        </div>
    </div>
</body>
</html>
