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
 * 管理者用ホスト一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.8 $
 * $Date: 2014/07/17 07:07:10 $
 **********************************************************/
include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("TMPLFILE",        "hostlist.tmpl");
define("OPERATION",       "Search and change hostlist");

define("SELECT_SQL",      "SELECT * FROM hosts;");
define("CHECK_HOST_NAME_SQL", "SELECT * FROM hosts WHERE host_name=\"%s\"");
define("INSERT_SQL",      "INSERT INTO hosts (host_name) values (\"%s\");");
define("HOSTNAME_MAXLEN", 64);
define("HOSTNAME_DISP",   "ホスト名");
define("HOSTNAME_LOG",    "HostName");

/*********************************************************
 * make_select_option()
 *
 * セレクトボックス作成関数
 *
 * [引数]
 *      $values         オプションに使用する値の配列
 *      $post           入力された値
 *
 * [返り値]
 *      なし
 ********************************************************/
function make_select_option($values, $post = "", &$option)
{

    /* valueの配列をループ */
    foreach ($values as $one_val) {
        $host_id   = escape_html($one_val["host_id"]);
        $host_name = escape_html($one_val["host_name"]);

        /* 全てのホストは表示しない */
        if ($host_id === "1") {
            continue;
        }

        if ($one_val["host_name"] === $post) {
            $option .= <<<HERE
<option value="$host_id" selected>$host_name</option>
HERE;
        } else {
            $option .= <<<HERE
<option value="$host_id" >$host_name</option>
HERE;
        }
    }

    return;
}

/***********************************************************
 * 初期処理
 **********************************************************/

/* タグ初期化 */
$tag["<<TITLE>>"]      = "";
$tag["<<JAVASCRIPT>>"] = "";
$tag["<<SK>>"]         = "";
$tag["<<TOPIC>>"]      = "";
$tag["<<MESSAGE>>"]    = "";
$tag["<<TAB>>"]        = "";
$tag["<<HOSTNAME>>"]   = "";
$tag["<<OPTION>>"]     = "";

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

/* 登録ボタンが押されたとき */
if (isset($_POST["add"])) {

    /* 入力値チェック */
    $host_name = $_POST["host_name"];
    if ($host_name != "") {
        $ret = check_alpha_bars_dot($host_name, HOSTNAME_MAXLEN);
        if ($ret === 0) {

            /* MySQL接続 */
            $conn = MySQL_connect_server();
            if ($conn === FALSE) {
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* 重複チェック */
            $check_sql = sprintf(CHECK_HOST_NAME_SQL,
                                 mysqli_real_escape_string($conn,$host_name));

            $result = MySQL_exec_query($conn, $check_sql);
            if ($result === FALSE) {
                mysqli_close($conn);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* MySQLに登録されたログ管理テーブルの情報を配列に格納 */
            MySQL_get_data($result, $data);

            /* 重複があった場合(ログ名で限定した情報が1つ以上取得できた場合 */
            if(count($data) != 0) {
                mysqli_close($conn);
                $err_msg = sprintf($msgarr['28021'][SCREEN_MSG],
                                   escape_html($host_name));
                $log_msg = sprintf($msgarr['28021'][LOG_MSG], $host_name);
                result_log(OPERATION . ":NG:" . $log_msg);

            } else {

                /* SQLを作成する */
                $insert_sql = sprintf(INSERT_SQL, 
                                    mysqli_real_escape_string($conn, $host_name));

                /* SQLを実行する */
                $result = MySQL_exec_query($conn, $insert_sql);

                /* MySQLとの接続を閉じる */
                mysqli_close($conn);

                if ($result === FALSE) {
                    result_log(OPERATION . ":NG:" . $log_msg);
                    syserr_display();
                    exit(1);
                }

                /* 成功メッセージを代入 */
                $err_msg = sprintf($msgarr['28005'][SCREEN_MSG],
                                   escape_html($host_name));
                $log_msg = sprintf($msgarr['28005'][LOG_MSG], $host_name);

                result_log(OPERATION . ":OK:" . $log_msg);
                dgp_location("./index.php", $err_msg);
                exit(0);
            }

        /* 入力エラーの時 */
        } else {
            /* エラーメッセージをセット */
            $err_msg = sprintf($msgarr['28002'][SCREEN_MSG], HOSTNAME_DISP);
            $log_msg = sprintf($msgarr['28002'][LOG_MSG], HOSTNAME_LOG);
            result_log(OPERATION . ":NG:" . $log_msg);
        }

    /* ホスト名が入力されていない時 */
    } else {
        /* エラーメッセージをセット */
        $err_msg = sprintf($msgarr['28001'][SCREEN_MSG], HOSTNAME_DISP);
        $log_msg = sprintf($msgarr['28001'][LOG_MSG], HOSTNAME_LOG);
        result_log(OPERATION . ":NG:" . $log_msg);
    }

} else if (isset($_POST["del"])) {

    if (isset($_POST ["host_dellist"]) === TRUE) {
        /* ホスト名削除 */
        $host_dellist = $_POST["host_dellist"];
        $ret = delete_hostlist($host_dellist);
        if ($ret === 1) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);

        /* すべてのホストが削除できた場合 */
        } else if ($ret === 0) {
            /* 成功メッセージを代入 */
            $err_msg = $msgarr['28007'][SCREEN_MSG];
            $log_msg = $msgarr['28007'][LOG_MSG];

            result_log(OPERATION . ":OK:" . $log_msg);
            dgp_location("./index.php", $err_msg);
            exit(0);
        }

    /* ホスト名が選択されていないとき */
    } else {
        /* エラーメッセージをセット */
        $err_msg = $msgarr['28011'][SCREEN_MSG];
    }
}

/***********************************************************
 * 表示処理
 **********************************************************/

/* タグ 設定 */
$javascript = "";
set_tag_common($tag, $javascript);

/* 値保持 */
if (isset($_POST["host_name"])) {
    $tag["<<HOSTNAME>>"] =escape_html($_POST["host_name"]); 
}

/* ホストをMySQLから取得 */
$ret = get_data(SELECT_SQL, $data);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* セレクトボックスのオプション作成 */
$option = "";
make_select_option($data, "", $option);
$tag["<<OPTION>>"] = $option;

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

exit(0);
?>
