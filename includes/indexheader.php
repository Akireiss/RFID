<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Card Reader</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script> -->
    <link href="assets/css/@5.0.2.css" rel="stylesheet">
    <script src="assets/js/@5.0.2.js"></script>
    <script src="assets/js/jquery3.6.4.js"></script>
    <script src="assets/js/boostrap5.3.0.js"></script>

    <style>
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            text-align: center;
            margin: 30px auto;
            max-width: 900px;
            background-color: #495057;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.4);
            color: #ffffff;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ffc107;
        }

        .rfid-input {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #6c757d;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .rfid-input:focus {
            outline: none;
            border-color: #007bff;
        }

        .link-group {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .link-group a {
            text-decoration: none;
            margin: 5px;
            transition: background-color 0.3s, color 0.3s;
        }

        .link-group a:hover {
            background-color: #007bff;
            color: #ffffff;
        }

        .modal-content {
            background-color: #343a40;
            color: #ffffff;
        }

        .modal-title {
            color: #ffc107;
        }

        .btn-secondary {
            background-color: #6c757d !important;
        }

        .btn-primary {
            background-color: #007bff !important;
        }

        .btn-danger {
            background-color: #dc3545 !important;
        }
    </style>
</head>

<body>