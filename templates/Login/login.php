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
            Gib Potato
        </h1>

        <?= $this->Html->link('Sign in with Slack', [
                'controller' => 'Login',
                'action' => 'startOpenId',
            ], [
                'class' => 'inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 focus:ring-offset-gray-50 dark:focus:ring-offset-zinc-900',
            ]);
        ?>
    </div>
</div>

