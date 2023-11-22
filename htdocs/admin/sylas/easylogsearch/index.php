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
 * 管理者用簡易ログ検索画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.12 $
 * $Date: 2014/07/18 01:45:50 $
 **********************************************************/
include_once("../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("TMPLFILE",         "easylogsearch.tmpl");
define("OPERATION",        "Easy logsearch");

define("SELECT_GROUP_SQL", "SELECT * FROM loggroup;");

define("REGEXP_ERR_NUM", 1139);

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
    $priority   = $post["priority"];
    $hostname   = escape_html($post["hostname"]);
    $keyword    = escape_html($post["keyword"]);
    $searchtype = $post["searchtype"];
    $start      = $post["startdate"];
    $end        = $post["enddate"];
    $sesskey    = escape_html($post["sk"]);
    $resultline = $post["resultline"];

    $hidden = <<<EOD
<form method="post" name="search_condition">
  <input type="hidden" name="search_button" value="">
  <input type="hidden" name="page">
  <input type="hidden" name="loggroup" value="$loggroup">
  <input type="hidden" name="priority" value="$priority">
  <input type="hidden" name="hostname" value="$hostname">
  <input type="hidden" name="keyword" value="$keyword">
  <input type="hidden" name="searchtype" value="$searchtype">
  <input type="hidden" name="startdate" value="$start">
  <input type="hidden" name="enddate" value="$end">
  <input type="hidden" name="sk" value="$sesskey">
  <input type="hidden" name="resultline" value="$resultline">
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
 *       $looptag            ループタグ
 *
 * [返り値]
 *       TRUE                正常
 *       FALSE               異常
 **********************************************************/
function set_loop_tag($data, $page, &$looptag)
{
    global $web_conf;

    /* ループタグの生成 */
    $start = ($page - 1) * $_POST["resultline"];
    $end   = ($page * $_POST["resultline"]) - 1;

    $i = 0;
    $k = 0;
    foreach ($data as $one_data) {

       if ($i >= $start && $i <= $end) {
           /* エスケープ                                 *
            * ログの日時、ホスト名、メッセージを表示する */
           $log_date    = escape_html($one_data["DeviceReportedTime"]);
           $log_host    = escape_html($one_data["FromHost"]);
           $log_message = escape_html($one_data["Message"]);

           /* ループタグに値を代入 */
           $looptag[$k]["<<LOG_DATE>>"] = $log_date;
           $looptag[$k]["<<LOG_HOST>>"] = $log_host;
           $looptag[$k]["<<LOG_MESSAGE>>"] = str_replace(" ", "&nbsp", $log_message);
           $k++;
       }

       /* インクリメント */
       $i++;
    }

    return;
}

/*********************************************************
 * set_tag_data
 *
 * タグの内容を作成する
 *
 * [引数]
 *       $post               入力された値
 *       $tag                置き換えタグ
 *
 * [返り値]
 *       TRUE                正常
 *       FALSE               異常
 **********************************************************/
function set_tag_data($post, &$tag)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $lob_msg;

    /* タグ 設定 */
    $javascript = <<<EOD
    function allSubmit(url, page) {
        document.search_condition.action = url;
        document.search_condition.page.value = page;
        document.search_condition.submit();
    }

EOD;

    set_tag_common($tag, $javascript);

    /* 検索対象ログのセレクトボックス作成 */
    if (isset($_POST["loggroup"]) === TRUE) {
        $selected_log =$_POST["loggroup"];
    } else {
        $selected_log = -1;
    }
    $ret = make_log_option(SELECT_GROUP_SQL, $selected_log, $option);
    if ($ret === FALSE) {
        return FALSE;
    }
    $tag["<<LOG_OPTION>>"] = $option;

    /* プライオリティのセレクトボックス作成 */
    $option = "";
    if (isset($_POST["priority"]) === TRUE) {
        $priority =$_POST["priority"];
    } else {
        $priority = -1;
    }
    make_priority_option($priority, $option);
    $tag["<<PRIORITY_OPTION>>"] = $option;

    /* ホスト名のテキストボックス */
    if (isset($_POST["hostname"]) === TRUE) {
        $tag["<<HOSTNAME>>"] = escape_html($_POST["hostname"]);
    }

    /* キーワードのテキストボックス */
    if (isset($_POST["keyword"]) === TRUE) {
        $tag["<<KEYWORD>>"] = escape_html($_POST["keyword"]);
    }

    /* ラジオボタンのチェック決定 */
    if (isset($_POST["searchtype"]) === TRUE) {
        $radio =$_POST["searchtype"];
    } else {
        $radio = 0;
    }
    $option = array("", "", "");
    make_checked_radio($radio, $option);
    $tag["<<CHECKED0>>"] = $option[0];
    $tag["<<CHECKED1>>"] = $option[1];
    $tag["<<CHECKED2>>"] = $option[2];

    /* 検索開始時間セレクトボックス作成 */
    $option = array("0" => "",
                    "1" => "",
                    "2" => "",
                    "3" => "",
                    "4" => "",
                    "5" => ""
                   );


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


/***********************************************************
 * printCSV()
 * ダウンロード処理時に、ストリームに対して検索結果をCSV形式で吐き出す。
 *
 * [引数]
 *      $data       SQLから得られた検索結果(配列)
 * [返り値]
 *      なし
 **********************************************************/
function printCSV($data)
{
    global $web_conf;

    $keymap = array("msg"=>"Message",
                    "host"=>"FromHost",
                    "date"=>"DeviceReportedTime",
                   );

    $order = explode(",", $web_conf["sylas"]["csvformat"]);

    /* 検索結果を1件ずつ処理する */
    foreach ($data as $result) {
        $line = array();

        /* ダブルクオートをエスケープ */
        $result = str_replace('"', '""', $result);

        foreach ($order as $key) {
            $line[] = '"'. $result[$keymap[$key]]. '"';
        }

        /* カンマ区切りでつなげて出力 */
        print implode(",", $line);
        print "\r\n";
    }

    return;
}

/***********************************************************
 * 初期処理
 **********************************************************/

/* タグ初期化 */
$tag["<<TITLE>>"]               = "";
$tag["<<JAVASCRIPT>>"]          = "";
$tag["<<SK>>"]                  = "";
$tag["<<TOPIC>>"]               = "";
$tag["<<MESSAGE>>"]             = "";
$tag["<<TAB>>"]                 = "";
$tag["<<LOG_OPTION>>"]          = "";
$tag["<<PRIORITY_OPTION>>"]     = "";
$tag["<<START_YEAR_OPTION>>"]   = "";
$tag["<<HOSTNAME>>"]            = "";
$tag["<<KEYWORD>>"]             = "";
$tag["<<CHECKED0>>"]            = "";
$tag["<<CHECKED1>>"]            = "";
$tag["<<CHECKED2>>"]            = "";
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
$tag["<<COMMENT_START>>"]       = "<!--";
$tag["<<COMMENT_END>>"]         = "-->";
$tag["<<SEARCH_COUNT>>"]        = 0;
$tag["<<PRE>>"]                 = "";
$tag["<<NEXT>>"]                = "";
$tag["<<HIDDEN>>"]              = "";

$page = 0;

$groupdata = array();

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/

/* 検索ボタンが押されたとき */
$looptag = array();
if (isset($_POST["search_button"]) || isset($_POST["download_button"])) {

     $post = $_POST;
     /* ページ数が渡ってきたときはPOSTされた値を使用 *
      * 渡ってきていない時は1ページ目 */
     if (isset($_POST["page"]) === TRUE) {
         $page = $_POST["page"];
     } else {
         $page = 1;
     }

    /* 入力値チェック(関数内ですべての値をチェック) */
    $ret = check_easy_search_condition($post);
    if ($ret === TRUE) {

        /* MySQL接続 */
        $conn = MySQL_connect_server();
        if ($conn === FALSE) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }

    /* 検索タイプが "MYSQL" の場合 */
    if ($web_conf['sylas']['searchtype'] === MYSQL) {

            /* 簡易検索用SQLを作成する */
            $ret = make_easy_search_sql($conn, $post, $search_sql);
            if ($ret === 2) {
                mysqli_close($conn);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            /* 検索ログ外のホストが指定され、検索結果0件が確定した場合 */
            } else if ($ret === 3) {
                mysqli_close($conn);
                /* タグ置き換え */
                $tag["<<COMMENT_START>>"]       = "";
                $tag["<<COMMENT_END>>"]         = "";
                $tag["<<SEARCH_COUNT>>"]        = '0';
                $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], '0');
                /* 検索情報保持のhiddenを作成 */
                make_hidden($post, $tag);
                /* ダウンロードボタン押下時 */
                if (isset($_POST["download_button"])) {
                    /* ファイル名を決めてストリームセット */
                    $fn = "log_" . date("YmdHis") . ".csv";
                    header("Content-Disposition: attachment; filename=\"$fn\"");
                    header("Content-Type: application/octet-stream");
                    exit(0);
                }
            } else if ($ret === 0) {
                /* MySQLから情報を取得 */
                $result = MySQL_exec_query($conn, $search_sql);
                $err_num = 0;
                if ($result === FALSE) {
                    $err_num = mysqli_errno($conn);
                    mysqli_close($conn);

                    /* 正規表現不正でなないクエリ失敗はシステムエラー */
                    if ($err_num != REGEXP_ERR_NUM) {
                        result_log(OPERATION . ":NG:" . $log_msg);
                        syserr_display();
                        exit(1);
                   }
                }

                if ($err_num != REGEXP_ERR_NUM) {
                    /* MySQLに登録されたログ管理テーブルの情報を配列に格納 */
                    MySQL_get_data($result, $data);
                    mysqli_close($conn);

                    /* ダウンロードボタン押下時 */
                    if (isset($_POST["download_button"])) {
                        /* ファイル名を決めてストリームセット */
                        $fn = "log_" . date("YmdHis") . ".csv";
                        header("Content-Disposition: attachment; filename=\"$fn\"");
                        header("Content-Type: application/octet-stream");
                        /* 取得したデータをアウトプット */
                        printCSV($data);
                        exit(0);
                    }

                    /* タグ置き換え */
                    $data_count = count($data);
                    $tag["<<COMMENT_START>>"]       = "";
                    $tag["<<COMMENT_END>>"]         = "";
                    $tag["<<SEARCH_COUNT>>"]        = $data_count;
                    $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], $data_count);

                    /* 表示件数の決定 */
                    get_page($data, $page, $tag);

                    /* 検索情報保持のhiddenを作成 */
                    make_hidden($post, $tag);

                    /* ループタグの作成 */
                    set_loop_tag($data, $page, $looptag);

                } else {

                   $err_msg = $msgarr['28026'][SCREEN_MSG];

                   /* ログ出力 */
                   result_log(OPERATION . ":NG:" . $log_msg);
                }

            } else {
                /* ログ出力 */
                result_log(OPERATION . ":NG:" . $log_msg);
            }

        /* 検索タイプが "elasticsearch" の場合 */
        } else if ($web_conf['sylas']['searchtype'] === ELASTICSEARCH) {

            /* ロググループを検索する */
            $ret = get_loggroup($conn, $post['loggroup'], $groupdata);

            /* システムエラー(MYSQL接続エラー) */
            if ($ret === 2) {
                mysqli_close($conn);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);

            /* ロググループが存在する場合 */
            } else if ($ret === 0) {

                mysqli_close($conn);
                /* elasticsearchからログデータ取得 */
                $elastic_data = get_elasticdata($groupdata, $post);

                /* 検索対象のelasticsearchサーバに接続できなかった場合 */
                if ($elastic_data === FALSE) {
                    $err_msg = sprintf($msgarr['50000'][SCREEN_MSG], LOG_NAME_DISP);
                    $log_msg = sprintf($msgarr['50000'][LOG_MSG], LOG_NAME_LOG);
                    result_log(OPERATION . ":NG:" . $log_msg);
                    syserr_display();
                    exit(1);
                }
                   
                /* elasticserachの返り値をjsonデコード */
                $xmlarr = json_decode($elastic_data);

                /* jsonデコードから必要な値だけ取得 */
                $data = extract_values($xmlarr);

                /* 検索失敗時 */
                if ($data === false) {
                    result_log(OPERATION . ":NG:" . $log_msg);
                    $data = "";
                }

                /* ダウンロードボタン押下時 */
                if (isset($_POST["download_button"])) {
                    /* ファイル名を決めてストリームセット */
                    $fn = "log_" . date("YmdHis") . ".csv";
                    header("Content-Disposition: attachment; filename=\"$fn\"");
                    header("Content-Type: application/octet-stream");
                    /* 取得したデータをアウトプット */
                    printCSV($data);
                    exit(0);
                }

                /* タグ置換 検索結果が存在する場合 */
                if ($data !== "") {
                    $data_count = count($data);
                    $tag["<<COMMENT_START>>"]       = "";
                    $tag["<<COMMENT_END>>"]         = "";
                    $tag["<<SEARCH_COUNT>>"]        = $data_count;
                    $err_msg = sprintf($msgarr['28022'][SCREEN_MSG], $data_count);

                    /* 表示件数の決定 */
                    get_page($data, $page, $tag);

                    /* 検索情報保持のhiddenを作成 */
                    make_hidden($post, $tag);

                    /* ループタグの作成 */
                    set_loop_tag($data, $page, $looptag);
                };

            } else {
                mysqli_close($conn);
                /* ログ出力 */
                result_log(OPERATION . ":NG:" . $log_msg);
            }
        }

    } else {
        /* ログ出力 */
        result_log(OPERATION . ":NG:" . $log_msg);
    }
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

exit(0);
?>
