<?php
/**
 * App\Model\User.
 *
 * @version 1.0.1
 */

namespace MonitoLib\App\Model;

class User extends \MonitoLib\Database\Model
{
    protected $fields = [
        'userId' => [
            'type' => 'int',
            'label' => 'User Id',
            'maxLength' => 11,
            'required' => true,
        ],
        'name' => [
            'label' => 'Name',
            'maxLength' => 11,
            'required' => true,
        ],
        'username' => [
            'label' => 'Username',
        ],
    ];

    protected $keys = ['_id'];
}
