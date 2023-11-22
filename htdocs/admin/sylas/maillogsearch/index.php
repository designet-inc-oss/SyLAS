<?php 

/*
 * postLDAPadmin
 *
 * Copyright (C) 2006,2007 DesigNET, INC.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
/***********************************************************
 * 管理者用Mailログ検索画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.00 $
 * $Date: 2015/12/07 13:21:00 $
 **********************************************************/
include_once("lib/dglibcommon");
include_once("../initial");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("TMPLFILE",         "maillogsearch.tmpl");
define("OPERATION",        "MAIL logsearch");
define("SELECT_GROUP_SQL", "SELECT loggroup.group_id, loggroup.group_name, loginfo.log_type, loginfo.facility_name ". 
                           "FROM loggroup JOIN loginfo ON loggroup.log_id=loginfo.log_id WHERE log_type='mail';");
define("SELECT_FACILITY_SQL", "SELECT loginfo.facility_name, loginfo.search_tab, loginfo.app_name " .
                              "FROM loggroup JOIN loginfo ON loggroup.log_id=loginfo.log_id WHERE group_id=%s;");
define("MAIL_SEARCH_SQL",   "SELECT DeviceReportedTime, FromHost, Message " .
                            "FROM %s %s" .
                            " ORDER BY DeviceReportedTime desc LIMIT %s ;");

/*********************************************************
 * set_tag_data($post, &$tag)
 *
 * タグのセットをする
 *
 * [引数]
 *       $post       渡ってきた値
 *       $tag               タグ
 * [返り値]
 *       なし
 **********************************************************/
function set_tag_data($post, &$tag)
{
    global $web_conf;

    /*今日から24時間前の日付を取得*/
    $daylist = getdate(time() - 86400);

    /* タグ 設定 */
    $javascript = <<<EOD
    function allSubmit(url, page) {
        document.search_condition.action = url;
        document.search_condition.page.value = page;
        document.search_condition.submit();
    }
EOD;

    /*共通で利用するタグをセットする*/
    /*<<TITLE>>,<<MESSAGE>>,<<SK>>,<<TOPIC>>,<<TAB>>*/
    set_tag_common($tag, $javascript);

    /* ログのセレクトボックス作成 */
    if (isset($post["loggroup"])) {
        $selected_log =$post["loggroup"];
    } else {
        $selected_log = -1;
    }
    $ret = make_mail_log_option($selected_log, $option);
    if ($ret === FALSE) {
        return FALSE;
    }
    $tag["<<LOG>>"] = $option;

    /* 送信者のテキストボックス */
    if (isset($post["sendaddr"])) {
        $tag["<<SEARCH_FROM>>"] = escape_html($post["sendaddr"]);
    }

    /* 宛先アドレスのテキストボックス */
    if (isset($post["reciveaddr"])) {
        $tag["<<SEARCH_TO>>"] = escape_html($post["reciveaddr"]);
    }

    /*送信者アドレス条件リストボックス*/
    if (isset($post["sendrule"])) {
        $list = $post["sendrule"];
    } else {
        $list = "0";
    }
    $tag["<<FROM_RULE>>"] = make_checked_list($list);
    /*宛先アドレス条件リストボックス*/
    if (isset($post["reciverule"])) {
        $list = $post["reciverule"];
    } else {
        $list = "0";
    }
    $tag["<<TO_RULE>>"] = make_checked_list($list);

    $tag["<<STARTDATE>>"] = date("Y/m/d 00:00:00");
    if (isset($_POST["startdate"]) === TRUE) {
        $tag["<<STARTDATE>>"] = $_POST["startdate"];
    }

    $tag["<<ENDDATE>>"] = date("Y/m/d 23:59:59");
    if (isset($_POST["enddate"]) === TRUE) {
        $tag["<<ENDDATE>>"] = $_POST["enddate"];
    }

    $tag["<<DEFLINE>>"] = $web_conf["sylas"]["displaylines"];
    if (isset($_POST["resultline"])) {
        $tag["<<DEFLINE>>"] = $_POST["resultline"];
    }

    return TRUE;
}

