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
 * �������ѥ����롼�װ�������
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
 * �ƥڡ����������
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
 * �롼�ץ������������
 *
 * [����]
 *       $looptag            �롼�ץ���
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function set_loop_tag(&$looptag)
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

    /* MySQL��������롼�פξ������� */
    $result = MySQL_exec_query($conn, SELECT_SQL);
    if ($result === FALSE) {
        mysqli_close($conn);
        return FALSE;
    }

    /* MySQL����Ͽ���줿�����롼�פξ��������˳�Ǽ */
    MySQL_get_data($result, $data);

    /* MySQL�Ȥ���³���Ĥ��� */
    mysqli_close($conn);

    /* ���롼�ץꥹ�Ȥ��������� */
    $grouplist = array();
    make_grouplist($data, $grouplist);

    /* �롼�ץ��������� */
    $i = 0;
    foreach ($grouplist as $key => $group_info) {

       /* ���������� */
       $loggroup_name   = escape_html($group_info["group_name"]);
       $log_name        = escape_html($group_info["log_name"]);
       $host_name       = escape_html($group_info["host_name"]);
       $group_id        = escape_html($key);

       /* �롼�ץ������ͤ����� */
       $looptag[$i]["<<LOGGROUPNAME>>"] = $loggroup_name;
       $looptag[$i]["<<LOG>>"]          = $log_name;
       $looptag[$i]["<<HOST>>"]         = $host_name;
       $looptag[$i]["<<GROUP_ID>>"]     = $group_id;

       /* ���󥯥���� */
       $i++;
    }

    return TRUE;
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
    $sesskey = $_POST["sk"];
    /* �����롼���ɲò��̤����� */
    dgp_location("./add.php");
    exit(0);
}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���� ���� */
$javascript = <<<HERE
function sysSubmit(url, group_id) {
    document.form_main.action=url;
    document.form_main.group_id.value=group_id;
    document.form_main.submit();
}
HERE;

$err_msg = escape_html($err_msg);
set_tag_common($tag, $javascript);

/* �롼�ץ����κ��� */
$ret = set_loop_tag($looptag);
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, "<<STARTLOOP>>", "<<ENDLOOP>>");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

exit(0);
?>
