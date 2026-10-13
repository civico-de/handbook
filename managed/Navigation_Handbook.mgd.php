<?php

declare(strict_types = 1);

use CRM_Handbook_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_Handbook',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'name' => 'handbook_manual',
        'label' => E::ts('Handbook'),
        'url' => 'civicrm/handbook',
        'permission' => ['access handbook'],
        'parent_id.name' => 'Support',
        'is_active' => TRUE,
        'weight' => 0,
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
];
