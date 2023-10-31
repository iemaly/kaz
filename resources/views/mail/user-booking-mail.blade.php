<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            padding: 20px;
            max-width: 600px;
        }
        .header {
            text-align: center;
        }
        .header h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px;
        }
        .details {
            background-color: #ffffff;
            border-radius: 5px;
            padding: 20px;
        }
        p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Booking</h1>
        </div>
        <div class="content">
            <h2>Appointment Details</h2>
            <div class="details">
                <p><strong>User:</strong> {{$user->fname.' '.$user->lname}}</p>
                <p><strong>Service:</strong> {{$service->title}}</p>
                <p><strong>Barber:</strong> {{$barber->fname.' '.$barber->lname}}</p>
                <p><strong>Date:</strong> {{$date}}</p>
                <p><strong>Time Slot:</strong> {{$slot->start_time.'-'.$slot->end_time}}</p>
            </div>
        </div>
    </div>
</body>
</html>
