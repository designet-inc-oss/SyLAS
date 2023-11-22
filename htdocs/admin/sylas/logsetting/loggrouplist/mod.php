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
 * 管理者用ロググループ編集画面
 *
 * $RCSfile: mod.php,v $
 * $Revision: 1.9 $
 * $Date: 2014/07/16 04:42:11 $
 **********************************************************/

include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("OPERATION",          "Modfying loggroup list");
define("TMPLFILE",           "loggrouplist_mod.tmpl");

define("GROUP_NAME_DISP",    "ロググループ");
define("GROUP_NAME_LOG",     "Log Group");
define("SELECT_SQL",         "SELECT * FROM loggroup WHERE group_id=%s;");
define("SELECT_LOGNAME_SQL", "SELECT log_id, log_name FROM loginfo;");
define("SELECT_HOST_SQL",    "SELECT * FROM search_hosts LEFT JOIN hosts " .
                             "ON search_hosts.host_id=hosts.host_id " . 
                             "WHERE group_id=%s;");
define("LOGGROUP_MAXLEN",     64);
define("UPDATE_GROUP_SQL",   "UPDATE loggroup SET log_id=\"%s\" ");
define("SQL_CONDITION",      "WHERE group_id=%s;");
define("DELETE_GROUP_SQL",   "DELETE FROM loggroup WHERE group_id=%s;");
define("DELETE_SERCHHOST_SQL", "DELETE FROM search_hosts WHERE group_id=%s;");
define("UPDATE", 1);
define("NO_HOST", "無し");


/*********************************************************
 * set_tag_data()
 *
 * タグ情報セット関数
 *
 * [引数]
 *  	$post		入力された値
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 ********************************************************/
function set_tag_data(&$post, &$tag)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* JavaScript 設定 */
    $java_script = "";

    /* 基本タグ 設定 */
    set_tag_common($tag, $java_script);

    /* タグに値を設定 */
    if (isset($post["group_name"]) === FALSE) {
        /* MySQL接続 */
        $conn = MySQL_connect_server();
        if ($conn === FALSE) {
            return FALSE;
        }

        /* MySQLからロググループ情報を取得 */
        $select_sql = sprintf(SELECT_SQL, $_POST["group_id"]);
        $result = MySQL_exec_query($conn, $select_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return FALSE;
        }

        /* MySQLに登録されたロググループ情報を配列に格納 */
        MySQL_get_data($result, $data);

        /* MySQLとの接続を閉じる */
        mysqli_close($conn);

        $tag["<<LOGGROUP_NAME>>"] = escape_html($data[0]["group_name"]);
        $tag["<<LOGGROUP_ID>>"]   = escape_html($_POST["group_id"]);
        $post["log_name"]         = $data[0]["log_id"];

    } else {
        $tag["<<LOGGROUP_NAME>>"] = escape_html($post["group_name"]);
        $tag["<<LOGGROUP_ID>>"]   = escape_html($post["group_id"]);
    }

    return TRUE;
}

/*********************************************************
 * make_select_option()
 *
 * セレクトボックス作成関数
 *
 * [引数]
 *  	$values         オプションに使用する値の配列
 *  	$post           入力された値
 *
 * [返り値]
 *	なし
 ********************************************************/
function make_select_option($values, $post = "", &$option)
{
    /* valueの配列をループ */
    foreach ($values as $one_val) {
        $log_name = escape_html($one_val["log_name"]);
        $log_id   = escape_html($one_val["log_id"]);
        if ($one_val["log_id"] === $post) {
            $option .= <<<HERE
<option value="$log_id" selected>$log_name</option>
HERE;
        } else {
            $option .= <<<HERE
<option value="$log_id">$log_name</option>
HERE;
        }
    }

    return;
}

/*********************************************************
 * get_hosts()
 *
 * MySQLからホスト名を取得し、カンマでつないだ形に変える
 *
 * [引数]
 *  	$post		入力された値
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 ********************************************************/
function get_hosts($post, &$hosts)
{
    /* MySQLからホスト名を取得 */
    $sql = sprintf(SELECT_HOST_SQL, $_POST["group_id"]);
    $ret = get_data($sql, $data);
    if ($ret === FALSE) {
        return FALSE;
    }

    /* 取得したホスト名を","でつないだ文字列に変換 */
    $hosts = "";

    /* 検索対象ホストがないとき */
    if (count($data) === 0) {
        $hosts = NO_HOST;
        return TRUE;
    }

    foreach ($data as $line) {
        if ($hosts === "") {
            $hosts = $line["host_name"];
        } else {
            $hosts .= "," . $line["host_name"];
        }
    }

    return TRUE;
}

