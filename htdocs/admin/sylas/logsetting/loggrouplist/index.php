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
 * 管理者用ロググループ一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.7 $
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

define("TMPLFILE",    "loggrouplist.tmpl");
define("OPERATION",   "Display loggroupsearch list");

define("SQL1", "select loggroup.group_id,loggroup.group_name,");
define("SQL2", "loginfo.log_id,loginfo.log_name,");
define("SQL3", "hosts.host_id,hosts.host_name");
define("SQL4", " from (");
define("SQL5", "(loggroup left join loginfo on loggroup.log_id = loginfo.log_id)");
define("SQL6", "left join search_hosts on search_hosts.group_id = loggroup.group_id)");
define("SQL7", "left join hosts on search_hosts.host_id = hosts.host_id;");
define("SELECT_SQL", SQL1.SQL2.SQL3.SQL4.SQL5.SQL6.SQL7);

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

    /* MySQLからロググループの情報を取得 */
    $result = MySQL_exec_query($conn, SELECT_SQL);
    if ($result === FALSE) {
        mysqli_close($conn);
        return FALSE;
    }

    /* MySQLに登録されたロググループの情報を配列に格納 */
    MySQL_get_data($result, $data);

    /* MySQLとの接続を閉じる */
    mysqli_close($conn);

    /* グループリストを整形する */
    $grouplist = array();
    make_grouplist($data, $grouplist);

    /* ループタグの生成 */
    $i = 0;
    foreach ($grouplist as $key => $group_info) {

       /* エスケープ */
       $loggroup_name   = escape_html($group_info["group_name"]);
       $log_name        = escape_html($group_info["log_name"]);
       $host_name       = escape_html($group_info["host_name"]);
       $group_id        = escape_html($key);

       /* ループタグに値を代入 */
       $looptag[$i]["<<LOGGROUPNAME>>"] = $loggroup_name;
       $looptag[$i]["<<LOG>>"]          = $log_name;
       $looptag[$i]["<<HOST>>"]         = $host_name;
       $looptag[$i]["<<GROUP_ID>>"]     = $group_id;

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
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/

/* 登録ボタンが押されたとき */
if (isset($_POST["add"])) {
    $sesskey = $_POST["sk"];
    /* ロググループ追加画面に遷移 */
    dgp_location("./add.php");
    exit(0);
}

/***********************************************************
 * 表示処理
 **********************************************************/

/* タグ 設定 */
$javascript = <<<HERE
function sysSubmit(url, group_id) {
    document.form_main.action=url;
    document.form_main.group_id.value=group_id;
    document.form_main.submit();
}
HERE;

$err_msg = escape_html($err_msg);
set_tag_common($tag, $javascript);

/* ループタグの作成 */
$ret = set_loop_tag($looptag);
if ($ret === FALSE) {
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
