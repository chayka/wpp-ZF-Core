<?php

class ZFCore_UploadController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }
    
    public function indexAction(){
    }
    
    public function attachmentAction(){
//        JsonHelper::respond($_FILES);
        Util::turnRendererOff();
        $error = "";
        $message = "";
        $fileElementName = 'file';
        if (!empty($_FILES[$fileElementName]['error'])) {
            switch ($_FILES[$fileElementName]['error']) {

                case '1':
                    $error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                    break;
                case '2':
                    $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                    break;
                case '3':
                    $error = 'The uploaded file was only partially uploaded';
                    break;
                case '4':
                    $error = 'No file was uploaded.';
                    break;

                case '6':
                    $error = 'Missing a temporary folder';
                    break;
                case '7':
                    $error = 'Failed to write file to disk';
                    break;
                case '8':
                    $error = 'File upload stopped by extension';
                    break;
                case '999':
                default:
                    $error = 'No error code avaiable';
            }
        } elseif (empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none') {
            $error = 'No file was uploaded..';
        } else {
//            $message .= " File Name: " . $_FILES[$fileElementName]['name'] . ", ";
//            $message .= " File Size: " . @filesize($_FILES[$fileElementName]['tmp_name']);

            if (!function_exists('wp_handle_upload')) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
            $uploadedfile = $_FILES[$fileElementName];
            $formats = array('jpg', 'jpe', 'jpeg', 'gif', 'png', 'zip', 'rar',
                'doc', 'docx', 'xsl', 'xlsx', 'ppt', 'pptx');
            if(preg_match('%\.([^.]+)$%', $uploadedfile['name'], $m) 
            && !in_array(strtolower($m[1]), $formats)){
                JsonHelper::respond(array(), 'invalid_format');
            }
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile) {
                if (!empty($movefile['error'])) {
                    JsonHelper::respond(array(), 'file_upload', $movefile['error']);
                } else {
                    $wp_upload_dir = wp_upload_dir();
                    $filename = $movefile['file'];
                    $attachment = array(
                        'guid' => $movefile['url'],
                        'post_mime_type' => $movefile['type'],
                        'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $filename, 0);
                    // you must first include the image.php file
                    // for the function wp_generate_attachment_metadata() to work
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    
                    $attachment = PostModel::selectById($attach_id);
                    $attachment->loadImageData();
                    JsonHelper::respond($attachment);
                }
            } else {
//                echo "Possible file upload attack!\n";
            }
            //for security reason, we force to remove all uploaded file
            @unlink($_FILES[$fileElementName]['tmp_name']);
        }
        JsonHelper::respond(null, $_FILES[$fileElementName]['error'] ? 'file_upload_' . $_FILES[$fileElementName]['error'] : 0, $error ? $error : $message);
    }
}