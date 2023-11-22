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
 * ��������Mail����������
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.00 $
 * $Date: 2015/12/07 13:21:00 $
 **********************************************************/
include_once("lib/dglibcommon");
include_once("../initial");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibsylas");

/********************************************************
 * �ƥڡ����������
 ********************************************************/

define("TMPLFILE",         "maillogsearch.tmpl");
define("OPERATION",        "MAIL logsearch");
define("SELECT_GROUP_SQL", "SELECT loggroup.group_id, loggroup.group_name, loginfo.log_type, loginfo.facility_name ". 
                           "FROM loggroup JOIN loginfo ON loggroup.log_id=loginfo.log_id WHERE log_type='mail';");
define("SELECT_FACILITY_SQL", "SELECT loginfo.facility_name, loginfo.search_tab, loginfo.app_name " .
                              "FROM loggroup JOIN loginfo ON loggroup.log_id=loginfo.log_id WHERE group_id=%s;");
define("MAIL_SEARCH_SQL",   "SELECT DeviceReportedTime, FromHost, Message " .
                            "FROM %s %s" .
                            " ORDER BY DeviceReportedTime desc LIMIT %s ;");

/*********************************************************
 * set_tag_data($post, &$tag)
 *
 * �����Υ��åȤ򤹤�
 *
 * [����]
 *       $post       �ϤäƤ�����
 *       $tag               ����
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function set_tag_data($post, &$tag)
{
    global $web_conf;

    /*��������24�����������դ����*/
    $daylist = getdate(time() - 86400);

    /* ���� ���� */
    $javascript = <<<EOD
    function allSubmit(url, page) {
        document.search_condition.action = url;
        document.search_condition.page.value = page;
        document.search_condition.submit();
    }
EOD;

    /*���̤����Ѥ��륿���򥻥åȤ���*/
    /*<<TITLE>>,<<MESSAGE>>,<<SK>>,<<TOPIC>>,<<TAB>>*/
    set_tag_common($tag, $javascript);

    /* ���Υ��쥯�ȥܥå������� */
    if (isset($post["loggroup"])) {
        $selected_log =$post["loggroup"];
    } else {
        $selected_log = -1;
    }
    $ret = make_mail_log_option($selected_log, $option);
    if ($ret === FALSE) {
        return FALSE;
    }
    $tag["<<LOG>>"] = $option;

    /* �����ԤΥƥ����ȥܥå��� */
    if (isset($post["sendaddr"])) {
        $tag["<<SEARCH_FROM>>"] = escape_html($post["sendaddr"]);
    }

    /* ���襢�ɥ쥹�Υƥ����ȥܥå��� */
    if (isset($post["reciveaddr"])) {
        $tag["<<SEARCH_TO>>"] = escape_html($post["reciveaddr"]);
    }

    /*�����ԥ��ɥ쥹���ꥹ�ȥܥå���*/
    if (isset($post["sendrule"])) {
        $list = $post["sendrule"];
    } else {
        $list = "0";
    }
    $tag["<<FROM_RULE>>"] = make_checked_list($list);
    /*���襢�ɥ쥹���ꥹ�ȥܥå���*/
    if (isset($post["reciverule"])) {
        $list = $post["reciverule"];
    } else {
        $list = "0";
    }
    $tag["<<TO_RULE>>"] = make_checked_list($list);

    $tag["<<STARTDATE>>"] = date("Y/m/d 00:00:00");
    if (isset($_POST["startdate"]) === TRUE) {
        $tag["<<STARTDATE>>"] = $_POST["startdate"];
    }

    $tag["<<ENDDATE>>"] = date("Y/m/d 23:59:59");
    if (isset($_POST["enddate"]) === TRUE) {
        $tag["<<ENDDATE>>"] = $_POST["enddate"];
    }

    $tag["<<DEFLINE>>"] = $web_conf["sylas"]["displaylines"];
    if (isset($_POST["resultline"])) {
        $tag["<<DEFLINE>>"] = $_POST["resultline"];
    }

    return TRUE;
}

