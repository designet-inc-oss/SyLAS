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
 * ルール追加画面
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

define("TMPLFILE",  "rule_add.tmpl");
define("OPERATION",  "Rule add");

/*********************************************************
 * next_file()
 *
 * 書き込むrsyslogファイルを割り当てる
 *
 * [引数]
 *      $dir           rsyslogの設定ファイルがあるディレクトリ
 *      $nextnum       ファイルの番号
 *
 * [返り値]
 *      $nextfile      書き込むファイル
 *      FALSE          ディレクトリオープン失敗 
 ********************************************************/

function next_file($dir, &$nextnum)
{
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* ディレクトリハンドルを取得する */
    $rsysdir = opendir($dir);
    if ($rsysdir === FALSE) {
        $err_msg = sprintf($msgarr['28038'][SCREEN_MSG], $dir);
        $log_msg = sprintf($msgarr['28038'][LOG_MSG], $dir);
        return FALSE;
    }
    $rsysarray = array();
    /* readdirでファイル名を取得する */
    while (false !== ($rsysfile = readdir($rsysdir))) {
        /* .confファイルのみ残す */
        $ret = preg_match("/^[0-9]*.conf$/", $rsysfile, $confarray);
        if ($ret === FALSE) {
            $err_msg = sprintf($msgarr['28038'][SCREEN_MSG], $dir);
            $log_msg = sprintf($msgarr['28038'][LOG_MSG], $dir);
            return FALSE;
        }
        /* 配列に入れ直す */
        foreach($confarray as $rsysfile) {
            $rsysfile = substr($rsysfile, 0, -5);
            array_push($rsysarray, $rsysfile);
        }
    }
    closedir($rsysdir);
    sort($rsysarray);
    $nextnum = end($rsysarray) + 1;
    $nextfile = $dir . "$nextnum.conf";
    return $nextfile;
}

/*********************************************************
 * rule_add()
 *
 * ルールの追加処理
 *
 * [引数]
 *      $post          入力された値
 *      $lockfile      ロックファイルのパス
 *
 * [返り値]
 *      0              成功
 *      1              画面遷移しない失敗 
 *      2              画面遷移する失敗
 ********************************************************/

function rule_add($post, $lockfile)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
    /* ロックファイル作成 */ 
    /* ロックファイルがあるか見る */
    while (file_exists("$lockfile") === TRUE) {
        /* ロックファイルあるならなくなるまで待つ */
        /* 1秒待つ */
        sleep(1);
    }
    /* ロックファイル作る */
    $mkfile = touch("$lockfile");
    if ($mkfile === FALSE) {
        /* ロックファイル作成に失敗 */
        /* エラー処理 */
        $err_msg = sprintf($msgarr['28032'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28032'][LOG_MSG]);
        return 2;
    }
    /* ロックファイル作成成功 */
    /* 処理開始 */
    /* 書き込むrsyslogファイルの番号を割り当てる */
    $nextfile = next_file($web_conf["sylas"]["rsyslogconfdir"], $nextnum);
    /* ディレクトリの権限がない */
    if ($nextfile == FALSE) {
        $delfile = unlink("$lockfile");
        return 2;
    }
    /* ファイルを作成する */
    /* 既にファイルがある場合はエラー */
    if (file_exists($nextfile)) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28031'][SCREEN_MSG], $nextfile);
        $log_msg = sprintf($msgarr['28031'][LOG_MSG], $nextfile);
        return 2;
    }
    /* rsyslogファイルをopenする */
    $fh = fopen($nextfile, "w");
    if ($fh === FALSE) {
    /* エラー処理 */
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28033'][SCREEN_MSG], $nextfile);
        $log_msg = sprintf($msgarr['28033'][LOG_MSG], $nextfile);
        return 2;
    }
    /* 権限を与える */
    chmod ($nextfile, 0775);
    /* rsyslogファイルに書き込む内容を作成する */
    $contents = in_contents($_POST, $nextnum);
    /* rsyslogファイルに書き込む */
    $ret = fwrite($fh, $contents);
    /* 書き込みエラー処理 */
    if ($ret === FALSE) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28034'][SCREEN_MSG], $nextfile);
        $log_msg = sprintf($msgarr['28034'][LOG_MSG], $nextfile);
        return 1;
    }
    /* 書き込み成功したらrsyslog再起動 */
    $cmd = $web_conf["sylas"]["rsyslogrestartcmd"];
    $output = "";
    exec($cmd, $output, $ret);
    /* 終了コードが0でなければ再起動失敗 */
    if ($ret != 0) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28035'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28035'][LOG_MSG]);
        return 1;
    }
    /* ロックファイル消す */
    $delfile = unlink("$lockfile");
    if ($delfile === FALSE){
        /* ロックファイル削除に失敗 */
        /* エラー処理 */
        $err_msg = sprintf($msgarr['28036'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28036'][LOG_MSG]);
        return 2;
    }
    /* 一覧画面へ飛ぶ */
    $log_msg = sprintf($msgarr['28037'][LOG_MSG], $nextfile);
    return 0;
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
$tag["<<KEYWORD>>"]    = "";
$tag["<<MAILTO>>"]     = "";
$tag["<<SUBJECT>>"]    = "";
$tag["<<FACILITY_OPTION>>"]    = "";
$tag["<<BODY>>"]       = "";

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/
/* 追加が押されたら */
if (isset($_POST["add"])){
    $lockfile = $web_conf["sylas"]["rsyslogconfdir"] . LOCK;
    /* 入力値チェック */
    $ret = check_rule($_POST);
    /*入力値エラーの場合*/
    if ($ret  === FALSE) {
        /* エラーメッセージを表示 */
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* 入力値が正しい場合 */
        /* 登録処理 */
        $ret = rule_add($_POST, $lockfile);
        switch ($ret) {
        case 1:
            /* 画面遷移しないエラー */
            result_log(OPERATION . ":NG:" . $log_msg);
            break;
        case 2:
            /* 画面遷移するエラー */
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit (1);
        case 0:
            /* 成功 */
            /* 一覧画面へ飛ぶ */
            result_log(OPERATION . ":OK:" . $log_msg);
            dgp_location("index.php");
            exit (0);
        }
    }
}
/* キャンセルボタンが押されたら */
if (isset($_POST["cancel"])) {
    dgp_location("index.php");
    exit;
}
/***********************************************************
 * 表示処理
 **********************************************************/

/* 初期化 */
$post = array();
if (isset($_POST["facility"]) === FALSE) {
    $post["facility"] = "すべて";
    $post["degree"] = "すべて";
} else {
    $post = $_POST;
    $tag["<<IPADDRESS>>"] = escape_html($post["ipaddress"]);
    $tag["<<KEYWORD>>"]   = escape_html($post["keyword"]);
    $tag["<<MAILTO>>"]    = escape_html($post["mailto"]);
    $tag["<<SUBJECT>>"]   = escape_html($post["subject"]);
    $tag["<<BODY>>"]      = escape_html($post["body"]);
}

/* タグ 設定 */
set_tag_common($tag);

/* セレクトボックス作成 */
$facilityoption = "";
make_select($facility_arr, $facilityoption,  $post["facility"]);
$tag["<<FACILITY_OPTION>>"] = $facilityoption;

$degreeoption = "";
make_select($degree_arr, $degreeoption,  $post["degree"]);
$tag["<<DEGREE_OPTION>>"] = $degreeoption;

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

exit(0);
?>
