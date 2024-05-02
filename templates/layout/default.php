<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html class="h-full min-w-[320px]">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= $this->Sentry->sentryTracingMeta() ?>
    <?php // $this->Sentry->sentryBaggageMeta() ?>
    <?= $this->fetch('meta') ?>
    <?= $this->Html->meta('icon') ?>
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->element('assets') ?>
    <?= $this->fetch('script') ?>
    <?= $this->fetch('css') ?>
</head>
<body
    class="h-full bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 font-mono"
    data-sentry-frontend-dsn="<?= env('SENTRY_FRONTEND_DSN') ?>"
    data-sentry-environment="<?= env('ENVIRONMENT') ?>"
    data-sentry-release="<?= env('RELEASE') ?>"
    data-username="<?= h($this->Identity->get('slack_name')) ?>"
>
    <?= $this->fetch('content') ?>
    <?= $this->element('footer') ?>
</body>
</html>
