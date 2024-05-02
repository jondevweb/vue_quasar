<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">

    <title>ICI On Recycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('image/Favicon_IOR.svg') }}">
    <link rel="apple-touch-icon" href="{{ url('image/Favicon_IOR.png') }}">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <!-- https://www.srihash.org/ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/minireset.css/0.0.2/minireset.min.css" integrity="sha512-uBLaY+6crwV4JAHILx0HWvYncrX7TXL770hqxly0ZsQ199v4lr2yNB2jiPMoxNajFPHSQnU80B1O8dJLujWZMg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900|Material+Icons" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/7.1.96/css/materialdesignicons.min.css" integrity="sha512-NaaXI5f4rdmlThv3ZAVS44U9yNWJaUYWzPhvlg5SC7nMRvQYV9suauRK3gVbxh7qjE33ApTPD+hkOW78VSHyeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@^4.0.0/animate.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/quasar@2.11.5/dist/quasar.prod.css" rel="stylesheet" type="text/css">

    <link href="/css/common.css" rel="stylesheet" type="text/css">
  </head>

  <body>
    {{ $slot }}
    @isset($quasarconfig)
      {{ $quasarconfig }}
    @else
      <script>
        quasarConfig = {
          config: {
            brand: {
                primary: '#027BE3',
                secondary: '#26A69A',
                accent: '#9C27B0',

                dark: '#1d1d1d',

                positive: '#21BA45',
                negative: '#C10015',
                info: '#31CCEC',
                warning: '#F2C037'
            }
          }
        }
      </script>
    @endif
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.47/vue.global.min.js" integrity="sha512-DJ2+sosWB5FnZAIeCWAHu2gxQ7Gi438oOZLcBQSOrW8gD0s7LIXDv/5RW76B3FcljF40BXnfqNIo6Dhp7dFHJg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!--script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.47/vue.global.prod.min.js" integrity="sha512-Wn/yBJ4RQtrSFtq1z61/DM40a7VGN8wnyg8oVhWSZAZchTO9zS/l8Kw6bk32CYjS5VgProK4ujLRMqxEE/bUPQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script-->
    <script src="https://cdn.jsdelivr.net/npm/quasar@2.11.5/dist/quasar.umd.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quasar@2.11.5/dist/lang/fr.umd.prod.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/validator/13.9.0/validator.min.js" integrity="sha512-mC5BW2SjEdyK0/AwoSMdAUOq3xv9zzNwbWLiQLTlriZ9b9Tq0vLTwe7aBgD44Xq8ud8rhr533rbY97s5GA+d2g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"                   integrity="sha512-WFN04846sdKMIP5LKNphMaWzU7YpMyCU245etK3g/2ARYbPK9Ub18eG+ljU96qKRCWh+quCY7yefSmlkQw1ANQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.3.2/axios.min.js" integrity="sha512-NCiXRSV460cHD9ClGDrTbTaw0muWUBf/zB/yLzJavRsPNUl9ODkUVmUHsZtKu17XknhsGlmyVoJxLg/ZQQEeGA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment-with-locales.min.js" integrity="sha512-42PE0rd+wZ2hNXftlM78BSehIGzezNeQuzihiBCvUEB3CVxHvsShF86wBWwQORNxNINlBPuq7rG4WWhNiTVHFg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.40/moment-timezone-with-data-10-year-range.min.js" integrity="sha512-GKxhLkFh/5zSOuvIDwC5cdQkh13mR+jMgSA/9nBgA530xRXiwWhT7uje6b6Tpboa95M7OTSKxbYdMHRgLLBILQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/libphonenumber-js/1.10.19/libphonenumber-js.min.js" integrity="sha512-k1TDgSjE5vwF0L2JDCHO0mZZ+jZN0nPSE+DbCKu76U07b8Rvi5WB5KYDg+HMv847x541eAbhJcgcV9CHHTbCqA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="/js/common.js"></script>
    <script src="/js/common-business.js"></script>
    {{ $script }}
  </body>
</html>
