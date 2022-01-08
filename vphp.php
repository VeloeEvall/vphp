<?php

//DOCS Rready:
//vUI

class VPHP1_MTD extends VPHP1
{
    //BASE
    public static function vstr_contains(string $haystack, string $needle)
    {
        if (strpos($haystack, $needle) !== false) {
            return true;
        }
    }
    public static function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
    public static function VPHP_EXCLUDE($input, string $key)
    {
        if (isset($input) && isset($key)) {
            $akey = explode(';', $key);

            if (is_string($input)) {
                foreach ($akey as $skey) {
                    if (VPHP1_MTD::vstr_contains($input, $skey) != true) {
                        return $input;
                    }
                }
            } elseif (is_array($input)) {
                foreach ($akey as $skey) {
                    foreach ($input as $aline) {
                        if (VPHP1_MTD::vstr_contains($aline, $skey) == true) {
                            unset($input[array_search($aline, $input)]);
                        }
                    }
                }
                return $input;
            }
        } else {
            ErrorHandling::DisplayError("500", "[VPHP1_MTD::VPHP_EXCLUDE(...)]", "Parameters not set!");
        }
    }

}

class VPHP1_TranslationRequirements extends VPHP1_VTranslation
{
    protected static $translationLanguageContents = null;

    public static function vExtractString($string, $start, $end)
    {
        $string = " " . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return "";
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}
class VPHP1_VTranslation
{
    public static function init(String $translationsFilePath = null, int $defaultLanguageIndex = 0)
    {
        if ($translationsFilePath != null && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $translationsFilePath)) {
            $translationsContents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/' . $translationsFilePath);
            VPHP1_TranslationRequirements::$translationLanguageContents = VPHP1_TranslationRequirements::vExtractString($translationsContents, strval($defaultLanguageIndex), '/' . strval($defaultLanguageIndex));
        } else {
            ErrorHandling::DisplayError("500", "Details: ", "Invalid tranlation file.");
        }
    }
    public static function vTranslate(string $SentenceToTranslate = null)
    {
        if ($SentenceToTranslate != null) {
            if (VPHP1_TranslationRequirements::$translationLanguageContents != null) {
                echo VPHP1_TranslationRequirements::vExtractString(VPHP1_TranslationRequirements::$translationLanguageContents, $SentenceToTranslate, $SentenceToTranslate);
            } else {
                ErrorHandling::DisplayError("500", "[VPHP1.VTranslation.vTranslate]", "Please initialize vTranslate first using VPHP1.VTranslation::init(...)");
            }
        } else {
            ErrorHandling::DisplayError("500", "[VPHP1.VTranslation.vTranslate]", "Sentence not selected.");
        }
    }
}
class VPHP1
{
    /*

    ########## vGetFiles ##########

    - outFormats:
    > 0 (default) - returns php array
    > 1 - echoes json encoded string eg. ['http://example.com/root_directory/filename1.ext', 'http://example.com/root_directory/filename2.ext'...]
    > 2 - own format; eg.
    USE:   <?php VPHP1::vGetFiles('images/*{.jpg,.png,.gif}', 2, '<img src="%file_name%" width="500" height="500"><br>')?>
    PRINTS: <img src="http://example.com/root_directory/images/filename1.jpng" width="500" height="500"><br>...

     */

