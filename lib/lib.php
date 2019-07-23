<?php
/*
 * MyOwnFreeHost Client
 * Created by Hostronavt Team
 * https://hostronavt.ru
 * https://github.com/hostronavt
*/

require_once('reader.php');

class Panel {
    function handle($url, $data = '', $nofollow = '') {
        $urlgo = "http://panel.myownfreehost.net/".$url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlgo);
        if ($nofollow == '') {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt ($ch, CURLOPT_VERBOSE, true);
        curl_setopt ($ch, CURLOPT_ENCODING, 0);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36');
        curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt ($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($data !== '') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt ($ch, CURLOPT_COOKIEFILE, "lib/".$this->login.".php");
        curl_setopt ($ch, CURLOPT_COOKIEJAR, "lib/".$this->login.".php");
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    function __construct($login, $password) {
        $this->login = $login;
        $this->password = $password;
        $gettry = $this->handle('panel/index.php');
	$htmltry = new simple_html_dom();
        $htmltry->load($gettry);
        $this->page = $htmltry;
        if (strpos($gettry, 'Go To Login')) {
        $get = $this->handle('index.php', array('uname' => $login, 'passwd' => $password,'role' => 'administrator','submit' => 'Login'));
        preg_match_all("/\\nLocation:\\s*(.*?)\\n/i", $get, $matches);
        if(!empty($matches[1])){
	    if (substr($matches[1][0], 0, -1) === 'panel/index.php') {
	        $html = new simple_html_dom();
            $html->load($get);
            $this->page = $html;
            $status = $html->find('.username', 0)->plaintext;
            if ($status === "Administrator") {
	         return null;
            }
            else {
                throw new Exception("Error. You have to be an administrator");
            }
	    }
        }
        else {
	        throw new Exception("Error. Incorrect login and password");
	    }
        }
    }
    function getStatistics() {
       $html = $this->page;
       $this->lastdayusers = $html->find('td[plaintext="New accounts created in the last 24 hours"]', 0)->nextSibling()->plaintext;
	   $this->activeusers = $html->find('td[plaintext="Total active accounts"]', 0)->nextSibling()->plaintext;
	   $this->idleusers = $html->find('td[plaintext="Total idle accounts"]', 0)->nextSibling()->plaintext;
	   $this->monthtraffic = $html->find('td[plaintext="Total traffic this month"]', 0)->nextSibling()->plaintext;
	   $this->monthhits = $html->find('td[plaintext="Total traffic this month"]', 0)->parentNode()->nextSibling()->childNodes(1)->plaintext;
    }
    function changePassword($new) {
            if (preg_match("/^[a-zA-Z0-9]{10,}$/", $new)) {
            $this->handle("panel/index2.php?option=passwordchanger", array('old_password' => $this->password, 'new_password' => $new, 'confirm_new_password' => $new, 'submit' => 'Change Password'));
            $this->__construct($this->login, $new);
            }
            else {
                throw new Exception("Error. Password must contain only letters and numbers and be minimum of 10 characters");
            }
    }
    function findUsers($typeofsearch, $value) {
            switch ($typeofsearch) {
                case 'email':
                    $get = $this->handle("panel/index2.php?option=finder", array('email_address' => $value));
                    break;
                case 'ipaddress':
                    $get = $this->handle("panel/index2.php?option=finder", array('ipaddress' => $value));
                    break;
                case 'domain':
                    $get = $this->handle("panel/index2.php?option=finder2", array('domain_name' => $value));
                    break;
                default:
                    throw new Exception("Error. There are three types of search: email, ipaddress and domain");
            }
            $html = new simple_html_dom();
            $html->load($get);
            $trs = $html->find('tr');
            $users = array();
            if ($typeofsearch === 'domain') {
                foreach ($trs as $user) {
                    $usertext = $user->childNodes(0)->childNodes(0)->innertext;
                    $domain = $html->find('td', 1)->innertext;
                    $username = substr($usertext, 0, strpos($usertext, $domain));
                    array_push($users, $username);
                }
            }
            else {
            foreach ($trs as $user) {
                array_push($users, $user->childNodes(0)->childNodes(0)->innertext);
            }
            }
            return $users;
    }
}

class Client {
    function __construct($panel, $user) {
        $this->user = $user;
        $this->panel = $panel;
        $get = $this->panel->handle("panel/index2.php?option=drilldown&username=".$user);
        $this->page = $get;
        $html = new simple_html_dom();
        $html->load($get);
        if ($html->find('table', 0) !== null) {
         $this->status = $html->find('td[plaintext="Status"]', 0)->nextSibling()->plaintext;
        $this->domains = explode('<br>', strip_tags($html->find('td[plaintext="Domain Names"]', 0)->nextSibling(), '<br>'));
        $this->email = $html->find('td[plaintext="Email Address"]', 0)->nextSibling()->plaintext;
        $this->plan = strip_tags($html->find('option[selected]', 0));
        $this->signupDate = $html->find('td[plaintext="Signup date"]', 0)->nextSibling()->plaintext;
        $this->signupIP = $html->find('td[plaintext="Signup IP Address"]', 0)->nextSibling()->plaintext;
        $this->suspendComment = strip_tags($html->find('textarea[name="admin_comments"]', 0));
        $this->resellerComment = strip_tags($html->find('textarea[name="admin_comment"]', 0));
        }
        else {
            throw new Exception("Error. User is not found.");
        }
    }
    function connectLink() {
        if ($this->status === "Active") {
        $domain = $this->domains[0];
        $get = $this->panel->handle("panel/index2.php?option=drilldowndom&domain_name=".$domain);
        $get2 = $this->panel->handle("panel/index.php?option=connectuser", '', 'yes');
        preg_match_all("/\\nLocation:\\s*(.*?)\\n/i", $get2, $matches);
        if(!empty($matches[1])){
	    return substr($matches[1][0], 0, -1);
        }
        }
        else {
            throw new Exception("Error. The user has to be active to perform it");
        }
    }
    function changePlan($plan) {
        $this->panel->handle("panel/index.php?option=changeuserpackage", array ('user_name' => $this->user, 'plan_name' => $plan));
        $this->__construct($this->panel, $this->user);
        if ($this->plan !== $plan) {
            throw new Exception("Error. Can't change plan to this one");
        }
    }
    function displayErrors($bool = true) {
        if ($this->status === "Active") {
        if ($bool) {
            $todo = "enable";
        }
        else {
            $todo = "disable";
        }
        $domain = $this->domains[0];
        $this->panel->handle("panel/index.php?option=displayerrorsadmin&domain=".$domain."&act=".$todo);
        }
        else {
            throw new Exception("Error. The user has to be active to perform it");
        }
    }
    function showBanners($bool = true) {
        if ($this->status === 'Active') {
        $domain = $this->domains[0];
        $get2 = $this->panel->handle("panel/index2.php?option=drilldowndom&domain_name=".$domain);
        $html2 = new simple_html_dom();
        $html2->load($get2);
        $banners = current(explode(' ',$html2->find('td[plaintext="Banners for domain:"]', 0)->nextSibling()->plaintext));
        if ($banners === "DISABLE") {
            $current = true;
        }
        else {
            $current = false;
        }
        if ($current !== $bool) {
        $this->panel->handle("panel/index.php?option=fixesbanner&domain=".$domain."&act=dom");
        }
        }
        else {
            throw new Exception("Error. The user has to be active to perform it");
        }
    }
    function suspend($reason) {
        if ($this->status === 'Active') {
        $this->panel->handle("panel/index.php?option=changestatus", array ('user_name' => $this->user, 'reason' => $reason));
        $this->__construct($this->panel, $this->user);
        }
        else {
            throw new Exception("Error. The user has to be active to perform it");
        }
    }
    function unsuspend() {
        if ($this->status === 'Closed') {
        $this->panel->handle("panel/index.php?option=changestatus", array ('user_name' => $this->user));
        $this->__construct($this->panel, $this->user);
        if ($this->status === 'Closed') {
            throw new Exception("Error. Impossible to reactivate account");
        }
        }
        else {
            throw new Exception("Error. The user has to be suspended to perform it");
        }
    }
    function setResellerComment($comment) {
            $this->panel->handle("/panel/index.php?option=addadmincomment", array ('admin_comment' => $comment));
            $this->__construct($this->panel, $this->user);
    }
}
