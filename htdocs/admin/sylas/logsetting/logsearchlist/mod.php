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
 * �������Ѹ������Խ�����
 *
 * $RCSfile: mod.php,v $
 * $Revision: 1.6 $
 * $Date: 2014/07/15 06:32:44 $
 **********************************************************/

include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",   "logsearchlist_mod.tmpl");
define("OPERATION",  "Modifying logsearch list");
define("SELECT_SQL", "SELECT * FROM loginfo where log_id=%s;");
define("UPDATE_SQL", "UPDATE loginfo SET facility_name=\"%s\"," .
                     "search_tab=\"%s\",log_type=\"%s\",app_name=\"%s\" " .
                     "WHERE log_id=%s;");
define("DELETE_SQL", "DELETE FROM loginfo WHERE log_id=%s;");
define("CHECKLOG_SQL", "SELECT * FROM loggroup WHERE log_id=%s;");
define("LOGSEARCH_UPDATE", 1);

/*********************************************************
 * set_tag_data()
 *
 * �������󥻥åȴؿ�
 *
 * [����]
 *  	$post		���Ϥ��줿��
 *
 * [�֤���]
 *	�ʤ�
 ********************************************************/
function set_tag_data(&$post, &$tag)
{
    global $err_msg;
    global $web_conf;

    /* JavaScript ���� */
    $java_script = "";

    /* ���ܥ��� ���� */
    set_tag_common($tag, $java_script);

    /* �ݥ��Ȥ��ͤ��ϤäƤ��Ƥ��ʤ��Ȥ� */
    if (isset($post["log_name"]) === FALSE) {
        /* MySQL��³ */
        $conn = MySQL_connect_server();
        if ($conn === FALSE) {
            return FALSE;
        }

        /* MySQL����������ơ��֥�ξ������� */
        $select_sql = sprintf(SELECT_SQL, $_POST["log_id"]);
        $result = MySQL_exec_query($conn, $select_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return FALSE;
        }

        /* MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ */
        MySQL_get_data($result, $data);

        /* MySQL�Ȥ���³���Ĥ��� */
        mysqli_close($conn);

        $tag["<<LOGNAME>>"]      = escape_html($data[0]["log_name"]);
        $tag["<<FACILITY>>"]     = escape_html($data[0]["facility_name"]);
        $tag["<<SEARCH_TABLE>>"] = escape_html($data[0]["search_tab"]);
        $tag["<<APPLICATION>>"]  = escape_html($data[0]["app_name"]);
        $tag["<<LOG_ID>>"]       = $_POST["log_id"];
        $post["log_type"]        = $data[0]["log_type"];

    } else {
        $tag["<<LOGNAME>>"]      = escape_html($post["log_name"]);
        $tag["<<FACILITY>>"]     = escape_html($post["facility"]);
        $tag["<<SEARCH_TABLE>>"] = escape_html($post["search_tab"]);
        $tag["<<APPLICATION>>"]  = escape_html($post["app_name"]);
        $tag["<<LOG_ID>>"]       = $post["log_id"];
    }

    /* ��Facility�����ꤵ��Ƥ��������ˤ��� */
    if ($tag["<<FACILITY>>"] == ALL_FACILITY) {
        $tag["<<FACILITY>>"] = "";
    }

    return TRUE;
}

/*********************************************************
 * make_select_option()
 *
 * ���쥯�ȥܥå��������ؿ�
 *
 * [����]
 *  	$values         ���ץ����˻��Ѥ����ͤ�����
 *  	$post           ���Ϥ��줿��
 *
 * [�֤���]
 *	�ʤ�
 ********************************************************/
function make_select_option($values, $post = "", &$option)
{

    /* value�������롼�� */
    foreach ($values as $val) {
        if ($val === $post) {
            $option .= <<<HERE
<option value="$val" selected>$val</option>
HERE;
        } else {
            $option .= <<<HERE
<option value="$val">$val</option>
HERE;
        }
    }

    return;
}

/*********************************************************
 * get_log_type()
 *
 * �������׼����ؿ�
 *
 * [����]
 *  	$values         ��������ͤ��Ǽ��������
 *
 * [�֤���]
 *	�ʤ�
 ********************************************************/
