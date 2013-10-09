<?php 

namespace JsonKit;

class JsonKit {

	public static function replaceObject($data) {
        if (is_object($data)) {
            if ($data instanceof \JsonKit\JsonSerializer) {
                return $data->jsonSerialize();
            }
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::replaceObject($value);
            }
        }

        return $data;
    }

    public static function encode($data){
        $jsonData = self::replaceObject($data);
        return json_encode($jsonData);
    }
}