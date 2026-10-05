<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Choice;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Responses\Data\ChoiceAnswer;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchSpoonFoodMenu implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Fetch today\'s Spoon Food menu (Tageskarte) from spoonfood.at as HTML. Call when the user asks about the Spoon Food menu, lunch options, or what\'s available at Spoon Food today. Dishes inside elements with the `w-condition-invisible` class are hidden on the website, never mention them. Format the result nicely for Slack using mrkdwn.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->get('https://www.spoonfood.at/');

        if ($response->failed()) {
            return 'Could not fetch the Spoon Food menu. The website might be unavailable.';
        }

        $menu = $this->extractMenu($response->body());

        if ($menu === null) {
            return 'Could not find the Tageskarte on the Spoon Food website.';
        }

        return match ($this->classifyMenu($menu)->choice) {
            'outdated' => "The menu is not dated today, tell the user it is outdated.\n\n{$menu}",
            'closed' => "Spoon Food is not serving today, tell the user.\n\n{$menu}",
            default => $menu,
        };
    }

    /**
     * Extract the Tageskarte section HTML from a Spoon Food page body.
     */
    private function extractMenu(string $html): ?string
    {
        $section = strpos($html, 'id="tageskarte"');
        $start = $section === false ? false : strpos($html, '>', $section);
        $end = $start === false ? false : strpos($html, 'Alle Preise in EURO', $start);

        if ($end === false) {
            return null;
        }

        return substr($html, $start + 1, $end - $start - 1);
    }

    /**
     * Classify whether the menu is for today using Jev.
     */
    private function classifyMenu(string $menu): ChoiceAnswer
    {
        $answer = Classification::of([
            'today' => now('Europe/Vienna')->format('j/n/Y'),
            'menu' => $menu,
        ])->question('status', new Choice(
            'Compare the Spoon Food menu HTML with today\'s date.',
            [
                'current' => 'A lunch menu dated today',
                'outdated' => 'A lunch menu dated a day other than today',
                'closed' => 'A notice that Spoon Food is closed or not serving today',
            ],
        ))->classify()->answer('status');

        assert($answer instanceof ChoiceAnswer);

        return $answer;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
