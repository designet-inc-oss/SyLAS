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
 * 管理者用検索ログ一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.3 $
 * $Date: 2014/07/15 02:15:09 $
 **********************************************************/
include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("TMPLFILE",    "logsearchlist.tmpl");
define("OPERATION",   "Display logsearch list");

define("SELECT_SQL",  "SELECT * FROM loginfo;");
define("NON_TYPE",    "（無し）");

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
function set_loop_tag(&$looptag)
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

    /* MySQLからログ管理テーブルの情報を取得 */
    $result = MySQL_exec_query($conn, SELECT_SQL);
    if ($result === FALSE) {
        mysqli_close($conn);
        return FALSE;
    }

    /* MySQLに登録されたログ管理テーブルの情報を配列に格納 */
    MySQL_get_data($result, $data);

    /* MySQLとの接続を閉じる */
    mysqli_close($conn);

    /* ループタグの生成 */
    $i = 0;
    foreach ($data as $one_data) {

       /* エスケープ */
       /* 値が空で登録されている場合、（無し）を入れる */
       /* ログ名は必ず値が入っている */
       $log_name   = escape_html($one_data["log_name"]);

       if ($one_data["log_type"] === "") {
           $tmp        = NON_TYPE;
           $log_type   = escape_html($tmp);
       } else {
           $log_type   = escape_html($one_data["log_type"]);
       }

       /* ファシリティは必ず値が入っている。ALLだったら（全て）にする */
       $fac_name = $one_data["facility_name"] == ALL_FACILITY ? ALL_TYPE :
                                   escape_html($one_data["facility_name"]);

       if ($one_data["search_tab"] === "") {
           $tmp        = NON_TYPE;
           $search_tab = escape_html($tmp);
       } else {
           $search_tab = escape_html($one_data["search_tab"]);
       }

       if ($one_data["app_name"] === "") {
           $tmp        = NON_TYPE;
           $app_name   = escape_html($tmp);
       } else {
           $app_name   = escape_html($one_data["app_name"]);
       }

       /* log_idは必ず値が入っている */
       $log_id     = escape_html($one_data["log_id"]);

       /* ループタグに値を代入 */
       $looptag[$i]["<<LOGNAME>>"] = $log_name;
       $looptag[$i]["<<LOGTYPE>>"] = $log_type;
       $looptag[$i]["<<FACILITY>>"] = $fac_name;
       $looptag[$i]["<<SEARCH_TABLE>>"] = $search_tab;
       $looptag[$i]["<<APPLICATION>>"] = $app_name;
       $looptag[$i]["<<LOG_ID>>"] = $log_id;

       /* インクリメント */
       $i++;
    }

    return TRUE;
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

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/

/* 登録ボタンが押されたとき */
if (isset($_POST["add"])) {
    $sesskey = $_POST["sk"];
    /* 検索ログ登録画面に遷移 */
    dgp_location("./add.php");
    exit;
}

/***********************************************************
 * 表示処理
 **********************************************************/

/* タグ 設定 */
$javascript = <<<HERE
function sysSubmit(url, log_id) {
    document.form_main.action=url;
    document.form_main.log_id.value=log_id;
    document.form_main.submit();
}
HERE;

$err_msg = escape_html($err_msg);
set_tag_common($tag, $javascript);

/* ループタグの作成 */
$ret = set_loop_tag($looptag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
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
