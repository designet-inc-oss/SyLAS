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
 * �����оݥ��ɲò���
 *
 * $RCSfile: host.php,v $
 * $Revision: 1.3 $
 * $Date: 2014/07/16 03:58:45 $
 **********************************************************/
include_once("../../initial");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",              "host_add_del.tmpl");
define("OPERATION",             "Search host add_del");

define("SELECT_HOST_SQL",       "SELECT * FROM hosts;");
define("SELECT_SEARCHHOST_SQL", "SELECT * FROM search_hosts WHERE group_id=%s;");
define("INSERT_GROUP_SQL",   "INSERT INTO loggroup " .
                             "(group_name, log_id) VALUES (\"%s\", \"%s\");");
define("GET_GROUPID_SQL",    "SELECT group_id FROM loggroup where " .
                             "group_name=\"%s\";");


/*********************************************************
 * SetTag_for_FirstTime()
 *
 * ���ɽ�����Υ������������
 *
 * [����]
 *       $tag               �֤���������
 *
 * [�֤���]
 *       TRUE               ����
 *       FALSE              ����
 *********************************************************/
function SetTag_for_FirstTime(&$tag)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
 
    /* MySQL��³ */
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return FALSE;
    }

    /* MySQL���餹�٤ƤΥۥ��Ȥ���� */
    $result = MySQL_exec_query($conn, SELECT_HOST_SQL);
    if ($result === FALSE) {
        mysqli_close($conn);
        return FALSE;
    }

    /* MySQL����Ͽ���줿���٤ƤΥۥ��Ⱦ��������˳�Ǽ */
    MySQL_get_data($result, $all_hosts);

    /* �Խ����̤��餭�Ƥ�����硢���Υ��롼�פ˴ޤޤ��ۥ��Ȥ���� */
    if (isset($_POST["hostadd_del"])) {
        /* MySQL���餹�٤Ƥθ����оݥۥ��Ȥ���� */
        $search_host_sql = sprintf(SELECT_SEARCHHOST_SQL, $_POST["group_id"]);
        $result = MySQL_exec_query($conn, $search_host_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return FALSE;
        }

        /* MySQL����Ͽ���줿�����оݥۥ��Ⱦ��������˳�Ǽ */
        MySQL_get_data($result, $search_hosts);
    /* ��Ͽ�����ξ�硢�ꥹ�Ȥ�����Ѱդ��Ƥ���(Nortice����) */
    } else {
        $search_hosts = array();
    }

    /* MySQL�Ȥ���³���Ĥ��� */
    mysqli_close($conn);

    /* �����оݡ����оݥۥ��ȥꥹ�Ȥ��������� */
    $non_search = array();
    $search     = array();
    make_search_hosts($all_hosts, $search_hosts, $non_search, $search);

    /* html���������� */
    make_hosts_option($non_search, $a_option);
    make_hosts_option($search, $s_option);

    $tag["<<ALL_HOST_OPTION>>"]     = $a_option;
    $tag["<<SEARCH_HOST_OPTION>>"]  = $s_option;

    /* hidden�κ��� */
    make_id_hidden($non_search, $a_hidden, "all_id");
    make_id_hidden($search, $s_hidden, "search_id");
    $tag["<<NON_SEARCH_ID_HIDDEN>>"]    = $a_hidden;
    $tag["<<SEARCH_ID_HIDDEN>>"]        = $s_hidden;

    return TRUE;
}

