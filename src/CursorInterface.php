<?php
namespace Norm;

use Iterator;
use Countable;
use JsonKit\JsonSerializer;

interface CursorInterface extends Iterator, Countable, JsonSerializer
{

}
