<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchSpoonFoodMenu implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Fetch today\'s Spoon Food menu (Tageskarte) from spoonfood.at. Call when the user asks about the Spoon Food menu, lunch options, or what\'s available at Spoon Food today. Format the result nicely for Slack using mrkdwn. If the date on the menu is not today, tell the user it is outdated.';
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

        $html = $response->body();

        $pos = strpos($html, 'id="tageskarte"');

        if ($pos === false) {
            return 'Could not find the Tageskarte on the Spoon Food website.';
        }

        $start = strpos($html, '>', $pos);

        if ($start === false) {
            return 'Could not find the Tageskarte on the Spoon Food website.';
        }

        $start++;

        $end = strpos($html, 'Alle Preise in EURO', $start);

        if ($end === false) {
            return 'Could not parse the menu from the Spoon Food website.';
        }

        $section = substr($html, $start, $end - $start);

        $text = preg_replace('/<[^>]+>/', "\n", $section);
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        return html_entity_decode(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
