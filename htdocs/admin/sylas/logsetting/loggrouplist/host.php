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
 * 検索対象ログ追加画面
 *
 * $RCSfile: host.php,v $
 * $Revision: 1.3 $
 * $Date: 2014/07/16 03:58:45 $
 **********************************************************/
include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("TMPLFILE",              "host_add_del.tmpl");
define("OPERATION",             "Search host add_del");

define("SELECT_HOST_SQL",       "SELECT * FROM hosts;");
define("SELECT_SEARCHHOST_SQL", "SELECT * FROM search_hosts WHERE group_id=%s;");
define("INSERT_GROUP_SQL",   "INSERT INTO loggroup " .
                             "(group_name, log_id) VALUES (\"%s\", \"%s\");");
define("GET_GROUPID_SQL",    "SELECT group_id FROM loggroup where " .
                             "group_name=\"%s\";");


/*********************************************************
 * SetTag_for_FirstTime()
 *
 * 初期表示時のタグを作成する
 *
 * [引数]
 *       $tag               置き換えタグ
 *
 * [返り値]
 *       TRUE               成功
 *       FALSE              失敗
 *********************************************************/
function SetTag_for_FirstTime(&$tag)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
 
    /* MySQL接続 */
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return FALSE;
    }

    /* MySQLからすべてのホストを取得 */
    $result = MySQL_exec_query($conn, SELECT_HOST_SQL);
    if ($result === FALSE) {
        mysqli_close($conn);
        return FALSE;
    }

    /* MySQLに登録されたすべてのホスト情報を配列に格納 */
    MySQL_get_data($result, $all_hosts);

    /* 編集画面からきていた場合、そのグループに含まれるホストを取得 */
    if (isset($_POST["hostadd_del"])) {
        /* MySQLからすべての検索対象ホストを取得 */
        $search_host_sql = sprintf(SELECT_SEARCHHOST_SQL, $_POST["group_id"]);
        $result = MySQL_exec_query($conn, $search_host_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return FALSE;
        }

        /* MySQLに登録された検索対象ホスト情報を配列に格納 */
        MySQL_get_data($result, $search_hosts);
    /* 登録処理の場合、リストを空で用意しておく(Nortice回避) */
    } else {
        $search_hosts = array();
    }

    /* MySQLとの接続を閉じる */
    mysqli_close($conn);

    /* 検索対象、非対象ホストリストを整形する */
    $non_search = array();
    $search     = array();
    make_search_hosts($all_hosts, $search_hosts, $non_search, $search);

    /* htmlタグに整形 */
    make_hosts_option($non_search, $a_option);
    make_hosts_option($search, $s_option);

    $tag["<<ALL_HOST_OPTION>>"]     = $a_option;
    $tag["<<SEARCH_HOST_OPTION>>"]  = $s_option;

    /* hiddenの作成 */
    make_id_hidden($non_search, $a_hidden, "all_id");
    make_id_hidden($search, $s_hidden, "search_id");
    $tag["<<NON_SEARCH_ID_HIDDEN>>"]    = $a_hidden;
    $tag["<<SEARCH_ID_HIDDEN>>"]        = $s_hidden;

    return TRUE;
}

/**********************************************************
 * move_hosts()
 *
 * 上下の矢印が押された際に、片方のリストからもう片方のリストに
 * ホストを移動させる。
 *
 * [引数]
 *       &$topList      上のリストに入れるホスト群
 *                          (id => ホスト名の連想配列)
 *       &$botList      下のリストに入れるホスト群
 *                          (id => ホスト名の連想配列)
 *
 * [返り値]
 *       なし
 **********************************************************/
