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
 * 管理者用ロググループ追加画面
 *
 * $RCSfile: add.php,v $
 * $Revision: 1.7 $
 * $Date: 2014/08/27 02:19:55 $
 **********************************************************/

include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpostldapadmin");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("OPERATION",          "Adding loggroup list");
define("TMPLFILE",           "loggrouplist_add.tmpl");

define("GROUP_NAME_DISP",    "ロググループ");
define("GROUP_NAME_LOG",     "Log Group");
define("SELECT_LOGNAME_SQL", "SELECT log_id, log_name FROM loginfo;");
define("LOGGROUP_MAXLEN",     64);

/*********************************************************
 * set_tag_data()
 *
 * タグ情報セット関数
 *
 * [引数]
 *  	$post		入力された値
 *
 * [返り値]
 *	なし
 ********************************************************/
function set_tag_data($post, &$tag)
{
    /* JavaScript 設定 */
    $java_script = "";

    /* 基本タグ 設定 */
    set_tag_common($tag, $java_script);

    /* タグに値を設定 */
    if (isset($post["group_name"]) === FALSE) {
        return;
    } else {
        $tag["<<LOGGROUP_NAME>>"]      = escape_html($post["group_name"]);
    }

    return;
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
if (isset($_POST["add"])) {

    /* 入力値チェック */
    $group_name = $_POST["group_name"];
    $log_id     = $_POST["log_name"];
    if ($group_name != "") {
        
        $ret = check_groupname($conn, $group_name, LOGGROUP_MAXLEN);
        if ($ret === 0) {
            mysqli_close($conn);
            
            /* 成功メッセージを代入 */
            $group_name = escape_html($group_name);
            $err_msg = sprintf($msgarr['28027'][SCREEN_MSG], $group_name);
            /* ホスト追加画面へ */
            $sesskey = $_POST["sk"];
            $postval = array("fromADD" => "$group_name,$log_id");
            post_location("./host.php", $err_msg, $postval);
            exit(0);

        /* 入力値チェック中にDBエラーが起きたとき */
        } else if ($ret === 2) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);

        /* 入力値エラーの場合 */
        } else {
            /* ログ出力 */
            result_log(OPERATION . ":NG:" . $log_msg);
        }

    /* ホスト名が入力されていない時 */
    } else {
        /* エラーメッセージをセット */
        $err_msg = sprintf($msgarr['28001'][SCREEN_MSG], GROUP_NAME_DISP);
        $log_msg = sprintf($msgarr['28001'][LOG_MSG], GROUP_NAME_LOG);
        result_log(OPERATION . ":NG:" . $log_msg);
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
if (isset($_POST["log_name"]) === FALSE) {
    $post["log_name"] = "";
} else {
    $post = $_POST;
}

/* タグ情報 セット */
set_tag_data($post, $tag);

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

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}
?>
