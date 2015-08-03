<?php

namespace Norm\Schema;

class File extends Object
{
    public function prepare($value)
    {
        $app = \App::getInstance();

        if ($app->request->isPost() && !empty($_FILES)) {
            $fileMeta = $_FILES[$this['name']];

            $bucket = trim($this['bucket'], '/');
            $bucketPath = getcwd().'/'.$bucket;

            @mkdir($bucketPath, 0755, true);

            $fileMeta['bucket'] = $bucket;

            switch ($fileMeta['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('The uploaded file exceeds allowed file size.');
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception('The uploaded file was only partially uploaded.');
                case UPLOAD_ERR_NO_FILE:
                    $this->filter[] = 'ignoreNull';
                    return null;
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \Exception('Missing a temporary folder.');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \Exception('Failed to write file to disk.');
                case UPLOAD_ERR_EXTENSION:
                    throw new \Exception('Unknown upload error.');
            }


            unset($fileMeta['error']);

            $filename = $this['filename'];
            if (is_callable($filename)) {
                $filename = call_user_func($filename, $fileMeta);
            } else {
                throw new \Exception('Unimplemented yet!');
            }


            $fileMeta['original_name'] = $fileMeta['name'];
            $fileMeta['name'] = $filename;

            $filepath = $bucketPath.'/'.$filename;
            $tmpName = $fileMeta['tmp_name'];

            unset($fileMeta['tmp_name']);

            move_uploaded_file($tmpName, $filepath);

            $value = $fileMeta;
        }

        return parent::prepare($value);
    }

    public function formatReadonly($value, $entry = null)
    {
        return '<span class="field">'.($value['name'] ?: '-').'</span>';
    }

    public function formatInput($value, $entry = null)
    {
        return '
            <input type="hidden" name="'.$this['name'].'">
            <input type="file" name="'.$this['name'].'">
        '.@$value['name'];
    }
}
