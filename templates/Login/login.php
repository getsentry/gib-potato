<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="h-full flex items-center justify-center">
    <div class="max-w-sm text-center">
        <?= $this->Flash->render(); ?>

        <?= $this->Html->image('logo.png', ['class' => 'w-32 mb-4 mx-auto rounded-full']); ?>

        <h1 class="text-center text-3xl font-bold mb-16">
            GibPotato
        </h1>

        <?= $this->Html->link('Sign in with Slack', [
                'controller' => 'Login',
                'action' => 'startOpenId',
            ], [
                'class' => 'inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-md text-zinc-900 bg-amber-200',
            ]);
        ?>
    </div>
</div>