function move_hosts(&$topList, &$botList)
{
    /* 新しいリストボックスを格納する変数を初期化 */
    $newFrom = array();
    $newTo   = array();

    /* upボタン, downボタンに両対応させる。
     * fromからtoへ、selectedを移動させる。
     * downボタンが押されたときは、
     * fromがhiddenで渡される選択可能ホスト($_POST["all_id"])となり、
     *   toがhiddenで渡される検索対象ホスト($_POST["search_id"])になる。
     * upボタンが押されたときはその逆 */
    list($selected, $from, $to, $mode) = isset($_POST["down"]) ?
                          array("all_host", "all_id", "search_id", "down")
                        : array("search_host", "search_id", "all_id", "up");

    /* 各$_POSTの値が空かどうかをチェック(エラー回避) */
    $moveHosts = isset($_POST[$selected]) ? $_POST[$selected] : array();
    $fromHosts = isset($_POST[$from])     ? $_POST[$from]     : array();
    $toHosts   = isset($_POST[$to])       ? $_POST[$to]       : array();

    /* 移動もとのホストリストを作り直す。
     * $fromHostsは、古い移動元ホストリストを格納した配列。
     * hiddenタグで送られてくる */
    foreach ($fromHosts as $host) {
        /* 古いリストから、移動させる対象は飛ばす */
        if (in_array($host, $moveHosts)) {
            continue;
        }
        /* HTMLには、"id,ホスト名" の形式で記述されている */
        list($host_id, $host_name) = explode(",", $host);
        /* のちの関数(make_hosts_optionなど)に合わせて整形 */
        $newFrom[$host_id] = $host_name;
    }

    /* 移動先のホストリストを作り直す。
     * $toHostsはhiddenから来る
     */
    foreach ($toHosts as $host) {
        list($host_id, $host_name) = explode(",", $host);
        $newTo[$host_id] = $host_name;
    }
    /* 移動させる対象を移動先に追加する */
    foreach ($moveHosts as $host) {
        list($host_id, $host_name) = explode(",", $host);
        $newTo[$host_id] = $host_name;
    }

    /* モードに合わせ、引数で渡された変数($topList, $botList)に
     * いま作成した上下のリストボックス内容をセット */
    list($topList, $botList) = $mode == "down" ? array($newFrom, $newTo)
                                               : array($newTo, $newFrom);

    return;
}


/**********************************************************
 * add_loggroup()
 * $_POST["fromADD"]の値を使い、データベースに新規ロググループを作成する。
 *
 * [引数]
 *          &$group_name    画面に表示するメッセージ用
 *
 * [返り値]
 *          $group_id       登録したグループのID
 *          FALSE           SQLエラー
 **********************************************************/
function add_loggroup(&$group_name)
{
    /* 新規ホスト追加画面で入力されたロググループ名、検索ログIDを取得 */
    list($group_name, $log_id) = explode(",", $_POST["fromADD"]);

    /* MySQL接続 */
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return FALSE;
    }

    $search_group_name = mysqli_real_escape_string($conn, $group_name);

    /* MySQLにロググループを登録 */
    $ret = add_mod_loggroup($conn, INSERT_GROUP_SQL, 
                            $group_name, $log_id);
    if ($ret === FALSE) {
        return FALSE;
    }   

    /* MySQLから、今登録したグループのIDを取得 */

    $gid_sql = sprintf(GET_GROUPID_SQL, $search_group_name);
    $ret = get_data($gid_sql, $data);
    if ($ret === FALSE) {
        return FALSE;
    }

    return $data[0]["group_id"];
}


/***********************************************************
 * 初期処理
 **********************************************************/

/* タグ初期化 */
$tag["<<TITLE>>"]                = "";
$tag["<<JAVASCRIPT>>"]           = "";
$tag["<<SK>>"]                   = "";
$tag["<<TOPIC>>"]                = "";
$tag["<<MESSAGE>>"]              = "";
$tag["<<TAB>>"]                  = "";
$tag["<<ALL_HOST_OPTION>>"]      = "";
$tag["<<SEARCH_HOST_OPTION>>"]   = "";
$tag["<<NAME_HIDDEN>>"]          = "";
$tag["<<SEARCH_ID_HIDDEN>>"]     = "";
$tag["<<NON_SEARCH_ID_HIDDEN>>"] = "";

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/