/*********************************************************
 * make_hidden
 *
 * hiddenタグのフォームを作成する
 *
 * [引数]
 *       $post               入力された値
 *       $tag                置き換えタグ
 *
 * [返り値]
 *       TRUE                正常
 *       FALSE               異常
 **********************************************************/
function make_hidden($post, &$tag)
{
    /* 置き換える値を変数に代入 */
    $loggroup   = $post["loggroup"];
    $sendaddr   = escape_html($post["sendaddr"]);
    $reciveaddr   = escape_html($post["reciveaddr"]);
    $start      = $post["startdate"];
    $end        = $post["enddate"];
    $resultline = $post["resultline"];
    $sesskey    = escape_html($post["sk"]);

    /*hiddenタグ作成*/
    $hidden = <<<EOD
  <form method="post" name="search_condition">
  <input type="hidden" name="page">
  <input type="hidden" name="loggroup" value="$loggroup">
  <input type="hidden" name="sendaddr" value="$sendaddr">
  <input type="hidden" name="reciveaddr" value="$reciveaddr">
  <input type="hidden" name="startdate" value="$start">
  <input type="hidden" name="enddate" value="$end">
  <input type="hidden" name="sk" value="$sesskey">
  <input type="hidden" name="resultline" value="$resultline">
  <input type="hidden" name="next_button" value="search">
</form>
    
EOD;

    $tag["<<HIDDEN>>"] =  $hidden;
    return;
}

/*********************************************************
 * set_loop_tag
 *
 * ループタグを作成する
 *
 * [引数]
 *       $page               ページ
 *       $looptag            ループタグ
 *       $sesskey            セッションキー
 *       $post               $_POSTの値
 *
 * [返り値]
 *       TRUE                正常
 *       FALSE               異常
 **********************************************************/
function set_loop_tag($page, &$looptag, $sesskey, $post)
{
    global $web_conf;

    /* ループタグの生成 */
    $start = ($page - 1) * $_POST["resultline"];
    $end   = ($page * $_POST["resultline"]) - 1;

    $k = 0; 

    for ( ; $start <= $end ; $start++) {
        
        if (isset($_SESSION["result"][$start]) === FALSE) {
            break;
        }

        /* ループタグに値を代入 */
        $log_date = date("Y-m-d H:i:s" , $_SESSION["result"][$start]["date"]);
        $looptag[$k]["<<LOG_DATE>>"] = $log_date;
        $looptag[$k]["<<LOG_FROM>>"] = $_SESSION["result"][$start]["from"];
        $looptag[$k]["<<LOG_TO>>"] = $_SESSION["result"][$start]["to"];
        $looptag[$k]["<<LOG_STATUS>>"] = $_SESSION["result"][$start]["status"];
        $looptag[$k]["<<RL>>"] = $web_conf["sylas"]["displaylines"];

        /*詳細ボタン作成*/
        $looptag[$k]["<<MORE>>"] =  "<button class=\"mail_button\" type=\"submit\" name=\"more\" value=\"".$k."\">詳細</button>";
        $looptag[$k]["<<E_SESS>>"] = $sesskey;

        /*QIDチェック*/

        if (strpos($_SESSION["result"][$start]["qid"], "NOQUEUE") === FALSE) {
            $sdate = date("Y/m/d H:i:s", strtotime($log_date. " -1 day"));
            $edate = date("Y/m/d H:i:s", strtotime($log_date. " +1 day"));

            $looptag[$k]["<<S_D>>"] = $sdate;
            $looptag[$k]["<<E_D>>"] = $edate;
            $looptag[$k]["<<QID>>"] = $_SESSION["result"][$start]["qid"]. ":";
        } else {
            $date = preg_replace("/-/", "/", $log_date);
            $looptag[$k]["<<S_D>>"] = $date;
            $looptag[$k]["<<E_D>>"] = $date;
            $looptag[$k]["<<QID>>"] = "NOQUEUE";
        }
        $looptag[$k]["<<HOSTNAME>>"] = $_SESSION["result"][$start]["host"];
        $looptag[$k]["<<E_LOG>>"] = $post["loggroup"];
        $k++;
    }
    return;
}