     /**
      * @param string $path - path from vphp1.php file
      */
    public static function vGetFiles(String $path = '/*{.jpg,.png,.gif}', int $outFormat = 0, String $ownFormat = null, string $ExcludeFromOutput = null)
    {
        $rpath = substr($path, 0,strrpos($path, '/'));
        $path = substr($path, strpos($path, '/') + 1);
        $rd = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $rpath . '/';
        if ($outFormat == 0) {
            $out = array();
            foreach (glob($path, GLOB_BRACE) as $filename) {
                $p = pathinfo($filename);
                $out[] = $rd . '/' . $p['dirname'] . '/' . $p['filename'] . '.' . $p['extension'];
            }
            return $out;
        } elseif ($outFormat == 1) {
            $out = array();
            foreach (glob($path, GLOB_BRACE) as $filename) {
                $p = pathinfo($filename);
                $out[] = $rd . '/' . $p['dirname'] . '/' . $p['filename'] . '.' . $p['extension'];
            }
            echo json_encode($out, JSON_UNESCAPED_SLASHES);
        } elseif ($outFormat == 2) {
            if ($ownFormat != null) {
                foreach (glob($path, GLOB_BRACE) as $filename) {
                    $p = pathinfo($filename);
                    $out = $rd . $p['filename'] . '.' . $p['extension'];
                    echo str_replace("%file_name%", $out, $ownFormat);
                }
            } else {
                ErrorHandling::DisplayError("500", "[VPHP1::vGetFiles(...)]", "Output format not selected.");
            }
        } else {
            ErrorHandling::DisplayError("500", "[VPHP1::vGetFiles(...)]", "Invalid output format type.");
        }
    }

    public static function vDownload(String $path = null)
    {
        if ($path != null) {
            clearstatcache();
            if (file_exists($path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                header('Content-Length: ' . filesize($path));
                header('Pragma: public');
                flush();
                readfile($path, true);
                die();
            } else {
                ErrorHandling::DisplayError("500", "[VPHP1::vDownload]", "File not found.");
            }
        }
    }

    public static function vGetDirs(String $path = '*', int $outFormat = 0, String $ownFormat = null, string $ExcludeFromOutput = null)
    {
        $dirs = array_filter(glob($path), 'is_dir');
        if ($outFormat == 0) {
            if ($ExcludeFromOutput != null) {
                return VPHP1_MTD::VPHP_EXCLUDE($dirs, $ExcludeFromOutput);
            } else {
                return $dirs;
            }
        } elseif ($outFormat == 1) {
            if ($ExcludeFromOutput != null) {
                $odirs = VPHP1_MTD::VPHP_EXCLUDE($dirs, $ExcludeFromOutput);
            }
            echo json_encode($odirs, JSON_UNESCAPED_SLASHES);
        } elseif ($outFormat == 2) {
            if ($ownFormat != null) {
                if ($ExcludeFromOutput != null) {
                    $odirs = VPHP1_MTD::VPHP_EXCLUDE($dirs, $ExcludeFromOutput);
                }
                foreach ($odirs as $out) {
                    echo str_replace("%dir_name%", $out, $ownFormat);
                }
            } else {
                ErrorHandling::DisplayError("500", "[VPHP1::vGetDirs]", "Output format not selected.");
            }
        } else {
            ErrorHandling::DisplayError("500", "[VPHP1::vGetDirs]", "Invalid output format type.");
        }
    }

}

class VPHP1_vDatabaseRequirements extends VPHP1_vDB
{
    public static $servername = null;
    public static $username = null;
    public static $password = null;
    public static $dbname = null;

    public static $conn = null;
}

class VPHP1_vDB
{

    /**
     * Login to mysql server and test connection
     * @param string $Host - mysql server addres
     * @param string $User - login username
     * @param string $Pass - login password
     * @param string $DatabaseName - name of the database if one exists
     * @return true if operation successful
     * @access public
     * @since Method available since Release 1.0
     */
    public static function CreateConnection(string $Host = null, string $User = null, string $Pass = null, string $DatabaseName = null)
    {
        if ($Host != null && $User != null && $Pass != null) {
            if (isset($DatabaseName)) {VPHP1_vDatabaseRequirements::$conn = mysqli_connect($Host, $User, $Pass, $DatabaseName);} else {VPHP1_vDatabaseRequirements::$conn = mysqli_connect($Host, $User, $Pass);}
            if (!VPHP1_vDatabaseRequirements::$conn) {
                ErrorHandling::DisplayError(mysqli_connect_errno(), "Details: ", mysqli_connect_error());
                exit();
            } else {
                VPHP1_vDatabaseRequirements::$servername = $Host;
                VPHP1_vDatabaseRequirements::$username = $User;
                VPHP1_vDatabaseRequirements::$password = $Pass;
                if (isset($DatabaseName)) {VPHP1_vDatabaseRequirements::$dbname = $DatabaseName;}
                mysqli_close(VPHP1_vDatabaseRequirements::$conn);
                return true;
            }
        } else {
            ErrorHandling::DisplayError("500", "[VPHP1_vDB::CreateConnection(...)]", "Please specify host, user and pass. Database is optional.");
        }
    }