function get_log_type(&$values)
{
    global $web_conf;

    $values = explode(":", $web_conf["sylas"]["logtype"]);

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
$tag["<<LOG_NAME>>"]   = "";
$tag["<<OPTION>>"]     = "";
$tag["<<FACILITY>>"]   = "";
$tag["<<SEARCH_TAB>>"] = "";
$tag["<<APP_NAME>>"]   = "";
$tag["<<LOG_ID>>"]     = "";

/* ����ե����륿�ִ����ե������ɹ������å����Υ����å� */
$ret = init();
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/


/* ������ʬ�� */
if (isset($_POST["mod"])) {
    /* �����ͥ����å� */
    $post = $_POST;
    $ret = check_logsearch_input_value($post, $conn, LOGSEARCH_UPDATE);

    /* ���Ϥ��������ʤ��Ȥ���MySQL����Ͽ */
    if ($ret === TRUE) {

        /* ¸�ߥ����å� */
        $logsearchlist_sql = sprintf(SELECT_SQL, $post["log_id"]);

        /* MySQL����������ơ��֥�ξ������� */
        $result = MySQL_exec_query($conn, $logsearchlist_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }

        /* MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ */
        MySQL_get_data($result, $data);

        if (count($data) > 0) {
            $update_sql = sprintf(UPDATE_SQL,
                                 mysqli_real_escape_string($conn, 
                                                           $post["facility"]),
                                 mysqli_real_escape_string($conn, 
                                                           $post["search_tab"]),
                                 mysqli_real_escape_string($conn, 
                                                           $post["log_type"]),
                                 mysqli_real_escape_string($conn, 
                                                           $post["app_name"]),
                                 $post["log_id"]
                                 );

            $result = MySQL_exec_query($conn, $update_sql);

            /* MySQL�Ȥ���³�����Ǥ��� */
            mysqli_close($conn);

            if ($result === FALSE) {
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit (1);
            }

            /* ������å����������� */
            $err_msg = sprintf($msgarr['28004'][SCREEN_MSG],
                               escape_html($post["log_name"]));
            $log_msg = sprintf($msgarr['28004'][LOG_MSG], $post["log_name"]);

            result_log(OPERATION . ":OK:" . $log_msg);

        /* ���˺������Ƥ���� */
        } else {
            mysqli_close($conn);
            $err_msg = sprintf($msgarr['28024'][SCREEN_MSG],
                               escape_html($post["log_name"]));
            $log_msg = sprintf($msgarr['28024'][LOG_MSG], $post["log_name"]);

            result_log(OPERATION . ":NG:" . $log_msg);
        }

        dgp_location("./index.php", $err_msg);
        exit(0);

    /* �����ͤ˥��顼������� */
    } else {
        result_log(OPERATION . ":NG:" . $log_msg);
    }

/* ����ܥ��󤬲����줿�Ȥ� */
} elseif(isset($_POST["delete"])) {

    /* SQL����� */
    $delete_sql[] = sprintf(DELETE_SQL, $_POST["log_id"]);
    $check_sql = sprintf(CHECKLOG_SQL, $_POST["log_id"]);

    /* ���������� */
    $ret = delete_a_data($delete_sql, $check_sql);
    /* ����Ǥ��ʤ���� */
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    /* ���������������� */
    } else {
        /* ������å����������� */
        $err_msg = $msgarr['28010'][SCREEN_MSG];
        $log_msg = $msgarr['28010'][LOG_MSG];

        /* �����롼�װ������̤� */
        result_log(OPERATION . ":OK:" . $log_msg);
        dgp_location("./index.php", $err_msg);
        exit(0);
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif(isset($_POST["cancel"])) {

    /* �������������̤� */
    dgp_location("./index.php", $err_msg);
    exit;
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ����� */
$post = array();
if (isset($_POST["log_type"]) === FALSE) {
    $post["log_type"] = "----------";
} else {
    $post = $_POST;
}

/* �������� ���å� */
$ret = set_tag_data($post, $tag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
}

/* ���쥯�ȥܥå����˻��Ѥ����ͤ�����˳�Ǽ */
$val_arr = array();
$val_arr[0] = "----------";
get_log_type($val_arr);

/* ���쥯�ȥܥå������� */
$option = "";
make_select_option($val_arr, $post["log_type"], $option);
$tag["<<OPTION>>"] = $option;

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}
?>