/* タグ初期化 */
$tag["<<TITLE>>"]               = "";
$tag["<<JAVASCRIPT>>"]          = "";
$tag["<<SK>>"]                  = "";
$tag["<<TOPIC>>"]               = "";
$tag["<<MESSAGE>>"]             = "";
$tag["<<TAB>>"]                 = "";
$tag["<<LOG>>"]                 = "";
$tag["<<SEARCH_FROM>>"]         = "";
$tag["<<SEARCH_TO>>"]           = "";
$tag["<<FROM_RULE>>"]           = "";
$tag["<<TO_RULE>>"]             = "";
$tag["<<STATUS>>"]              = "";
$tag["<<START_YEAR_OPTION>>"]   = "";
$tag["<<START_MONTH_OPTION>>"]  = "";
$tag["<<START_DATE_OPTION>>"]   = "";
$tag["<<START_HOUR_OPTION>>"]   = "";
$tag["<<START_MINUTE_OPTION>>"] = "";
$tag["<<START_SECOND_OPTION>>"] = "";
$tag["<<END_YEAR_OPTION>>"]     = "";
$tag["<<END_MONTH_OPTION>>"]    = "";
$tag["<<END_DATE_OPTION>>"]     = "";
$tag["<<END_HOUR_OPTION>>"]     = "";
$tag["<<END_MINUTE_OPTION>>"]   = "";
$tag["<<END_SECOND_OPTION>>"]   = "";
$tag["<<SEARCH_COUNT>>"]        = 0;/*全..件*/
$tag["<<COMMENT_START>>"]       = "<!--";
$tag["<<COMMENT_END>>"]         = "-->";
$tag["<<PRE>>"]                 = "";
$tag["<<NEXT>>"]                = "";
$tag["<<HIDDEN>>"]              = "";
$page = 0;
/*********************************************************
 * make_chcked_list()
 *
 * 指定のリストセレクトボックスのオプションを作成
 * 
 *
 * [引数]
 *       $list              $_POSTから渡ってきた値
 *       
 *
 * [返り値]
 *      $optionlist 
 **********************************************************/
function make_checked_list($list)
{
    $selected = array("0" => "",
                      "1" => "",
                     );
    $optionlist = "";
    if($list === "0"){
        $selected[0] = " selected";
    } else {
        $selected[1] = " selected";
    }  
    $optionlist .= "<option value=0 ".$selected[0].">と一致する</option>\n";
    $optionlist .= "<option value=1 ".$selected[1].">を含む</option>\n";
    return $optionlist;
}

/*********************************************************
 * make_mail_log_option()
 *
 * ロググループのセレクトボックスのオプションを作成
 * (mailログ検索画面)
 *
 * [引数]
 *       $post_group_id      selectedにするログ
 *       $option
 *
 * [返り値]
 *       なし
 **********************************************************/
function make_mail_log_option($post_group_id, &$option)
{

    /* ロググループ情報をMySQLから取得 */
    $ret = get_data(SELECT_GROUP_SQL, $data);
    if ($ret === FALSE) {
        return FALSE;
    }

    /*ログの種類がmailのグループが存在しないとき*/
    $option = "";
    if (isset($data[0]) === FALSE) {
        /* セレクトボックスを作成 */
        $option .= "<option value=\"-1\" selected>----------</option>";
    } else {
        /*ログの種類がmailのグループが存在するとき*/
        foreach ($data as $one_data) {
            $group_name = escape_html($one_data["group_name"]);
            $group_id   = escape_html($one_data["group_id"]);
            /* POSTで渡ってきた値と同じ時(値保持) */
            if ($one_data["group_id"] === $post_group_id) {
                $select = " selected";
            } else {
                $select = "";
            }
            $option .= "<option value=\"".$group_id."\"".$select.">".$group_name."</option>";
        }
    }
    return TRUE;
}