/*********************************************************
 * make_hidden
 *
 * hidden�����Υե�������������
 *
 * [����]
 *       $post               ���Ϥ��줿��
 *       $tag                �֤���������
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function make_hidden($post, &$tag)
{
    /* �֤��������ͤ��ѿ������� */
    $loggroup   = $post["loggroup"];
    $sendaddr   = escape_html($post["sendaddr"]);
    $reciveaddr   = escape_html($post["reciveaddr"]);
    $start      = $post["startdate"];
    $end        = $post["enddate"];
    $resultline = $post["resultline"];
    $sesskey    = escape_html($post["sk"]);

    /*hidden��������*/
    $hidden = <<<EOD
  <form method="post" name="search_condition">
  <input type="hidden" name="page">
  <input type="hidden" name="loggroup" value="$loggroup">
  <input type="hidden" name="sendaddr" value="$sendaddr">
  <input type="hidden" name="reciveaddr" value="$reciveaddr">
  <input type="hidden" name="startdate" value="$start">
  <input type="hidden" name="enddate" value="$end">
  <input type="hidden" name="sk" value="$sesskey">
  <input type="hidden" name="resultline" value="$resultline">
  <input type="hidden" name="next_button" value="search">
</form>
    
EOD;

    $tag["<<HIDDEN>>"] =  $hidden;
    return;
}

/*********************************************************
 * set_loop_tag
 *
 * �롼�ץ������������
 *
 * [����]
 *       $page               �ڡ���
 *       $looptag            �롼�ץ���
 *       $sesskey            ���å���󥭡�
 *       $post               $_POST����
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function set_loop_tag($page, &$looptag, $sesskey, $post)
{
    global $web_conf;

    /* �롼�ץ��������� */
    $start = ($page - 1) * $_POST["resultline"];
    $end   = ($page * $_POST["resultline"]) - 1;

    $k = 0; 

    for ( ; $start <= $end ; $start++) {
        
        if (isset($_SESSION["result"][$start]) === FALSE) {
            break;
        }

        /* �롼�ץ������ͤ����� */
        $log_date = date("Y-m-d H:i:s" , $_SESSION["result"][$start]["date"]);
        $looptag[$k]["<<LOG_DATE>>"] = $log_date;
        $looptag[$k]["<<LOG_FROM>>"] = $_SESSION["result"][$start]["from"];
        $looptag[$k]["<<LOG_TO>>"] = $_SESSION["result"][$start]["to"];
        $looptag[$k]["<<LOG_STATUS>>"] = $_SESSION["result"][$start]["status"];
        $looptag[$k]["<<RL>>"] = $web_conf["sylas"]["displaylines"];

        /*�ܺ٥ܥ������*/
        $looptag[$k]["<<MORE>>"] =  "<button class=\"mail_button\" type=\"submit\" name=\"more\" value=\"".$k."\">�ܺ�</button>";
        $looptag[$k]["<<E_SESS>>"] = $sesskey;

        /*QID�����å�*/

        if (strpos($_SESSION["result"][$start]["qid"], "NOQUEUE") === FALSE) {
            $sdate = date("Y/m/d H:i:s", strtotime($log_date. " -1 day"));
            $edate = date("Y/m/d H:i:s", strtotime($log_date. " +1 day"));

            $looptag[$k]["<<S_D>>"] = $sdate;
            $looptag[$k]["<<E_D>>"] = $edate;
            $looptag[$k]["<<QID>>"] = $_SESSION["result"][$start]["qid"]. ":";
        } else {
            $date = preg_replace("/-/", "/", $log_date);
            $looptag[$k]["<<S_D>>"] = $date;
            $looptag[$k]["<<E_D>>"] = $date;
            $looptag[$k]["<<QID>>"] = "NOQUEUE";
        }
        $looptag[$k]["<<HOSTNAME>>"] = $_SESSION["result"][$start]["host"];
        $looptag[$k]["<<E_LOG>>"] = $post["loggroup"];
        $k++;
    }
    return;
}

