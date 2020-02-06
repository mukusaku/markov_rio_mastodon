<?php
require __DIR__ . '/vendor/autoload.php';
require 'convertEntity.php';
require 'originalList.php';
use YuzuruS\Mecab\Markovchain;
$formatter = new formatter();
$formatter->execToot();
class formatter {
    function execToot(){
        // マルコフ連鎖の元となるテキストを生成する
        $rawText = $this->generateText();
        //print_r($rawText, false);
        $convertedText = $this->convertToAko($rawText);
        //print_r($convertedText, false);
        $markovText = $this->convertToMarkov($convertedText);
        //print_r($markovText, false);
        if(isset($markovText)) {
            $this->toot($markovText);
            return;
        } else {
            return;
        }
    }
    
    // 連合TLからトゥートを取得し整形する
    function generateText(){
        $ol = new originalList();
        $url = "https://akanechan.love/api/v1/timelines/public?limit=40";
        $json = file_get_contents($url); // 連合から取得したJSON
        $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        $ary = json_decode($json,true);
        $string = "";
        $i = 0; // ループ用
        foreach($ary as $skey => $sValue) {
            // 先頭40トゥートを抽出対象とする
            if($i == 40) {
                break;
            }
            
            // 取得したJSONをパースしhtmlタグを削除したトゥートだけを抽出する
            $rawValue = strip_tags($sValue['content']);
            
            // 英字が含まれていたらスキップ
            if(preg_match('/[a-zA-Z]/', $rawValue)) {
                continue;
            }
            
            // 末尾が句読点や感嘆符じゃなかったら文節判定用に「。」を付ける
            if(substr($rawValue,-1) != "。" 
                && substr($rawValue,-1) != "、" 
                && substr($rawValue,-1) != "！" 
                && substr($rawValue,-1) != "？")
            {
                $rawValue .= "。";
            }
            
            // 文章を連結する
            $string .= $rawValue;
            $i++;
        }
        //$rawText = $string . $ol->implodeSentences(); // この行を有効化するとオリジナルテキストも参照する
        
        return $string;
    }
    // 変換リストに沿った文章の加工を行う
    function convertToAko($rawText) {
        $sentence = "";
        $convertEntity = new convertEntity();
        // 変換対象の用語リストを配列で取得
        $aryConvertList = $convertEntity->aryConvertList;
        foreach($aryConvertList as $sBefore => $sAfter) {
            $rawText = str_replace($sBefore, $sAfter, $rawText);
        }
        $sentence = $rawText;
        return $sentence;
    }
    // マルコフ連鎖を利用した変換を行う
    function convertToMarkov($rawText) {
        //return $rawText; // この行を有効化するとマルコフ連鎖をオフ
        $mc = new Markovchain();
        $i = 0; // 無限ループ回避
        do {
            // 1文字以上50文字以下の文章ができるまで処理をやり直す
            $markovText = $mc->makeMarkovText($rawText);
            // 最初に句点が出るところまで切り出す
            $markovText = substr($markovText,0,strpos($markovText, '。'));
            ++$i;
        } while(mb_strlen($markovText) == 0 || mb_strlen($markovText) > 50 || $i < 100);
        
        return $markovText;
    }
    // 接頭辞の追加
    function addPrefix($sentence) {
        $convertEntity = new convertEntity();
        $aryPrefix = $convertEntity->aryPrefixList;
        $rand = array_rand($aryPrefix);
        return $aryPrefix[$rand] . $sentence;
    }
    // 接尾辞の追加
    function addSuffix($sentence) {
        $convertEntity = new convertEntity();
        $arySuffix = $convertEntity->arySuffixList;
        $rand = array_rand($arySuffix);
        return $sentence . $arySuffix[$rand];
    }
    // 実際のトゥート処理
    function toot($sentence, $addString = true) {
        // サーバ情報などの読み込み
        $arySetting = parse_ini_file("mastodon_setting.ini");
        /* Settings */
        $schema       = 'https';
        $host         = $arySetting['server'];
        $access_token = $arySetting['access_token'];
        $method       = 'POST';
        $endpoint     = '/api/v1/statuses';
        $url          = "${schema}://${host}${endpoint}";
        $visibility   = 'unlisted'; //投稿のプライバシー設定→「未収載」
        $toot_msg     = rawurlencode($sentence); //メッセージをcURL用にエスケープ
        if ($addString) {
            $toot_msg = $this->addPrefix($toot_msg);
            $toot_msg = $this->addSuffix($toot_msg);
        }
        /* Build request */
        $query  = "curl -X ${method}";
        $query .= " -d 'status=${toot_msg}'";
        $query .= " -d 'visibility=${visibility}'";
        $query .= " --header 'Authorization:";
        $query .= " Bearer ${access_token}'";
        $query .= " -sS ${url}";
        /* Request */
        $result = `$query`; //バッククォートに注意
        /* Show result */
        //print_r(json_decode($result, JSON_OBJECT_AS_ARRAY));
        //print $toot_msg;
    }
}
