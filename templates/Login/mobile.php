<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="h-full flex items-center justify-center">
    <div class="max-w-sm text-center">
        <?= $this->Html->image('logo.png', ['class' => 'w-32 mb-4 mx-auto rounded-full']); ?>

        <h1 class="text-center text-3xl font-bold mb-16">
            GibPotato
        </h1>

        <div id="token" class="hidden">
            <?= $token ?>
        </div>
    </div>
</div>

