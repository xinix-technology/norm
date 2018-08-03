<?php

// namespace Norm\Schema;

// use ArrayAccess;
// use Norm\Collection;
// use Norm\Exception\NormException;

// class NReference extends NField
// {
//     protected $cache;

//     protected $fetcher;

//     public function __construct(string $name)
//     {
//         parent::__construct($name, $format, $attributes);

//         $this['nocache'] = $this['nocache'] ?: false;
//     }

//     public function fetch($key = null, $offset = 0, $limit = 100)
//     {
//         $fetcher = $this->fetcher;
//         if ($this['nocache']) {
//             return $fetcher($key, $offset, $limit);
//         } else {
//             if (null === $this->cache) {
//                 $this->cache = $fetcher();
//             }

//             if (null === $key) {
//                 return $this->cache;
//             } else {
//                 return isset($this->cache[$key]) ? $this->cache[$key] : null;
//             }
//         }
//     }

//     public function to($foreign)
//     {
//         if (is_string($foreign)) {
//             $query = [];
//             @list($meta, $qs) = explode('?', $foreign, 2);
//             parse_str($qs, $query);
//             @list($col, $params) = explode(':', $meta, 2);
//             @list($key, $label) = explode(',', $params);

//             $this['to$collection'] = trim($col);
//             $this['to$key'] = trim($key) ?: '$id';
//             $this['to$label'] = trim($label) ?: '';

//             if (isset($query['!sort'])) {
//                 $this['to$sort'] = $query['!sort'];
//                 unset($query['!sort']);
//             }
//             if (!empty($query)) {
//                 $this['to$criteria'] = $query;
//             }

//             $this->fetcher = [$this, 'normFetcher'];
//         } elseif (is_array($foreign)) {
//             $this['nocache'] = false;
//             $this->cache = $foreign;
//         } elseif (is_callable($foreign)) {
//             $this->fetcher = $foreign;
//         } else {
//             throw new NormException('Foreign must be instance of string, array, callable or Collection');
//         }
//     }

//     protected function normFetcher($key = null, $offset = 0, $limit = 100)
//     {
//         $col = $this->collection->factory($this['to$collection']);
//         $cursor = $this->collection->factory($this['to$collection'])
//             ->find($this['to$criteria'])
//             ->skip($offset)
//             ->limit($limit);

//         if ($this['to$sort']) {
//             $cursor->sort($this['to$sort']);
//         }

//         if (null === $key) {
//             $result = [];
//             foreach ($cursor->toArray() as $entry) {
//                 $result[$entry[$this['to$key']]] = $entry;
//             }
//             return $result;
//         } else {
//             return $cursor->first();
//         }
//     }

//     public function execPrepare($value)
//     {
//         $value = $value ?: null;

//         if (is_array($value) || $value instanceof ArrayAccess) {
//             if (null !== $this['to$key'] && isset($value[$this['to$key']])) {
//                 return $value[$this['to$key']];
//             } else {
//                 throw new NormException('Unable to get reference key from value');
//             }
//         } else {
//             return $value;
//         }
//     }
// }
