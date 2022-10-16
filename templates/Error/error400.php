<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Database\StatementInterface $error
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;
use Sentry\SentrySdk;

$this->layout = 'error';

if (Configure::read('debug')) :
    $this->layout = 'dev_error';

    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');

    $this->start('file');
?>
<?php if (!empty($error->queryString)) : ?>
    <p class="notice">
        <strong>SQL Query: </strong>
        <?= h($error->queryString) ?>
    </p>
<?php endif; ?>
<?php if (!empty($error->params)) : ?>
        <strong>SQL Query Params: </strong>
        <?php Debugger::dump($error->params) ?>
<?php endif; ?>
<?= $this->element('auto_table_warning') ?>
<?php

$this->end();
endif;
?>
<div class="h-full flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-2xl font-semibold">
            404 | Not Found
        </h1>
        <?php if (SentrySdk::getCurrentHub()->getLastEventId() !== null): ?>
            <p class="mt-4 text-sm text-gray-600">
                <?= SentrySdk::getCurrentHub()->getLastEventId(); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
