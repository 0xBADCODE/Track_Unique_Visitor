<html>
<head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <title>...</title>
</head>
<body>
<script type="text/javascript">
function recon() {
        var form = document.createElement('form');
        form._submit_function_ = form.submit;

        form.setAttribute('method', 'POST');
        form.setAttribute('action', 'recon.php?id=<?php echo $_REQUEST['id']; ?>');
        form.setAttribute('target', '_self');

        var js_enabled = document.createElement('input');
        js_enabled.setAttribute('type', 'hidden');
        js_enabled.setAttribute('name', 'js_enabled');
        js_enabled.setAttribute('value', '1');
        form.appendChild(js_enabled);

        var language = document.createElement('input');
        language.setAttribute('type', 'hidden');
        language.setAttribute('name', 'language');
        language.setAttribute('value', navigator.language || navigator.userLanguage);
        form.appendChild(language);

        var reswidth = document.createElement('input');
        reswidth.setAttribute('type', 'hidden');
        reswidth.setAttribute('name', 'reswidth');
        reswidth.setAttribute('value', screen.width);
        form.appendChild(reswidth);

        var resheight = document.createElement('input');
        resheight.setAttribute('type', 'hidden');
        resheight.setAttribute('name', 'resheight');
        resheight.setAttribute('value', screen.height);
        form.appendChild(resheight);

        var timezone = document.createElement('input');
        timezone.setAttribute('type', 'hidden');
        timezone.setAttribute('name', 'timezone');
        timezone.setAttribute('value', new Date().getTimezoneOffset());
        form.appendChild(timezone);

        var pluginslist;
        for(var i = 0; i < navigator.plugins.length; i++)
                pluginslist += navigator.plugins[i].name + ",";

        var plugins = document.createElement('input');
        plugins.setAttribute('type', 'hidden');
        plugins.setAttribute('name', 'plugins');
        plugins.setAttribute('value', pluginslist);
        form.appendChild(plugins);

        document.body.appendChild(form);
        form._submit_function_();
}
</script>
<?php
        //Check form submitted
        if(!isset($_POST['js_enabled']))
                echo "<script>recon();</script>";
        else {
                $language = $_POST['language'];
                $reswidth = $_POST['reswidth'];
                $resheight = $_POST['resheight'];
                $timezone = $_POST['timezone'];
                $plugins = $_POST['plugins'];

                $ip = $_SERVER['REMOTE_ADDR'];
                $time = $_SERVER['REQUEST_TIME'];
                $browser = $_SERVER['HTTP_USER_AGENT'];

                if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                        $forward_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                else
                        $forward_ip = 0;

                $hash = hash('sha256', $ip . $browser . $timezone . $language . $reswidth . $resheight . $plugins);

                if(preg_match('/Linux/', $browser)) $operating_system = 'Linux';
                elseif(preg_match('/Win/', $browser)) $operating_system = 'Windows';
                elseif(preg_match('/Mac/', $browser)) $operating_system = 'Mac';
                elseif(preg_match('/Android/', $browser)) $operating_system = 'Android';
                elseif(preg_match('/iPhone/', $browser) || preg_match('/iPad/', $browser)) $operating_system = 'iOS';
                else $operating_system = 'Unknown';


                // DATABASE & COOKIE STUFF

                $cookie_unique_id = "unique_id";
                $cookie_hash = "hash";

                $dbhost = '127.0.0.1:3306';
                $dbuser = ''; #user
                $dbpass = ''; #password
                $db = 'recon';
                $table = 'targets';

                $conn = mysql_connect($dbhost, $dbuser, $dbpass);

                if(!$conn)
                        die('Could not connect: ' . mysql_error());

                mysql_select_db($db, $conn);

                //Check if user exists in db
                $user_exists = false;

                if(isset($_COOKIE[$cookie_unique_id]))
                        $ident = $_COOKIE[$cookie_unique_id];
                elseif(empty($_REQUEST['id'])) {
                        while(1){
                                $ident = rand(1000000,9999999); //Generate 7 digit ident
                                $sql = "SELECT ident FROM " . $table . " WHERE ident='" . $ident . "' LIMIT 1";
                                if(!mysql_fetch_array(mysql_query($sql, $conn)) !== false)
                                        break;
                        }
                }
                else
                        $ident = $_REQUEST['id'];

                $sql = "SELECT ident FROM " . $table . " WHERE ident='" . $ident . "' LIMIT 1";

                $result = mysql_query($sql, $conn);

                if(!$result)
                        die('Could not get data: ' . mysql_error());

                if(mysql_fetch_array($result) !== false)
                        $user_exists = true;

                //Add or update user in db
                if(!$user_exists)
                        $sql = "INSERT INTO " . $table . " (id, ident, first_visit, last_visit, hash, ip, forward_ip, timezone, browser, operating_system, reswidth, resheight, language, plugins) VALUES (NULL, '" . $ident . "', '" . $time . "', '" . $time . "', '" . $hash . "', '" . $ip . "', '" . $forward_ip . "', '" . $timezone . "', '" . $browser . "', '" . $operating_system . "', '" . $reswidth . "', '" . $resheight . "', '" . $language . "', '" . $plugins . "')";
                else
                        $sql = "UPDATE " . $table . " SET last_visit='" . $time . "', hash='" . $hash . "', ip='" . $ip . "', forward_ip='" . $forward_ip . "', timezone ='" . $timezone . "', browser='" . $browser . "', operating_system='" . $operating_system . "', reswidth='" . $reswidth . "', resheight='" . $resheight . "', language='" . $language . "', plugins='" . $plugins . "' WHERE ident='" . $ident . "'";

                mysql_query($sql, $conn);

                //Set or update cookies
                $timeout = time() + 315360000; //10 years

                if(!isset($_COOKIE[$cookie_unique_id]))
                        setcookie($cookie_unique_id, $ident, $timeout, "/");

                if($_COOKIE[$cookie_hash] !== $hash) {
                        mysql_query("UPDATE " . $table . " SET hash_change = 1 WHERE ident='" . $ident . "'");
                        setcookie($cookie_hash, $hash, $timeout, "/");
                }
                else
                        mysql_query("UPDATE " . $table . " SET hash_change = 0 WHERE ident='" . $ident . "'");

                mysql_close($conn);
        }
?>
</body>
</html>
