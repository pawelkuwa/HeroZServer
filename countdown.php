<?php
if(!defined('IN_INDEX')) exit();
?>
<html>
    <head>
        <meta charset="utf-8">
        <title>Start <?php echo $start; ?></title>
        <style>
            *{
                font-family:'Helvetica', sans-serif;
            }
            body{
                margin:0;
                padding:0;
                background-color:#000;
                color:#fff;
            }
            #count-down{
                margin-top:calc((100vh / 2) - 100px);
                text-align:center;
                font-size:20pt;
            }
            #timer{
                color:orange;
                font-size:50pt;
                text-shadow:0 0 10px orange;
            }
            .lang-switch{
                text-align:center;
                margin-top:30px;
            }
            .lang-switch a{
                color:#888;
                text-decoration:none;
                font-size:14px;
                margin:0 8px;
                cursor:pointer;
            }
            .lang-switch a:hover{ color:orange; }
            .lang-switch a.active{ color:orange; font-weight:bold; }
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="js/js.cookie.js"></script>
        <script>
            $(document).ready(function(){
                var lang = Cookies.get('web-lang') || 'pl_PL';

                var translations = {
                    pl_PL: {
                        countdown: 'Odliczanie do startu',
                        started: 'Serwer wystartował! Odśwież stronę!',
                        days: ' dni '
                    },
                    en_GB: {
                        countdown: 'Countdown to launch',
                        started: 'Server started! Refresh the page!',
                        days: ' days '
                    },
                    pt_BR: {
                        countdown: 'Contagem regressiva',
                        started: 'O servidor iniciou! Atualize a página!',
                        days: ' dias '
                    }
                };
                var t = translations[lang] || translations['en_GB'];

                $('#countdownTitle').text(t.countdown);

                $('.lang-switch a').each(function(){
                    if($(this).data('lang') === lang) $(this).addClass('active');
                });
                $('.lang-switch a').on('click', function(){
                    Cookies.set('web-lang', $(this).data('lang'), { expires: 365, path: '/' });
                    location.reload();
                });

                var timer = $('#timer');
                var countDownDate = Date.parse("<?php echo $start; ?>");
                var _second = 1000;
                var _minute = _second * 60;
                var _hour = _minute * 60;
                var _day = _hour * 24;

                var r = setInterval(function(){
                    var now = new Date().getTime();
                    var dist = countDownDate - now;

                    if(dist <= 0){
                        clearTimeout(r);
                        x = t.started;
                    }else{

                        var days = Math.floor(dist / _day);
                        var hours = ("0"+Math.floor( (dist % _day ) / _hour )).substr(-2);
                        var minutes = ("0"+Math.floor( (dist % _hour) / _minute )).substr(-2);
                        var seconds = ("0"+Math.floor( (dist % _minute) / _second )).substr(-2);
                        var milliseconds = dist % _second;


                        var x = days + t.days + hours + ":" + minutes + ":" + seconds + "." + Math.floor(milliseconds/100);
                    }
                    timer.html(x);
                }, 100);
            });
        </script>
    </head>
    <body>
        <div id="count-down">
            <div id="countdownTitle">Countdown</div>
            <div id="timer">-days --:--:--.-</div>
        </div>
        <div class="lang-switch">
            <a data-lang="pl_PL">PL</a>
            <a data-lang="en_GB">EN</a>
            <a data-lang="pt_BR">BR</a>
        </div>
    </body>
</html>