/* ��������� */
$tag["<<TITLE>>"]               = "";
$tag["<<JAVASCRIPT>>"]          = "";
$tag["<<SK>>"]                  = "";
$tag["<<TOPIC>>"]               = "";
$tag["<<MESSAGE>>"]             = "";
$tag["<<TAB>>"]                 = "";
$tag["<<LOG>>"]                 = "";
$tag["<<SEARCH_FROM>>"]         = "";
$tag["<<SEARCH_TO>>"]           = "";
$tag["<<FROM_RULE>>"]           = "";
$tag["<<TO_RULE>>"]             = "";
$tag["<<STATUS>>"]              = "";
$tag["<<START_YEAR_OPTION>>"]   = "";
$tag["<<START_MONTH_OPTION>>"]  = "";
$tag["<<START_DATE_OPTION>>"]   = "";
$tag["<<START_HOUR_OPTION>>"]   = "";
$tag["<<START_MINUTE_OPTION>>"] = "";
$tag["<<START_SECOND_OPTION>>"] = "";
$tag["<<END_YEAR_OPTION>>"]     = "";
$tag["<<END_MONTH_OPTION>>"]    = "";
$tag["<<END_DATE_OPTION>>"]     = "";
$tag["<<END_HOUR_OPTION>>"]     = "";
$tag["<<END_MINUTE_OPTION>>"]   = "";
$tag["<<END_SECOND_OPTION>>"]   = "";
$tag["<<SEARCH_COUNT>>"]        = 0;/*��..��*/
$tag["<<COMMENT_START>>"]       = "<!--";
$tag["<<COMMENT_END>>"]         = "-->";
$tag["<<PRE>>"]                 = "";
$tag["<<NEXT>>"]                = "";
$tag["<<HIDDEN>>"]              = "";
$page = 0;
/*********************************************************
 * make_chcked_list()
 *
 * ����Υꥹ�ȥ��쥯�ȥܥå����Υ��ץ��������
 * 
 *
 * [����]
 *       $list              $_POST�����ϤäƤ�����
 *       
 *
 * [�֤���]
 *      $optionlist 
 **********************************************************/
function make_checked_list($list)
{
    $selected = array("0" => "",
                      "1" => "",
                     );
    $optionlist = "";
    if($list === "0"){
        $selected[0] = " selected";
    } else {
        $selected[1] = " selected";
    }  
    $optionlist .= "<option value=0 ".$selected[0].">�Ȱ��פ���</option>\n";
    $optionlist .= "<option value=1 ".$selected[1].">��ޤ�</option>\n";
    return $optionlist;
}

/*********************************************************
 * make_mail_log_option()
 *
 * �����롼�פΥ��쥯�ȥܥå����Υ��ץ��������
 * (mail����������)
 *
 * [����]
 *       $post_group_id      selected�ˤ����
 *       $option
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function make_mail_log_option($post_group_id, &$option)
{

    /* �����롼�׾����MySQL������� */
    $ret = get_data(SELECT_GROUP_SQL, $data);
    if ($ret === FALSE) {
        return FALSE;
    }

    /*���μ��बmail�Υ��롼�פ�¸�ߤ��ʤ��Ȥ�*/
    $option = "";
    if (isset($data[0]) === FALSE) {
        /* ���쥯�ȥܥå�������� */
        $option .= "<option value=\"-1\" selected>----------</option>";
    } else {
        /*���μ��बmail�Υ��롼�פ�¸�ߤ���Ȥ�*/
        foreach ($data as $one_data) {
            $group_name = escape_html($one_data["group_name"]);
            $group_id   = escape_html($one_data["group_id"]);
            /* POST���ϤäƤ����ͤ�Ʊ����(���ݻ�) */
            if ($one_data["group_id"] === $post_group_id) {
                $select = " selected";
            } else {
                $select = "";
            }
            $option .= "<option value=\"".$group_id."\"".$select.">".$group_name."</option>";
        }
    }
    return TRUE;
}

/*********************************************************
 * check_mail_search_condition()
 *
 * mail�������θ�����������å�����
 *
 * [����]
 *       $post               ���Ϥ��줿��
 *
 * [�֤���]
 *       TRUE                ����
 *       FALSE               �۾�
 **********************************************************/
