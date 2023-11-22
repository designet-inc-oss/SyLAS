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
 * �롼���������
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
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",  "rule.tmpl");
define("OPERATION", "Rule list");

/*********************************************************
 * set_loop_tag
 *
 * �롼�ץ������������
 *
 * [����]
 *       $looptag            �롼�ץ���
 *       $tag                ����
 *       $facility_arr       �ե�����ƥ�������
 *       $degree_arr         �����٤�����
 *
 * [�֤���]
 *       0               ����
 *       1               �������ܤ��ʤ����顼������
 *       2               �������ܤ��륨�顼
 **********************************************************/
function set_loop_tag(&$looptag, $tag, $facility_arr, $degree_arr)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
    $flag = 0;

    /* ����ե������������� */
    $dir = $web_conf["sylas"]["rsyslogconfdir"];
    $ret =  get_rsys_file($dir, $arrayfile);
    if ($ret === FALSE) {
        $err_msg = sprintf($msgarr['28038'][SCREEN_MSG], $dir);
        $log_msg = sprintf($msgarr['28038'][LOG_MSG], $dir);
        return 2;
    }
    /* .conf����Ȥ�Ϣ������������ */
    $confarray = get_array_file($arrayfile, $dir, $flag);
    /* �롼�ץ��������� */
    $i = 0;
    foreach ($confarray as $conf_data) {
        /* ���������׽��� */
        /* �ͤ����ξ��֤��٤ơפ������ */
        /* ������IP���ɥ쥹 */
        if (isset($conf_data[IP_SET])) {
            $from_ip = escape_html($conf_data[IP_SET]);
        } else {
            $from_ip = ALL_TYPE; 
        }
        /* �ե�����ƥ� */
        if (isset($conf_data[FACILITY_SET])) {
            /* �������Ѵ����� */
            $facility = array_search($conf_data[FACILITY_SET], $facility_arr);
        } else {
            $facility = ALL_TYPE; 
        }
        /* ������ */
        if (isset($conf_data[DEGREE_SET])) {
            /* �������Ѵ����� */
            $degree = array_search($conf_data[DEGREE_SET], $degree_arr);
        } else {
            $degree = ALL_TYPE; 
        }
        /* ������ɤ�ɬ�����äƤ��� */
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
 * �ե������������
 *
 * [����]
 *       $deletefile     �������ե�����
 *
 * [�֤���]
 *       TRUE               ����
 *       FALSE              �۾�
 **********************************************************/
function delete_file($deletefile)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* �ե����뤬¸�ߤ��뤫��ǧ */
    if (file_exists($deletefile) === FALSE) {
        $err_msg = sprintf($msgarr['28043'][SCREEN_MSG], $deletefile);
        $log_msg = sprintf($msgarr['28043'][LOG_MSG], $deletefile);
        return FALSE;
    }
    /* �ե������ä� */
    $ret = unlink("$deletefile");
    if ($ret === FALSE) {
        $err_msg = sprintf($msgarr['28040'][SCREEN_MSG], $deletefile);
        $log_msg = sprintf($msgarr['28040'][LOG_MSG], $deletefile);
        return FALSE;
    }
    /* rsyslog�Ƶ�ư */
    $cmd = $web_conf["sylas"]["rsyslogrestartcmd"];
    $output = "";
    exec($cmd, $output, $ret);
    /* ��λ�����ɤ�0�Ǥʤ���кƵ�ư���� */
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
 * �������
 **********************************************************/

/* ��������� */
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

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* �Խ��ܥ��󲡤��줿��*/
if (isset($_POST["modify"])) {
    /* �롼�뤬¸�ߤ��뤫���� */
    if (empty($_POST["radio"])) {
        $err_msg = sprintf($msgarr['28039'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28039'][LOG_MSG]);
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* �ե����뤬¸�ߤ��뤫���� */
        $modfile = $web_conf["sylas"]["rsyslogconfdir"] . $_POST["radio"];
        if (!file_exists($modfile)) {
            $err_msg = sprintf($msgarr['28043'][SCREEN_MSG], $modfile);
            $log_msg = sprintf($msgarr['28043'][LOG_MSG], $modfile);
            result_log(OPERATION . ":NG:" . $log_msg);
        } else {
            /* ���򤵤줿�ե�������Ϥ� */
            $hidden_data["radio"] = $_POST["radio"];
            dgp_location_hidden("modify.php", $hidden_data);
            exit (0);
        }
    }
}

/* ����ܥ��󤬲����줿�� */
if (isset($_POST["delete"])) {
    /* �롼�뤬¸�ߤ��뤫���� */
    if (empty($_POST["radio"])) {
        $err_msg = sprintf($msgarr['28039'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28039'][LOG_MSG]);
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* ���򤵤줿�ե������ä� */
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
 * ɽ������
 **********************************************************/

/* ���� ���� */
set_tag_common($tag);

$ret = set_loop_tag($looptag, $tag, $facility_arr, $degree_arr);
switch ($ret) {
case 1:
    /* �������ܤ��ʤ����顼 */
    $tag["<<MESSAGE>>"] = $err_msg;
    result_log(OPERATION . ":NG:" . $log_msg);
    break;
case 2:
    /* �������ܤ��륨�顼 */
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit (1);
case 0:
    /* ���� */
    break;
}

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, "<<STARTLOOP>>", "<<ENDLOOP>>");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

exit(0);
?>
