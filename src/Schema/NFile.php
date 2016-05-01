<?php

namespace Norm\Schema;

use Norm\Exception\NormException;
use Norm\Schema;
use Norm\Type\File;

class NFile extends NField
{
    public function prepare($value)
    {
        if (null === $value) {
            // return null;
        } elseif ($value instanceof File) {
            if ($value->getBaseDirectory() !== $this['dataDir']) {
                throw new NormException('Incompatible file');
            }
            return $value;
        } elseif (is_string($value)) {
            return new File($this['dataDir'], $value);
        // } else {
        //     throw new NormException('Unimplemented yet');
        }

        return null;

        // $app = App::getInstance();

        // if ($app->request->isPost() && !empty($_FILES)) {
        //     $fileMeta = $_FILES[$this['name']];

        //     $bucket = trim($this['bucket'], '/');
        //     $bucketPath = getcwd().'/'.$bucket;

        //     @mkdir($bucketPath, 0755, true);

        //     $fileMeta['bucket'] = $bucket;

        //     switch ($fileMeta['error']) {
        //         case UPLOAD_ERR_INI_SIZE:
        //         case UPLOAD_ERR_FORM_SIZE:
        //             throw new Exception('The uploaded file exceeds allowed file size.');
        //         case UPLOAD_ERR_PARTIAL:
        //             throw new Exception('The uploaded file was only partially uploaded.');
        //         case UPLOAD_ERR_NO_FILE:
        //             $this->filter[] = 'ignoreNull';
        //             return null;
        //         case UPLOAD_ERR_NO_TMP_DIR:
        //             throw new Exception('Missing a temporary folder.');
        //         case UPLOAD_ERR_CANT_WRITE:
        //             throw new Exception('Failed to write file to disk.');
        //         case UPLOAD_ERR_EXTENSION:
        //             throw new Exception('Unknown upload error.');
        //     }


        //     unset($fileMeta['error']);

        //     $filename = $this['filename'];
        //     if (is_callable($filename)) {
        //         $filename = call_user_func($filename, $fileMeta);
        //     } else {
        //         throw new Exception('Unimplemented yet!');
        //     }


        //     $fileMeta['original_name'] = $fileMeta['name'];
        //     $fileMeta['name'] = $filename;

        //     $filepath = $bucketPath.'/'.$filename;
        //     $tmpName = $fileMeta['tmp_name'];

        //     unset($fileMeta['tmp_name']);

        //     move_uploaded_file($tmpName, $filepath);

        //     $value = $fileMeta;
        // }

        // return parent::prepare($value);
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->render('__norm__/nfile/readonly', [
            'self' => $this,
            'value' => $value,
            'model' => $model,
        ]);
    }

    protected function formatInput($value, $model = null)
    {
        return $this->render('__norm__/nfile/input', [
            'self' => $this,
            'value' => $value,
            'model' => $model,
        ]);
    }
}
