<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>B2B App Account verification</title>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
        a {
            font-family: Avenir, Helvetica, sans-serif;
            box-sizing: border-box;
            border-radius: 3px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
            color: #FFF !important;
            display: inline-block;
            text-decoration: none;
            -webkit-text-size-adjust: none;
            background-color: #3097D1;
            border-top: 10px solid #3097D1;
            border-right: 18px solid #3097D1;
            border-bottom: 10px solid #3097D1;
            border-left: 18px solid #3097D1;
            margin: 14px 0 30px 0;
        }

        p {
            font-family: Avenir, Helvetica, sans-serif;
            box-sizing: border-box;
            color: #74787E;
            font-size: 16px;
            line-height: 1.5em;
            margin-top: 0;
            text-align: left;
        }
    </style>
</head>
<body>

<div>
    <h3>Hello</h3>
    <p>Your activation code is {{$verify_token}}</p>
    <p>Please use this code to activate your account.</p>
    
    <p>If you did not create an account, no further action is required.</p>
    <p>Regards,<br>Ryans Computers B2B App</p>
</div>

</body>
</html>