/*********************************************************
 * check_mail_search_condition()
 *
 * mailログ検索の検索条件をチェックする
 *
 * [引数]
 *       $post               入力された値
 *
 * [返り値]
 *       TRUE                正常
 *       FALSE               異常
 **********************************************************/
function check_mail_search_condition($post)
{
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /*初期化処理*/
    $start = "";
    $end = "";

    /*ログのチェック*/
    if (isset($post["loggroup"]) === FALSE || $post["loggroup"] == "-1") {
        $err_msg = $msgarr['28014'][SCREEN_MSG];
        return FALSE;
    }
      
    $strmaxlen = 256;
    /*送信者アドレスが入力されているとき、最大文字数、文字種をチェック*/
    if (isset($post["sendaddr"])) {
        $ret = check_string($post["sendaddr"], $strmaxlen);
        if($ret !== 0) {
            /*retの値が1,2(文字列、文字種エラー)のときエラーメッセージ*/
            $err_msg = $msgarr['41005'][SCREEN_MSG];
            return FALSE;
        }
    } else {
        $err_msg = $msgarr['41005'][SCREEN_MSG];
        return FALSE;
    } 

    /*送信者条件空チェック*/
    if (isset($post["sendrule"]) === FALSE) {
        $err_msg = $msgarr['41005'][SCREEN_MSG];
        return FALSE;
    }

    /*宛先アドレスが入力されているとき、最大文字数、文字種をチェック*/
    if (isset($post["reciveaddr"])) {
        $ret = check_string($post["reciveaddr"], $strmaxlen);
        if($ret !== 0) {
            /*retの値が1,2(文字列、文字種エラー)のときエラーメッセージ*/
            $err_msg = $msgarr['41006'][SCREEN_MSG];
            return FALSE;
        }
    } else {
        $err_msg = $msgarr['41006'][SCREEN_MSG];
        return FALSE;
    }

    if (isset($post["reciverule"]) === FALSE) {
        $err_msg = $msgarr['41006'][SCREEN_MSG];
        return FALSE;
    }

    /*検索期間のチェック*/
    /*開始日付*/
    if (!isset($post["startdate"])) {
        $err_msg = $msgarr['41008'][SCREEN_MSG];
        return FALSE;
    }

    $ret = check_time_format($post["startdate"]);
    if ($ret === FALSE) {
        $err_msg = $msgarr['41008'][SCREEN_MSG];
        return FALSE;
    }

    /*終了日付*/
    if (!isset($post["enddate"])) {
        $err_msg = $msgarr['41009'][SCREEN_MSG];
        return FALSE;
    }

    $ret = check_time_format($post["enddate"]);
    if ($ret === FALSE) {
        $err_msg = $msgarr['41009'][SCREEN_MSG];
        return FALSE;
    }

    /* 開始と終了の前後チェック */
    $start = strtotime($post["startdate"]);
    $end   = strtotime($post["enddate"]);
    if ($start > $end) {
        $err_msg = $msgarr['28018'][SCREEN_MSG];
        $log_msg = $msgarr['28018'][LOG_MSG];
        return FALSE;
    }

    $ret = check_duration($post["startdate"], $post["enddate"]);
    if ($ret === FALSE) {
        return FALSE;
    }

    $ret = check_resultline($post);
    if ($ret === FALSE) {
        return FALSE;
    }

    return TRUE;
}

/*********************************************************
 * check_string()
 *
 * mailログ検索画面の入力値チェックを行なう
 *
 * [引数]
 *        $string      検査する文字列
 *        $maxlen      最大文字長
 *
 * [返り値]
 *       0             正常
 *       1             文字長エラー
 *       2             文字種エラー
**********************************************************/
function check_string($string, $maxlen)
  {
     $length = strlen($string);
     if ($length > $maxlen) {
         return 1;
     }

     /* 半角英大小文字、数字、特定記号のみ許可 */
     $num = "0123456789";
     $sl = "abcdefghijklmnopqrstuvwxyz";
     $ll = strtoupper($sl);
     $sym = "!#$%&'*+-/=?^_{}~.@";
     $allow_letter = $num . $sl . $ll . $sym;
     if (strspn($string, $allow_letter) !== $length) {
         return 2;
     }

     return 0;
}