/* 上下のボタンが押されたとき */
if (isset($_POST["up"]) || isset($_POST["down"])) {
    /* リストの入れ替えを行う */
    $non_search = array(); // これに「選択可能ホスト」に入れたいものを詰めこむ
    $search = array();     // これに「検索対象ホスト」に入れたいものを詰めこむ
    move_hosts($non_search, $search);

    /* タグを作成する */
    /* セレクトのオプション作成 */
    make_hosts_option($non_search, $a_option);
    make_hosts_option($search, $s_option);
    $tag["<<ALL_HOST_OPTION>>"]     = $a_option;
    $tag["<<SEARCH_HOST_OPTION>>"]  = $s_option;

    /* hiddenの作成 */
    make_id_hidden($non_search, $a_hidden, "all_id");
    make_id_hidden($search, $s_hidden, "search_id");
    $tag["<<NON_SEARCH_ID_HIDDEN>>"]    = $a_hidden;
    $tag["<<SEARCH_ID_HIDDEN>>"]        = $s_hidden;
    $_POST["fromADD"] = escape_html($_POST["fromADD"]);

/* 登録ボタンが押されたとき */
} else if (isset($_POST["mod"])) {
    /* 新規登録のときと更新処理のときで、グループIDを変える */
    /* 新規登録(add.phpから遷移してきた) */
    if ($_POST["fromADD"] != "") {
        /* 新規グループを登録し、IDを取得する */
        $group_id = add_loggroup($group_name);
        if ($group_id === FALSE) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }
        /* 遷移先画面を設定 */
        $location = "index.php";
        /* 登録完了メッセージをセット */
        $group_name = escape_html($group_name);
        $err_msg = sprintf($msgarr['28028'][SCREEN_MSG], $group_name);
        $log_msg = sprintf($msgarr['28028'][LOG_MSG], $group_name);
    /* 更新処理 */
    } else {
        /* 一覧画面から継承しているIDを設定 */
        $group_id = $_POST["group_id"];
        /* 遷移先画面を設定 */
        $location = "mod.php";
        /* 更新完了メッセージをセット */
        $err_msg = $msgarr['28020'][SCREEN_MSG];
        $log_msg = $msgarr['28020'][LOG_MSG];
    }

    /* データベースを編集 */
    if (!isset($_POST["search_id"]) || !is_array($_POST["search_id"])) {
        $_POST["search_id"] = array();
    }
    $ret = modify_search_host($_POST["search_id"], $group_id);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    }

    /* 次の画面に遷移 */
    result_log(OPERATION . ":OK:" . $log_msg);
    $sesskey = $_POST["sk"];
    $postval = array("group_id" => $group_id);
    post_location($location, $err_msg, $postval);
    exit(0);
/* 初期表示のとき */
} else {
    /* リストとhiddenタグをセット */
    $ret = SetTag_for_FirstTime($tag);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    }
}

/***********************************************************
 * 表示処理
 **********************************************************/

/* タグ 設定 */
$javascript = <<<HERE
function sysSubmit(url) {
    document.form_main.action=url;
    document.form_main.submit();
}
HERE;

$err_msg = escape_html($err_msg);

set_tag_common($tag, $javascript);
$tag["<<GROUP_ID>>"] = isset($_POST["group_id"]) ? $_POST["group_id"] : "";
/* 登録・編集で異なる部分を設定する */
if (isset($_POST["fromADD"])) {

    /* 登録の場合 */
    $tag["<<NEW_HOSTNAME>>"] = $_POST["fromADD"]; // 登録ホスト名,検索ログID
    $tag["<<BUTTON_NAME>>"]  = "add_btn"; // ボタン名を「登録」にする
    $tag["<<CANCEL>>"]       = "add.php"; // キャンセルボタンの遷移先
} else {
    /* 編集の場合 */
    $tag["<<NEW_HOSTNAME>>"] = ""; // 編集処理では使用しない
    $tag["<<BUTTON_NAME>>"]  = "mod_btn"; // ボタン名を「更新」にする
    $tag["<<CANCEL>>"]       = "mod.php"; // キャンセルボタンの遷移先
}

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

exit(0);
?>
