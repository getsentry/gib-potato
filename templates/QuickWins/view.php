<div class="h-full w-full flex items-center justify-center">
    <div class="flex items-start">
        <img
            class="w-16 h-16 rounded-full mr-4"
            src="<?= $quickWin->user->slack_picture ?>"
        >
        <div class="max-w-[320px] p-8 border border-zinc-300 rounded-e-xl rounded-es-xl">
            <div class="font-semibold">
                <?= h($quickWin->user->slack_name); ?> 
            </div>
            <div class="text-zinc-500">
                <?= h($quickWin->created) ?>
            </div>
            <p class="pt-4">
                <?= h($quickWin->message) ?>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>
<script>
    const jsConfetti = new JSConfetti()
    jsConfetti.addConfetti({
        emojis: ['🥔'],
    })
</script>