<?php
namespace FApi;

use FApi\traits\Instance;
use FApi\traits\Jump as jumpTraits;

/**
 * 跳转服务实例
 */
class Jump
{
    use Instance, jumpTraits;
}