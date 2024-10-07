<?php

include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbutils.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

http_response_code(200);
// var_dump($_FILES["files"]);
// echo "\n===========\n";
// echo "tmp_name: ", $_FILES["files"]["tmp_name"];
// echo "\n===========\n";
// echo "tmp_name, ->: ", $_FILES["files"]["tmp_name"][0];

$out = [];
$out["files"] = $_FILES;

$appid = getAppId();
$token = getToken();
$userid = getUserId($token);

$prefix = getParam("pre", "CONTENT");

$id = getUserId($token);
$content = getParam("content", "");
$title = getParam("title", "");
$type = getParam("type", 5);
$parent = getParam("parent", 0);

if (isLocalHost()) {
    $fileconfig = array(
        "target_dir" => "C:/xampp/htdocs/files/"
    );
} else {
    $fileconfig = array(
        "target_dir" => '/cairnsgames/files'
    );
}

function mime2ext($mime)
{
    $all_mimes = '{
        "png":["image\/png","image\/x-png"],
        "bmp":["image\/bmp","image\/x-bmp","image\/x-bitmap","image\/x-xbitmap","image\/x-win-bitmap","image\/x-windows-bmp",
            "image\/ms-bmp","image\/x-ms-bmp","application\/bmp","application\/x-bmp","application\/x-win-bitmap"],
        "gif":["image\/gif"],
        "jpeg":["image\/jpeg","image\/jeg","image\/pjpeg"],
        "svg":["image\/svg+xml"],
        "webp":["image/webp"],
        "3g2":["video\/3gpp2"],
        "3gp":["video\/3gp","video\/3gpp"],
        "mp4":["video\/mp4"],
        "m4a":["audio\/x-m4a"],
        "f4v":["video\/x-f4v"],
        "flv":["video\/x-flv"],
        "webm":["video\/webm"]
    }';
    $all_mimes = json_decode($all_mimes, true);
    foreach ($all_mimes as $key => $value) {
        if (array_search($mime, $value) !== false) {
            return $key;
        }
    }
    $out = array("error" => "Invalid file type", "type" => $mime, "mimetypes" => $all_mimes);
    http_response_code(200);
    die(json_encode($out));

    /*
    "xspf":["application\/xspf+xml"],
    "vlc":["application\/videolan"],
    "wmv":["video\/x-ms-wmv","video\/x-ms-asf"],
    "au":["audio\/x-au"],
    "ac3":["audio\/ac3"],
    "flac":["audio\/x-flac"],
    "ogg":["audio\/ogg",
    "video\/ogg","application\/ogg"],
    "kmz":["application\/vnd.google-earth.kmz"],
    "kml":["application\/vnd.google-earth.kml+xml"],
    "rtx":["text\/richtext"],
    "rtf":["text\/rtf"],
    "jar":["application\/java-archive","application\/x-java-application","application\/x-jar"],
    "zip":["application\/x-zip","application\/zip","application\/x-zip-compressed","application\/s-compressed","multipart\/x-zip"],
    "7zip":["application\/x-compressed"],
    "xml":["application\/xml","text\/xml"]
    "aac":["audio\/x-acc"],
    "m4u":["application\/vnd.mpegurl"],
    "pdf":["application\/pdf","application\/octet-stream"],
    "pptx":["application\/vnd.openxmlformats-officedocument.presentationml.presentation"],
    "ppt":["application\/powerpoint","application\/vnd.ms-powerpoint","application\/vnd.ms-office",
    "application\/msword"],"docx":["application\/vnd.openxmlformats-officedocument.wordprocessingml.document"],
    "xlsx":["application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet","application\/vnd.ms-excel"],
    "xl":["application\/excel"],"xls":["application\/msexcel","application\/x-msexcel","application\/x-ms-excel",
    "application\/x-excel","application\/x-dos_ms_excel","application\/xls","application\/x-xls"],
    "xsl":["text\/xsl"],"mpeg":["video\/mpeg"],"mov":["video\/quicktime"],"avi":["video\/x-msvideo",
    "video\/msvideo","video\/avi","application\/x-troff-msvideo"],"movie":["video\/x-sgi-movie"],
    "log":["text\/x-log"],"txt":["text\/plain"],"css":["text\/css"],"html":["text\/html"],
    "wav":["audio\/x-wav","audio\/wave","audio\/wav"],"xhtml":["application\/xhtml+xml"],
    "tar":["application\/x-tar"],"tgz":["application\/x-gzip-compressed"],"psd":["application\/x-photoshop",
    "image\/vnd.adobe.photoshop"],"exe":["application\/x-msdownload"],"js":["application\/x-javascript"],
    "mp3":["audio\/mpeg","audio\/mpg","audio\/mpeg3","audio\/mp3"],"rar":["application\/x-rar","application\/rar",
    "application\/x-rar-compressed"],"gzip":["application\/x-gzip"],"hqx":["application\/mac-binhex40",
    "application\/mac-binhex","application\/x-binhex40","application\/x-mac-binhex40"],
    "cpt":["application\/mac-compactpro"],"bin":["application\/macbinary","application\/mac-binary",
    "application\/x-binary","application\/x-macbinary"],"oda":["application\/oda"],
    "ai":["application\/postscript"],"smil":["application\/smil"],"mif":["application\/vnd.mif"],
    "wbxml":["application\/wbxml"],"wmlc":["application\/wmlc"],"dcr":["application\/x-director"],
    "dvi":["application\/x-dvi"],"gtar":["application\/x-gtar"],"php":["application\/x-httpd-php",
    "application\/php","application\/x-php","text\/php","text\/x-php","application\/x-httpd-php-source"],
    "swf":["application\/x-shockwave-flash"],"sit":["application\/x-stuffit"],"z":["application\/x-compress"],
    "mid":["audio\/midi"],"aif":["audio\/x-aiff","audio\/aiff"],"ram":["audio\/x-pn-realaudio"],
    "rpm":["audio\/x-pn-realaudio-plugin"],"ra":["audio\/x-realaudio"],"rv":["video\/vnd.rn-realvideo"],
    "jp2":["image\/jp2","video\/mj2","image\/jpx","image\/jpm"],"tiff":["image\/tiff"],
    "eml":["message\/rfc822"],"pem":["application\/x-x509-user-cert","application\/x-pem-file"],
    "p10":["application\/x-pkcs10","application\/pkcs10"],"p12":["application\/x-pkcs12"],
    "p7a":["application\/x-pkcs7-signature"],"p7c":["application\/pkcs7-mime","application\/x-pkcs7-mime"],
    "p7r":["application\/x-pkcs7-certreqresp"],"p7s":["application\/pkcs7-signature"],
    "crt":["application\/x-x509-ca-cert","application\/pkix-cert"],
    "crl":["application\/pkix-crl","application\/pkcs-crl"],"pgp":["application\/pgp"],
    "gpg":["application\/gpg-keys"],"rsa":["application\/x-pkcs7"],"ics":["text\/calendar"],
    "zsh":["text\/x-scriptzsh"],
    "cdr":["application\/cdr","application\/coreldraw","application\/x-cdr","application\/x-coreldraw","image\/cdr","image\/x-cdr","zz-application\/zz-winassoc-cdr"],
    "wma":["audio\/x-ms-wma"],"vcf":["text\/x-vcard"],"srt":["text\/srt"],"vtt":["text\/vtt"],"ico":["image\/x-icon","image\/x-ico","image\/vnd.microsoft.icon"],
    "csv":["text\/x-comma-separated-values","text\/comma-separated-values","application\/vnd.msexcel"],"json":["application\/json","text\/json"]
    */
}

