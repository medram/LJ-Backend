<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;


// Clean up input from HTML tags.
class StripTagsRule implements DataAwareRule, InvokableRule
{
    protected $data = [];

    public function __invoke($attribute, $value, $fail)
    {
        request()->merge([$attribute => strip_tags($value)]);
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}
