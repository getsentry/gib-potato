<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;

if (Configure::read('debug')) {
    echo $this->Html->script('https://localhost:5173/@vite/client', ['type' => 'module']);
    echo $this->Html->script('https://localhost:5173/frontend/src/main.ts', ['type' => 'module']);
} else {
    echo $this->Vite->css('frontend/src/main.ts');
    echo $this->Vite->script('frontend/src/main.ts');
}