    /**
     * Create database if not exists
     * @param string $DatabaseName - name of the database to create
     * @return true if operation successful
     * @access public
     * @since Method available since Release 1.0
     */
    public static function CreateDatabase(string $DatabaseName)
    {
        if (isset($DatabaseName)) {
            if (!self::OpenConnection()) {exit();}
            $sql = "CREATE DATABASE " . $DatabaseName;
            if (mysqli_query(VPHP1_vDatabaseRequirements::$conn, $sql)) {
                return true;
            } else {
                ErrorHandling::DisplayError("500", "Details: ", mysqli_error(VPHP1_vDatabaseRequirements::$conn));
                exit();
            }
            self::CloseConnection();
        } else {
            ErrorHandling::DisplayError("500", "[VPHP1_vDB::CreateDatabase]", "Database name not selected.");
        }
    }

    /**
     * Create new table
     * @param string $TableName - name of the table to create
     * @param string $TableContent - optional content to put into table
     * @return true if operation successful
     * @access public
     * @since Method available since Release 1.0
     */
    public static function CreateTable(string $TableName, string $TableContent)
    {
        if (isset($TableName)) {
            if (!self::OpenConnection()) {exit();}
            if (!self::TestDBConn()) {exit();}

            if (isset($TableContent)) {$sql = "CREATE TABLE " . $TableName . " (" . $TableContent . ")";} else { $sql = "CREATE TABLE " . $TableName;};
            if (mysqli_query(VPHP1_vDatabaseRequirements::$conn, $sql)) {
                return true;
            } else {
                ErrorHandling::DisplayError("500", "Details: ", mysqli_error(VPHP1_vDatabaseRequirements::$conn));
            }
            self::CloseConnection();
        } else {
            throw new Exception("Enter new table name.", 1);
        }
    }

    public static function InsertData(string $TableName, string $RowsToInsertTo, string $ValuesToInsert)
    {
        if (isset($TableName) && isset($RowsToInsertTo) && isset($ValuesToInsert)) {
            if (!self::OpenConnection()) {exit();}
            if (!self::TestDBConn()) {exit();}
            $sql = "INSERT INTO " . $TableName . " (" . $RowsToInsertTo . ") VALUES (" . $ValuesToInsert . ")";
            if (mysqli_query(VPHP1_vDatabaseRequirements::$conn, $sql)) {
                return true;
            } else {
                ErrorHandling::DisplayError("500", "Error: " . $sql, mysqli_error(VPHP1_vDatabaseRequirements::$conn));
            }
            self::CloseConnection();
        } else {
            throw new Exception("Select table name, where to insert values and values to insert.", 1);
        }
    }

    public static function GetAllData(string $TableName, string $RowsToGet)
    {
        if (isset($TableName) && isset($RowsToGet)) {
            if (!self::OpenConnection()) {exit();}
            if (!self::TestDBConn()) {exit();}
            $sql = "SELECT " . $RowsToGet . " FROM " . $TableName;
            $result = mysqli_query(VPHP1_vDatabaseRequirements::$conn, $sql);
            $fres = [];
            if (mysqli_num_rows($result) > 0) {
                foreach (mysqli_fetch_all($result, MYSQLI_BOTH) as $arrkey) {
                    $fres[] = $arrkey[0];
                }
                return $fres;
            } else {
                return "No results.";
            }
            self::CloseConnection();
        } else {
            throw new Exception("Select table name and values to get.", 1);
        }
    }