/*********************************************************
 * exec_mail_search
 *
 * 入力値チェックをし、条件に合致したログから情報を取得する
 *
 * [引数]
 *       $post               渡ってきた値
 *
 * [返り値]
 *       0               正常
 *       1               入力チェック失敗
 *       2               mysql失敗
 **********************************************************/
function exec_mail_search($post)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /*セッション初期化*/
    $_SESSION = array();

    /*初期化処理*/
    $data_count = 0;
    $maxsearch = 0;
    $num_key = 0;
    $num = array();
    $num_data = array();
    $session = array();
    $noqsession = array();
    $noqkey = 0;
 
    /*入力値チェック*/
    $ret = check_mail_search_condition($post);
    if ($ret === FALSE) {
        return(1);
    }

    /*MySQL接続*/
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return(2);
    }

    /* 検索タイプが "MYSQL" の場合 */
    if ($web_conf['sylas']['searchtype'] === MYSQL) {

        /*Mailログ検索用SQLを作成する*/
        if(make_mail_search_sql($post, $search_sql, $conn) === FALSE) {
            mysqli_close($conn);
            return(1);
        }

        /*MySQLから情報を取得*/
        $result = MySQL_exec_query($conn, $search_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return(2);
        }

        /*MySQLに登録されたログ管理テーブルの情報を配列に格納*/
        MySQL_get_data($result, $data);
        mysqli_close($conn);

    /* 検索タイプが "elasticsearch" の場合 */
    } else if ($web_conf['sylas']['searchtype'] === ELASTICSEARCH) {

        /* ロググループを検索する */
        $ret = get_loggroup($conn, $post['loggroup'], $groupdata);
        mysqli_close($conn);

        /* システムエラー(MYSQL接続エラー) */
        if ($ret === 2) {
            result_log(OPERATION . ":NG:" . $log_msg);
            return(1);

        /* ロググループが存在する場合 */
        } else if ($ret === 0) {

            $gettype = "mail";
            /* elasticsearchからログデータ取得 */
            $elastic_data = get_elasticdata($groupdata, $post, $gettype);

            /* 検索対象のelasticsearchサーバに接続できなかった場合 */
            if ($elastic_data === NULL) {
                $err_msg = sprintf($msgarr['50000'][SCREEN_MSG], LOG_NAME_DISP);
                $log_msg = sprintf($msgarr['50000'][LOG_MSG], LOG_NAME_LOG);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                return(1);
            }

            /* elasticserachの返り値をjsonデコード(連想配列に代入) */
            $xmlarr = json_decode($elastic_data);
            /* jsonデコードから必要な値だけ取得 */
            $data = extract_values($xmlarr);

            /* 検索失敗時 */
            if ($data === false) {
                return(1);
            }
        } else {
            /* ログ出力 */
            result_log(OPERATION . ":NG:" . $log_msg);
            return(1);
        }
    }

    /*データベースでの件数を$maxsearchに代入*/
    $maxsearch = count($data);

    if ($maxsearch >= $web_conf['sylas']['maxsearchcount']) {     
        unset($data[$maxsearch -1]);
    }

    /*$session格納*/
    foreach($data as $value) {

        if (preg_match("/(^NOQUEUE): (.*)from=<(.*)> to=<(.*)> proto/", $value["Message"], $NOQID)) {
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["from"] = escape_html($NOQID[3]);
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["to"] = escape_html($NOQID[4]);
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["status"] = escape_html($NOQID[2]);
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["date"] = escape_html(strtotime($value["DeviceReportedTime"]));
            $noqkey++;
       /*QID正規表現*/
       } elseif (preg_match("/^([A-F 0-9]*): /", $value["Message"], $QID)) {

            /*既にQIDがあるかどうか*/
            if (isset($session[$value["FromHost"]][$QID[1]]) === FALSE) {

                /* QIDがまだ見つからなかったら、新規にセット */
                $session[$value["FromHost"]][$QID[1]]["from"] = "";
                $session[$value["FromHost"]][$QID[1]]["to"] = "";
                $session[$value["FromHost"]][$QID[1]]["status"] = "";
                $session[$value["FromHost"]][$QID[1]]["date"] = escape_html(strtotime($value["DeviceReportedTime"]));
            }

            /*fromの正規表現*/
            if (preg_match("/from=<(.*)>/", $value["Message"], $from)) {
                $session[$value["FromHost"]][$QID[1]]["from"] = escape_html($from[1]);
            }

            /*toの正規表現*/
            if (preg_match("/to=<(.*)>, /", $value["Message"], $to)) {
                $session[$value["FromHost"]][$QID[1]]["to"] = escape_html($to[1]);
            }

            /*statusの正規表現*/
            if (preg_match("/status=(.*)/", $value["Message"], $status)) {
                $session[$value["FromHost"]][$QID[1]]["status"] = escape_html($status[1]);
            }
        }
    }

    /*変数に代入*/
    foreach ($session as $num_HOST => $num_val1) {
        foreach ($num_val1 as $num_qid => $num_val2) {
            $num[$num_key]["host"] = $num_HOST;
            $num[$num_key]["qid"] = $num_qid;
            $num[$num_key]["from"] = $num_val2["from"];
            $num[$num_key]["to"] = $num_val2["to"];
            $num[$num_key]["date"] = $num_val2["date"];
            $num[$num_key]["status"] = $num_val2["status"];
            $num_key++;
        }
    }

    /*ソート処理*/
    foreach ($num as $key_num => $value_num) {
            $num_data[$key_num] = $value_num["date"];
    }

    /*降順にソート*/
    array_multisort($num_data, SORT_DESC, $num);

    /*条件合致処理*/
    foreach ($num as $key_num => $val1) {

        /*送信者条件合致処理*/
        if ($post["sendaddr"] !== "") {
            $lpaddr = strtolower($post["sendaddr"]);
            $lladdr = strtolower($val1["from"]);

            if ($post["sendrule"] === "0") {
                if ($lpaddr !== $lladdr) {
                     continue;
                }
            } elseif (strstr($lladdr, $lpaddr) === FALSE) {
                continue;
            }
        }
 
        /*宛先条件合致処理*/
        if ($post["reciveaddr"] !== "") {
            $lpaddr = strtolower($post["reciveaddr"]);
            $lladdr = strtolower($val1["to"]);
            if ($post["reciverule"] === "0") {
                if ($lpaddr !== $lladdr) {
                    continue;
                }
            } elseif (strstr($lladdr, $lpaddr) === FALSE) {
                continue;
            }
        }

        /*セッション格納*/
        $_SESSION["result"][$data_count]["from"] = $val1["from"];
        $_SESSION["result"][$data_count]["to"] = $val1["to"];
        $_SESSION["result"][$data_count]["date"] = $val1["date"];
        $_SESSION["result"][$data_count]["status"] = $val1["status"];
        $_SESSION["result"][$data_count]["host"] = $val1["host"];
        $_SESSION["result"][$data_count]["qid"] = $val1["qid"];
        $data_count++;
    }

    /*件数表示*/
    if ($maxsearch <=  $web_conf['sylas']['maxsearchcount']) {
        $err_msg = sprintf($msgarr['41000'][SCREEN_MSG], $data_count);
    } else {
        $err_msg = sprintf($msgarr['41001'][SCREEN_MSG], $web_conf['sylas']['maxsearchcount'], $data_count);
    }

    return(0);
}