function check_mail_search_condition($post)
{
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /*���������*/
    $start = "";
    $end = "";

    /*���Υ����å�*/
    if (isset($post["loggroup"]) === FALSE || $post["loggroup"] == "-1") {
        $err_msg = $msgarr['28014'][SCREEN_MSG];
        return FALSE;
    }
      
    $strmaxlen = 256;
    /*�����ԥ��ɥ쥹�����Ϥ���Ƥ���Ȥ�������ʸ������ʸ���������å�*/
    if (isset($post["sendaddr"])) {
        $ret = check_string($post["sendaddr"], $strmaxlen);
        if($ret !== 0) {
            /*ret���ͤ�1,2(ʸ����ʸ���泌�顼)�ΤȤ����顼��å�����*/
            $err_msg = $msgarr['41005'][SCREEN_MSG];
            return FALSE;
        }
    } else {
        $err_msg = $msgarr['41005'][SCREEN_MSG];
        return FALSE;
    } 

    /*�����Ծ��������å�*/
    if (isset($post["sendrule"]) === FALSE) {
        $err_msg = $msgarr['41005'][SCREEN_MSG];
        return FALSE;
    }

    /*���襢�ɥ쥹�����Ϥ���Ƥ���Ȥ�������ʸ������ʸ���������å�*/
    if (isset($post["reciveaddr"])) {
        $ret = check_string($post["reciveaddr"], $strmaxlen);
        if($ret !== 0) {
            /*ret���ͤ�1,2(ʸ����ʸ���泌�顼)�ΤȤ����顼��å�����*/
            $err_msg = $msgarr['41006'][SCREEN_MSG];
            return FALSE;
        }
    } else {
        $err_msg = $msgarr['41006'][SCREEN_MSG];
        return FALSE;
    }

    if (isset($post["reciverule"]) === FALSE) {
        $err_msg = $msgarr['41006'][SCREEN_MSG];
        return FALSE;
    }

    /*�������֤Υ����å�*/
    /*��������*/
    if (!isset($post["startdate"])) {
        $err_msg = $msgarr['41008'][SCREEN_MSG];
        return FALSE;
    }

    $ret = check_time_format($post["startdate"]);
    if ($ret === FALSE) {
        $err_msg = $msgarr['41008'][SCREEN_MSG];
        return FALSE;
    }

    /*��λ����*/
    if (!isset($post["enddate"])) {
        $err_msg = $msgarr['41009'][SCREEN_MSG];
        return FALSE;
    }

    $ret = check_time_format($post["enddate"]);
    if ($ret === FALSE) {
        $err_msg = $msgarr['41009'][SCREEN_MSG];
        return FALSE;
    }

    /* ���ϤȽ�λ����������å� */
    $start = strtotime($post["startdate"]);
    $end   = strtotime($post["enddate"]);
    if ($start > $end) {
        $err_msg = $msgarr['28018'][SCREEN_MSG];
        $log_msg = $msgarr['28018'][LOG_MSG];
        return FALSE;
    }

    $ret = check_duration($post["startdate"], $post["enddate"]);
    if ($ret === FALSE) {
        return FALSE;
    }

    $ret = check_resultline($post);
    if ($ret === FALSE) {
        return FALSE;
    }

    return TRUE;
}

/*********************************************************
 * check_string()
 *
 * mail���������̤������ͥ����å���Ԥʤ�
 *
 * [����]
 *        $string      ��������ʸ����
 *        $maxlen      ����ʸ��Ĺ
 *
 * [�֤���]
 *       0             ����
 *       1             ʸ��Ĺ���顼
 *       2             ʸ���泌�顼
**********************************************************/
function check_string($string, $maxlen)
  {
     $length = strlen($string);
     if ($length > $maxlen) {
         return 1;
     }

     /* Ⱦ�ѱ��羮ʸ�������������국��Τߵ��� */
     $num = "0123456789";
     $sl = "abcdefghijklmnopqrstuvwxyz";
     $ll = strtoupper($sl);
     $sym = "!#$%&'*+-/=?^_{}~.@";
     $allow_letter = $num . $sl . $ll . $sym;
     if (strspn($string, $allow_letter) !== $length) {
         return 2;
     }

     return 0;
}