/***********************************************************
 * 初期処理
 **********************************************************/

/* タグ初期化 */
$tag["<<TITLE>>"]         = "";
$tag["<<JAVASCRIPT>>"]    = "";
$tag["<<SK>>"]            = "";
$tag["<<TOPIC>>"]         = "";
$tag["<<MESSAGE>>"]       = "";
$tag["<<TAB>>"]           = "";
$tag["<<LOGGROUP_NAME>>"] = "";
$tag["<<OPTION>>"]        = "";
$tag["<<HOSTNAME>>"]      = "";

/* 設定ファイルタブ管理ファイル読込、セッションのチェック */
$ret = init();
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/


/* 処理の分岐 */
if (isset($_POST["mod"])) {

    /* 入力値チェック */
    $group_name = $_POST["group_name"];
    $group_id   = $_POST["group_id"];
    $log_id     = $_POST["log_name"];
        
    $ret = check_groupname($conn, $group_name, LOGGROUP_MAXLEN, UPDATE);
    if ($ret === 0) {

        /* 存在チェック */
        $group_check_sql = sprintf(SELECT_SQL, $group_id);

        /* MySQLからログ管理テーブルの情報を取得 */
        $result = MySQL_exec_query($conn, $group_check_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }

        /* MySQLに登録されたログ管理テーブルの情報を配列に格納 */
        MySQL_get_data($result, $data);

        if (count($data) > 0) {
            /* MySQLにロググループを登録 */
            $sql_condition = sprintf(SQL_CONDITION, $group_id);
            $ret = add_mod_loggroup($conn, UPDATE_GROUP_SQL . $sql_condition, 
                                "", $log_id);
            if ($ret === FALSE) {
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* 成功メッセージを代入 */
            $err_msg = sprintf($msgarr['28008'][SCREEN_MSG],
                               escape_html($group_name));
            $log_msg = sprintf($msgarr['28008'][LOG_MSG], $group_name);
            result_log(OPERATION . ":OK:" . $log_msg);

        } else {
            /* 既に削除されている場合 */
            mysqli_close($conn);
            $err_msg = sprintf($msgarr['28023'][SCREEN_MSG],
                               escape_html($group_name));
            $log_msg = sprintf($msgarr['28023'][LOG_MSG], $group_name);
            result_log(OPERATION . ":NG:" . $log_msg);
        }

        /* ロググループ一覧画面へ */
        dgp_location("./index.php", $err_msg);
        exit(0);

    /* 入力値チェック中にDBエラーが起きたとき */
    } else if ($ret === 2) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);

    /* 入力値エラーの場合 */
    } else {
        result_log(OPERATION . ":NG:" . $log_msg);
    }

/* 削除ボタンが押されたとき */
} elseif(isset($_POST["delete"])) {

    /* SQLを作成 */
    $delete_sql["search"] = sprintf(DELETE_SERCHHOST_SQL, $_POST["group_id"]);
    $delete_sql["group"] = sprintf(DELETE_GROUP_SQL, $_POST["group_id"]);

    /* ホストを削除 */
    $ret = delete_a_data($delete_sql);
    /* DBエラーがあった場合 */
    if ($ret === FALSE) {
        syserr_display();
        exit(1);

    /* 削除に成功した場合 */
    } else {
        /* 成功メッセージを代入 */
        $err_msg = $msgarr['28009'][SCREEN_MSG];
        $log_msg = $msgarr['28009'][LOG_MSG];

        /* ロググループ一覧画面へ */
        result_log(OPERATION . ":OK:" . $log_msg);
        dgp_location("./index.php", $err_msg);
        exit(0);
    }

/* キャンセルボタンが押されたとき */
} elseif(isset($_POST["cancel"])) {

    /* ロググループ一覧画面へ */
    dgp_location("./index.php", $err_msg);
    exit(0);
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 初期化 */
$post = array();
if (isset($_POST["log_name"]) === TRUE) {
    $post = $_POST;
}

/* タグ情報 セット */
$ret = set_tag_data($post, $tag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* セレクトボックスに使用する値を配列に格納 */
$data = array();
$ret = get_data(SELECT_LOGNAME_SQL, $data);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* セレクトボックス作成 */
$option = "";
make_select_option($data, $post["log_name"], $option);
$tag["<<OPTION>>"] = $option;

/* ホスト名を取得する */
$ret = get_hosts($post, $hosts);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}
$tag["<<HOSTNAME>>"] = escape_html($hosts);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}
?>
