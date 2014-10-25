<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Cursor\BonoCursor;
use Guzzle\Http\Client;

class BonoConnection extends Connection
{
    /**
     * @see Norm\Connection::query()
     */
    public function query($collection, array $criteria = null)
    {
        return new BonoCursor($this->factory($collection), $criteria);
    }

    /**
     * @see Norm\Connection::persist()
     */
    public function persist($collection, array $document)
    {
        throw new \Exception(__METHOD__.' unimplemented!');
    }

    /**
     * @see Norm\Connection::remove()
     */
    public function remove($collection, $criteria = null)
    {
        throw new \Exception(__METHOD__.' unimplemented!');
    }

    public function restGet($cursor) {
        if ($cursor instanceof \Norm\Cursor) {
            $name = $cursor->getCollection()->getName();
            $criteria = $cursor->getCriteria();
            $limit = $cursor->limit();
            $skip = $cursor->skip();
            $sorts = $cursor->sort();

            $query = array();
            foreach ($criteria as $key => $value) {
                $query[$key] = $value;
            }

            if ($limit) {
                $query['!limit'] = $limit;
            }

            if ($skip) {
                $query['!skip'] = $skip;
            }

            if (!empty($sorts)) {
                foreach ($sorts as $key => $value) {
                    # code...
                    $query["!sort[$key]"] = $value;
                }
            }

            $qs = array();

            foreach ($query as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $qs[] = '!'.$key.'='.$v;
                    }
                } else {
                    $qs[] = $key.'='.$value;
                }
            }


            if ($qs) {
                $qs = '?'.implode('&', $qs);
            } else {
                $qs = '';
            }

            $url = $this->option('baseUrl').'/'.$name.'.json'.$qs;

            $client = new Client();
            $response = $client->get($url)->send();

            return json_decode($response->getBody(true), true);
        } else {
            throw new \Exception('Unimplemented yet!');
        }
    }
}