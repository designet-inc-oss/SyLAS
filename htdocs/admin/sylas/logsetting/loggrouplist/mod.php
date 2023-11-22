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
 * �������ѥ����롼���Խ�����
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
 * �ƥڡ����������
 ********************************************************/

define("OPERATION",          "Modfying loggroup list");
define("TMPLFILE",           "loggrouplist_mod.tmpl");

define("GROUP_NAME_DISP",    "�����롼��");
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
define("NO_HOST", "̵��");


/*********************************************************
 * set_tag_data()
 *
 * �������󥻥åȴؿ�
 *
 * [����]
 *  	$post		���Ϥ��줿��
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           �۾�
 ********************************************************/
function set_tag_data(&$post, &$tag)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* JavaScript ���� */
    $java_script = "";

    /* ���ܥ��� ���� */
    set_tag_common($tag, $java_script);

    /* �������ͤ����� */
    if (isset($post["group_name"]) === FALSE) {
        /* MySQL��³ */
        $conn = MySQL_connect_server();
        if ($conn === FALSE) {
            return FALSE;
        }

        /* MySQL��������롼�׾������� */
        $select_sql = sprintf(SELECT_SQL, $_POST["group_id"]);
        $result = MySQL_exec_query($conn, $select_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return FALSE;
        }

        /* MySQL����Ͽ���줿�����롼�׾��������˳�Ǽ */
        MySQL_get_data($result, $data);

        /* MySQL�Ȥ���³���Ĥ��� */
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
 * MySQL����ۥ���̾�������������ޤǤĤʤ��������Ѥ���
 *
 * [����]
 *  	$post		���Ϥ��줿��
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           �۾�
 ********************************************************/
function get_hosts($post, &$hosts)
{
    /* MySQL����ۥ���̾����� */
    $sql = sprintf(SELECT_HOST_SQL, $_POST["group_id"]);
    $ret = get_data($sql, $data);
    if ($ret === FALSE) {
        return FALSE;
    }

    /* ���������ۥ���̾��","�ǤĤʤ���ʸ������Ѵ� */
    $hosts = "";

    /* �����оݥۥ��Ȥ��ʤ��Ȥ� */
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
 * �������
 **********************************************************/

/* ��������� */
$tag["<<TITLE>>"]         = "";
$tag["<<JAVASCRIPT>>"]    = "";
$tag["<<SK>>"]            = "";
$tag["<<TOPIC>>"]         = "";
$tag["<<MESSAGE>>"]       = "";
$tag["<<TAB>>"]           = "";
$tag["<<LOGGROUP_NAME>>"] = "";
$tag["<<OPTION>>"]        = "";
$tag["<<HOSTNAME>>"]      = "";

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
    $group_name = $_POST["group_name"];
    $group_id   = $_POST["group_id"];
    $log_id     = $_POST["log_name"];
        
    $ret = check_groupname($conn, $group_name, LOGGROUP_MAXLEN, UPDATE);
    if ($ret === 0) {

        /* ¸�ߥ����å� */
        $group_check_sql = sprintf(SELECT_SQL, $group_id);

        /* MySQL����������ơ��֥�ξ������� */
        $result = MySQL_exec_query($conn, $group_check_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }

        /* MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ */
        MySQL_get_data($result, $data);

        if (count($data) > 0) {
            /* MySQL�˥����롼�פ���Ͽ */
            $sql_condition = sprintf(SQL_CONDITION, $group_id);
            $ret = add_mod_loggroup($conn, UPDATE_GROUP_SQL . $sql_condition, 
                                "", $log_id);
            if ($ret === FALSE) {
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                exit(1);
            }

            /* ������å����������� */
            $err_msg = sprintf($msgarr['28008'][SCREEN_MSG],
                               escape_html($group_name));
            $log_msg = sprintf($msgarr['28008'][LOG_MSG], $group_name);
            result_log(OPERATION . ":OK:" . $log_msg);

        } else {
            /* ���˺������Ƥ����� */
            mysqli_close($conn);
            $err_msg = sprintf($msgarr['28023'][SCREEN_MSG],
                               escape_html($group_name));
            $log_msg = sprintf($msgarr['28023'][LOG_MSG], $group_name);
            result_log(OPERATION . ":NG:" . $log_msg);
        }

        /* �����롼�װ������̤� */
        dgp_location("./index.php", $err_msg);
        exit(0);

    /* �����ͥ����å����DB���顼���������Ȥ� */
    } else if ($ret === 2) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);

    /* �����ͥ��顼�ξ�� */
    } else {
        result_log(OPERATION . ":NG:" . $log_msg);
    }

/* ����ܥ��󤬲����줿�Ȥ� */
} elseif(isset($_POST["delete"])) {

    /* SQL����� */
    $delete_sql["search"] = sprintf(DELETE_SERCHHOST_SQL, $_POST["group_id"]);
    $delete_sql["group"] = sprintf(DELETE_GROUP_SQL, $_POST["group_id"]);

    /* �ۥ��Ȥ��� */
    $ret = delete_a_data($delete_sql);
    /* DB���顼�����ä���� */
    if ($ret === FALSE) {
        syserr_display();
        exit(1);

    /* ���������������� */
    } else {
        /* ������å����������� */
        $err_msg = $msgarr['28009'][SCREEN_MSG];
        $log_msg = $msgarr['28009'][LOG_MSG];

        /* �����롼�װ������̤� */
        result_log(OPERATION . ":OK:" . $log_msg);
        dgp_location("./index.php", $err_msg);
        exit(0);
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif(isset($_POST["cancel"])) {

    /* �����롼�װ������̤� */
    dgp_location("./index.php", $err_msg);
    exit(0);
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ����� */
$post = array();
if (isset($_POST["log_name"]) === TRUE) {
    $post = $_POST;
}

/* �������� ���å� */
$ret = set_tag_data($post, $tag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* ���쥯�ȥܥå����˻��Ѥ����ͤ�����˳�Ǽ */
$data = array();
$ret = get_data(SELECT_LOGNAME_SQL, $data);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* ���쥯�ȥܥå������� */
$option = "";
make_select_option($data, $post["log_name"], $option);
$tag["<<OPTION>>"] = $option;

/* �ۥ���̾��������� */
$ret = get_hosts($post, $hosts);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}
$tag["<<HOSTNAME>>"] = escape_html($hosts);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}
?>
