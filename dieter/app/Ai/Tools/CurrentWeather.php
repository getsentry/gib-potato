<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CurrentWeather implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Fetch the current weather for a given city. Returns temperature, wind speed, and weather conditions.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $geocoding = Http::timeout(10)
            ->connectTimeout(5)
            ->throw()
            ->get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $request['city'],
                'count' => 1,
            ])
            ->json();

        if (empty($geocoding['results'])) {
            return "Could not find a city matching '{$request['city']}'.";
        }

        $location = $geocoding['results'][0];

        $weather = Http::timeout(10)
            ->connectTimeout(5)
            ->throw()
            ->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
                'timezone' => $location['timezone'] ?? 'auto',
            ])
            ->json();

        $current = $weather['current'];

        return sprintf(
            'Current weather in %s, %s: %.1f°C (feels like %.1f°C), humidity %d%%, wind %.1f km/h, condition code %d.',
            $location['name'],
            $location['country'] ?? '',
            $current['temperature_2m'],
            $current['apparent_temperature'],
            $current['relative_humidity_2m'],
            $current['wind_speed_10m'],
            $current['weather_code'],
        );
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'city' => $schema->string()->description('The name of the city to get weather for')->required(),
        ];
    }
}