/**********************************************************
 * move_hosts()
 *
 * �岼������������줿�ݤˡ������Υꥹ�Ȥ���⤦�����Υꥹ�Ȥ�
 * �ۥ��Ȥ��ư�����롣
 *
 * [����]
 *       &$topList      ��Υꥹ�Ȥ������ۥ��ȷ�
 *                          (id => �ۥ���̾��Ϣ������)
 *       &$botList      ���Υꥹ�Ȥ������ۥ��ȷ�
 *                          (id => �ۥ���̾��Ϣ������)
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function move_hosts(&$topList, &$botList)
{
    /* �������ꥹ�ȥܥå������Ǽ�����ѿ������� */
    $newFrom = array();
    $newTo   = array();

    /* up�ܥ���, down�ܥ����ξ�б������롣
     * from����to�ء�selected���ư�����롣
     * down�ܥ��󤬲����줿�Ȥ��ϡ�
     * from��hidden���Ϥ���������ǽ�ۥ���($_POST["all_id"])�Ȥʤꡢ
     *   to��hidden���Ϥ���븡���оݥۥ���($_POST["search_id"])�ˤʤ롣
     * up�ܥ��󤬲����줿�Ȥ��Ϥ��ε� */
    list($selected, $from, $to, $mode) = isset($_POST["down"]) ?
                          array("all_host", "all_id", "search_id", "down")
                        : array("search_host", "search_id", "all_id", "up");

    /* ��$_POST���ͤ������ɤ���������å�(���顼����) */
    $moveHosts = isset($_POST[$selected]) ? $_POST[$selected] : array();
    $fromHosts = isset($_POST[$from])     ? $_POST[$from]     : array();
    $toHosts   = isset($_POST[$to])       ? $_POST[$to]       : array();

    /* ��ư��ȤΥۥ��ȥꥹ�Ȥ���ľ����
     * $fromHosts�ϡ��Ť���ư���ۥ��ȥꥹ�Ȥ��Ǽ��������
     * hidden�����������Ƥ��� */
    foreach ($fromHosts as $host) {
        /* �Ť��ꥹ�Ȥ��顢��ư�������оݤ����Ф� */
        if (in_array($host, $moveHosts)) {
            continue;
        }
        /* HTML�ˤϡ�"id,�ۥ���̾" �η����ǵ��Ҥ���Ƥ��� */
        list($host_id, $host_name) = explode(",", $host);
        /* �Τ��δؿ�(make_hosts_option�ʤ�)�˹�碌������ */
        $newFrom[$host_id] = $host_name;
    }

    /* ��ư��Υۥ��ȥꥹ�Ȥ���ľ����
     * $toHosts��hidden�������
     */
    foreach ($toHosts as $host) {
        list($host_id, $host_name) = explode(",", $host);
        $newTo[$host_id] = $host_name;
    }
    /* ��ư�������оݤ��ư����ɲä��� */
    foreach ($moveHosts as $host) {
        list($host_id, $host_name) = explode(",", $host);
        $newTo[$host_id] = $host_name;
    }

    /* �⡼�ɤ˹�碌���������Ϥ��줿�ѿ�($topList, $botList)��
     * ���޺��������岼�Υꥹ�ȥܥå������Ƥ򥻥å� */
    list($topList, $botList) = $mode == "down" ? array($newFrom, $newTo)
                                               : array($newTo, $newFrom);

    return;
}


/**********************************************************
 * add_loggroup()
 * $_POST["fromADD"]���ͤ�Ȥ����ǡ����١����˿��������롼�פ�������롣
 *
 * [����]
 *          &$group_name    ���̤�ɽ�������å�������
 *
 * [�֤���]
 *          $group_id       ��Ͽ�������롼�פ�ID
 *          FALSE           SQL���顼
 **********************************************************/
function add_loggroup(&$group_name)
{
    /* �����ۥ����ɲò��̤����Ϥ��줿�����롼��̾��������ID����� */
    list($group_name, $log_id) = explode(",", $_POST["fromADD"]);

    /* MySQL��³ */
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return FALSE;
    }

    $search_group_name = mysqli_real_escape_string($conn, $group_name);

    /* MySQL�˥����롼�פ���Ͽ */
    $ret = add_mod_loggroup($conn, INSERT_GROUP_SQL, 
                            $group_name, $log_id);
    if ($ret === FALSE) {
        return FALSE;
    }   

    /* MySQL���顢����Ͽ�������롼�פ�ID����� */

    $gid_sql = sprintf(GET_GROUPID_SQL, $search_group_name);
    $ret = get_data($gid_sql, $data);
    if ($ret === FALSE) {
        return FALSE;
    }

    return $data[0]["group_id"];
}


/***********************************************************
 * �������
 **********************************************************/

/* ��������� */
$tag["<<TITLE>>"]                = "";
$tag["<<JAVASCRIPT>>"]           = "";
$tag["<<SK>>"]                   = "";
$tag["<<TOPIC>>"]                = "";
$tag["<<MESSAGE>>"]              = "";
$tag["<<TAB>>"]                  = "";
$tag["<<ALL_HOST_OPTION>>"]      = "";
$tag["<<SEARCH_HOST_OPTION>>"]   = "";
$tag["<<NAME_HIDDEN>>"]          = "";
$tag["<<SEARCH_ID_HIDDEN>>"]     = "";
$tag["<<NON_SEARCH_ID_HIDDEN>>"] = "";

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

