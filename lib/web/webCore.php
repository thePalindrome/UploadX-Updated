<?php

/*

@author: Pips

@title: Web Core.
@desc: Class that handles all web-based requests, such as administrator settings and file viewing.

*/


class webCore
{
    
    protected $settingsHandler;
    protected $userHandler;
    protected $errorHandler;
    protected $fileHandler;
    
    function __construct()
    {
        
        $this->errorHandler    = new errorHandler();
        $this->settingsHandler = new settingsHandler();
        $this->userHandler     = new userHandler();
        $this->fileHandler     = new fileHandler();
        
        
    }
    
    function process()
    {
        
        
        $action = $_POST['action'];
        
        if (($action == 'createuser')) {
            
            $this->userHandler->createUser($_POST['username']);
            $this->refreshPage();
            
        } 
		
		else if ($action == 'addextension') {
            
            $this->settingsHandler->addExtension($_POST['extension']);
            $this->refreshPage();
            
        }
		
		else if ($action == 'deleteextension'){
			
			$this->settingsHandler->deleteExtension($_POST['extension']);
			$this->refreshPage();
		}
        
        else if ($action == 'changekey') {
            
            $this->userHandler->changeKey($_POST['username'], $_POST['key']);
            $this->refreshPage();
            
        }
        
        else if ($action == 'newkey') {
            
            $this->userHandler->newKey($_POST['username']);
            $this->refreshPage();
            
        }

        else if ($action == 'deleteuser') {
            
            $this->userHandler->deleteUser($_POST['username']);
            $this->refreshPage();
            
        }
        
        else if ($action == 'login') {
            
            $submitted_pw = $_POST['password'];
            $server_hash = $this->settingsHandler->getSettings()['security']['password_hash'];
            
            if (password_verify($submitted_pw, $server_hash)) {
                
                $_SESSION['loggedin'] = true;
                $this->refreshPage();
                
            } else {
                
                $this->refreshPage();
                
            }
            
        }
        
        else if ($action == 'logout') {
            
            $_SESSION['loggedin'] = false;
            $this->refreshPage();
            
        }
        
        else if ($action == 'changepassword') {
            
            $this->settingsHandler->changePassword($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password']);
			$this->refreshPage();
			
        } 
		
		else if (isset($_POST['changesettings'])) {
            
            /*
            
            we have these weird if statments to check the state of a checkbox.
            It doesn't reuturn true or false, it returns "checked" and null. Thanks, html.
            
            */
            
            
            if (!isset($_POST['show_uploader']))
                $_POST['show_uploader'] = false;
            else
                $_POST['show_uploader'] = true;
            
            if (!isset($_POST['show_views']))
                $_POST['show_views'] = false;
            else
                $_POST['show_views'] = true;
            
            if (!isset($_POST['show_ip']))
                $_POST['show_ip'] = false;
            else
                $_POST['show_ip'] = true;
            
            $this->settingsHandler->changeSetting('viewer', 'show_uploader', $_POST['show_uploader']);
            $this->settingsHandler->changeSetting('viewer', 'show_views', $_POST['show_views']);
            $this->settingsHandler->changeSetting('viewer', 'show_ip', $_POST['show_ip']);
            $this->settingsHandler->changeSetting('viewer', 'theme', $_POST['theme']);
            
            $this->settingsHandler->changeSetting('security', 'storage_folder', $_POST['save_location']);
            
            $this->settingsHandler->changeSetting('generator', 'characters', $_POST['generator_legnth']);
            
            $this->refreshPage();
            
        }
        
        else if ($action == "deletefile") {
            
            $this->fileHandler->deleteFile($_POST['id']);
            $this->refreshPage();    
            
        }
        
		else if ($action == 'generatejson'){
			
			$this->userHandler->generateJson($_POST['username']);
			
		}
		
		else if ($action == 'enable'){
			
			if (!isset($_POST['enabled']))
                $_POST['enabled'] = false;
            else
                $_POST['enabled'] = true;
			
			
			$this->userHandler->enableUser($_POST['username'], $_POST['enabled']);
			$this->refreshPage();
			
		}
        
        else if (isset($_POST['fixfiles'])){
			
			$this->fileHandler->fixFiles();
			
		}
		
		else {
            echo "unknown action: $action";
        }
		
		# refresh page
        
    }
    
    function buildPage($page, $option)
        
    {
        
        
        $theme       = $this->settingsHandler->getSettings()['viewer']['theme'];
        
        include_once $GLOBALS['dir'] . '/lib/templates/admin/default_header.php';
        
        if (!$_SESSION['loggedin']) {
            
            $title = 'Login page';
            
            include_once $GLOBALS['dir'] . '/lib/templates/admin/login.php';
            
            
        } else {
            
            if ($page == 'home') {
                
                $title = "Admin Panel";
                include_once $GLOBALS['dir'] . '/lib/templates/admin/main.php';
              
            } else if ($page == 'settings') {
                
                $title = 'Settings';
                include_once $GLOBALS['dir'] . '/lib/templates/admin/settings.php';
                
            } else if ($page == 'users') {
                
                $title = 'Users';
                include_once $GLOBALS['dir'] . '/lib/templates/admin/users.php';
                
            } else if ($page == 'uploads') {
                
                $title = 'Uploads';
                include_once $GLOBALS['dir'] . '/lib/templates/admin/uploads.php';
                
            } else if ($page == 'logout') {
                
                 $_SESSION['loggedin'] = false;
                header("Location: ./");
                
            }
        }
        
        
    }
    
function filesizeConvert($bytes)
{
    $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

    foreach($arBytes as $arItem)
    {
        if($bytes >= $arItem["VALUE"])
        {
            $result = $bytes / $arItem["VALUE"];
            $result = str_replace(".", "." , strval(round($result, 2)))." ".$arItem["UNIT"];
            break;
        }
    }
    return $result;
}
    
    
    // this build's the file viewer/preview based on GET headers.
    function buildPreview()
    {
        
        $id          = $_GET['id'];
        $file_data   = $this->fileHandler->getFileData($id);
        $views       = $file_data['access_count'];
        $src         = $GLOBALS['home'] . $id . '/view'; // file source location ( + /view). Use this for actual linking
        $type        = $file_data['type']; // filetype in MIME. THere's some extra code to figure this out.
        $uploader    = $file_data['uploader']; // the file uploader. Not an object, just a piece of text. 
        $uploader_ip = $file_data['uploader_ip']; // IP of the uploader. 
        $upload_time = $file_data['upload_time'];
        $file_name   = $file_data['old_name'];
        $is_admin    = $_SESSION['loggedin']; //is admin. This is used in the bottom half of frame.php
        $title       = $id;
        $file_size   = $file_data['filesize'];
        $theme       = $this->settingsHandler->getSettings()['viewer']['theme'];
        
        $show = true; // top half, used in frame.php. Need a better way of doing this
        
        include_once $GLOBALS['dir'] . '/lib/templates/admin/default_header.php';
        
        // stupid way of showing the top half and bottom half of the frame.
        include $GLOBALS['dir'] . '/lib/templates/frame/frame.php';
        
        if (strpos($type, 'image') !== false) {
            
            include $GLOBALS['dir'] . '/lib/templates/viewer/image.php';
            
        } else if (strpos($type, 'text') !== false) {
            
            include $GLOBALS['dir'] . '/lib/templates/viewer/text.php';
            
        } else if (strpos($type, 'video') !== false) {
            
            include $GLOBALS['dir'] . '/lib/templates/viewer/video.php';
            
        } else if (strpos($type, 'audio') !== false) {
            
            include $GLOBALS['dir'] . '/lib/templates/viewer/audio.php';
            
        }
        
        else {
            include $GLOBALS['dir'] . '/lib/templates/viewer/unknown.php';
        }
        
        $show = false;
        // stupid way of showing the top half and bottom half of the frame.
        include $GLOBALS['dir'] . '/lib/templates/frame/frame.php';
        
        
    }
    
    function refreshPage()
    {
        
        header("Location: ./");
        
    }
    
    
    
    
}
// thans php comments section


?>
