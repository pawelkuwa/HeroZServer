<?php
if(!defined('IN_INDEX')) exit();
define('IN_ENGINE',TRUE);
$cfg = include(__DIR__.'/server/config.php');
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?php echo $cfg['site']['title']; ?></title>
        <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="styles/game.css" type="text/css">
        <link rel="stylesheet" href="styles/heroz.css" type="text/css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <script>
            window.RufflePlayer = window.RufflePlayer || {};
            window.RufflePlayer.config = {
                socketProxy: [
                    { host: "localhost", port: 9999, proxyUrl: "ws://localhost:9998" }
                ]
            };
        </script>
		<script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
        <script src="js/swfobject.js"></script>
        <script src="js/js.cookie.js"></script>
        <script>
            $(function(){
                var currentLang = Cookies.get('web-lang') || $('#Language').find('div[default]').attr('lang');
                $('#Language .btn').each(function(){
                    if($(this).attr('lang') === currentLang){
                        $(this).addClass('disabled');
                    }
                });
                $('#Language').on('click', '.btn', function(){
                    var lang = $(this).attr('lang');
                    if(lang){
                        Cookies.set('web-lang', lang, { expires: 365, path: '/' });
                        location.reload();
                    }
                });
            });
        </script>
    </head>
    <body>
        <div class="gameContainer">
            <div class="topPanel">
                <span class="logo">HeroZ</span>
                <div class="language" id="Language">
                    <div class="btn" lang="pl_PL" default>PL</div>
                    <div class="btn" lang="en_GB">EN</div>
                    <div class="btn" lang="pt_BR">BR</div>
                </div>
            </div>
            <div class="rightPanel">
                <div class="gameBtn gameBtn-Basement"></div>
            </div>
            <div id="flashContainer" class="midPanel overflowHide">
                <div id="flashGame">
                    <script type="text/javascript">
                        var gameLang = Cookies.get('web-lang') || $('#Language').find('div[default]').attr('lang');
                        appCDNUrl = "<?php echo $cfg['site']['resource_cdn']; ?>";
                        appConfigPlatform = "standalone";
                        appConfigLocale = gameLang;
                        appConfigServerId = "heroz";

                        var flashVars = {
                            applicationTitle: "<?php echo $cfg['site']['title'];?>",
                            urlPublic: "<?php echo $cfg['site']['public_url']; ?>",
                            urlRequestServer: "<?php echo $cfg['site']['request_url'].(isset($_GET['d'])?'?d':''); ?>",
                            urlSocketServer: "<?php echo $cfg['site']['socket_url'] ?>",
                            urlSwfMain: "<?php echo $cfg['site']['swf_main'] ?>",
                            urlSwfCharacter: "<?php echo $cfg['site']['swf_character'] ?>",
                            urlSwfUi: "<?php echo $cfg['site']['swf_ui'] ?>",
                            urlCDN: "<?php echo $cfg['site']['resource_cdn'] ?>",
                            userId: "0",
                            userSessionId: "0",
                            testMode: "<?php echo isset($_GET['d'])?'true':'false'; ?>",
                            debugRunTests: "<?php echo isset($_GET['d'])?'true':'false'; ?>",
                            registrationSource: "",
                            startupParams: "",
                            platform: "standalone",
                            ssoInfo: "",
                            uniqueId: "",
                            server_id: "<?php echo $cfg['site']['server_id'] ?>", //Original pl18
                            default_locale: gameLang,
                            localeVersion: "",
                            blockRegistration: "false",
                            isFriendbarSupported: "false"
                        };

                        var params = {
                            menu: "true",
                            allowFullscreen: "false",
                            allowScriptAccess: "always",
                            bgcolor: "#6c5bb7"
                        };

                        var isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') != -1;
                        var isOpera = (navigator.userAgent.match(/Opera|OPR\//) ? true : false);
                        var isWin = navigator.appVersion.indexOf("Win") != -1;
                        var isMac = navigator.appVersion.indexOf("Mac") !=-1;
                        var isLinux = navigator.appVersion.indexOf("Linux") !=-1;

                        if (isChrome && (isWin || isMac)) {
                            params.wmode = "opaque";
                            flashVars["browser"] = "chrome";
                        }

                        var attributes = {
                            id:"swfClient"
                        };

                        swfobject.embedSWF("<?php echo $cfg['site']['swf_preloader'] ?>", "altContent", "900", "630", "10.1.0", "<?php echo $cfg['site']['swf_install'] ?>", flashVars, params, attributes);
                    </script>
                    <div id="altContent">
                        <div id="content">
                            <p id="loadingMsg">Loading the game...</p>
                            <p id="loadingHelp">If the game doesn't load, install the <a href="https://chromewebstore.google.com/detail/ruffle-flash-emulator/donbcfbmhbcapadipfkeojnmajbakjdc" target="_blank">Ruffle Flash Emulator</a> extension on Chrome and reload the page.</p>
                            <script>
                                (function(){
                                    var msgs = {
                                        pl_PL: 'Ładowanie gry...',
                                        en_GB: 'Loading the game...',
                                        pt_BR: 'A carregar o jogo...'
                                    };
                                    var helps = {
                                        pl_PL: 'Jeśli gra się nie ładuje, zainstaluj rozszerzenie <a href="https://chromewebstore.google.com/detail/ruffle-flash-emulator/donbcfbmhbcapadipfkeojnmajbakjdc" target="_blank">Ruffle Flash Emulator</a> w Chrome i odśwież stronę.',
                                        en_GB: 'If the game doesn\'t load, install the <a href="https://chromewebstore.google.com/detail/ruffle-flash-emulator/donbcfbmhbcapadipfkeojnmajbakjdc" target="_blank">Ruffle Flash Emulator</a> extension on Chrome and reload the page.',
                                        pt_BR: 'Se o jogo não carregar, instala a extensão <a href="https://chromewebstore.google.com/detail/ruffle-flash-emulator/donbcfbmhbcapadipfkeojnmajbakjdc" target="_blank">Ruffle Flash Emulator</a> no Chrome e recarrega a página.'
                                    };
                                    document.getElementById('loadingMsg').textContent = msgs[gameLang] || msgs['en_GB'];
                                    document.getElementById('loadingHelp').innerHTML = helps[gameLang] || helps['en_GB'];
                                })();
                            </script>
                        </div>
                    </div>
                </div>
                <div id="HeroZ">
                    <div class="HeroZ-Basement-Panel">
                        <div class="HeroZ-Alert">
                            <div class="Title" id="comingSoonTitle">Coming soon</div>
                            <script>
                                (function(){
                                    var t = { pl_PL:'Wkrótce', en_GB:'Coming soon', pt_BR:'Em breve' };
                                    document.getElementById('comingSoonTitle').textContent = t[gameLang] || t['en_GB'];
                                })();
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