/*********************************************************
 * exec_mail_search
 *
 * �����ͥ����å��򤷡����˹��פ��������������������
 *
 * [����]
 *       $post               �ϤäƤ�����
 *
 * [�֤���]
 *       0               ����
 *       1               ���ϥ����å�����
 *       2               mysql����
 **********************************************************/
function exec_mail_search($post)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;

    /*���å��������*/
    $_SESSION = array();

    /*���������*/
    $data_count = 0;
    $maxsearch = 0;
    $num_key = 0;
    $num = array();
    $num_data = array();
    $session = array();
    $noqsession = array();
    $noqkey = 0;
 
    /*�����ͥ����å�*/
    $ret = check_mail_search_condition($post);
    if ($ret === FALSE) {
        return(1);
    }

    /*MySQL��³*/
    $conn = MySQL_connect_server();
    if ($conn === FALSE) {
        return(2);
    }

    /* ���������פ� "MYSQL" �ξ�� */
    if ($web_conf['sylas']['searchtype'] === MYSQL) {

        /*Mail��������SQL���������*/
        if(make_mail_search_sql($post, $search_sql, $conn) === FALSE) {
            mysqli_close($conn);
            return(1);
        }

        /*MySQL�����������*/
        $result = MySQL_exec_query($conn, $search_sql);
        if ($result === FALSE) {
            mysqli_close($conn);
            return(2);
        }

        /*MySQL����Ͽ���줿�������ơ��֥�ξ��������˳�Ǽ*/
        MySQL_get_data($result, $data);
        mysqli_close($conn);

    /* ���������פ� "elasticsearch" �ξ�� */
    } else if ($web_conf['sylas']['searchtype'] === ELASTICSEARCH) {

        /* �����롼�פ򸡺����� */
        $ret = get_loggroup($conn, $post['loggroup'], $groupdata);
        mysqli_close($conn);

        /* �����ƥ२�顼(MYSQL��³���顼) */
        if ($ret === 2) {
            result_log(OPERATION . ":NG:" . $log_msg);
            return(1);

        /* �����롼�פ�¸�ߤ����� */
        } else if ($ret === 0) {

            $gettype = "mail";
            /* elasticsearch������ǡ������� */
            $elastic_data = get_elasticdata($groupdata, $post, $gettype);

            /* �����оݤ�elasticsearch�����Ф���³�Ǥ��ʤ��ä���� */
            if ($elastic_data === NULL) {
                $err_msg = sprintf($msgarr['50000'][SCREEN_MSG], LOG_NAME_DISP);
                $log_msg = sprintf($msgarr['50000'][LOG_MSG], LOG_NAME_LOG);
                result_log(OPERATION . ":NG:" . $log_msg);
                syserr_display();
                return(1);
            }

            /* elasticserach���֤��ͤ�json�ǥ�����(Ϣ�����������) */
            $xmlarr = json_decode($elastic_data);
            /* json�ǥ����ɤ���ɬ�פ��ͤ������� */
            $data = extract_values($xmlarr);

            /* �������Ի� */
            if ($data === false) {
                return(1);
            }
        } else {
            /* ������ */
            result_log(OPERATION . ":NG:" . $log_msg);
            return(1);
        }
    }

    /*�ǡ����١����Ǥη����$maxsearch������*/
    $maxsearch = count($data);

    if ($maxsearch >= $web_conf['sylas']['maxsearchcount']) {     
        unset($data[$maxsearch -1]);
    }

    /*$session��Ǽ*/
    foreach($data as $value) {

        if (preg_match("/(^NOQUEUE): (.*)from=<(.*)> to=<(.*)> proto/", $value["Message"], $NOQID)) {
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["from"] = escape_html($NOQID[3]);
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["to"] = escape_html($NOQID[4]);
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["status"] = escape_html($NOQID[2]);
            $session[$value["FromHost"]][$NOQID[1].$noqkey]["date"] = escape_html(strtotime($value["DeviceReportedTime"]));
            $noqkey++;
       /*QID����ɽ��*/
       } elseif (preg_match("/^([A-F 0-9]*): /", $value["Message"], $QID)) {

            /*����QID�����뤫�ɤ���*/
            if (isset($session[$value["FromHost"]][$QID[1]]) === FALSE) {

                /* QID���ޤ����Ĥ���ʤ��ä��顢�����˥��å� */
                $session[$value["FromHost"]][$QID[1]]["from"] = "";
                $session[$value["FromHost"]][$QID[1]]["to"] = "";
                $session[$value["FromHost"]][$QID[1]]["status"] = "";
                $session[$value["FromHost"]][$QID[1]]["date"] = escape_html(strtotime($value["DeviceReportedTime"]));
            }

            /*from������ɽ��*/
            if (preg_match("/from=<(.*)>/", $value["Message"], $from)) {
                $session[$value["FromHost"]][$QID[1]]["from"] = escape_html($from[1]);
            }

            /*to������ɽ��*/
            if (preg_match("/to=<(.*)>, /", $value["Message"], $to)) {
                $session[$value["FromHost"]][$QID[1]]["to"] = escape_html($to[1]);
            }

            /*status������ɽ��*/
            if (preg_match("/status=(.*)/", $value["Message"], $status)) {
                $session[$value["FromHost"]][$QID[1]]["status"] = escape_html($status[1]);
            }
        }
    }

    /*�ѿ�������*/
    foreach ($session as $num_HOST => $num_val1) {
        foreach ($num_val1 as $num_qid => $num_val2) {
            $num[$num_key]["host"] = $num_HOST;
            $num[$num_key]["qid"] = $num_qid;
            $num[$num_key]["from"] = $num_val2["from"];
            $num[$num_key]["to"] = $num_val2["to"];
            $num[$num_key]["date"] = $num_val2["date"];
            $num[$num_key]["status"] = $num_val2["status"];
            $num_key++;
        }
    }

    /*�����Ƚ���*/
    foreach ($num as $key_num => $value_num) {
            $num_data[$key_num] = $value_num["date"];
    }

    /*�߽�˥�����*/
    array_multisort($num_data, SORT_DESC, $num);

    /*�����׽���*/
    foreach ($num as $key_num => $val1) {

        /*�����Ծ����׽���*/
        if ($post["sendaddr"] !== "") {
            $lpaddr = strtolower($post["sendaddr"]);
            $lladdr = strtolower($val1["from"]);

            if ($post["sendrule"] === "0") {
                if ($lpaddr !== $lladdr) {
                     continue;
                }
            } elseif (strstr($lladdr, $lpaddr) === FALSE) {
                continue;
            }
        }
 
        /*��������׽���*/
        if ($post["reciveaddr"] !== "") {
            $lpaddr = strtolower($post["reciveaddr"]);
            $lladdr = strtolower($val1["to"]);
            if ($post["reciverule"] === "0") {
                if ($lpaddr !== $lladdr) {
                    continue;
                }
            } elseif (strstr($lladdr, $lpaddr) === FALSE) {
                continue;
            }
        }

        /*���å�����Ǽ*/
        $_SESSION["result"][$data_count]["from"] = $val1["from"];
        $_SESSION["result"][$data_count]["to"] = $val1["to"];
        $_SESSION["result"][$data_count]["date"] = $val1["date"];
        $_SESSION["result"][$data_count]["status"] = $val1["status"];
        $_SESSION["result"][$data_count]["host"] = $val1["host"];
        $_SESSION["result"][$data_count]["qid"] = $val1["qid"];
        $data_count++;
    }

    /*���ɽ��*/
    if ($maxsearch <=  $web_conf['sylas']['maxsearchcount']) {
        $err_msg = sprintf($msgarr['41000'][SCREEN_MSG], $data_count);
    } else {
        $err_msg = sprintf($msgarr['41001'][SCREEN_MSG], $web_conf['sylas']['maxsearchcount'], $data_count);
    }

    return(0);
}

