<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Intake Form PDF</title>
  <link href="{{ asset('css/app.css') }}" rel="stylesheet" />
</head>
<body class="p-6 font-sans">
  <h1 class="text-2xl font-bold mb-4">Client Intake Form</h1>
  <p><strong>Name:</strong> {{ $name }}</p>
  <p><strong>Email:</strong> {{ $email }}</p>
  <p><strong>Comments:</strong> {{ $comments }}</p>
</body>
</html>
