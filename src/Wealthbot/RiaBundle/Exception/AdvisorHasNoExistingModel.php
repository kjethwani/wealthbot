<?php

namespace Wealthbot\RiaBundle\Exception;


class AdvisorHasNoExistingModel extends \Exception
{
    public function __construct($message = "Advisor does not have existing model.", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}