/*********************************************************
 * make_mail_search_sql()
 *
 * �������θ�����SQL���������
 *
 * [����]
 *       $post              POST����
 *       $sql               ���Ϥ��줿��
 *
 * [�֤���]
 *       �ʤ� 
 **********************************************************/
function make_mail_search_sql($post, &$sql, $conn)
{
    global $web_conf;
    global $msgarr;
    global $err_msg;
    global $log_msg;
    
    /*���������*/ 
    $end = "";
    $start = "";
    $check_count = $web_conf['sylas']['maxsearchcount'] + 1;
    $search_sql = array();
    $facility_arr = array();
    $app_name = "";


    $mysql_facilitynumbers = array(
                         "kern"     => 0,
                         "user"     => 1,
                         "mail"     => 2,
                         "daemon"   => 3,
                         "auth"     => 4,
                         "security" => 4,
                         "syslog"   => 5,
                         "lpr"      => 6,
                         "news"     => 7,
                         "uucp"     => 8,
                         "cron"     => 9,
                         "authpriv" => 10,
                         "ftp"      => 11,
                         "local0"   => 16,
                         "local1"   => 17,
                         "local2"   => 18,
                         "local3"   => 19,
                         "local4"   => 20,
                         "local5"   => 21,
                         "local6"   => 22,
                         "local7"   => 23);

    $sql_str = sprintf(ESL_LOGGROUP_SQL,$post["loggroup"]); 
    $ret = get_data($sql_str, $data);
    if ($ret === FALSE) {
        return FALSE;
    }
    /* �ǡ������ʤ��Ȥ��������롼�פ˸����оݥۥ��Ȥ����ꤵ��Ƥ��ʤ� */
    if (count($data) === 0) {
        $err_msg = $msgarr['28019'][SCREEN_MSG];
        $log_msg = $msgarr['28019'][LOG_MSG];
        return FALSE;
    }

    $tmp_sql = "";
   /*�ơ��֥����*/
   if ($data[0]["search_tab"] === "") {
       $tab =  mysqli_real_escape_string($conn, $web_conf["sylas"]["defaultsearchtable"]);
   } else {
       $tab = mysqli_real_escape_string($conn, $data[0]["search_tab"]);
   }

    /* ���־����ʬ */
    make_timerange_sql($conn, $post, $timerange_sql);
    $tmp_sql = "WHERE ". $timerange_sql;

    /*�ե�����ƥ�����*/
    /* �ե�����ƥ���ʸ���󤫤��ֹ���Ѵ�
    * (�ǡ����١����ˤ��ֹ����Ͽ����Ƥ���) */
    $facilitynames = explode(" ", $data[0]["facility_name"]);

    foreach ($facilitynames as $facility_num => $facility_name) {
        if (isset ($mysql_facilitynumbers["$facility_name"])){
            $facility_arr[] = "Facility=". $mysql_facilitynumbers["$facility_name"];
        }
    }
    $facility_values = implode(" OR ", $facility_arr);
    if ($facility_values !== "") {
        if ($tmp_sql != "") {
            $tmp_sql .= " AND ";
        } else {
            $tmp_sql .= " WHERE ";
        }
        $tmp_sql .= "(" . $facility_values . ")";
    }
    
   /*���ץꥱ����������*/
   if ($data[0]["app_name"] !== "") {
       if ($tmp_sql != "") {
           $tmp_sql .= " AND ";
       } else {
           $tmp_sql .= " WHERE ";
       }
       $tmp_sql .= "SysLogTag LIKE \"". mysqli_real_escape_string($conn, $data[0]["app_name"])."%\"";
   }


    foreach ($data as $one_data) {
        /* ���ƤΥۥ��Ȥ����ꤵ��Ƥ���Ȥ����ۥ��Ⱦ����ղä��ʤ� */
        if ($one_data["host_id"] === "1") {
            $tmp_sql .= "";
            break;
        }
        if ($tmp_sql != "") {
            $tmp_sql .= " AND ";
        } else {
            $tmp_sql .= " WHERE ";
        }
        $tmp_sql .= sprintf("FromHost IN (SELECT hosts.host_name From hosts JOIN search_hosts on hosts.host_id=search_hosts.host_id WHERE group_id=%s)", $post['loggroup']);
    }

   /* SQL���� */
   $sql = sprintf(MAIL_SEARCH_SQL, $tab, $tmp_sql, $check_count);

   return;
}

