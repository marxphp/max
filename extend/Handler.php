<?php


class Handler extends \Max\Exception\Handler
{
    public function __toString()
    {
        return <<<ETO
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Something went error！</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            text-align: center;
        }
        .box {
            flex: 1;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }
        .number {
            font-size: 80px;
            color: #666;
            font-weight: bold;
        }
        .text {
            font-size: 14px;
            margin: 24px;
            color: #333;
        }
        .btn-container {
            display: flex;
            justify-content: center;
        }
        .btn {
            padding: 8px 24px;
            text-decoration: none;
            background: #fff;
            border: 2px solid #efefef;
            color: #333;
            margin: 24px;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .footer {
            padding: 16px;
            border-top: 1px solid #efefef;
            color: #777;
            font-weight: lighter;
        }
        .footer a {
            text-decoration: none;
            font-weight: bold;
            color: #000;
        }
    </style>
</head>
<body>
<div class="box">
    <div class="number">Sorry!</div>
    <div class="text">
        Something went error！
    </div>
    <div class="btn-container">
        <a class="btn" id="back">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" style="margin-right: 8px;">
                <path fill="none" d="M0 0h24v24H0z"/>
                <path d="M5.828 7l2.536 2.536L6.95 10.95 2 6l4.95-4.95 1.414 1.414L5.828 5H13a8 8 0 1 1 0 16H4v-2h9a6 6 0 1 0 0-12H5.828z"/>
            </svg>
            Back
        </a>
    </div>
</div>
<footer class="footer">
    Powered by <a href="https://www.chengyao.xyz" target="_blank">MaxPHP</a>
</footer>
<script>
    var back = document.getElementById('back')
    back.onclick = function() { console.log('run...'); history.back() }
</script>
</body>
</html>
ETO;

    }
}