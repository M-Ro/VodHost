<!DOCTYPE html>
<html>
<head>
    <title>Welcome to VodHost</title>
</head>

<body>
    <h4>Hello {{$user['name']}},</h4>
    <br/>
    Please click the link below to verify your email account
    <br/>
    <a href="{{url('user/verify', $user->verify->token)}}">Verify Email</a>
</body>

</html>