/*********************************************************
 * make_mail_search_sql()
 *
 * ログ検索の検索用SQLを作成する
 *
 * [引数]
 *       $post              POST情報
 *       $sql               入力された値
 *
 * [返り値]
 *       なし 
 **********************************************************/
function make_mail_search_sql($post, &$sql, $conn)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
    
    /*初期化処理*/ 
    $end = "";
    $start = "";
    $check_count = $web_conf['sylas']['maxsearchcount'] + 1;
    $search_sql = array();
    $facility_arr = array();
    $app_name = "";


    $mysql_facilitynumbers = array(
                         "kern"     => 0,
                         "user"     => 1,
                         "mail"     => 2,
                         "daemon"   => 3,
                         "auth"     => 4,
                         "security" => 4,
                         "syslog"   => 5,
                         "lpr"      => 6,
                         "news"     => 7,
                         "uucp"     => 8,
                         "cron"     => 9,
                         "authpriv" => 10,
                         "ftp"      => 11,
                         "local0"   => 16,
                         "local1"   => 17,
                         "local2"   => 18,
                         "local3"   => 19,
                         "local4"   => 20,
                         "local5"   => 21,
                         "local6"   => 22,
                         "local7"   => 23);

    $sql_str = sprintf(ESL_LOGGROUP_SQL,$post["loggroup"]); 
    $ret = get_data($sql_str, $data);
    if ($ret === FALSE) {
        return FALSE;
    }
    /* データがないとき、ロググループに検索対象ホストが設定されていない */
    if (count($data) === 0) {
        $err_msg = $msgarr['28019'][SCREEN_MSG];
        $log_msg = $msgarr['28019'][LOG_MSG];
        return FALSE;
    }

    $tmp_sql = "";
   /*テーブル処理*/
   if ($data[0]["search_tab"] === "") {
       $tab =  mysqli_real_escape_string($conn, $web_conf["sylas"]["defaultsearchtable"]);
   } else {
       $tab = mysqli_real_escape_string($conn, $data[0]["search_tab"]);
   }

    /* 期間条件部分 */
    make_timerange_sql($conn, $post, $timerange_sql);
    $tmp_sql = "WHERE ". $timerange_sql;

    /*ファシリティ処理*/
    /* ファシリティを文字列から番号に変換
    * (データベースには番号で登録されている) */
    $facilitynames = explode(" ", $data[0]["facility_name"]);

    foreach ($facilitynames as $facility_num => $facility_name) {
        if (isset ($mysql_facilitynumbers["$facility_name"])){
            $facility_arr[] = "Facility=". $mysql_facilitynumbers["$facility_name"];
        }
    }
    $facility_values = implode(" OR ", $facility_arr);
    if ($facility_values !== "") {
        if ($tmp_sql != "") {
            $tmp_sql .= " AND ";
        } else {
            $tmp_sql .= " WHERE ";
        }
        $tmp_sql .= "(" . $facility_values . ")";
    }
    
   /*アプリケーション処理*/
   if ($data[0]["app_name"] !== "") {
       if ($tmp_sql != "") {
           $tmp_sql .= " AND ";
       } else {
           $tmp_sql .= " WHERE ";
       }
       $tmp_sql .= "SysLogTag LIKE \"". mysqli_real_escape_string($conn, $data[0]["app_name"])."%\"";
   }


    foreach ($data as $one_data) {
        /* 全てのホストが指定されているとき、ホスト条件は付加しない */
        if ($one_data["host_id"] === "1") {
            $tmp_sql .= "";
            break;
        }
        if ($tmp_sql != "") {
            $tmp_sql .= " AND ";
        } else {
            $tmp_sql .= " WHERE ";
        }
        $tmp_sql .= sprintf("FromHost IN (SELECT hosts.host_name From hosts JOIN search_hosts on hosts.host_id=search_hosts.host_id WHERE group_id=%s)", $post['loggroup']);
    }

   /* SQL作成 */
   $sql = sprintf(MAIL_SEARCH_SQL, $tab, $tmp_sql, $check_count);

   return;
}

