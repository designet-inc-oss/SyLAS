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
 * �������Ѹ������ɲò���
 *
 * $RCSfile: add.php,v $
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

define("OPERATION", "Adding logsearch list");
define("TMPLFILE",  "logsearchlist_add.tmpl");

define("INSERT_SQL",        
       "INSERT INTO loginfo " .
       "(log_name, facility_name, search_tab, log_type, app_name) " . 
       "values (\"%s\", \"%s\", \"%s\", \"%s\", \"%s\");");


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
function set_tag_data($post, &$tag)
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
    if (isset($post["log_name"]) === FALSE) {
        return;
    } else {
        $tag["<<LOGNAME>>"]      = escape_html($post["log_name"]);
        $tag["<<FACILITY>>"]     = escape_html($post["facility"]);
        $tag["<<SEARCH_TABLE>>"] = escape_html($post["search_tab"]);
        $tag["<<APPLICATION>>"]  = escape_html($post["app_name"]);
    }

    return;
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
$tag["<<TITLE>>"]        = "";
$tag["<<JAVASCRIPT>>"]   = "";
$tag["<<SK>>"]           = "";
$tag["<<TOPIC>>"]        = "";
$tag["<<MESSAGE>>"]      = "";
$tag["<<TAB>>"]          = "";
$tag["<<LOGNAME>>"]      = "";
$tag["<<OPTION>>"]       = "";
$tag["<<FACILITY>>"]     = "";
$tag["<<SEARCH_TABLE>>"] = "";
$tag["<<APPLICATION>>"]  = "";

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
if (isset($_POST["add"])) {

    /* �����ͥ����å� */
    $post = $_POST;
    $ret = check_logsearch_input_value($post, $conn);

    /* ���Ϥ��������ʤ��Ȥ���MySQL����Ͽ */
    if ($ret === TRUE) {
        $insert_sql = sprintf(INSERT_SQL,
                          mysqli_real_escape_string($conn, $post["log_name"]),
                          mysqli_real_escape_string($conn, $post["facility"]),
                          mysqli_real_escape_string($conn, $post["search_tab"]),
                          mysqli_real_escape_string($conn, $post["log_type"]),
                          mysqli_real_escape_string($conn, $post["app_name"])
                             );

        /* SQL��¹Ԥ��� */
        $result = MySQL_exec_query($conn, $insert_sql);

        /* MySQL�Ȥ���³�����Ǥ��� */
        mysqli_close($conn);

        if ($result === FALSE) {
            result_log(OPERATION . ":NG:" . $log_msg);
            syserr_display();
            exit(1);
        }

        /* ������å����������� */
        $err_msg = sprintf($msgarr['28049'][SCREEN_MSG], 
                           escape_html($post["log_name"]));
        $log_msg = sprintf($msgarr['28049'][LOG_MSG], $post["log_name"]);

        result_log(OPERATION . ":OK:" . $log_msg);
        dgp_location("./index.php", $err_msg);
        exit(0);

    /* �����ͤ˥��顼������� */
    } else {
        result_log(OPERATION . ":NG:" . $log_msg);
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif(isset($_POST["cancel"])) {

    /* �������������̤� */
    dgp_location("./index.php", $err_msg);
    exit(0);
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
set_tag_data($post, $tag);

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