/* �岼�Υܥ��󤬲����줿�Ȥ� */
if (isset($_POST["up"]) || isset($_POST["down"])) {
    /* �ꥹ�Ȥ������ؤ���Ԥ� */
    $non_search = array(); // ����ˡ������ǽ�ۥ��ȡפ����줿����Τ�ͤᤳ��
    $search = array();     // ����ˡָ����оݥۥ��ȡפ����줿����Τ�ͤᤳ��
    move_hosts($non_search, $search);

    /* ������������� */
    /* ���쥯�ȤΥ��ץ������� */
    make_hosts_option($non_search, $a_option);
    make_hosts_option($search, $s_option);
    $tag["<<ALL_HOST_OPTION>>"]     = $a_option;
    $tag["<<SEARCH_HOST_OPTION>>"]  = $s_option;

    /* hidden�κ��� */
    make_id_hidden($non_search, $a_hidden, "all_id");
    make_id_hidden($search, $s_hidden, "search_id");
    $tag["<<NON_SEARCH_ID_HIDDEN>>"]    = $a_hidden;
    $tag["<<SEARCH_ID_HIDDEN>>"]        = $s_hidden;
    $_POST["fromADD"] = escape_html($_POST["fromADD"]);

/* ��Ͽ�ܥ��󤬲����줿�Ȥ� */
} else if (isset($_POST["mod"])) {
    /* ������Ͽ�ΤȤ��ȹ��������ΤȤ��ǡ����롼��ID���Ѥ��� */
    /* ������Ͽ(add.php�������ܤ��Ƥ���) */
    if ($_POST["fromADD"] != "") {
        /* �������롼�פ���Ͽ����ID��������� */
        $group_id = add_loggroup($group_name);
        if ($group_id === FALSE) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }
        /* ��������̤����� */
        $location = "index.php";
        /* ��Ͽ��λ��å������򥻥å� */
        $group_name = escape_html($group_name);
        $err_msg = sprintf($msgarr['28028'][SCREEN_MSG], $group_name);
        $log_msg = sprintf($msgarr['28028'][LOG_MSG], $group_name);
    /* �������� */
    } else {
        /* �������̤���Ѿ����Ƥ���ID������ */
        $group_id = $_POST["group_id"];
        /* ��������̤����� */
        $location = "mod.php";
        /* ������λ��å������򥻥å� */
        $err_msg = $msgarr['28020'][SCREEN_MSG];
        $log_msg = $msgarr['28020'][LOG_MSG];
    }

    /* �ǡ����١������Խ� */
    if (!isset($_POST["search_id"]) || !is_array($_POST["search_id"])) {
        $_POST["search_id"] = array();
    }
    $ret = modify_search_host($_POST["search_id"], $group_id);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    }

    /* ���β��̤����� */
    result_log(OPERATION . ":OK:" . $log_msg);
    $sesskey = $_POST["sk"];
    $postval = array("group_id" => $group_id);
    post_location($location, $err_msg, $postval);
    exit(0);
/* ���ɽ���ΤȤ� */
} else {
    /* �ꥹ�Ȥ�hidden�����򥻥å� */
    $ret = SetTag_for_FirstTime($tag);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    }
}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���� ���� */
$javascript = <<<HERE
function sysSubmit(url) {
    document.form_main.action=url;
    document.form_main.submit();
}
HERE;

$err_msg = escape_html($err_msg);

set_tag_common($tag, $javascript);
$tag["<<GROUP_ID>>"] = isset($_POST["group_id"]) ? $_POST["group_id"] : "";
/* ��Ͽ���Խ��ǰۤʤ���ʬ�����ꤹ�� */
if (isset($_POST["fromADD"])) {

    /* ��Ͽ�ξ�� */
    $tag["<<NEW_HOSTNAME>>"] = $_POST["fromADD"]; // ��Ͽ�ۥ���̾,������ID
    $tag["<<BUTTON_NAME>>"]  = "add_btn"; // �ܥ���̾�����Ͽ�פˤ���
    $tag["<<CANCEL>>"]       = "add.php"; // ����󥻥�ܥ����������
} else {
    /* �Խ��ξ�� */
    $tag["<<NEW_HOSTNAME>>"] = ""; // �Խ������Ǥϻ��Ѥ��ʤ�
    $tag["<<BUTTON_NAME>>"]  = "mod_btn"; // �ܥ���̾��ֹ����פˤ���
    $tag["<<CANCEL>>"]       = "mod.php"; // ����󥻥�ܥ����������
}

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

exit(0);
?>
