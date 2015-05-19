<?php namespace Norm\Connection;

use Exception;
use Norm\Cursor;
use Norm\Connection;
use Guzzle\Http\Client;
use Norm\Cursor\BonoCursor;

/**
 * Bono Collection.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class BonoConnection extends Connection
{
    /**
     * {@inheritDoc}
     */
    public function query($collection, array $criteria = null)
    {
        return new BonoCursor($this->factory($collection), $criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function persist($collection, array $document)
    {
        throw new \Exception(__METHOD__.' unimplemented!');
    }

    /**
     * @see {@inheritDoc}
     */
    public function remove($collection, $criteria = null)
    {
        throw new \Exception(__METHOD__.' unimplemented!');
    }

    /**
     * Get data from rest service.
     *
     * @param \Norm\Cursor $cursor
     *
     * @throws \Exception
     *
     * @return array
     */
    public function restGet($cursor) {
        if ($cursor instanceof Cursor) {
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
            throw new Exception('Unimplemented yet!');
        }
    }
}