/*********************************************************
 * get_mailpage
 *
 * 前ページ・次ページの取得
 *
 * [引数]
 *       $data_count        表示件数
 *       $page              ページ
 *       $tag               タグ
 * [返り値]
 *       なし
 **********************************************************/
function get_mailpage($data_count, $page, &$tag)
{
    global $web_conf;

        /* 初期表示(0ページ目表示)の場合、特に何もしない */
    if ($page === 0) {
        return;
    }
    /* ===== 検索後 ===== */

    /* 全ページ数 */
    $all_page = ceil($data_count / $_POST["resultline"]);
    if ($all_page === 0) {
        $all_page = 1;
    }

    /* 全ページ以上の数字が渡ってきたら最後のページを表示 */
    if ($page >= $all_page) {
        $page = $all_page;
    }
    /* 最初のページでなければ前ページを表示 */
    if ($page > 1) {
        $previous_page = $page - 1;
        $tag["<<PRE>>"] = "<a href=\"#\" onClick=\"allSubmit('index.php', '$previous_page')\">前ページ</a>";
    }

    /* 最後のページでなければ次ページを表示 */
    if ($page !== $all_page) {
        $next_page = $page + 1;
        $tag["<<NEXT>>"] = "<a href=\"#\" onClick=\"allSubmit('index.php', '$next_page')\">次ページ</a>";
    }
    /* ページ番号を置き換えタグに入れる */
    $tag["<<PAGE_NUM>>"] = $page;

    return;
}

