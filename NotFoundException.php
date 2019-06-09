<?php

namespace mouse\container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends ContainerException  implements NotFoundExceptionInterface
{
    
}