    public static function GetDataWhere(string $TableName, string $RowsToGet, string $where)
    {
        if (isset($TableName) && isset($RowsToGet) && isset($where)) {
            if (!self::OpenConnection()) {exit();}
            if (!self::TestDBConn()) {exit();}
            $sql = "SELECT " . $RowsToGet . " FROM " . $TableName . " WHERE " . $where;
            $result = mysqli_query(VPHP1_vDatabaseRequirements::$conn, $sql);
            $fres = [];
            if (mysqli_num_rows($result) > 0) {
                foreach (mysqli_fetch_all($result, MYSQLI_BOTH) as $arrkey) {
                    $fres[] = $arrkey[0];
                }
                return $fres;
            } else {
                return "No results.";
            }
            self::CloseConnection();
        } else {
            throw new Exception("Select table name and values to get.", 1);
        }
    }

    /**
     * Deletes data from table
     * @param string $TableName name of the table to delete data from
     * @param string $key Name of the row
     * @param string $keyValue Value of the key
     * @param mixed $DOCS Please refer to vPHP Documentation for any help and examples.
     * @return true if operation successful
     * @access private
     * @since Method available since Release 1.0
     */
    public static function DeleteData(string $TableName, string $key, string $keyValue)
    {
        if (isset($TableName) && isset($key) && isset($keyValue)) {
            if (!self::OpenConnection()) {exit();}
            if (!self::TestDBConn()) {exit();}

            $sql = "DELETE FROM " . $TableName . " WHERE " . $key . "=" . $keyValue;

            if (mysqli_query(VPHP1_vDatabaseRequirements::$conn, $sql)) {
                return true;
            } else {
                ErrorHandling::DisplayError("500", "Details: ", mysqli_error(VPHP1_vDatabaseRequirements::$conn));
            }

            self::CloseConnection();
        } else {
            throw new Exception("Select table name and values to get.", 1);
        }
    }

    /**
     * Opens connection required by VPHP1_vDB
     * @param mixed $DOCS Please refer to vPHP Documentation for any help and examples.
     * @access private
     * @since Method available since Release 1.0
     */
    private static function OpenConnection()
    {
        if (isset(VPHP1_vDatabaseRequirements::$servername) && isset(VPHP1_vDatabaseRequirements::$username) && isset(VPHP1_vDatabaseRequirements::$password)) {
            if (isset(VPHP1_vDatabaseRequirements::$dbname)) {
                VPHP1_vDatabaseRequirements::$conn = mysqli_connect(VPHP1_vDatabaseRequirements::$servername, VPHP1_vDatabaseRequirements::$username, VPHP1_vDatabaseRequirements::$password, VPHP1_vDatabaseRequirements::$dbname);
            } else {
                VPHP1_vDatabaseRequirements::$conn = mysqli_connect(VPHP1_vDatabaseRequirements::$servername, VPHP1_vDatabaseRequirements::$username, VPHP1_vDatabaseRequirements::$password);
            }
            if (!VPHP1_vDatabaseRequirements::$conn) {throw new Exception("Can't estabilish connection!", 1);}
            return true;
        } else {
            throw new Exception("Create connection first using VPHP1_vDB::CreateConnection(...)", 1);
        }
    }
    /**
     * Closes connection after executinf methonds by VPHP1_vDB
     * @param mixed $DOCS Please refer to vPHP Documentation for any help and examples.
     * @access private
     * @since Method available since Release 1.0
     */
    private static function CloseConnection()
    {
        mysqli_close(VPHP1_vDatabaseRequirements::$conn);
    }
    private static function TestDBConn()
    {
        if (!isset(VPHP1_vDatabaseRequirements::$dbname)) {
            throw new Exception("Database not selected!", 1);
            exit();
        } else {return true;}
    }

}

/**
 * Display vPHP Error in custo html style.
 */
class ErrorHandling
{public static function DisplayError(string $ErrorCode, string $ErrorMessage, string $ErrorDescription)
    {echo "<style>*{transition:all .6s}html{height:100%}body{font-family:Lato,sans-serif;color:#888;margin:0}#main{display:table;width:100%;height:100vh;text-align:center}.fof{display:table-cell;vertical-align:middle}.fof h1{font-size:50px;display:inline-block;padding-right:12px;animation:type .5s alternate infinite}@keyframes type{from{box-shadow:inset -3px 0 0 #888}to{box-shadow:inset -3px 0 0 transparent}}</style>";
    echo '<div id="main"><div class="fof"><h1>Error ', $ErrorCode, '</h1><h2>', $ErrorMessage, '</h2><h3>', $ErrorDescription, '</h3></div></div>';exit();}}

/**
 * Navbar constant definitions
 */
class Navbar_Position extends vUI
{
    public const TOP = 0;
    public const LEFT = 1;
    public const RIGHT = 2;
    public const FULLSCREEN_LEFT = 3;
    public const FULLSCREEN_RIGHT = 4;
}

class Navbar extends vUI
{

