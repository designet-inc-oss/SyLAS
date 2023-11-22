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
 * �������Ѹ�������������
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
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",    "logsearchlist.tmpl");
define("OPERATION",   "Display logsearch list");

define("SELECT_SQL",  "SELECT * FROM loginfo;");
define("NON_TYPE",    "��̵����");

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

    /* MySQL����������ơ��֥�ξ������� */
    $result = MySQL_exec_query($conn, SELECT_SQL);
    if ($result === FALSE) {
        mysqli_close($conn);
        return FALSE;
    }

    /* MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ */
    MySQL_get_data($result, $data);

    /* MySQL�Ȥ���³���Ĥ��� */
    mysqli_close($conn);

    /* �롼�ץ��������� */
    $i = 0;
    foreach ($data as $one_data) {

       /* ���������� */
       /* �ͤ�������Ͽ����Ƥ����硢��̵���ˤ������ */
       /* ��̾��ɬ���ͤ����äƤ��� */
       $log_name   = escape_html($one_data["log_name"]);

       if ($one_data["log_type"] === "") {
           $tmp        = NON_TYPE;
           $log_type   = escape_html($tmp);
       } else {
           $log_type   = escape_html($one_data["log_type"]);
       }

       /* �ե�����ƥ���ɬ���ͤ����äƤ��롣ALL���ä�������ơˤˤ��� */
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

       /* log_id��ɬ���ͤ����äƤ��� */
       $log_id     = escape_html($one_data["log_id"]);

       /* �롼�ץ������ͤ����� */
       $looptag[$i]["<<LOGNAME>>"] = $log_name;
       $looptag[$i]["<<LOGTYPE>>"] = $log_type;
       $looptag[$i]["<<FACILITY>>"] = $fac_name;
       $looptag[$i]["<<SEARCH_TABLE>>"] = $search_tab;
       $looptag[$i]["<<APPLICATION>>"] = $app_name;
       $looptag[$i]["<<LOG_ID>>"] = $log_id;

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
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/

/* ��Ͽ�ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["add"])) {
    $sesskey = $_POST["sk"];
    /* ��������Ͽ���̤����� */
    dgp_location("./add.php");
    exit;
}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���� ���� */
$javascript = <<<HERE
function sysSubmit(url, log_id) {
    document.form_main.action=url;
    document.form_main.log_id.value=log_id;
    document.form_main.submit();
}
HERE;

$err_msg = escape_html($err_msg);
set_tag_common($tag, $javascript);

/* �롼�ץ����κ��� */
$ret = set_loop_tag($looptag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
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
