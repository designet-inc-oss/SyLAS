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
 * �������ѥۥ��Ȱ�������
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
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",        "hostlist.tmpl");
define("OPERATION",       "Search and change hostlist");

define("SELECT_SQL",      "SELECT * FROM hosts;");
define("CHECK_HOST_NAME_SQL", "SELECT * FROM hosts WHERE host_name=\"%s\"");
define("INSERT_SQL",      "INSERT INTO hosts (host_name) values (\"%s\");");
define("HOSTNAME_MAXLEN", 64);
define("HOSTNAME_DISP",   "�ۥ���̾");
define("HOSTNAME_LOG",    "HostName");

/*********************************************************
 * make_select_option()
 *
 * ���쥯�ȥܥå��������ؿ�
 *
 * [����]
 *      $values         ���ץ����˻��Ѥ����ͤ�����
 *      $post           ���Ϥ��줿��
 *
 * [�֤���]
 *      �ʤ�
 ********************************************************/
function make_select_option($values, $post = "", &$option)
{

    /* value�������롼�� */
    foreach ($values as $one_val) {
        $host_id   = escape_html($one_val["host_id"]);
        $host_name = escape_html($one_val["host_name"]);

        /* ���ƤΥۥ��Ȥ�ɽ�����ʤ� */
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
 * �������
 **********************************************************/

/* ��������� */
$tag["<<TITLE>>"]      = "";
$tag["<<JAVASCRIPT>>"] = "";
$tag["<<SK>>"]         = "";
$tag["<<TOPIC>>"]      = "";
$tag["<<MESSAGE>>"]    = "";
$tag["<<TAB>>"]        = "";
$tag["<<HOSTNAME>>"]   = "";
$tag["<<OPTION>>"]     = "";

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/

/* ��Ͽ�ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["add"])) {

    /* �����ͥ����å� */
    $host_name = $_POST["host_name"];
    if ($host_name != "") {
        $ret = check_alpha_bars_dot($host_name, HOSTNAME_MAXLEN);
        if ($ret === 0) {

            /* MySQL��³ */
            $conn = MySQL_connect_server();
            if ($conn === FALSE) {
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* ��ʣ�����å� */
            $check_sql = sprintf(CHECK_HOST_NAME_SQL,
                                 mysqli_real_escape_string($conn,$host_name));

            $result = MySQL_exec_query($conn, $check_sql);
            if ($result === FALSE) {
                mysqli_close($conn);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ */
            MySQL_get_data($result, $data);

            /* ��ʣ�����ä����(��̾�Ǹ��ꤷ������1�İʾ�����Ǥ������ */
            if(count($data) != 0) {
                mysqli_close($conn);
                $err_msg = sprintf($msgarr['28021'][SCREEN_MSG],
                                   escape_html($host_name));
                $log_msg = sprintf($msgarr['28021'][LOG_MSG], $host_name);
                result_log(OPERATION . ":NG:" . $log_msg);

            } else {

                /* SQL��������� */
                $insert_sql = sprintf(INSERT_SQL, 
                                    mysqli_real_escape_string($conn, $host_name));

                /* SQL��¹Ԥ��� */
                $result = MySQL_exec_query($conn, $insert_sql);

                /* MySQL�Ȥ���³���Ĥ��� */
                mysqli_close($conn);

                if ($result === FALSE) {
                    result_log(OPERATION . ":NG:" . $log_msg);
                    syserr_display();
                    exit(1);
                }

                /* ������å����������� */
                $err_msg = sprintf($msgarr['28005'][SCREEN_MSG],
                                   escape_html($host_name));
                $log_msg = sprintf($msgarr['28005'][LOG_MSG], $host_name);

                result_log(OPERATION . ":OK:" . $log_msg);
                dgp_location("./index.php", $err_msg);
                exit(0);
            }

        /* ���ϥ��顼�λ� */
        } else {
            /* ���顼��å������򥻥å� */
            $err_msg = sprintf($msgarr['28002'][SCREEN_MSG], HOSTNAME_DISP);
            $log_msg = sprintf($msgarr['28002'][LOG_MSG], HOSTNAME_LOG);
            result_log(OPERATION . ":NG:" . $log_msg);
        }

    /* �ۥ���̾�����Ϥ���Ƥ��ʤ��� */
    } else {
        /* ���顼��å������򥻥å� */
        $err_msg = sprintf($msgarr['28001'][SCREEN_MSG], HOSTNAME_DISP);
        $log_msg = sprintf($msgarr['28001'][LOG_MSG], HOSTNAME_LOG);
        result_log(OPERATION . ":NG:" . $log_msg);
    }

} else if (isset($_POST["del"])) {

    if (isset($_POST ["host_dellist"]) === TRUE) {
        /* �ۥ���̾��� */
        $host_dellist = $_POST["host_dellist"];
        $ret = delete_hostlist($host_dellist);
        if ($ret === 1) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);

        /* ���٤ƤΥۥ��Ȥ�����Ǥ������ */
        } else if ($ret === 0) {
            /* ������å����������� */
            $err_msg = $msgarr['28007'][SCREEN_MSG];
            $log_msg = $msgarr['28007'][LOG_MSG];

            result_log(OPERATION . ":OK:" . $log_msg);
            dgp_location("./index.php", $err_msg);
            exit(0);
        }

    /* �ۥ���̾�����򤵤�Ƥ��ʤ��Ȥ� */
    } else {
        /* ���顼��å������򥻥å� */
        $err_msg = $msgarr['28011'][SCREEN_MSG];
    }
}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���� ���� */
$javascript = "";
set_tag_common($tag, $javascript);

/* ���ݻ� */
if (isset($_POST["host_name"])) {
    $tag["<<HOSTNAME>>"] =escape_html($_POST["host_name"]); 
}

/* �ۥ��Ȥ�MySQL������� */
$ret = get_data(SELECT_SQL, $data);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* ���쥯�ȥܥå����Υ��ץ������� */
$option = "";
make_select_option($data, "", $option);
$tag["<<OPTION>>"] = $option;

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

exit(0);
?>