    public function __construct(string $stylePath, string $styleName)
    {
        vUI::LoadControls($stylePath, $styleName);
    }

    /**
     * Renders navbar and generates style code.
     * @param int $nv_pos Navbar position. Use Navbar_Position::...
     * @param string $BackgroundColor Background color of navbar.
     * @param string $foregroundColor Font color of navbar's content.
     * @param array $menuItems Actual navbar content.
     * @param mixed $DOCS Please refer to vPHP Documentation for any help and examples.
     * @access public
     * @since Method available since Release 1.0
     */
    public function Render(int $nv_pos,
        string $BackgroundColor,
        string $foregroundColor,
        array $menuItems
    ) {
        return true;
    }
}

class Button extends vUI
{

    public function __construct(string $stylePath, string $styleName)
    {
        vUI::LoadControls($stylePath, $styleName);
    }

    /**
     * Renders simple button and generates style code.
     * @param string $Text The text of the button.
     * @param mixed $DOCS Please refer to vPHP Documentation for any help and examples.
     * @access public
     * @since Method available since Release 1.0
     */
    public function Render(string $Text)
    {
        echo '<button>', $Text, '</button>';
    }
}

class vUI
{
    public static $stylePath = null;

    protected static function LoadControls(string $stylePath, string $styleName)
    {
        if (self::$stylePath == null) {
            self::$stylePath = $stylePath . $styleName . '.css';
            echo '<link rel="stylesheet" href="', self::$stylePath, '">';
            if (!file_exists(self::$stylePath)) {
                $initialString = "/*\r\n" . "vUI Stylesheet generated on: " . date('Y-m-d H:i:s') . "\r\nVersion:1" . "\r\n*/" . "\r\n" . ".vui { padding: 0; }";
                $styleFile = fopen(getcwd() . "/" . self::$stylePath, "w");
                fwrite($styleFile, $initialString);
                fclose($styleFile);
            }
        }
    }

    public static function vcss_init()
    {
        echo '<script>var s=document.styleSheets[0];function changeStylesheetRule(e,t,o,l){t=t.toLowerCase(),o=o.toLowerCase(),l=l.toLowerCase();for(var r=0;r<s.cssRules.length;r++){var n=s.cssRules[r];if(n.selectorText===t)return void(n.style[o]=l)}e.insertRule(t+" { "+o+": "+l+"; }",0)}console.log(s);</script>';
    }
    public static function vcss_Rule(string $content, bool $includeSTag = false)
    {
        if ($includeSTag) {echo '<script>changeStylesheetRule(s, ', $content, ');</script>';} else {echo 'changeStylesheetRule(s, ', $content, ');';}
    }
    public static function vcss_MultipleRules(string $content, bool $includeSTag = false)
    {
        $contentArr = explode(";", $content);
        if ($includeSTag) {
            echo '<script>';
            foreach ($contentArr as $cntarr) {
                echo 'changeStylesheetRule(s, ', trim($cntarr), ');';
            }
            echo '</script>';
        }
    }
}