/*********************************************************
 * get_mailpage
 *
 * ���ڡ��������ڡ����μ���
 *
 * [����]
 *       $data_count        ɽ�����
 *       $page              �ڡ���
 *       $tag               ����
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function get_mailpage($data_count, $page, &$tag)
{
    global $web_conf;

        /* ���ɽ��(0�ڡ�����ɽ��)�ξ�硢�ä˲��⤷�ʤ� */
    if ($page === 0) {
        return;
    }
    /* ===== ������ ===== */

    /* ���ڡ����� */
    $all_page = ceil($data_count / $_POST["resultline"]);
    if ($all_page === 0) {
        $all_page = 1;
    }

    /* ���ڡ����ʾ�ο������ϤäƤ�����Ǹ�Υڡ�����ɽ�� */
    if ($page >= $all_page) {
        $page = $all_page;
    }
    /* �ǽ�Υڡ����Ǥʤ�������ڡ�����ɽ�� */
    if ($page > 1) {
        $previous_page = $page - 1;
        $tag["<<PRE>>"] = "<a href=\"#\" onClick=\"allSubmit('index.php', '$previous_page')\">���ڡ���</a>";
    }

    /* �Ǹ�Υڡ����Ǥʤ���м��ڡ�����ɽ�� */
    if ($page !== $all_page) {
        $next_page = $page + 1;
        $tag["<<NEXT>>"] = "<a href=\"#\" onClick=\"allSubmit('index.php', '$next_page')\">���ڡ���</a>";
    }
    /* �ڡ����ֹ���֤���������������� */
    $tag["<<PAGE_NUM>>"] = $page;

    return;
}

