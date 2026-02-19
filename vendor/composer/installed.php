<?php return array(
    'root' => array(
        'name' => 'ebics-api/ebics-api-wordpress-plugin',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'ebics-api/ebics-api-client-php' => array(
            'pretty_version' => '1.0.3',
            'version' => '1.0.3.0',
            'reference' => '0a44a64f036f77094cd7b23730a287bac463b8c4',
            'type' => 'library',
            'install_path' => __DIR__ . '/../ebics-api/ebics-api-client-php',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'ebics-api/ebics-api-wordpress-plugin' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
