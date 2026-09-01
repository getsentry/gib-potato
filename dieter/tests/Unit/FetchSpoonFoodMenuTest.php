<?php

use App\Ai\Tools\FetchSpoonFoodMenu;

it('strips webflow-hidden dishes from the tageskarte text', function () {
    $html = <<<'HTML'
<html>
<body>
<section id="tageskarte" class="standard-section tageskarte">
  <h2>Tageskarte</h2>
  <div class="text-style-tagline">(ab 11 Uhr)</div>
  <div class="text-align-center text-weight-bold text-size-medium">1/9/2026</div>
  <h3>Suppen</h3>
  <div class="gericht-holder">
    <div class="gericht-name-holder"><p>Erdäpfel-Dill-Suppe veg., gf.</p></div>
    <div class="preis-holder">
      <p>4,20</p><p>/</p><p>6,20</p>
    </div>
  </div>
  <div class="gericht-holder w-condition-invisible">
    <div class="gericht-name-holder"><p>SpoonFood’s Veg-Tea veg.</p></div>
    <div class="preis-holder">
      <p>4,20</p><p>/</p><p>6,20</p>
    </div>
  </div>
  <h3>Eintöpfe</h3>
  <div class="gericht-holder w-condition-invisible">
    <div class="gericht-name-holder"><p>Butter Chicken mit Reis gf.</p></div>
    <div class="preis-holder">
      <p>6,90</p><p>/</p><p>9,90</p>
    </div>
  </div>
  <div class="gericht-holder">
    <div class="gericht-name-holder"><p>Steckrüben-Eintopf gf.</p></div>
    <div class="preis-holder">
      <p>6,90</p><p>/</p><p>9,90</p>
    </div>
  </div>
  <div class="kleingedrucktes">Alle Preise in EURO inkl. UST.</div>
</section>
</body>
</html>
HTML;

    $menu = (new FetchSpoonFoodMenu)->parseMenuHtml($html);

    expect($menu)
        ->toContain('Erdäpfel-Dill-Suppe veg., gf.')
        ->toContain('Steckrüben-Eintopf gf.')
        ->not->toContain('Butter Chicken')
        ->not->toContain('Veg-Tea');
});

it('returns an error when the tageskarte section is missing', function () {
    $menu = (new FetchSpoonFoodMenu)->parseMenuHtml('<html><body>no menu here</body></html>');

    expect($menu)->toBe('Could not find the Tageskarte on the Spoon Food website.');
});
