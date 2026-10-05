<?php

use App\Ai\Tools\FetchSpoonFoodMenu;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Classification;
use Laravel\Ai\Prompts\ClassificationPrompt;
use Laravel\Ai\Responses\Data\ChoiceAnswer;
use Laravel\Ai\Tools\Request;

$menuHtml = <<<'HTML'
<html>
<body>
<section id="tageskarte" class="standard-section tageskarte">
  <h2>Tageskarte</h2>
  <div class="text-align-center text-weight-bold text-size-medium">28/9/2026</div>
  <div class="gericht-holder"><p>Erdäpfel-Dill-Suppe veg., gf.</p></div>
  <div class="kleingedrucktes">Alle Preise in EURO inkl. UST.</div>
</section>
</body>
</html>
HTML;

beforeEach(function () {
    $this->travelTo(now('Europe/Vienna')->setDate(2026, 9, 28)->setTime(11, 30));
});

it('returns the menu html when jev classifies it as current', function () use ($menuHtml) {
    Http::fake(['www.spoonfood.at/*' => Http::response($menuHtml)]);
    Classification::fake([['status' => new ChoiceAnswer('current', ['current' => 1.0])]]);

    $result = (new FetchSpoonFoodMenu)->handle(new Request([]));

    expect($result)
        ->toContain('Erdäpfel-Dill-Suppe veg., gf.')
        ->toContain('28/9/2026')
        ->not->toContain('Alle Preise in EURO')
        ->not->toContain('outdated');

    Classification::assertClassified(fn (ClassificationPrompt $prompt) => $prompt->state['today'] === '28/9/2026');
});

it('tells the agent when the menu is not for today', function (string $choice, string $message) use ($menuHtml) {
    Http::fake(['www.spoonfood.at/*' => Http::response($menuHtml)]);
    Classification::fake([['status' => new ChoiceAnswer($choice, [$choice => 1.0])]]);

    $result = (new FetchSpoonFoodMenu)->handle(new Request([]));

    expect($result)
        ->toStartWith($message)
        ->toContain('Erdäpfel-Dill-Suppe veg., gf.');
})->with([
    'outdated' => ['outdated', 'The menu is not dated today'],
    'closed' => ['closed', 'Spoon Food is not serving today'],
]);

it('returns an error when the tageskarte section is missing', function () {
    Http::fake(['www.spoonfood.at/*' => Http::response('<html><body>no menu here</body></html>')]);
    Classification::fake();

    $result = (new FetchSpoonFoodMenu)->handle(new Request([]));

    expect($result)->toBe('Could not find the Tageskarte on the Spoon Food website.');

    Classification::assertNothingClassified();
});

it('returns an error when the website is unavailable', function () {
    Http::fake(['www.spoonfood.at/*' => Http::response(status: 503)]);
    Classification::fake();

    $result = (new FetchSpoonFoodMenu)->handle(new Request([]));

    expect($result)->toBe('Could not fetch the Spoon Food menu. The website might be unavailable.');

    Classification::assertNothingClassified();
});
