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
 * �롼���ɲò���
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

define("TMPLFILE",  "rule_add.tmpl");
define("OPERATION",  "Rule add");

/*********************************************************
 * next_file()
 *
 * �񤭹���rsyslog�ե�����������Ƥ�
 *
 * [����]
 *      $dir           rsyslog������ե����뤬����ǥ��쥯�ȥ�
 *      $nextnum       �ե�������ֹ�
 *
 * [�֤���]
 *      $nextfile      �񤭹���ե�����
 *      FALSE          �ǥ��쥯�ȥꥪ���ץ��� 
 ********************************************************/

function next_file($dir, &$nextnum)
{
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /* �ǥ��쥯�ȥ�ϥ�ɥ��������� */
    $rsysdir = opendir($dir);
    if ($rsysdir === FALSE) {
        $err_msg = sprintf($msgarr['28038'][SCREEN_MSG], $dir);
        $log_msg = sprintf($msgarr['28038'][LOG_MSG], $dir);
        return FALSE;
    }
    $rsysarray = array();
    /* readdir�ǥե�����̾��������� */
    while (false !== ($rsysfile = readdir($rsysdir))) {
        /* .conf�ե�����Τ߻Ĥ� */
        $ret = preg_match("/^[0-9]*.conf$/", $rsysfile, $confarray);
        if ($ret === FALSE) {
            $err_msg = sprintf($msgarr['28038'][SCREEN_MSG], $dir);
            $log_msg = sprintf($msgarr['28038'][LOG_MSG], $dir);
            return FALSE;
        }
        /* ���������ľ�� */
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
 * �롼����ɲý���
 *
 * [����]
 *      $post          ���Ϥ��줿��
 *      $lockfile      ��å��ե�����Υѥ�
 *
 * [�֤���]
 *      0              ����
 *      1              �������ܤ��ʤ����� 
 *      2              �������ܤ��뼺��
 ********************************************************/

function rule_add($post, $lockfile)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
    /* ��å��ե�������� */ 
    /* ��å��ե����뤬���뤫���� */
    while (file_exists("$lockfile") === TRUE) {
        /* ��å��ե����뤢��ʤ�ʤ��ʤ�ޤ��Ԥ� */
        /* 1���Ԥ� */
        sleep(1);
    }
    /* ��å��ե������� */
    $mkfile = touch("$lockfile");
    if ($mkfile === FALSE) {
        /* ��å��ե���������˼��� */
        /* ���顼���� */
        $err_msg = sprintf($msgarr['28032'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28032'][LOG_MSG]);
        return 2;
    }
    /* ��å��ե������������ */
    /* �������� */
    /* �񤭹���rsyslog�ե�������ֹ�������Ƥ� */
    $nextfile = next_file($web_conf["sylas"]["rsyslogconfdir"], $nextnum);
    /* �ǥ��쥯�ȥ�θ��¤��ʤ� */
    if ($nextfile == FALSE) {
        $delfile = unlink("$lockfile");
        return 2;
    }
    /* �ե������������� */
    /* ���˥ե����뤬������ϥ��顼 */
    if (file_exists($nextfile)) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28031'][SCREEN_MSG], $nextfile);
        $log_msg = sprintf($msgarr['28031'][LOG_MSG], $nextfile);
        return 2;
    }
    /* rsyslog�ե������open���� */
    $fh = fopen($nextfile, "w");
    if ($fh === FALSE) {
    /* ���顼���� */
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28033'][SCREEN_MSG], $nextfile);
        $log_msg = sprintf($msgarr['28033'][LOG_MSG], $nextfile);
        return 2;
    }
    /* ���¤�Ϳ���� */
    chmod ($nextfile, 0775);
    /* rsyslog�ե�����˽񤭹������Ƥ�������� */
    $contents = in_contents($_POST, $nextnum);
    /* rsyslog�ե�����˽񤭹��� */
    $ret = fwrite($fh, $contents);
    /* �񤭹��ߥ��顼���� */
    if ($ret === FALSE) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28034'][SCREEN_MSG], $nextfile);
        $log_msg = sprintf($msgarr['28034'][LOG_MSG], $nextfile);
        return 1;
    }
    /* �񤭹�������������rsyslog�Ƶ�ư */
    $cmd = $web_conf["sylas"]["rsyslogrestartcmd"];
    $output = "";
    exec($cmd, $output, $ret);
    /* ��λ�����ɤ�0�Ǥʤ���кƵ�ư���� */
    if ($ret != 0) {
        $delfile = unlink("$lockfile");
        $err_msg = sprintf($msgarr['28035'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28035'][LOG_MSG]);
        return 1;
    }
    /* ��å��ե�����ä� */
    $delfile = unlink("$lockfile");
    if ($delfile === FALSE){
        /* ��å��ե��������˼��� */
        /* ���顼���� */
        $err_msg = sprintf($msgarr['28036'][SCREEN_MSG]);
        $log_msg = sprintf($msgarr['28036'][LOG_MSG]);
        return 2;
    }
    /* �������̤����� */
    $log_msg = sprintf($msgarr['28037'][LOG_MSG], $nextfile);
    return 0;
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
$tag["<<KEYWORD>>"]    = "";
$tag["<<MAILTO>>"]     = "";
$tag["<<SUBJECT>>"]    = "";
$tag["<<FACILITY_OPTION>>"]    = "";
$tag["<<BODY>>"]       = "";

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* �ɲä������줿�� */
if (isset($_POST["add"])){
    $lockfile = $web_conf["sylas"]["rsyslogconfdir"] . LOCK;
    /* �����ͥ����å� */
    $ret = check_rule($_POST);
    /*�����ͥ��顼�ξ��*/
    if ($ret  === FALSE) {
        /* ���顼��å�������ɽ�� */
        result_log(OPERATION . ":NG:" . $log_msg);
    } else {
        /* �����ͤ���������� */
        /* ��Ͽ���� */
        $ret = rule_add($_POST, $lockfile);
        switch ($ret) {
        case 1:
            /* �������ܤ��ʤ����顼 */
            result_log(OPERATION . ":NG:" . $log_msg);
            break;
        case 2:
            /* �������ܤ��륨�顼 */
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit (1);
        case 0:
            /* ���� */
            /* �������̤����� */
            result_log(OPERATION . ":OK:" . $log_msg);
            dgp_location("index.php");
            exit (0);
        }
    }
}
/* ����󥻥�ܥ��󤬲����줿�� */
if (isset($_POST["cancel"])) {
    dgp_location("index.php");
    exit;
}
/***********************************************************
 * ɽ������
 **********************************************************/

/* ����� */
$post = array();
if (isset($_POST["facility"]) === FALSE) {
    $post["facility"] = "���٤�";
    $post["degree"] = "���٤�";
} else {
    $post = $_POST;
    $tag["<<IPADDRESS>>"] = escape_html($post["ipaddress"]);
    $tag["<<KEYWORD>>"]   = escape_html($post["keyword"]);
    $tag["<<MAILTO>>"]    = escape_html($post["mailto"]);
    $tag["<<SUBJECT>>"]   = escape_html($post["subject"]);
    $tag["<<BODY>>"]      = escape_html($post["body"]);
}

/* ���� ���� */
set_tag_common($tag);

/* ���쥯�ȥܥå������� */
$facilityoption = "";
make_select($facility_arr, $facilityoption,  $post["facility"]);
$tag["<<FACILITY_OPTION>>"] = $facilityoption;

$degreeoption = "";
make_select($degree_arr, $degreeoption,  $post["degree"]);
$tag["<<DEGREE_OPTION>>"] = $degreeoption;

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

exit(0);
?>