function saveFileDetails($entity, $entityid, $filename, $originalfilename, $profileid) {
    $sql = "insert into files (entity, entityid, filename, originalfilename, profileid) values (?,?,?,?,?)";
    $params = [$entity, $entityid, $filename, $originalfilename, $profileid];
    $result = PrepareExecSQL($sql, 'sssss', $params);
    return $result;
}

if (is_uploaded_file($_FILES["files"]["tmp_name"][0])) {
    // grab MIME type.
    $mime_type = mime_content_type($_FILES["files"]["tmp_name"][0]);

    $out["mime"] = $mime_type;

    // If you want to allow certain files
    $allowed_file_types = ['image/png', 'image/jpeg'];
    
    $extension = mime2ext($mime_type); // extract extension from mime type
    if (! in_array($mime_type, $allowed_file_types)) {
        // File type is NOT allowed.
    }
}

if (!isset($_FILES['files'])) {
    $out["error"][] = 'No file uploaded.';
} else {
    // Make sure the captured data exists
    if (isset($_FILES['files']) && !empty($_FILES['files'])) {
        try {
            // Upload destination directory
            $target_dir = $fileconfig["target_dir"];
            
            // Iterate all the files and move the temporary file to the new directory
            for ($i = 0; $i < count($_FILES['files']['tmp_name']); $i++) {

                // Add your validation here
                $file = $target_dir . $_FILES['files']['name'][$i];
                

                $fname = $prefix."-".$id;
                if (!$fname == "") {
                    $fname .= "-";
                }
                
                $d = new DateTime();
                $fname .= $d->format('Y-m-d-H-i-s');
                if ($i > 0) {
                    $fname .= "-" . $i;
                }
                $file_dir = $target_dir . "/" . $fname . "." . $extension;
                $out["filename"] = $fname. "." . $extension;
                $out["filepath"] = $file_dir;

                // $out["data"] = saveFileDetails( $prefix, $id, $file_dir, $_FILES['files']['name'][$i], getUserId($token));

                // Move temporary files to new specified location
                $moved = move_uploaded_file($_FILES['files']['tmp_name'][$i], $file_dir);
                if (!$moved) {
                    $out["error"][] = 'File not uploaded.';
                } else {
                    $out["success"][] = 'File uploaded.';
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            $out["error"][] = $e->getMessage();
        }
    }
}

echo json_encode($out);
?>