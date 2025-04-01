<!-- Thanks to ChatGPT ^_^ -->
<div class="max-w-7xl mx-auto p-4 md:p-8">
    <h1 class="text-xl font-bold mb-6">
        Gib Credit
    </h1>

    <?php if ($hasCredit === null && $amount > 0): ?>
        <div>
            Based on your potato score, we are happy to offer you a GibPotato Credit in the amount of:
        </div>

        <div class="text-xl text-bold my-6">
            <?= $amount ?> 🥔
        </div>

        <?= $this->Form->create(null, [
            'novalidate' => true,
        ]); ?>

            <button
                type="submit"
                class="mb-2 px-6 py-3 border border-transparent text-base font-semibold rounded-md text-zinc-900 bg-amber-200"
            >
                Gib Credit
            </button>

            <div class="mb-16 text-[10px] text-zinc-500">
                Terms & Conditions apply
            </div>

        <?= $this->Form->end(); ?>

        <div class="text-[4px] text-zinc-200 dark:text-zinc-800">
            <h2 class="font-medium my-0.5">§1 Vertragsgegenstand</h2>
            Der Kreditgeber gewährt dem Kreditnehmer einen kurzfristigen Kredit in Höhe von <?= $amount ?> Kartoffel.

            <h2 class="font-medium my-0.5">§2 Zinssatz und Rückzahlung</h2>
            <ol>
                (1) Der Kreditbetrag ist mit einem Zinssatz von 25 % pro Kalendertag zu verzinsen.
                (2) Die gesamte Rückzahlung, inklusive Zinsen, ist innerhalb von 24 Stunden ab dem Zeitpunkt der Auszahlung fällig.
            </ol>

            <h2 class="font-medium my-0.5">§3 Zahlungsverzug</h2>
            Bei Nichtzahlung innerhalb der vereinbarten Frist gerät der Kreditnehmer automatisch in Verzug. Der Kreditgeber ist in diesem Fall berechtigt, weitere rechtliche Schritte einzuleiten.

            <h2 class="font-medium my-0.5">§4 Schlussbestimmungen</h2>
            <ol>
                (1) Änderungen und Ergänzungen dieses Vertrags bedürfen keinerlei Schriftform.
                (2) Sollte eine Bestimmung dieses Vertrags unwirksam sein, so bleibt der Vertrag im Übrigen wirksam.
                (3) Es gilt das Recht der Kartoffelrepublik. Gerichtsstand ist [Ort].
            </ol>
        </div>
    <?php elseif ($amount === 0): ?>
        Based on your potato score, we are unable to grant you a credit.<br>
        Maybe you should have sent more potato 🙂
    <?php else: ?>
        We already granted you a credit!
    <?php endif; ?>

    <div class="mt-32"></div>
</div>
