<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;

if (Configure::read('debug')) {
    echo $this->Html->script('http://localhost:5173/@vite/client', ['type' => 'module']);
    echo $this->Html->script('http://localhost:5173/frontend/src/main.js', ['type' => 'module']);
} else {
    echo $this->Vite->css('frontend/src/main.js');
    echo $this->Vite->script('frontend/src/main.js');
    echo '<script defer data-domain="gibpotato.app" src="https://plausible.io/js/script.js"></script>';
}
