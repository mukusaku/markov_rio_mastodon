<?php
require __DIR__ . '/vendor/autoload.php';
require 'convertEntity.php';
require 'originalList.php';
use YuzuruS\Mecab\Markovchain;
$toot = new main();
$toot->execFav();
//$toot->execToot();
class main {
    function execFav(){
        $aryNotifications = $this->getNotifications();
        $aryTimeline = $this->getHomeTimeline();
        $aryTarget = $aryNotifications + $aryTimeline;
        $this->actionFavorite($aryTarget);
    }

    function getNotifications() {
        // サーバ情報などの読み込み
        $arySetting = parse_ini_file("mastodon_setting.ini");
        /* Settings */
        $schema       = 'https';
        $host         = $arySetting['server'];
        $access_token = $arySetting['access_token'];
        $method       = 'GET';
        $endpoint     = '/api/v1/notifications';
        $url          = "${schema}://${host}${endpoint}";
        $url         .= "?limit=10";
        /* Build request */
        $query  = "curl -X ${method}";
        $query .= " --header 'Authorization:";
        $query .= " Bearer ${access_token}'";
        $query .= " -sS ${url}";
        /* Request */
        $result = `$query`; //バッククォートに注意
        /* Show result */
        $aryResult = json_decode($result, JSON_OBJECT_AS_ARRAY);
        //print_r(json_decode($result, JSON_OBJECT_AS_ARRAY));

        $aryBt = array();
        foreach($aryResult as $key => $value) {
            if($value['type'] != "mention") {
                continue;
            }
//            print_r($value, false);
            $aryBt += array($value['status']['id'] => str_replace("@rio", "", strip_tags($value['status']['content'])));
        }
        print_r($aryBt, false);
        return $aryBt;
    }

    function getHomeTimeline() {
        // サーバ情報などの読み込み
        $arySetting = parse_ini_file("mastodon_setting.ini");
        /* Settings */
        $schema       = 'https';
        $host         = $arySetting['server'];
        $access_token = $arySetting['access_token'];
        $method       = 'GET';
        $endpoint     = '/api/v1/timelines/home';
        $url          = "${schema}://${host}${endpoint}";
        $url         .= "?limit=15";
        /* Build request */
        $query  = "curl -X ${method}";
        $query .= " --header 'Authorization:";
        $query .= " Bearer ${access_token}'";
        $query .= " -sS ${url}";
        /* Request */
        $result = `$query`; //バッククォートに注意
        /* Show result */
        $aryResult = json_decode($result, JSON_OBJECT_AS_ARRAY);
        //print_r(json_decode($result, JSON_OBJECT_AS_ARRAY));

        $aryBt = array();
        foreach($aryResult as $key => $value) {
            if($value['account']['username'] == "rio"
                || strpos($value['content'], 'セクシー') === false ) {
                continue;
            }
//            print_r($value, false);
            $aryBt += array($value['id'] => str_replace("@rio", "", strip_tags($value['content'])));
        }
        print_r($aryBt, false);
        return $aryBt;
    }

    function actionFavorite($aryInfo) {
        // サーバ情報などの読み込み
        $arySetting = parse_ini_file("mastodon_setting.ini");
        /* Settings */
        $schema       = 'https';
        $host         = $arySetting['server'];
        $access_token = $arySetting['access_token'];
        $method       = 'POST';
        $endpoint     = '/api/v1/statuses/';
        $aryIds       = array_keys($aryInfo);
        foreach($aryIds as $id) {
            $status       = "$id/favourite/";
            $url          = "${schema}://${host}${endpoint}${status}";        
            /* Build request */
            $query  = "curl -X ${method}";
            $query .= " --header 'Authorization:";
            $query .= " Bearer ${access_token}'";
            $query .= " -sS ${url}";
            /* Request */
            $result = `$query`; //バッククォートに注意
//            print_r(json_decode($result, JSON_OBJECT_AS_ARRAY));
            print $query;
        }
    }
}
