<?php
namespace MonitoLib\App\Model;

class User extends \MonitoLib\Database\Model
{
    const VERSION = '1.0.0';

    // protected $tableName = 'orders';

    protected $fields = [
        // 'id' => [
        //     // 'name'      => '_id',
        //     'type'      => 'oid',
        //     'label'     => '#',
        //     'primary'   => true,
        //     'required'  => true,
        //     'auto'      => true,
        // ],
        'userId' => [
            'type'      => 'int',
            // 'name'      => 'entity_id',
            'label'     => 'User Id',
            'maxLength' => 11,
            'required'  => true,
        ],
        'name' => [
            // 'type'      => 'string',
            // 'name'      => 'increment_id',
            'label'     => 'Name',
            'maxLength' => 11,
            'required'  => true,
        ],
        'username' => [
            // 'name'      => 'codfilial',
            // 'type'      => 'datetime',
            'label'     => 'Username',
        ],
    ];

    protected $keys = ['_id'];
}