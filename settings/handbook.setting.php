<?php

declare(strict_types = 1);

use CRM_Handbook_ExtensionUtil as E;

return [
  'handbook_extra_dir' => [
    'name' => 'handbook_extra_dir',
    'type' => 'String',
    'html_type' => 'text',
    'default' => '',
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Extra handbook directory'),
    'description' => E::ts(
      'Absolute server path to a directory of further Markdown manuals. Leave it empty if only extensions ship manuals.',
    ),
    'validate_callback' => 'Civi\\Handbook\\ExtraDir::validate',
    'settings_pages' => ['handbook' => ['weight' => 10]],
  ],
];
