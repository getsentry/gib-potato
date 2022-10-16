<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html class="h-full">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= $this->fetch('meta') ?>
    <?= $this->Html->meta('icon') ?>
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->element('assets') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="h-full bg-gray-50 dark:text-zinc-50 text-zinc-900 dark:bg-zinc-900 font-mono">
    <?= $this->fetch('content') ?>
</body>
</html>
