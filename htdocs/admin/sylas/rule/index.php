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
 * ルール一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.1 $
 * $Date: 2014/07/07 08:10:53 $
 **********************************************************/
include_once("../initial");
include_once("lib/dglibldap");
include_once("lib/dglibpostldapadmin");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * 各ページ毎の設定
 ********************************************************/

define("TMPLFILE",  "rule.tmpl");
define("OPERATION", "Rule list");

/*********************************************************
 * set_loop_tag
 *
 * ループタグを作成する
 *
 * [引数]
 *       $looptag            ループタグ
 *       $tag                タグ
 *       $facility_arr       ファシリティの配列
 *       $degree_arr         重要度の配列
 *
 * [返り値]
 *       0               正常
 *       1               画面遷移しないエラーがある
 *       2               画面遷移するエラー
 **********************************************************/
function set_loop_tag(&$looptag, $tag, $facility_arr, $degree_arr)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
    $flag = 0;

    /* 設定ファイルを取得する */
    $dir = $web_conf["sylas"]["rsyslogconfdir"];
    $ret =  get_rsys_file($dir, $arrayfile);
    if ($ret === FALSE) {
        $err_msg = sprintf($msgarr['28038'][SCREEN_MSG], $dir);
        $log_msg = sprintf($msgarr['28038'][LOG_MSG], $dir);
        return 2;
    }
    /* .confの中身を連想配列に入れる */
    $confarray = get_array_file($arrayfile, $dir, $flag);
    /* ループタグの生成 */
    $i = 0;
    foreach ($confarray as $conf_data) {
        /* エスケープ処理 */
        /* 値が空の場合「すべて」を入れる */
        /* 送信元IPアドレス */
        if (isset($conf_data[IP_SET])) {
            $from_ip = escape_html($conf_data[IP_SET]);
        } else {
            $from_ip = ALL_TYPE; 
        }
        /* ファシリティ */
        if (isset($conf_data[FACILITY_SET])) {
            /* 数字を変換する */
            $facility = array_search($conf_data[FACILITY_SET], $facility_arr);
        } else {
            $facility = ALL_TYPE; 
        }
        /* 重要度 */
        if (isset($conf_data[DEGREE_SET])) {
            /* 数字を変換する */
            $degree = array_search($conf_data[DEGREE_SET], $degree_arr);
        } else {
            $degree = ALL_TYPE; 
        }
        /* キーワードは必ず入っている */
        $msg = cut_keyword($conf_data[KEYWORD_SET]);
        $msg = str_replace("\\'", "'", $msg);
        $msg = str_replace("\\\\", "\\", $msg);
        $keyword = escape_html($msg);

        $looptag[$i]["<<IPADDRES>>"] = $from_ip;
        $looptag[$i]["<<FACILITY>>"] = $facility;
        $looptag[$i]["<<DEGREE>>"]   = $degree;
        $looptag[$i]["<<KEYWORD>>"]  = $keyword;
        $looptag[$i]["<<FILENUM>>"]  = $conf_data["file"];

        $i ++;
    }
    if ($flag == 1) {
        return 1;
    }
    return 0;
}

/*********************************************************
 * delete_file 
 *
 * ファイルを削除する
 *
 * [引数]
 *       $deletefile     削除するファイル
 *
 * [返り値]
 *       TRUE               正常
 *       FALSE              異常
 **********************************************************/
function delete_file($deletefile)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* ファイルが存在するか確認 */
    if (file_exists($deletefile) === FALSE) {
        $err_msg = sprintf($msgarr['28043'][SCREEN_MSG], $deletefile);
        $log_msg = sprintf($msgarr['28043'][LOG_MSG], $deletefile);
        return FALSE;
    }
    /* ファイルを消す */
    $ret = unlink("$deletefile");
    if ($ret === FALSE) {
        $err_msg = sprintf($msgarr['28040'][SCREEN_MSG], $deletefile);
        $log_msg = sprintf($msgarr['28040'][LOG_MSG], $deletefile);
        return FALSE;
    }
    /* rsyslog再起動 */
    $cmd = $web_conf["sylas"]["rsyslogrestartcmd"];
    $output = "";
    exec($cmd, $output, $ret);
    /* 終了コードが0でなければ再起動失敗 */
    if ($ret != 0) {
        $err_msg = sprintf($msgarr['28035'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28035'][LOG_MSG]);
        return FALSE;
    }
    $err_msg = sprintf($msgarr['28041'][SCREEN_MSG], $deletefile);
    $log_msg = sprintf($msgarr['28041'][LOG_MSG], $deletefile);
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
$tag["<<MENU>>"]       = "";
$tag["<<IPADDRESS>>"]  = "";
$tag["<<FACILITY>>"]   = "";
$tag["<<DEGREE>>"]     = "";
$tag["<<KEYWORD>>"]    = "";
$tag["<<FILENUM>>"]    = "";

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/
/* 編集ボタン押されたら*/
if (isset($_POST["modify"])) {
    /* ルールが存在するか見る */
    if (empty($_POST["radio"])) {
        $err_msg = sprintf($msgarr['28039'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28039'][LOG_MSG]);
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* ファイルが存在するか見る */
        $modfile = $web_conf["sylas"]["rsyslogconfdir"] . $_POST["radio"];
        if (!file_exists($modfile)) {
            $err_msg = sprintf($msgarr['28043'][SCREEN_MSG], $modfile);
            $log_msg = sprintf($msgarr['28043'][LOG_MSG], $modfile);
            result_log(OPERATION . ":NG:" . $log_msg);
        } else {
            /* 選択されたファイルを渡す */
            $hidden_data["radio"] = $_POST["radio"];
            dgp_location_hidden("modify.php", $hidden_data);
            exit (0);
        }
    }
}

/* 削除ボタンが押されたら */
if (isset($_POST["delete"])) {
    /* ルールが存在するか見る */
    if (empty($_POST["radio"])) {
        $err_msg = sprintf($msgarr['28039'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28039'][LOG_MSG]);
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* 選択されたファイルを消す */
        $deletefile = $web_conf["sylas"]["rsyslogconfdir"] . $_POST["radio"];
        $ret = delete_file($deletefile);
        if ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $log_msg);
        } else {
            result_log(OPERATION . ":OK:" . $log_msg);
        }
    }
}

/***********************************************************
 * 表示処理
 **********************************************************/

/* タグ 設定 */
set_tag_common($tag);

$ret = set_loop_tag($looptag, $tag, $facility_arr, $degree_arr);
switch ($ret) {
case 1:
    /* 画面遷移しないエラー */
    $tag["<<MESSAGE>>"] = $err_msg;
    result_log(OPERATION . ":NG:" . $log_msg);
    break;
case 2:
    /* 画面遷移するエラー */
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
case 0:
    /* 正常 */
    break;
}

/* ページの出力 */
$ret = display(TMPLFILE, $tag, $looptag, "<<STARTLOOP>>", "<<ENDLOOP>>");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

exit(0);
?>