/****************************************
*             初期処理                  *
****************************************/

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}
$looptag = array();


/****************************************
*                main                   *
****************************************/

/* 検索ボタンが押されたとき */
if (isset($_POST["search_button"])) {

    /*セッション開始*/
    session_start();

    /*初期化処理*/
    $data_count = 0;
    $page = 1;

    /*セッション生成*/
    $ret = exec_mail_search($_POST);
    if ($ret === 2) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    } 
    if ($ret !== 1) {

        if (isset($_SESSION["result"])) {
            /*$_SESSION数を$data_countに代入する*/
            $data_count = count($_SESSION["result"]);
        }
        $tag["<<COMMENT_START>>"]       = "";
        $tag["<<COMMENT_END>>"]         = "";
        $tag["<<SEARCH_COUNT>>"]        = $data_count;
        
        /* ループタグの作成 */
        set_loop_tag($page, $looptag, $sesskey, $_POST);

        /* 表示件数の決定 */
        get_mailpage($data_count, $page, $tag);

        /*hidden作成*/
        /* 検索情報保持のhiddenを作成 */
        make_hidden($_POST, $tag);
    }
}

/* 前ページもしくは次ページが押されたとき */
if (isset($_POST["next_button"])) {

    /*セッション開始*/
    session_start();

    /*初期化処理*/
    $data_count = 0;

    /* ページ数が渡ってきたときはPOSTされた値を使用 *
     * 渡ってきていない時は1ページ目 */
    if (isset($_POST["page"])) {
        $page = $_POST["page"];
    } else {
        $page = "1";
    } 

    if (isset($_SESSION["result"])) {
        /*$_SESSION数を$data_countに代入する*/
        $data_count = count($_SESSION["result"]);
    }
    $tag["<<COMMENT_START>>"]       = "";
    $tag["<<COMMENT_END>>"]         = "";
    $tag["<<SEARCH_COUNT>>"]        = $data_count;

    /* ループタグの作成 */
    set_loop_tag($page, $looptag, $sesskey, $_POST);

    /* 表示件数の決定 */
    get_mailpage($data_count, $page, $tag);

    /*hidden作成*/
    /* 検索情報保持のhiddenを作成 */
    make_hidden($_POST, $tag);
}

/***********************************************************
 * 表示処理
 **********************************************************/

/* タグ 設定 */
$ret = set_tag_data($_POST, $tag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* ページの出力 */
$ret = display(TMPLFILE, $tag, $looptag, "<<STARTLOOP>>", "<<ENDLOOP>>");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

?>
