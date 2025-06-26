<!DOCTYPE html>
<html lang="zh-Hans-TW">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link href="{{ asset('css/index.css') }}" rel="stylesheet">
        <link rel="shortcut icon" href="{{ asset('/image/favicon.ico') }}">
        <link rel="bookmark" href="{{ asset('/image/favicon.ico') }}">
        <title>值勤打卡系統 - 國立彰化師範大學學生會</title>
        @vite('resources/js/app.js')
        @routes
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>