/****************************************
*             �������                  *
****************************************/

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}
$looptag = array();


/****************************************
*                main                   *
****************************************/

/* �����ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["search_button"])) {

    /*���å���󳫻�*/
    session_start();

    /*���������*/
    $data_count = 0;
    $page = 1;

    /*���å��������*/
    $ret = exec_mail_search($_POST);
    if ($ret === 2) {
        result_log(OPERATION . ":NG:" . $log_msg);
        syserr_display();
        exit(1);
    } 
    if ($ret !== 1) {

        if (isset($_SESSION["result"])) {
            /*$_SESSION����$data_count����������*/
            $data_count = count($_SESSION["result"]);
        }
        $tag["<<COMMENT_START>>"]       = "";
        $tag["<<COMMENT_END>>"]         = "";
        $tag["<<SEARCH_COUNT>>"]        = $data_count;
        
        /* �롼�ץ����κ��� */
        set_loop_tag($page, $looptag, $sesskey, $_POST);

        /* ɽ������η��� */
        get_mailpage($data_count, $page, $tag);

        /*hidden����*/
        /* ���������ݻ���hidden����� */
        make_hidden($_POST, $tag);
    }
}

/* ���ڡ����⤷���ϼ��ڡ����������줿�Ȥ� */
if (isset($_POST["next_button"])) {

    /*���å���󳫻�*/
    session_start();

    /*���������*/
    $data_count = 0;

    /* �ڡ��������ϤäƤ����Ȥ���POST���줿�ͤ���� *
     * �ϤäƤ��Ƥ��ʤ�����1�ڡ����� */
    if (isset($_POST["page"])) {
        $page = $_POST["page"];
    } else {
        $page = "1";
    } 

    if (isset($_SESSION["result"])) {
        /*$_SESSION����$data_count����������*/
        $data_count = count($_SESSION["result"]);
    }
    $tag["<<COMMENT_START>>"]       = "";
    $tag["<<COMMENT_END>>"]         = "";
    $tag["<<SEARCH_COUNT>>"]        = $data_count;

    /* �롼�ץ����κ��� */
    set_loop_tag($page, $looptag, $sesskey, $_POST);

    /* ɽ������η��� */
    get_mailpage($data_count, $page, $tag);

    /*hidden����*/
    /* ���������ݻ���hidden����� */
    make_hidden($_POST, $tag);
}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���� ���� */
$ret = set_tag_data($_POST, $tag);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, "<<STARTLOOP>>", "<<ENDLOOP>>");
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $log_msg);
    syserr_display();
    exit(1);
}

?>
