<?php
    $VKserverToken = "";
    
    function getVKServerToken() {
        $data = array('client_id' => \pass\VK::$client_id,
                      'client_secret' => \pass\VK::$client_secret,
                      'grant_type' => 'client_credentials');
        $result = file_get_contents("https://oauth.vk.com/access_token?".http_build_query($data));
        $result = json_decode($result, true);
        return $result['access_token'];
    }

    function getVKName($vkid, $token) {
        $data = array('user_ids'=>$vkid,
                      'fields'=>'photo_100',
                      'access_token'=>$token);
        $result = file_get_contents("https://api.vk.com/method/users.get?".http_build_query($data));
        $result = json_decode($result, true);
        if (array_key_exists('response', $result)) {
            $result = $result["response"][0];
            $fname = mysqli_real_escape_string($GLOBALS['mysqli'], $result['first_name']);
            $lname = mysqli_real_escape_string($GLOBALS['mysqli'], $result['last_name']);
            $photo = mysqli_real_escape_string($GLOBALS['mysqli'], $result['photo_100']);
            mysqli_query($GLOBALS['mysqli'], "UPDATE users SET login='$fname', lastName='$lname', photo='$photo' WHERE vkid=$vkid");
            return $fname;
        }
        return "user";
    }
    
    function sendVKNotification($vkid, $message) {
        if (!$VKserverToken)
            $VKserverToken = getVKServerToken();
        $data = array('user_ids'=>$vkid,
                      'message'=>$message,
                      'access_token'=>$VKserverToken,
                      'client_secret' => \pass\VK::$client_secret);
        $result = file_get_contents("https://api.vk.com/method/secure.sendNotification?".http_build_query($data));
        return $result;
    }
    
    function vkAuth(){
        //vk auth
        if (array_key_exists("code", $_GET)) {
            $data = array('client_id' => \pass\VK::$client_id,
                          'client_secret' => \pass\VK::$client_secret,
                          'code'=>$_GET['code'],
                          'redirect_uri'=>\pass\VK::$redirect_uri);
            $result = @file_get_contents("https://oauth.vk.com/access_token?".http_build_query($data));
            $result = json_decode($result, true);
            if (@array_key_exists('access_token', $result)) {
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE vkid=" . $result['user_id']);
                if (!mysqli_num_rows($sqlresult))
                    mysqli_query($GLOBALS['mysqli'], "INSERT INTO users (vkid) VALUES(" . $result['user_id'] . ")");
                $expires = time() + $result['expires_in'] + 7*24*60*60; //+7 days
                mysqli_query($GLOBALS['mysqli'], "UPDATE users SET expires=FROM_UNIXTIME($expires), token='" . $result['access_token'] . "' WHERE vkid=" . $result['user_id']);

                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE vkid=" . $result['user_id']);
                $user = mysqli_fetch_assoc($sqlresult);
                if (!$user['login']) {
                    $user['login'] = getVKName($result['user_id'], $result['access_token']);
                }
                $_SESSION['user'] = $user;
                $_SESSION['expires'] = $expires;
                $_SESSION['showSettings'] = true;
                header("Location: http://".$_SERVER[HTTP_HOST]);
            }
            return 0;
        }        
    }
?>