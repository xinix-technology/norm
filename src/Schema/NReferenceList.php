<?php

// namespace Norm\Schema;

// use ArrayAccess;
// use Norm\Type\ArrayList;

// class NReferenceList extends NReference
// {
//     public function execPrepare($value)
//     {
//         if (is_string($value)) {
//             $value = json_decode($value, true);
//         }

//         if (is_array($value) || $value instanceof ArrayAccess) {
//             $newValue = [];
//             foreach ($value as $k => $v) {
//                 $newValue[] = parent::prepare($v);
//             }
//             $value = $newValue;
//         }

//         return new ArrayList($value);
//     }
// }
