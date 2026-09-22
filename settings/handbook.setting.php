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
    'title' => E::ts('Zusätzliches Handbuch-Verzeichnis'),
    'description' => E::ts(
      'Absoluter Serverpfad zu einem Verzeichnis mit weiteren Markdown-Anleitungen. Leer lassen, wenn nur Erweiterungen Anleitungen liefern.',
    ),
    'validate_callback' => 'Civi\\Handbook\\Sources::validateExtraDir',
    'settings_pages' => ['handbook' => ['weight' => 10]],
  ],
];
